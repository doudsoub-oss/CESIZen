<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Users\ChangeUserRoleRequest;
use App\Http\Requests\Admin\Users\StoreUserRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', User::class);

        $actor = $request->user();

        $users = User::query()
            ->when(
                ! $actor->isSuperAdmin(),
                // Regular admins only see `role=user` per the role matrix.
                fn ($q) => $q->where('role', Role::User->value)
            )
            ->when(
                $request->filled('role'),
                fn ($q) => $q->where('role', $request->string('role'))
            )
            ->when(
                $request->has('active'),
                fn ($q) => $q->where('is_active', $request->boolean('active'))
            )
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('admin/users/Index', [
            'users' => $users,
            'filters' => [
                'role' => $request->string('role')->toString() ?: null,
                'active' => $request->has('active') ? $request->boolean('active') : null,
            ],
            'roleOptions' => $this->roleOptions($actor),
        ]);
    }

    public function show(Request $request, User $user): Response
    {
        $this->authorize('view', $user);

        $user->loadCount('diagnostics');

        $actor = $request->user();

        return Inertia::render('admin/users/Show', [
            'user' => $user,
            'roleOptions' => $this->roleOptions($actor),
            'can' => [
                'changeRole' => $actor->can('changeRole', [$user, $user->role]),
                'toggleActive' => $actor->can('toggleActive', $user),
                'delete' => $actor->can('delete', $user),
            ],
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('create', User::class);

        return Inertia::render('admin/users/Create', [
            'roleOptions' => $this->roleOptions($request->user()),
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $data = $request->validated();

        User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'role' => Role::from($data['role']),
            'is_active' => $data['is_active'] ?? true,
            'email_verified_at' => now(),
        ]);

        return redirect()
            ->route('admin.users.index')
            ->with('status', __('Compte créé.'));
    }

    public function destroy(User $user): RedirectResponse
    {
        $this->authorize('delete', $user);

        $user->delete();

        return redirect()
            ->route('admin.users.index')
            ->with('status', __('Compte supprimé.'));
    }

    public function toggleActive(User $user): RedirectResponse
    {
        $this->authorize('toggleActive', $user);

        $user->update(['is_active' => ! $user->is_active]);

        return back()->with('status', __('État du compte mis à jour.'));
    }

    public function changeRole(ChangeUserRoleRequest $request, User $user): RedirectResponse
    {
        $user->update(['role' => Role::from($request->input('role'))]);

        return back()->with('status', __('Rôle mis à jour.'));
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    private function roleOptions(User $actor): array
    {
        $available = $actor->isSuperAdmin()
            ? [Role::User, Role::Admin]
            : [Role::User];

        return array_map(
            fn (Role $r) => ['value' => $r->value, 'label' => $r->label()],
            $available
        );
    }
}
