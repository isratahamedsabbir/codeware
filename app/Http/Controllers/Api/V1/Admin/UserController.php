<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Concerns\PasswordValidationRules;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\AdminActivity;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    use PasswordValidationRules;

    public function index(Request $request): JsonResponse
    {
        $perPage = min((int) $request->query('per_page', 15), 100);

        $users = User::query()
            ->with('roles:id,name')
            ->when($request->query('search'), fn ($q, $search) => $q
                ->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%"))
            ->when($request->query('role'), fn ($q, $role) => $q->role($role))
            ->orderBy('id')
            ->paginate($perPage);

        return response()->json([
            'data' => $users->map(fn ($user) => $this->formatUser($user)),
            'meta' => [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
            ],
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $user = User::with('roles:id,name')->findOrFail($id);

        return response()->json(['data' => $this->formatUser($user)]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')],
            'password' => $this->passwordRules(),
            'is_admin' => 'sometimes|boolean',
            'roles' => 'sometimes|array',
            'roles.*' => 'string|exists:roles,name',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
        ]);

        // is_admin isn't in User's mass-assignable Fillable list (deliberately, so
        // register/profile endpoints can never grant it) — set it directly here,
        // the one place an already-access-admin-system-gated caller is meant to.
        $user->is_admin = $validated['is_admin'] ?? false;
        $user->save();

        $user->syncRoles($validated['roles'] ?? []);

        AdminActivity::log('created', "User: {$user->email}");

        return response()->json(['data' => $this->formatUser($user->load('roles:id,name'))], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => ['sometimes', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'is_admin' => 'sometimes|boolean',
            'roles' => 'sometimes|array',
            'roles.*' => 'string|exists:roles,name',
        ]);

        $user->update(collect($validated)->only(['name', 'email'])->all());

        // See store(): is_admin is deliberately excluded from Fillable, so it has
        // to be set directly rather than through the mass-assignment above.
        if (array_key_exists('is_admin', $validated)) {
            $user->is_admin = $validated['is_admin'];
            $user->save();
        }

        if (array_key_exists('roles', $validated)) {
            $user->syncRoles($validated['roles']);
        }

        AdminActivity::log('updated', "User: {$user->email}");

        return response()->json(['data' => $this->formatUser($user->fresh('roles'))]);
    }

    public function updatePassword(Request $request, int $id): JsonResponse
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'password' => $this->passwordRules(),
        ]);

        $user->forceFill(['password' => $validated['password']])->save();

        AdminActivity::log('updated', "User password: {$user->email}");

        return response()->json(['data' => ['message' => 'Password updated successfully']]);
    }

    public function destroy(Request $request, int $id): Response
    {
        $user = User::findOrFail($id);

        if ($user->id === $request->user()->id) {
            throw ValidationException::withMessages([
                'id' => ['You cannot delete your own account.'],
            ]);
        }

        AdminActivity::log('deleted', "User: {$user->email}");
        $user->delete();

        return response()->noContent();
    }

    private function formatUser(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'is_admin' => (bool) $user->is_admin,
            'roles' => $user->roles->pluck('name')->values(),
            'email_verified_at' => $user->email_verified_at?->toIso8601String(),
            'created_at' => $user->created_at?->toIso8601String(),
        ];
    }
}
