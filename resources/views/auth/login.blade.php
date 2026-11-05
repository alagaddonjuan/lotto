<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In | Q4I Financial Engine</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@500;600;700&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .font-mono { font-family: 'JetBrains Mono', monospace; }
        .auth-grid-bg {
            background-color: #0b1f3a;
            background-image: radial-gradient(rgba(255, 255, 255, 0.08) 1px, transparent 1px);
            background-size: 24px 24px;
        }
    </style>
</head>
<body class="bg-slate-50 text-slate-900 min-h-screen flex antialiased">

    <!-- Left Corporate Showcase Panel (Desktop) -->
    <div class="hidden lg:flex lg:w-[45%] xl:w-[48%] auth-grid-bg text-white flex-col justify-between p-12 relative overflow-hidden">
        <div class="absolute -top-24 -left-24 w-96 h-96 bg-[#1d5ba6]/30 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute bottom-0 right-0 w-96 h-96 bg-blue-500/15 rounded-full blur-3xl pointer-events-none"></div>

        <!-- Brand Badge -->
        <div class="relative z-10 flex items-center justify-between">
            <div class="bg-white px-4 py-2.5 rounded-xl shadow-lg inline-flex items-center">
                <img src="{{ asset('images/q4i-logo.png') }}" onerror="this.src='https://q4iltd.com/color@6x.png'" alt="Q4 International" class="h-10 w-auto object-contain">
            </div>
            <span class="text-xs font-mono uppercase tracking-widest px-3 py-1 rounded-full bg-white/10 border border-white/15 text-blue-200">
                Clearinghouse v2.4
            </span>
        </div>

        <!-- Executive Value Proposition -->
        <div class="relative z-10 max-w-lg my-auto py-12">
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-blue-500/20 border border-blue-400/30 text-blue-200 text-xs font-semibold mb-6">
                <i class="fa-solid fa-building-columns"></i>
                <span>Institutional Lottery Clearinghouse</span>
            </div>
            <h2 class="text-3xl xl:text-4xl font-extrabold tracking-tight text-white leading-tight mb-4">
                Real-Time Automated Split &amp; Escrow Settlement Engine
            </h2>
            <p class="text-slate-300 text-sm leading-relaxed mb-8">
                Centralized financial clearing, dual-officer Maker-Checker draw governance, and direct Meta Cloud API telemetry for licensed gaming operators.
            </p>

            <div class="space-y-4">
                <div class="flex items-start gap-3.5 p-3.5 rounded-xl bg-white/5 border border-white/10">
                    <div class="w-9 h-9 rounded-lg bg-[#1d5ba6] flex items-center justify-center text-white shrink-0 mt-0.5">
                        <i class="fa-solid fa-scale-balanced text-sm"></i>
                    </div>
                    <div>
                        <h4 class="text-sm font-bold text-white">35% / 65% Escrow Split Architecture</h4>
                        <p class="text-xs text-slate-300 mt-0.5">Strict integer Kobo accounting with automated turnover realization and zero-leakage draw voiding.</p>
                    </div>
                </div>

                <div class="flex items-start gap-3.5 p-3.5 rounded-xl bg-white/5 border border-white/10">
                    <div class="w-9 h-9 rounded-lg bg-[#1d5ba6] flex items-center justify-center text-white shrink-0 mt-0.5">
                        <i class="fa-solid fa-link text-sm"></i>
                    </div>
                    <div>
                        <h4 class="text-sm font-bold text-white">HMAC-SHA256 Cryptographic Audit Chain</h4>
                        <p class="text-xs text-slate-300 mt-0.5">Every stake, payout, and settlement is sequentially hashed onto an immutable ledger.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="relative z-10 flex items-center justify-between text-xs text-slate-400 border-t border-white/10 pt-6">
            <span>&copy; {{ date('Y') }} Q4 International Limited</span>
            <span class="flex items-center gap-1.5 text-blue-300 font-medium">
                <i class="fa-solid fa-shield-halved"></i> NLRC Compliant Node
            </span>
        </div>
    </div>

    <!-- Right Authentication Form Panel -->
    <div class="flex-1 flex flex-col justify-center items-center p-6 sm:p-12">
        <div class="w-full max-w-md">

            <!-- Brand Header -->
            <div class="text-center mb-8">
                <div class="inline-flex items-center justify-center bg-white px-5 py-3 rounded-2xl border border-slate-200 shadow-sm mb-5">
                    <img src="{{ asset('images/q4i-logo.png') }}" onerror="this.src='https://q4iltd.com/color@6x.png'" alt="Q4 International" class="h-12 w-auto object-contain">
                </div>
                <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Q4I Financial Engine</h1>
                <p class="text-sm text-slate-500 mt-1">Sign in to your authorized operator or clearinghouse portal</p>
            </div>

            <!-- Main White Card -->
            <div class="bg-white border border-slate-200/90 rounded-2xl p-8 shadow-xl shadow-slate-200/50">
                @if (session('success'))
                    <div class="mb-6 p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-medium flex items-center gap-2.5">
                        <i class="fa-solid fa-circle-check text-emerald-600 text-sm"></i>
                        <span>{{ session('success') }}</span>
                    </div>
                @endif

                @if ($errors->any())
                    <div class="mb-6 p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 text-xs font-medium flex items-center gap-2.5">
                        <i class="fa-solid fa-circle-exclamation text-rose-600 text-sm"></i>
                        <span>{{ $errors->first() }}</span>
                    </div>
                @endif

                <form method="POST" action="{{ route('login.post') }}" class="space-y-5">
                    @csrf

                    <div>
                        <label for="email" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                            Corporate Work Email
                        </label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400 text-sm">
                                <i class="fa-solid fa-envelope"></i>
                            </span>
                            <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus
                                placeholder="admin@q4iltd.com or corporate email"
                                class="w-full bg-slate-50/70 border border-slate-300 rounded-xl pl-10 pr-4 py-3 text-sm text-slate-900 placeholder-slate-400 focus:bg-white focus:outline-none focus:border-[#1d5ba6] focus:ring-2 focus:ring-[#1d5ba6]/15 transition">
                        </div>
                    </div>

                    <div>
                        <div class="flex justify-between items-center mb-2">
                            <label for="password" class="block text-xs font-bold text-slate-700 uppercase tracking-wider">
                                Password
                            </label>
                            <a href="{{ route('password.request') }}" class="text-xs font-semibold text-[#1d5ba6] hover:text-[#154682] transition">
                                Forgot password?
                            </a>
                        </div>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400 text-sm">
                                <i class="fa-solid fa-lock"></i>
                            </span>
                            <input type="password" id="password" name="password" required
                                placeholder="••••••••••••"
                                class="w-full bg-slate-50/70 border border-slate-300 rounded-xl pl-10 pr-4 py-3 text-sm text-slate-900 placeholder-slate-400 focus:bg-white focus:outline-none focus:border-[#1d5ba6] focus:ring-2 focus:ring-[#1d5ba6]/15 transition">
                        </div>
                    </div>

                    <div class="flex items-center justify-between pt-1">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="remember" class="rounded border-slate-300 text-[#1d5ba6] focus:ring-[#1d5ba6]">
                            <span class="text-xs font-medium text-slate-600">Remember session</span>
                        </label>
                        <span class="text-[11px] font-semibold text-slate-500 flex items-center gap-1.5 bg-slate-100 px-2.5 py-1 rounded-md">
                            <i class="fa-solid fa-shield-halved text-[#1d5ba6]"></i> 2FA Protected
                        </span>
                    </div>

                    <button type="submit"
                        class="w-full py-3.5 px-4 rounded-xl bg-[#1d5ba6] hover:bg-[#154682] font-bold text-sm text-white shadow-md shadow-[#1d5ba6]/20 transition duration-150 flex items-center justify-center gap-2">
                        <span>Sign In to Portal</span>
                        <i class="fa-solid fa-arrow-right text-xs"></i>
                    </button>
                </form>

                <div class="mt-6 pt-5 border-t border-slate-100 text-center">
                    <p class="text-[11px] text-slate-500 leading-relaxed">
                        Access restricted to authorized personnel. New operators require a cryptographic invitation from an executive clearinghouse administrator.
                    </p>
                </div>
            </div>

            <!-- Footer Note -->
            <div class="text-center mt-6 text-slate-400 text-xs flex justify-center items-center gap-3">
                <span>&copy; {{ date('Y') }} Q4 International</span>
                <span>&bull;</span>
                <span>PCI-DSS &amp; NLRC Compliant</span>
            </div>
        </div>
    </div>

</body>
</html>
