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
use Illuminate\Support\Facades\URL;

class AuthController extends Controller
{
    public function __construct(private AuthService $auth) {}

    // ── Register ─────────────────────────────────────────────────────────────

    public function register(RegisterRequest $request): JsonResponse
    {
        $result = $this->auth->register($request->validated());

        return response()->json([
            'user'    => new UserResource($result['user']),
            'token'   => $result['token'],
            'message' => 'Registration successful. Please check your email to verify your account.',
        ], 201);
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
            'name'   => 'sometimes|string|max:255',
            'phone'  => 'sometimes|nullable|string|max:20',
            'locale' => 'sometimes|in:en,ar',
        ]);

        $request->user()->update($data);

        return response()->json(['user' => new UserResource($request->user()->fresh())]);
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
