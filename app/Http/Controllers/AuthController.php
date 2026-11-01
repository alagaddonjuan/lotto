<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AuthController extends Controller
{
    /**
     * Display the login view.
     */
    public function showLogin(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return view('auth.login');
    }

    /**
     * Handle incoming login request.
     */
    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $user = User::where('email', $credentials['email'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            return back()->withErrors([
                'email' => 'The provided credentials do not match our records.',
            ])->onlyInput('email');
        }

        // If 2FA is enabled for this user, trigger the OTP challenge
        if ($user->two_factor_enabled) {
            $otp = $user->generateTwoFactorCode();

            session([
                'auth.2fa.id' => $user->id,
                'auth.2fa.remember' => $request->boolean('remember'),
                'auth.2fa.email' => $user->email,
            ]);

            return redirect()->route('two-factor.challenge')
                ->with('status', 'A 6-digit verification code has been generated.')
                ->with('demo_otp', $otp); // Helpful preview for developer/demo testing
        }

        // Standard Login
        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    /**
     * Show 2FA OTP Challenge screen.
     */
    public function showTwoFactorChallenge(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        if (! session()->has('auth.2fa.id')) {
            return redirect()->route('login');
        }

        $user = User::find(session('auth.2fa.id'));
        if (! $user) {
            session()->forget(['auth.2fa.id', 'auth.2fa.remember']);
            return redirect()->route('login');
        }

        return view('auth.two-factor', [
            'email' => $user->email,
            'demoOtp' => session('demo_otp') ?? $user->two_factor_code,
        ]);
    }

    /**
     * Verify 2FA OTP Code.
     */
    public function verifyTwoFactor(Request $request): RedirectResponse
    {
        $request->validate([
            'code' => ['required', 'string', 'min:6', 'max:6'],
        ]);

        $userId = session('auth.2fa.id');
        if (! $userId) {
            return redirect()->route('login')->withErrors(['email' => 'Session expired. Please log in again.']);
        }

        $user = User::find($userId);
        if (! $user || ! $user->verifyTwoFactorCode($request->code)) {
            return back()->withErrors(['code' => 'The 6-digit code is invalid or has expired. Please try again.']);
        }

        $remember = session('auth.2fa.remember', false);
        session()->forget(['auth.2fa.id', 'auth.2fa.remember', 'auth.2fa.email', 'demo_otp']);

        Auth::login($user, $remember);
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    /**
     * Resend 2FA OTP Code.
     */
    public function resendTwoFactor(): RedirectResponse
    {
        $userId = session('auth.2fa.id');
        if (! $userId) {
            return redirect()->route('login');
        }

        $user = User::find($userId);
        if ($user) {
            $otp = $user->generateTwoFactorCode();
            return back()
                ->with('status', 'A fresh 6-digit code has been generated.')
                ->with('demo_otp', $otp);
        }

        return redirect()->route('login');
    }

    /**
     * Display registration view for new operator reps and staff.
     */
    public function showRegister(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        $tenants = Tenant::where('is_active', true)->get();

        return view('auth.register', compact('tenants'));
    }

    /**
     * Handle registration request.
     */
    public function register(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'phone' => ['nullable', 'string', 'max:30'],
            'tenant_id' => ['required', 'exists:tenants,id'],
            'role' => ['required', 'in:operator_rep,super_admin'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'enable_2fa' => ['nullable', 'boolean'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'tenant_id' => $validated['tenant_id'],
            'role' => $validated['role'],
            'password' => Hash::make($validated['password']),
            'two_factor_enabled' => $request->boolean('enable_2fa'),
        ]);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('dashboard')->with('success', 'Welcome to Q4I Financial Engine! Your operator account has been created successfully.');
    }

    /**
     * Display Forgot Password screen.
     */
    public function showForgotPassword(): View
    {
        return view('auth.forgot-password');
    }

    /**
     * Handle Forgot Password email submission.
     */
    public function sendResetLink(Request $request): RedirectResponse
    {
        $request->validate(['email' => ['required', 'email']]);

        $user = User::where('email', $request->email)->first();
        if (! $user) {
            // Return status even if email not found to prevent user enumeration
            return back()->with('status', 'If this corporate email is registered, a password reset link has been generated.');
        }

        $token = Str::random(64);

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $user->email],
            [
                'token' => Hash::make($token),
                'created_at' => now(),
            ]
        );

        // Build direct reset link
        $resetUrl = route('password.reset', ['token' => $token, 'email' => $user->email]);

        return back()
            ->with('status', 'A password reset token has been generated.')
            ->with('reset_url', $resetUrl);
    }

    /**
     * Display Reset Password form.
     */
    public function showResetPassword(Request $request, string $token): View
    {
        return view('auth.reset-password', [
            'token' => $token,
            'email' => $request->query('email', ''),
        ]);
    }

    /**
     * Handle Reset Password submission.
     */
    public function resetPassword(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $record = DB::table('password_reset_tokens')->where('email', $request->email)->first();

        if (! $record || ! Hash::check($request->token, $record->token)) {
            return back()->withErrors(['email' => 'This password reset link is invalid or has expired.']);
        }

        $user = User::where('email', $request->email)->first();
        if (! $user) {
            return back()->withErrors(['email' => 'User account not found.']);
        }

        $user->forceFill([
            'password' => Hash::make($request->password),
        ])->save();

        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        return redirect()->route('login')->with('success', 'Your password has been reset successfully. Please log in with your new password.');
    }

    /**
     * Log the user out of the application.
     */
    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
