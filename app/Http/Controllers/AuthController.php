<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Models\User;
use App\Notifications\PasswordResetNotification;
use App\Notifications\TwoFactorCodeNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
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

            try {
                $user->notify(new TwoFactorCodeNotification($otp));
            } catch (\Throwable $e) {
                Log::warning("[2FA Security] Notification dispatch failed for {$user->email}: " . $e->getMessage());
            }

            Log::info("========================================\n[2FA AUTHENTICATION OTP ISSUED]\nUser: {$user->email}\nCode: {$otp}\nExpires: 10 minutes\n========================================");

            $isNonSmtp = config('mail.default') === 'log' || empty(config('mail.mailers.smtp.username')) || app()->environment('local');

            session([
                'auth.2fa.id' => $user->id,
                'auth.2fa.remember' => $request->boolean('remember'),
                'auth.2fa.email' => $user->email,
                'auth.2fa.code_preview' => $isNonSmtp ? $otp : null,
            ]);

            return redirect()->route('two-factor.challenge')
                ->with('status', 'A 6-digit verification code has been dispatched to your corporate email.');
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
            session()->forget(['auth.2fa.id', 'auth.2fa.remember', 'auth.2fa.email', 'auth.2fa.code_preview']);
            return redirect()->route('login');
        }

        $isNonSmtp = config('mail.default') === 'log' || empty(config('mail.mailers.smtp.username')) || app()->environment('local');
        $codePreview = $isNonSmtp ? ($user->two_factor_code ?? session('auth.2fa.code_preview')) : null;

        return view('auth.two-factor', [
            'email' => $user->email,
            'codePreview' => $codePreview,
            'isNonSmtp' => $isNonSmtp,
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
        session()->forget(['auth.2fa.id', 'auth.2fa.remember', 'auth.2fa.email', 'auth.2fa.code_preview']);

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

            try {
                $user->notify(new TwoFactorCodeNotification($otp));
            } catch (\Throwable $e) {
                Log::warning("[2FA Security] Notification resend failed for {$user->email}: " . $e->getMessage());
            }

            Log::info("========================================\n[2FA AUTHENTICATION OTP RE-ISSUED]\nUser: {$user->email}\nCode: {$otp}\n========================================");

            $isNonSmtp = config('mail.default') === 'log' || empty(config('mail.mailers.smtp.username')) || app()->environment('local');
            if ($isNonSmtp) {
                session(['auth.2fa.code_preview' => $otp]);
            }

            return back()->with('status', 'A fresh 6-digit verification code has been dispatched.');
        }

        return redirect()->route('login');
    }

    /**
     * Show Mandatory 2FA Setup Screen.
     */
    public function showSetupTwoFactor(): View|RedirectResponse
    {
        $user = Auth::user();
        if (! $user) {
            return redirect()->route('login');
        }

        if ($user->two_factor_enabled) {
            return redirect()->route('dashboard');
        }

        // Generate activation verification code
        if (! $user->two_factor_code || ! $user->two_factor_expires_at || $user->two_factor_expires_at->isPast()) {
            $otp = $user->generateTwoFactorCode();

            try {
                $user->notify(new TwoFactorCodeNotification($otp));
            } catch (\Throwable $e) {
                Log::warning("[2FA Setup] Notification dispatch failed: " . $e->getMessage());
            }

            Log::info("[2FA Setup] Mandatory 2FA activation code issued for {$user->email}: {$otp}");
        }

        $isNonSmtp = config('mail.default') === 'log' || empty(config('mail.mailers.smtp.username')) || app()->environment('local');
        $codePreview = $isNonSmtp ? $user->two_factor_code : null;

        return view('auth.two-factor-setup', [
            'user' => $user,
            'codePreview' => $codePreview,
            'isNonSmtp' => $isNonSmtp,
        ]);
    }

    /**
     * Enable Mandatory 2FA after verifying code.
     */
    public function enableTwoFactor(Request $request): RedirectResponse
    {
        $request->validate([
            'code' => ['required', 'string', 'min:6', 'max:6'],
        ]);

        $user = Auth::user();
        if (! $user) {
            return redirect()->route('login');
        }

        if (! $user->verifyTwoFactorCode($request->code)) {
            return back()->withErrors(['code' => 'The verification code is incorrect or expired. Please check your inbox or resend code.']);
        }

        $user->forceFill([
            'two_factor_enabled' => true,
        ])->save();

        Log::info("[2FA Security] Mandatory 2FA successfully configured and activated for {$user->email}");

        return redirect()->route('dashboard')->with('success', 'Two-Factor Authentication configured and verified! Welcome to the Clearinghouse.');
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
            return back()->with('status', 'If this corporate email is registered, password reset instructions have been dispatched to your inbox.');
        }

        $token = Str::random(64);

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $user->email],
            [
                'token' => Hash::make($token),
                'created_at' => now(),
            ]
        );

        try {
            $user->notify(new PasswordResetNotification($token, $user->email));
        } catch (\Throwable $e) {
            Log::warning("[Security] Password reset notification failed: " . $e->getMessage());
        }

        Log::info("[Security] Password reset requested for {$user->email}. Notification dispatched.");

        return back()->with('status', 'If this corporate email is registered, password reset instructions have been dispatched to your inbox.');
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
