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
    public function index(): View
    {
        return view('admin.users', ['users' => User::orderBy('name')->get()]);
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
