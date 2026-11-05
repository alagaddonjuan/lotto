<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\OperatorInvitation;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\OperatorInvitationNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\View\View;

class OperatorInviteController extends Controller
{
    /**
     * Dispatch an invitation link to onboard new operator staff.
     */
    public function invite(Request $request): JsonResponse|RedirectResponse
    {
        $currentUser = Auth::user();

        $rules = [
            'email' => ['required', 'email', 'unique:users,email'],
            'role' => ['required', 'in:operator_rep,operator_admin,super_admin'],
        ];

        if ($currentUser->isSuperAdmin()) {
            $rules['tenant_id'] = ['required', 'exists:tenants,id'];
        }

        $validated = $request->validate($rules);

        $tenantId = $currentUser->isSuperAdmin()
            ? (int) $validated['tenant_id']
            : (int) $currentUser->tenant_id;

        // Invalidate any pending invitation for this email
        OperatorInvitation::where('email', $validated['email'])
            ->whereNull('accepted_at')
            ->delete();

        $token = Str::random(48);
        $expiresAt = now()->addDays(3);

        $invitation = OperatorInvitation::create([
            'email' => strtolower($validated['email']),
            'tenant_id' => $tenantId,
            'role' => $validated['role'],
            'token' => $token,
            'expires_at' => $expiresAt,
        ]);

        $signedUrl = URL::temporarySignedRoute(
            'invitations.accept',
            $expiresAt,
            ['token' => $token]
        );

        try {
            Notification::route('mail', $invitation->email)
                ->notify(new OperatorInvitationNotification($invitation, $signedUrl));
        } catch (\Throwable $e) {
            Log::warning("[Security] Operator invite email failed for {$invitation->email}: " . $e->getMessage());
        }

        Log::info("[Security] Operator invitation generated for {$validated['email']} (Tenant: {$tenantId})");

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Institutional invitation issued successfully.',
                'invitation' => [
                    'email' => $invitation->email,
                    'role' => $invitation->role,
                    'tenant_id' => $invitation->tenant_id,
                    'expires_at' => $invitation->expires_at->toIso8601String(),
                    'signed_url' => $signedUrl,
                ],
            ]);
        }

        return back()->with('invitation_url', $signedUrl)->with('success', "Invitation issued for {$invitation->email}!");
    }

    /**
     * Display the invitation acceptance screen.
     */
    public function showAcceptForm(Request $request, string $token): View|RedirectResponse
    {
        if (! $request->hasValidSignature()) {
            return redirect()->route('login')->withErrors(['email' => 'This invitation link is invalid or has expired.']);
        }

        $invitation = OperatorInvitation::where('token', $token)
            ->whereNull('accepted_at')
            ->where('expires_at', '>', now())
            ->first();

        if (! $invitation) {
            return redirect()->route('login')->withErrors(['email' => 'This invitation has already been accepted or is no longer valid.']);
        }

        $tenant = Tenant::find($invitation->tenant_id);

        return view('auth.accept-invitation', [
            'invitation' => $invitation,
            'tenant' => $tenant,
            'token' => $token,
        ]);
    }

    /**
     * Complete account creation from signed invitation.
     */
    public function accept(Request $request, string $token): RedirectResponse
    {
        if (! $request->hasValidSignature()) {
            return redirect()->route('login')->withErrors(['email' => 'The invitation link signature is invalid or has expired.']);
        }

        $invitation = OperatorInvitation::where('token', $token)
            ->whereNull('accepted_at')
            ->where('expires_at', '>', now())
            ->first();

        if (! $invitation) {
            return redirect()->route('login')->withErrors(['email' => 'Invitation not found or already accepted.']);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $invitation->email,
            'password' => Hash::make($validated['password']),
            'tenant_id' => $invitation->tenant_id,
            'role' => $invitation->role,
            'two_factor_enabled' => false,
        ]);

        $invitation->update(['accepted_at' => now()]);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('two-factor.setup')->with('warning', 'Mandatory Security Requirement: Please configure Two-Factor Authentication (2FA) to activate institutional clearinghouse access.');
    }
}

