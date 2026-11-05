<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password | Q4I Financial Engine</title>
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
            <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Reset Password</h1>
            <p class="text-sm text-slate-500 mt-1">Operator Account Access Recovery</p>
        </div>

        <!-- Forgot Password Card -->
        <div class="bg-white border border-slate-200/90 rounded-2xl p-8 shadow-xl shadow-slate-200/50">
            <p class="text-xs text-slate-600 mb-6 leading-relaxed">
                Enter your registered corporate email below. If an active operator account is found, a secure password reset link will be dispatched to your inbox.
            </p>

            @if (session('status'))
                <div class="mb-5 p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-medium flex items-center gap-2">
                    <i class="fa-solid fa-circle-check text-emerald-600"></i>
                    <span>{{ session('status') }}</span>
                </div>
            @endif

            @if ($errors->any())
                <div class="mb-5 p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 text-xs font-medium flex items-center gap-2">
                    <i class="fa-solid fa-circle-exclamation text-rose-600"></i>
                    <span>{{ $errors->first() }}</span>
                </div>
            @endif

            <form method="POST" action="{{ route('password.email') }}" class="space-y-5">
                @csrf

                <div>
                    <label for="email" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Corporate Work Email</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400 text-sm">
                            <i class="fa-solid fa-envelope"></i>
                        </span>
                        <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus
                            placeholder="admin@q4iltd.com or corporate email"
                            class="w-full bg-slate-50 border border-slate-300 rounded-xl pl-10 pr-4 py-3 text-sm text-slate-900 placeholder-slate-400 focus:bg-white focus:outline-none focus:border-[#1d5ba6] focus:ring-2 focus:ring-[#1d5ba6]/15 transition">
                    </div>
                </div>

                <button type="submit"
                    class="w-full py-3.5 px-4 rounded-xl bg-[#1d5ba6] hover:bg-[#154682] font-bold text-sm text-white shadow-md shadow-[#1d5ba6]/20 transition duration-150 flex items-center justify-center gap-2">
                    <i class="fa-solid fa-paper-plane text-xs"></i>
                    <span>Send Password Reset Link</span>
                </button>
            </form>

            <div class="mt-6 pt-5 border-t border-slate-100 text-center text-xs">
                <a href="{{ route('login') }}" class="text-slate-600 hover:text-[#1d5ba6] font-semibold transition flex items-center justify-center gap-1.5">
                    <i class="fa-solid fa-arrow-left"></i>
                    <span>Return to Sign In</span>
                </a>
            </div>
        </div>
    </div>

</body>
</html>
