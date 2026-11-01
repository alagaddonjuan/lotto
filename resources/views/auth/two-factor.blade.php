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
        <!-- Logo & Header -->
        <div class="text-center mb-8">
            <div class="inline-flex w-14 h-14 rounded-2xl bg-gradient-to-tr from-emerald-500 to-cyan-500 items-center justify-center font-black text-2xl text-slate-950 shadow-xl shadow-emerald-500/20 mb-4">
                <i class="fa-solid fa-shield-halved text-2xl"></i>
            </div>
            <h1 class="text-2xl font-black text-white tracking-tight">Two-Factor Authentication</h1>
            <p class="text-xs text-slate-400 mt-1">Enterprise Operator Identity Verification</p>
        </div>

        <!-- 2FA Card -->
        <div class="bg-slate-900/90 border border-slate-800 rounded-3xl p-8 shadow-2xl backdrop-blur-xl">
            <div class="text-center mb-6">
                <p class="text-xs text-slate-300 leading-relaxed">
                    A secure 6-digit authentication code has been dispatched to:
                </p>
                <p class="text-xs font-mono font-bold text-emerald-400 mt-1 bg-slate-950/80 py-1 px-3 rounded-lg inline-block border border-slate-800">
                    {{ $email }}
                </p>
            </div>

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
                    <label for="code" class="block text-center text-xs font-semibold text-slate-400 uppercase tracking-wider mb-3">
                        Enter 6-Digit Authentication Code
                    </label>
                    <input type="text" id="code" name="code" required maxlength="6" autofocus pattern="[0-9]{6}" inputmode="numeric"
                        placeholder="••••••"
                        class="w-full text-center tracking-[0.5em] text-3xl font-mono font-bold bg-slate-950 border border-slate-700 rounded-2xl py-4 text-emerald-400 placeholder-slate-800 focus:outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/40 transition">
                </div>

                <button type="submit"
                    class="w-full py-3.5 px-4 rounded-xl bg-gradient-to-r from-emerald-500 to-teal-500 hover:from-emerald-400 hover:to-teal-400 font-bold text-sm text-slate-950 shadow-lg shadow-emerald-500/20 transition duration-150 flex items-center justify-center gap-2">
                    <i class="fa-solid fa-lock-open"></i>
                    <span>Verify & Continue to Clearinghouse</span>
                </button>
            </form>

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

        <div class="text-center mt-6 text-slate-600 text-xs flex justify-center items-center gap-4">
            <span>&copy; {{ date('Y') }} Q4I Limited</span>
            <span>&bull;</span>
            <span>PCI-DSS & NLRC Compliant</span>
        </div>
    </div>

</body>
</html>
