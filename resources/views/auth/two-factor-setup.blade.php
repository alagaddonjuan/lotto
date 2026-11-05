<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mandatory 2FA Setup | Q4I Financial Engine</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-slate-950 text-slate-100 min-h-screen flex items-center justify-center p-4">

    <div class="w-full max-w-md">
        <!-- Logo & Title -->
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center mb-4">
                <img src="https://q4iltd.com/color@6x.png" onerror="this.src='{{ asset('images/q4i-logo.png') }}'" alt="Q4I Limited" class="h-16 w-auto object-contain drop-shadow-2xl">
            </div>
            <h1 class="text-2xl font-black text-white tracking-tight">Security Policy Mandate</h1>
            <p class="text-xs text-slate-400 mt-1">Configure Two-Factor Authentication (2FA)</p>
        </div>

        <!-- 2FA Card -->
        <div class="bg-slate-900/90 border border-slate-800 rounded-3xl p-8 shadow-2xl backdrop-blur-xl">
            @if (session('warning'))
                <div class="mb-6 p-4 rounded-xl bg-amber-500/10 border border-amber-500/30 text-amber-400 text-xs flex items-start gap-2.5">
                    <i class="fa-solid fa-triangle-exclamation mt-0.5"></i>
                    <span>{{ session('warning') }}</span>
                </div>
            @endif

            @if (session('status'))
                <div class="mb-6 p-4 rounded-xl bg-cyan-500/10 border border-cyan-500/30 text-cyan-400 text-xs flex items-center gap-2">
                    <i class="fa-solid fa-circle-info"></i>
                    <span>{{ session('status') }}</span>
                </div>
            @endif

            @if ($errors->any())
                <div class="mb-6 p-4 rounded-xl bg-rose-500/10 border border-rose-500/30 text-rose-400 text-xs flex items-center gap-2">
                    <i class="fa-solid fa-circle-exclamation"></i>
                    <span>{{ $errors->first() }}</span>
                </div>
            @endif

            <div class="text-xs text-slate-300 leading-relaxed mb-6">
                To comply with National Lottery Regulatory Commission (NLRC) and institutional financial clearinghouse standards, all operator reps and administrative personnel must maintain active 2FA.
                A 6-digit verification code has been dispatched to <span class="font-bold text-white font-mono">{{ $user->email }}</span>.
            </div>

            @if (!empty($codePreview))
                <div class="mb-5 p-4 rounded-2xl bg-amber-500/10 border border-amber-500/30 text-amber-300 text-xs text-center space-y-2">
                    <div class="flex items-center justify-center gap-1.5 font-bold text-amber-400">
                        <i class="fa-solid fa-server"></i>
                        <span>Local / Staging Environment Notice</span>
                    </div>
                    <p class="text-[11px] text-slate-300">
                        Outbound mail is in <code class="bg-slate-950 px-1.5 py-0.5 rounded text-amber-300 font-mono">log</code> mode (no live SMTP credentials in .env).
                    </p>
                    <div class="flex items-center justify-center gap-2 pt-1">
                        <span class="font-mono text-base font-bold tracking-widest text-emerald-400 bg-slate-950 px-3 py-1 rounded-lg border border-slate-800">
                            {{ $codePreview }}
                        </span>
                        <button type="button" onclick="document.getElementById('code').value='{{ $codePreview }}'; document.getElementById('two-factor-setup-form').submit();"
                            class="text-[11px] font-bold bg-emerald-500/20 hover:bg-emerald-500/30 text-emerald-300 px-3 py-1.5 rounded-lg border border-emerald-500/30 transition flex items-center gap-1">
                            <i class="fa-solid fa-bolt"></i>
                            <span>Quick Fill & Activate</span>
                        </button>
                    </div>
                </div>
            @endif

            <form id="two-factor-setup-form" method="POST" action="{{ route('two-factor.setup.post') }}" class="space-y-6">
                @csrf

                <div>
                    <label for="code" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2">Enter 6-Digit Verification Code</label>
                    <input type="text" id="code" name="code" maxlength="6" pattern="[0-9]{6}" required autofocus
                        placeholder="••••••"
                        class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3.5 text-center text-2xl font-mono tracking-[0.5em] text-white placeholder-slate-700 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition">
                </div>

                <button type="submit"
                    class="w-full py-3.5 px-4 rounded-xl bg-gradient-to-r from-emerald-500 to-teal-500 hover:from-emerald-400 hover:to-teal-400 font-bold text-sm text-slate-950 shadow-lg shadow-emerald-500/20 transition duration-150 flex items-center justify-center gap-2">
                    <i class="fa-solid fa-lock"></i>
                    <span>Activate 2FA & Access Clearinghouse</span>
                </button>
            </form>

            <div class="mt-6 pt-6 border-t border-slate-800 flex items-center justify-between text-xs text-slate-400">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="hover:text-rose-400 transition flex items-center gap-1.5">
                        <i class="fa-solid fa-arrow-right-from-bracket"></i>
                        <span>Cancel & Log Out</span>
                    </button>
                </form>

                <form method="POST" action="{{ route('two-factor.resend') }}">
                    @csrf
                    <button type="submit" class="text-emerald-400 hover:text-emerald-300 font-semibold transition flex items-center gap-1.5">
                        <i class="fa-solid fa-rotate-right"></i>
                        <span>Resend Code</span>
                    </button>
                </form>
            </div>
        </div>

        <!-- Footer -->
        <div class="text-center mt-6 text-slate-600 text-xs">
            <span>&copy; {{ date('Y') }} Q4I Financial Engine &bull; Mandatory 2FA Active</span>
        </div>
    </div>

</body>
</html>

