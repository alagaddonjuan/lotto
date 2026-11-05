<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Two-Factor Authentication | Q4I Financial Engine</title>
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

    <!-- Left Corporate Security Panel -->
    <div class="hidden lg:flex lg:w-[45%] xl:w-[48%] auth-grid-bg text-white flex-col justify-between p-12 relative overflow-hidden">
        <div class="absolute -top-24 -left-24 w-96 h-96 bg-[#1d5ba6]/30 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute bottom-0 right-0 w-96 h-96 bg-blue-500/15 rounded-full blur-3xl pointer-events-none"></div>

        <div class="relative z-10 flex items-center justify-between">
            <div class="bg-white px-4 py-2.5 rounded-xl shadow-lg inline-flex items-center">
                <img src="{{ asset('images/q4i-logo.png') }}" onerror="this.src='https://q4iltd.com/color@6x.png'" alt="Q4 International" class="h-10 w-auto object-contain">
            </div>
            <span class="text-xs font-mono uppercase tracking-widest px-3 py-1 rounded-full bg-white/10 border border-white/15 text-blue-200">
                2FA Identity Gate
            </span>
        </div>

        <div class="relative z-10 max-w-lg my-auto py-12">
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-blue-500/20 border border-blue-400/30 text-blue-200 text-xs font-semibold mb-6">
                <i class="fa-solid fa-user-shield"></i>
                <span>Zero-Trust Access Control</span>
            </div>
            <h2 class="text-3xl xl:text-4xl font-extrabold tracking-tight text-white leading-tight mb-4">
                Multi-Factor Clearinghouse Verification
            </h2>
            <p class="text-slate-300 text-sm leading-relaxed">
                All operator representatives and platform administrators are verified through time-bound one-time authentication codes dispatched to registered corporate email addresses.
            </p>
        </div>

        <div class="relative z-10 flex items-center justify-between text-xs text-slate-400 border-t border-white/10 pt-6">
            <span>&copy; {{ date('Y') }} Q4 International Limited</span>
            <span class="flex items-center gap-1.5 text-blue-300 font-medium">
                <i class="fa-solid fa-lock"></i> Encrypted Session
            </span>
        </div>
    </div>

    <!-- Right Verification Card -->
    <div class="flex-1 flex flex-col justify-center items-center p-6 sm:p-12">
        <div class="w-full max-w-md">

            <!-- Brand Header -->
            <div class="text-center mb-8">
                <div class="inline-flex items-center justify-center bg-white px-5 py-3 rounded-2xl border border-slate-200 shadow-sm mb-5">
                    <img src="{{ asset('images/q4i-logo.png') }}" onerror="this.src='https://q4iltd.com/color@6x.png'" alt="Q4 International" class="h-12 w-auto object-contain">
                </div>
                <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Two-Factor Authentication</h1>
                <p class="text-sm text-slate-500 mt-1">Enterprise Operator Identity Verification</p>
            </div>

            <!-- 2FA White Card -->
            <div class="bg-white border border-slate-200/90 rounded-2xl p-8 shadow-xl shadow-slate-200/50">
                <div class="text-center mb-6">
                    <p class="text-xs text-slate-600 leading-relaxed">
                        A secure 6-digit authentication code has been dispatched to:
                    </p>
                    <p class="text-xs font-mono font-bold text-[#1d5ba6] mt-2 bg-blue-50 py-1.5 px-3.5 rounded-lg inline-block border border-blue-200">
                        {{ $email }}
                    </p>
                </div>

                @if (session('status'))
                    <div class="mb-5 p-3.5 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-medium text-center flex items-center justify-center gap-2">
                        <i class="fa-solid fa-circle-check text-emerald-600"></i>
                        <span>{{ session('status') }}</span>
                    </div>
                @endif

                @if ($errors->any())
                    <div class="mb-5 p-3.5 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 text-xs font-medium text-center flex items-center justify-center gap-2">
                        <i class="fa-solid fa-circle-exclamation text-rose-600"></i>
                        <span>{{ $errors->first() }}</span>
                    </div>
                @endif

                <form id="two-factor-form" method="POST" action="{{ route('two-factor.verify') }}" class="space-y-6">
                    @csrf

                    <div>
                        <label for="code" class="block text-center text-xs font-bold text-slate-600 uppercase tracking-wider mb-3">
                            Enter 6-Digit Authentication Code
                        </label>
                        <input type="text" id="code" name="code" required maxlength="6" autofocus pattern="[0-9]{6}" inputmode="numeric"
                            placeholder="••••••"
                            class="w-full text-center tracking-[0.5em] text-3xl font-mono font-bold bg-slate-50 border border-slate-300 rounded-xl py-4 text-slate-900 placeholder-slate-300 focus:bg-white focus:outline-none focus:border-[#1d5ba6] focus:ring-2 focus:ring-[#1d5ba6]/20 transition">
                    </div>

                    <button type="submit"
                        class="w-full py-3.5 px-4 rounded-xl bg-[#1d5ba6] hover:bg-[#154682] font-bold text-sm text-white shadow-md shadow-[#1d5ba6]/20 transition duration-150 flex items-center justify-center gap-2">
                        <i class="fa-solid fa-lock-open text-xs"></i>
                        <span>Verify &amp; Continue to Clearinghouse</span>
                    </button>
                </form>

                <div class="mt-6 pt-5 border-t border-slate-100 flex justify-between items-center text-xs">
                    <form method="POST" action="{{ route('two-factor.resend') }}">
                        @csrf
                        <button type="submit" class="font-semibold text-[#1d5ba6] hover:text-[#154682] transition flex items-center gap-1.5">
                            <i class="fa-solid fa-rotate-right"></i>
                            <span>Resend Code</span>
                        </button>
                    </form>

                    <a href="{{ route('login') }}" class="font-medium text-slate-500 hover:text-slate-800 transition">
                        Back to Login
                    </a>
                </div>
            </div>

            <div class="text-center mt-6 text-slate-400 text-xs flex justify-center items-center gap-3">
                <span>&copy; {{ date('Y') }} Q4 International</span>
                <span>&bull;</span>
                <span>PCI-DSS &amp; NLRC Compliant</span>
            </div>
        </div>
    </div>

</body>
</html>
