<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $filters = [
            'name' => trim((string) $request->query('name', '')),
            'email' => trim((string) $request->query('email', '')),
            'role' => in_array($request->query('role'), User::ROLES, true) ? $request->query('role') : '',
            'status' => in_array($request->query('status'), ['verified', 'unverified'], true) ? $request->query('status') : '',
            'per_page' => in_array((int) $request->query('per_page'), [10, 25, 50], true) ? (int) $request->query('per_page') : 10,
        ];
        $like = fn (string $v) => '%'.addcslashes($v, '%_\\').'%';

        $users = User::query()
            ->withCount('shipments')
            ->when($filters['name'] !== '', fn ($q) => $q->where('name', 'like', $like($filters['name'])))
            ->when($filters['email'] !== '', fn ($q) => $q->where('email', 'like', $like($filters['email'])))
            ->when($filters['role'] !== '', fn ($q) => $q->where('role', $filters['role']))
            ->when($filters['status'] === 'verified', fn ($q) => $q->whereNotNull('email_verified_at'))
            ->when($filters['status'] === 'unverified', fn ($q) => $q->whereNull('email_verified_at'))
            ->orderBy('name')
            ->paginate($filters['per_page'])
            ->withQueryString();

        return view('admin.users', compact('users', 'filters'));
    }

    public function store(Request $request, AuditService $audit): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'max:255'],
            'role' => ['required', Rule::in(User::ROLES)],
        ]);

        $user = new User(['name' => $data['name'], 'email' => $data['email'], 'password' => $data['password']]);
        $user->role = $data['role']; // explicit assignment: "role" is not mass-assignable
        $user->email_verified_at = now(); // created by an admin: trusted, no verification email needed
        $user->save();

        $audit->log($request->user(), 'user.created', $user, ['role' => $user->role]);

        return back()->with('success', 'User created.');
    }

    public function updateRole(Request $request, User $user, AuditService $audit): RedirectResponse
    {
        $data = $request->validate(['role' => ['required', Rule::in(User::ROLES)]]);

        if ($user->id === $request->user()->id) {
            return back()->with('error', 'You cannot change your own role.');
        }

        $from = $user->role;
        $user->role = $data['role'];
        $user->save();
        $audit->log($request->user(), 'user.role_changed', $user, ['from' => $from, 'to' => $user->role]);

        return back()->with('success', 'Role updated.');
    }
}
