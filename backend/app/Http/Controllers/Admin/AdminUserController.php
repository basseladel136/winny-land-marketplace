<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminUserController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'search'  => ['sometimes', 'string', 'max:100'],
            // BUG FIX: the User model uses 'customer' not 'user' as the role value.
            'role'    => ['sometimes', 'string', 'in:customer,admin'],
            'perPage' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        $query = User::latest();

        if ($search = $request->query('search')) {
            // Use database-agnostic LIKE (case-insensitive on PostgreSQL via
            // collation; on SQLite LIKE is case-insensitive for ASCII by default).
            $term = '%' . $search . '%';
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', $term)
                  ->orWhere('email', 'like', $term);
            });
        }

        if ($role = $request->query('role')) {
            $query->where('role', $role);
        }

        $users = $query->paginate($request->integer('perPage', 20));

        return response()->json(
            UserResource::collection($users)->response()->getData(true)
        );
    }

    public function show(int $id): JsonResponse
    {
        $user = User::withCount('orders')->findOrFail($id);

        return response()->json(['data' => new UserResource($user)]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        /**
         * SECURITY: Role assignment is intentionally NOT allowed via this API.
         *
         * The admin role is assigned exclusively by the ADMIN_EMAIL env variable
         * check in AuthService and User::syncAdminRole(). Allowing role changes
         * via API would enable privilege escalation attacks even from the admin panel.
         *
         * The only mutable field here is is_active (toggle account ban).
         */
        $data = $request->validate([
            'isActive' => 'sometimes|boolean',
        ]);

        $user = User::findOrFail($id);

        // Prevent deactivating the designated admin account
        $adminEmail = strtolower(config('app.admin_email', ''));
        if ($adminEmail && strtolower($user->email) === $adminEmail) {
            return response()->json([
                'message' => 'The primary administrator account cannot be modified.',
            ], 422);
        }

        if (isset($data['isActive'])) {
            $user->newQuery()->where('id', $user->id)->update(['is_active' => $data['isActive']]);
        }

        return response()->json(['data' => new UserResource($user->fresh())]);
    }

    public function destroy(int $id): JsonResponse
    {
        $user = User::findOrFail($id);

        // Prevent deleting the designated admin account
        $adminEmail = strtolower(config('app.admin_email', ''));
        if ($adminEmail && strtolower($user->email) === $adminEmail) {
            return response()->json([
                'message' => 'The primary administrator account cannot be deleted.',
            ], 422);
        }

        if ($user->isAdmin()) {
            return response()->json(['message' => 'Cannot delete an admin account.'], 422);
        }

        $user->delete();

        return response()->json(['message' => 'User deleted.']);
    }
}
