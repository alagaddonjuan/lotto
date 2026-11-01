<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Two-Factor Authentication | Q4I Financial Engine</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-slate-950 text-slate-100 min-h-screen flex items-center justify-center p-4">

    <div class="w-full max-w-md">
        <!-- Logo & Title -->
        <div class="text-center mb-8">
            <div class="inline-flex w-14 h-14 rounded-2xl bg-gradient-to-tr from-emerald-500 to-cyan-500 items-center justify-center font-black text-2xl text-slate-950 shadow-xl shadow-emerald-500/20 mb-4">
                <i class="fa-solid fa-shield-halved text-2xl"></i>
            </div>
            <h1 class="text-2xl font-black text-white tracking-tight">Two-Factor Authentication</h1>
            <p class="text-xs text-slate-400 mt-1">High-Security Verification Code Required</p>
        </div>

        <!-- 2FA Card -->
        <div class="bg-slate-900/90 border border-slate-800 rounded-3xl p-8 shadow-2xl backdrop-blur-xl">
            <p class="text-xs text-slate-300 text-center mb-6 leading-relaxed">
                Enter the 6-digit security code issued for your account (<span class="font-mono text-emerald-400">{{ $email }}</span>) to proceed.
            </p>

            @if (session('status'))
                <div class="mb-5 p-3.5 rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 text-xs text-center flex items-center justify-center gap-2">
                    <i class="fa-solid fa-circle-check"></i>
                    <span>{{ session('status') }}</span>
                </div>
            @endif

            @if ($errors->any())
                <div class="mb-5 p-3.5 rounded-xl bg-rose-500/10 border border-rose-500/30 text-rose-400 text-xs text-center flex items-center justify-center gap-2">
                    <i class="fa-solid fa-circle-exclamation"></i>
                    <span>{{ $errors->first() }}</span>
                </div>
            @endif

            <form method="POST" action="{{ route('two-factor.verify') }}" class="space-y-6">
                @csrf

                <div>
                    <label for="code" class="block text-center text-xs font-semibold text-slate-400 uppercase tracking-wider mb-3">6-Digit Security PIN</label>
                    <input type="text" id="code" name="code" required maxlength="6" autofocus
                        placeholder="••••••"
                        class="w-full text-center tracking-[0.5em] text-2xl font-mono font-bold bg-slate-950 border border-slate-700 rounded-2xl py-3.5 text-emerald-400 placeholder-slate-700 focus:outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/40 transition">
                </div>

                <button type="submit"
                    class="w-full py-3.5 px-4 rounded-xl bg-gradient-to-r from-emerald-500 to-teal-500 hover:from-emerald-400 hover:to-teal-400 font-bold text-sm text-slate-950 shadow-lg shadow-emerald-500/20 transition duration-150 flex items-center justify-center gap-2">
                    <i class="fa-solid fa-lock-open"></i>
                    <span>Verify & Access Dashboard</span>
                </button>
            </form>

            @if(isset($demoOtp) && $demoOtp)
                <!-- Developer / Demo Helper for Testing -->
                <div class="mt-6 p-3 rounded-xl bg-slate-950 border border-slate-800 text-center">
                    <p class="text-[10px] text-slate-500 uppercase tracking-wider mb-1 font-semibold">Active Security Code</p>
                    <button type="button" onclick="autofillCode('{{ $demoOtp }}')" class="font-mono text-sm font-bold text-emerald-400 hover:text-emerald-300 transition flex items-center justify-center gap-2 mx-auto">
                        <span>{{ $demoOtp }}</span>
                        <span class="text-[10px] bg-slate-800 text-slate-300 px-2 py-0.5 rounded font-sans">Click to Autofill</span>
                    </button>
                </div>
            @endif

            <div class="mt-6 pt-6 border-t border-slate-800 flex justify-between items-center text-xs">
                <form method="POST" action="{{ route('two-factor.resend') }}">
                    @csrf
                    <button type="submit" class="text-slate-400 hover:text-emerald-400 transition flex items-center gap-1.5">
                        <i class="fa-solid fa-rotate-right"></i>
                        <span>Resend Code</span>
                    </button>
                </form>

                <a href="{{ route('login') }}" class="text-slate-500 hover:text-slate-300 transition">
                    Back to Login
                </a>
            </div>
        </div>
    </div>

    <script>
        function autofillCode(code) {
            const input = document.getElementById('code');
            input.value = code;
            input.focus();
        }
    </script>
</body>
</html>
