<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(): View
    {
        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        // Marks that the errors of the next page belong to the login popup (it re-opens itself to show them).
        $request->session()->flash('login_failed', true);

        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()->withInput($request->only('email'))
                ->withErrors(['email' => 'These credentials do not match our records.']);
        }

        $request->session()->forget('login_failed');
        $request->session()->regenerate(); // prevents session fixation

        return redirect()->intended(route('dashboard'));
    }

    public function showRegister(): View
    {
        return view('auth.register');
    }

    /** Self-registration always creates a CUSTOMER. */
    public function register(Request $request): RedirectResponse
    {
        $request->session()->flash('register_failed', true); // errors of the next page belong to the customer popup

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'max:255', 'confirmed'],
        ]);

        $user = new User($data);
        $user->role = User::ROLE_CUSTOMER;
        $user->save();
        $request->session()->forget('register_failed');

        event(new Registered($user)); // sends the verification email (MustVerifyEmail)

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('verification.notice');
    }

    /**
     * Admin / logistics (operator) sign-up from the popup on the login page.
     * Never open to the public: it needs the role's authorization code (ADMIN_REGISTRATION_CODE /
     * OPERATOR_REGISTRATION_CODE). With no code configured, registration for that role is disabled.
     * Errors go to the named bag "staff_{role}" so the matching popup re-opens with its messages.
     */
    public function registerStaff(Request $request, string $role, AuditService $audit): RedirectResponse
    {
        $roles = ['admin' => User::ROLE_ADMIN, 'operator' => User::ROLE_OPERATOR];
        abort_unless(isset($roles[$role]), 404);
        $bag = 'staff_'.$role;

        // 1) The code first, so an unauthorized visitor learns nothing else (e.g. which emails exist).
        $code = $request->validateWithBag($bag, ['access_code' => ['required', 'string', 'max:255']])['access_code'];
        $expected = (string) config("cargotrace.registration_codes.$role");

        if ($expected === '') {
            throw ValidationException::withMessages(['access_code' => 'Registration for this role is currently disabled.'])->errorBag($bag);
        }
        if (! hash_equals($expected, $code)) {
            throw ValidationException::withMessages(['access_code' => 'The authorization code is incorrect.'])->errorBag($bag);
        }

        // 2) The account itself.
        $data = $request->validateWithBag($bag, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'max:255', 'confirmed'],
        ]);

        $user = new User($data);
        $user->role = $roles[$role]; // role is not mass-assignable: set explicitly, only after the code check
        $user->save();

        $audit->log($user, 'user.registered_staff', $user, ['role' => $user->role]);

        event(new Registered($user)); // verification email, same flow as customers

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('verification.notice');
    }

    /** "Check your email" page. Already verified users go straight to the dashboard. */
    public function verifyNotice(Request $request): View|RedirectResponse
    {
        return $request->user()->hasVerifiedEmail()
            ? redirect()->route('dashboard')
            : view('auth.verify-email');
    }

    /** Link in the email (signed URL, handled by Laravel's EmailVerificationRequest). */
    public function verifyEmail(EmailVerificationRequest $request): RedirectResponse
    {
        $request->fulfill();

        return redirect()->route('dashboard')->with('success', 'Email verified. Welcome to CargoTrace!');
    }

    public function resendVerification(Request $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->route('dashboard');
        }

        $request->user()->sendEmailVerificationNotification();

        return back()->with('status', 'verification-link-sent');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
