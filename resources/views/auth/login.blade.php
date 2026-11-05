<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In | Q4I Financial Engine</title>
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
            <h1 class="text-2xl font-black text-white tracking-tight">Q4I Financial Engine</h1>
            <p class="text-xs text-slate-400 mt-1">Multi-Tenant Lottery Operator & Commission Clearinghouse</p>
        </div>

        <!-- Login Card -->
        <div class="bg-slate-900/90 border border-slate-800 rounded-3xl p-8 shadow-2xl backdrop-blur-xl">
            @if (session('success'))
                <div class="mb-6 p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 text-xs flex items-center gap-2">
                    <i class="fa-solid fa-circle-check"></i>
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            @if ($errors->any())
                <div class="mb-6 p-4 rounded-xl bg-rose-500/10 border border-rose-500/30 text-rose-400 text-xs flex items-center gap-2">
                    <i class="fa-solid fa-circle-exclamation"></i>
                    <span>{{ $errors->first() }}</span>
                </div>
            @endif

            <form method="POST" action="{{ route('login.post') }}" class="space-y-5">
                @csrf

                <div>
                    <label for="email" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2">Corporate Work Email</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-500 text-sm">
                            <i class="fa-solid fa-envelope"></i>
                        </span>
                        <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus
                            placeholder="admin@q4iltd.com or corporate email"
                            class="w-full bg-slate-950 border border-slate-800 rounded-xl pl-10 pr-4 py-3 text-sm text-slate-100 placeholder-slate-600 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition">
                    </div>
                </div>

                <div>
                    <div class="flex justify-between items-center mb-2">
                        <label for="password" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider">Password</label>
                        <a href="{{ route('password.request') }}" class="text-xs text-emerald-400 hover:text-emerald-300 transition">
                            Forgot password?
                        </a>
                    </div>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-500 text-sm">
                            <i class="fa-solid fa-lock"></i>
                        </span>
                        <input type="password" id="password" name="password" required
                            placeholder="••••••••••••"
                            class="w-full bg-slate-950 border border-slate-800 rounded-xl pl-10 pr-4 py-3 text-sm text-slate-100 placeholder-slate-600 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition">
                    </div>
                </div>

                <div class="flex items-center justify-between text-xs text-slate-400">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="remember" class="rounded bg-slate-800 border-slate-700 text-emerald-500 focus:ring-emerald-500">
                        <span>Remember session</span>
                    </label>
                    <span class="text-slate-500 flex items-center gap-1">
                        <i class="fa-solid fa-shield-halved text-[10px]"></i> 2FA Protected
                    </span>
                </div>

                <button type="submit"
                    class="w-full py-3.5 px-4 rounded-xl bg-gradient-to-r from-emerald-500 to-teal-500 hover:from-emerald-400 hover:to-teal-400 font-bold text-sm text-slate-950 shadow-lg shadow-emerald-500/20 transition duration-150 flex items-center justify-center gap-2">
                    <span>Sign In to Portal</span>
                    <i class="fa-solid fa-arrow-right"></i>
                </button>
            </form>

            <!-- Institutional Access Notice -->
            <div class="mt-8 pt-6 border-t border-slate-800 text-center text-xs text-slate-500">
                <span>Access restricted to authorized personnel. New operators must receive a cryptographic invitation from an executive clearinghouse administrator.</span>
            </div>
        </div>

        <!-- Footer -->
        <div class="text-center mt-6 text-slate-600 text-xs flex justify-center items-center gap-4">
            <span>&copy; {{ date('Y') }} Q4I Limited</span>
            <span>&bull;</span>
            <span>NLRC Licensed Clearinghouse Node</span>
        </div>
    </div>

</body>
</html>
