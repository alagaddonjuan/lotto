<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureTwoFactorEnabled
{
    /**
     * Handle an incoming request.
     * Enforce mandatory Two-Factor Authentication for institutional clearinghouse routes.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if ($user && ! $user->two_factor_enabled) {
            $exemptRoutes = [
                'two-factor.setup',
                'two-factor.setup.post',
                'logout',
            ];

            if (! in_array($request->route()?->getName(), $exemptRoutes)) {
                if ($request->wantsJson()) {
                    return response()->json([
                        'error' => '2fa_required',
                        'message' => 'Institutional security policy requires Two-Factor Authentication to be enabled.',
                    ], 403);
                }

                return redirect()->route('two-factor.setup')
                    ->with('warning', 'Institutional security policy requires Two-Factor Authentication to be configured before accessing clearinghouse operations.');
            }
        }

        return $next($request);
    }
}

