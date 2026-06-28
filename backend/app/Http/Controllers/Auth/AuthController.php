<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Services\AuthService;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    public function __construct(private AuthService $auth) {}

    // ── Register ─────────────────────────────────────────────────────────────

    public function register(RegisterRequest $request): JsonResponse
    {
        $result = $this->auth->register($request->validated());

        return response()->json([
            'email'   => $result['user']->email,
            'message' => 'We sent a 6-digit verification code to your email. Enter it to activate your account.',
        ], 201);
    }

    /**
     * Verify the registration OTP and sign the user in.
     */
    public function verifyOtp(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'otp'   => ['required', 'string', 'regex:/^\d{6}$/'],
        ]);

        $result = $this->auth->verifyOtp($data['email'], $data['otp']);

        return response()->json([
            'user'  => new UserResource($result['user']),
            'token' => $result['token'],
        ]);
    }

    /**
     * Resend a verification OTP to an unverified account (public, by email).
     */
    public function resendOtp(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
        ]);

        $this->auth->resendOtp($data['email']);

        return response()->json([
            'message' => 'If the account exists and is unverified, a new code has been sent.',
        ]);
    }

    // ── Login ─────────────────────────────────────────────────────────────────

    public function login(LoginRequest $request): JsonResponse
    {
        $data   = $request->validated();
        $result = $this->auth->login($data['email'], $data['password']);

        return response()->json([
            'user'  => new UserResource($result['user']),
            'token' => $result['token'],
        ]);
    }

    // ── Logout ────────────────────────────────────────────────────────────────

    public function logout(Request $request): JsonResponse
    {
        $this->auth->logout($request->user());

        return response()->json(['message' => 'Logged out successfully.']);
    }

    public function logoutAll(Request $request): JsonResponse
    {
        $this->auth->logoutAll($request->user());

        return response()->json(['message' => 'Logged out from all devices.']);
    }

    // ── Profile ───────────────────────────────────────────────────────────────

    public function me(Request $request): JsonResponse
    {
        return response()->json(['user' => new UserResource($request->user())]);
    }

    public function update(Request $request): JsonResponse
    {
        // Explicitly whitelist updatable fields — role/is_admin are never accepted
        $data = $request->validate([
            'name'    => 'sometimes|string|max:255',
            'phone'   => 'sometimes|nullable|string|max:20',
            'address' => 'sometimes|nullable|string|max:500',
            'locale'  => 'sometimes|in:en,ar',
        ]);

        $request->user()->update($data);

        return response()->json(['user' => new UserResource($request->user()->fresh())]);
    }

    /**
     * Aggregate stats for the authenticated user's profile dashboard.
     */
    public function stats(Request $request): JsonResponse
    {
        // PERFORMANCE FIX: replace 4 separate DB round-trips with one aggregate query.
        $user = $request->user();
        $id   = $user->id;

        $row = \Illuminate\Support\Facades\DB::selectOne("
            SELECT
                (SELECT COUNT(*) FROM orders WHERE user_id = ?) AS orders_count,
                (SELECT COALESCE(SUM(total), 0) FROM orders WHERE user_id = ? AND status != 'cancelled') AS total_spent,
                (SELECT COUNT(*) FROM wishlists WHERE user_id = ?) AS wishlist_count,
                (SELECT COUNT(*) FROM reviews WHERE user_id = ?) AS reviews_count
        ", [$id, $id, $id, $id]);

        return response()->json([
            'data' => [
                'ordersCount'   => (int) $row->orders_count,
                'totalSpent'    => (float) $row->total_spent,
                'wishlistCount' => (int) $row->wishlist_count,
                'reviewsCount'  => (int) $row->reviews_count,
            ],
        ]);
    }

    /**
     * Change the authenticated user's password (requires the current one).
     */
    public function updatePassword(Request $request): JsonResponse
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password'         => ['required', 'confirmed', Password::defaults()],
        ]);

        $user = $request->user();

        $user->update([
            'password' => Hash::make($request->input('password')),
        ]);

        // Revoke all tokens except the current one so other devices must re-login.
        // SECURITY: Without this, a compromised password change wouldn't kick out
        // an active attacker who already has a token.
        $user->tokens()->where('id', '!=', $user->currentAccessToken()->id)->delete();

        return response()->json(['message' => 'Password updated successfully.']);
    }

    /**
     * Upload / replace the authenticated user's avatar image.
     */
    public function updateAvatar(Request $request): JsonResponse
    {
        $request->validate([
            'avatar' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $user = $request->user();

        // Remove a previously uploaded avatar that lives on our public disk
        // (skip externally-hosted URLs).
        if ($user->avatar
            && ! str_starts_with($user->avatar, 'http')
            && Storage::disk('public')->exists($user->avatar)
        ) {
            Storage::disk('public')->delete($user->avatar);
        }

        $path = $request->file('avatar')->store('avatars', 'public');

        $user->update(['avatar' => $path]);

        return response()->json(['user' => new UserResource($user->fresh())]);
    }

    // ── Email Verification ───────────────────────────────────────────────────

    /**
     * Verify the user's email via the signed URL in the verification email.
     *
     * This endpoint is accessed by clicking the link in the email (GET request).
     * After verification it redirects the user to the frontend.
     */
    public function verifyEmail(Request $request, int $id, string $hash): RedirectResponse
    {
        $user = \App\Models\User::findOrFail($id);
        $frontendUrl = config('app.frontend_url', 'http://localhost:5173');

        // Validate the signed URL
        if (! URL::hasValidSignature($request)) {
            return redirect("{$frontendUrl}/email-verified?status=invalid");
        }

        // Validate hash matches the user's email
        if (! hash_equals(sha1($user->getEmailForVerification()), $hash)) {
            return redirect("{$frontendUrl}/email-verified?status=invalid");
        }

        if ($user->hasVerifiedEmail()) {
            return redirect("{$frontendUrl}/email-verified?status=already_verified");
        }

        $user->markEmailAsVerified();
        event(new Verified($user));

        return redirect("{$frontendUrl}/email-verified?status=success");
    }

    /**
     * Resend the email verification notification.
     * Rate-limited to 3 requests per minute (see AppServiceProvider).
     */
    public function resendVerification(Request $request): JsonResponse
    {
        $sent = $this->auth->resendVerification($request->user());

        if (! $sent) {
            return response()->json(['message' => 'Email is already verified.'], 422);
        }

        return response()->json(['message' => 'Verification email sent.']);
    }
}
