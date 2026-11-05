<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mandatory 2FA Setup | Q4I Financial Engine</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@500;600;700&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .font-mono { font-family: 'JetBrains Mono', monospace; }
    </style>
</head>
<body class="bg-slate-50 text-slate-900 min-h-screen flex items-center justify-center p-6 antialiased">

    <div class="w-full max-w-md">
        <!-- Brand Header -->
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center bg-white px-5 py-3 rounded-2xl border border-slate-200 shadow-sm mb-5">
                <img src="{{ asset('images/q4i-logo.png') }}" onerror="this.src='https://q4iltd.com/color@6x.png'" alt="Q4 International" class="h-12 w-auto object-contain">
            </div>
            <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Security Policy Mandate</h1>
            <p class="text-sm text-slate-500 mt-1">Configure Two-Factor Authentication (2FA)</p>
        </div>

        <!-- 2FA Card -->
        <div class="bg-white border border-slate-200/90 rounded-2xl p-8 shadow-xl shadow-slate-200/50">
            @if (session('warning'))
                <div class="mb-6 p-4 rounded-xl bg-amber-50 border border-amber-200 text-amber-800 text-xs font-medium flex items-start gap-2.5">
                    <i class="fa-solid fa-triangle-exclamation text-amber-600 mt-0.5"></i>
                    <span>{{ session('warning') }}</span>
                </div>
            @endif

            @if (session('status'))
                <div class="mb-6 p-4 rounded-xl bg-blue-50 border border-blue-200 text-blue-800 text-xs font-medium flex items-center gap-2">
                    <i class="fa-solid fa-circle-info text-[#1d5ba6]"></i>
                    <span>{{ session('status') }}</span>
                </div>
            @endif

            @if ($errors->any())
                <div class="mb-6 p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 text-xs font-medium flex items-center gap-2">
                    <i class="fa-solid fa-circle-exclamation text-rose-600"></i>
                    <span>{{ $errors->first() }}</span>
                </div>
            @endif

            <div class="text-xs text-slate-600 leading-relaxed mb-6 bg-slate-50 p-4 rounded-xl border border-slate-200">
                To comply with National Lottery Regulatory Commission (NLRC) and institutional financial clearinghouse standards, all operator representatives and administrative personnel must maintain active 2FA.
                A 6-digit verification code has been dispatched to <span class="font-bold text-[#1d5ba6] font-mono">{{ $user->email }}</span>.
            </div>

            <form id="two-factor-setup-form" method="POST" action="{{ route('two-factor.setup.post') }}" class="space-y-6">
                @csrf

                <div>
                    <label for="code" class="block text-center text-xs font-bold text-slate-600 uppercase tracking-wider mb-2">Enter 6-Digit Verification Code</label>
                    <input type="text" id="code" name="code" maxlength="6" pattern="[0-9]{6}" required autofocus
                        placeholder="••••••"
                        class="w-full bg-slate-50 border border-slate-300 rounded-xl px-4 py-3.5 text-center text-2xl font-mono font-bold tracking-[0.5em] text-slate-900 placeholder-slate-300 focus:bg-white focus:outline-none focus:border-[#1d5ba6] focus:ring-2 focus:ring-[#1d5ba6]/20 transition">
                </div>

                <button type="submit"
                    class="w-full py-3.5 px-4 rounded-xl bg-[#1d5ba6] hover:bg-[#154682] font-bold text-sm text-white shadow-md shadow-[#1d5ba6]/20 transition duration-150 flex items-center justify-center gap-2">
                    <i class="fa-solid fa-lock text-xs"></i>
                    <span>Activate 2FA &amp; Access Clearinghouse</span>
                </button>
            </form>

            <div class="mt-6 pt-5 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="hover:text-rose-600 font-medium transition flex items-center gap-1.5">
                        <i class="fa-solid fa-arrow-right-from-bracket"></i>
                        <span>Cancel &amp; Log Out</span>
                    </button>
                </form>

                <form method="POST" action="{{ route('two-factor.resend') }}">
                    @csrf
                    <button type="submit" class="text-[#1d5ba6] hover:text-[#154682] font-semibold transition flex items-center gap-1.5">
                        <i class="fa-solid fa-rotate-right"></i>
                        <span>Resend Code</span>
                    </button>
                </form>
            </div>
        </div>

        <!-- Footer -->
        <div class="text-center mt-6 text-slate-400 text-xs">
            <span>&copy; {{ date('Y') }} Q4 International &bull; Mandatory 2FA Active</span>
        </div>
    </div>

</body>
</html>
