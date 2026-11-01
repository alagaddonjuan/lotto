<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Q4I Lotto Platform</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-slate-950 text-slate-100 min-h-screen flex items-center justify-center p-4">

    <div class="w-full max-w-md">
        <!-- Logo & Title -->
        <div class="text-center mb-8">
            <div class="inline-flex w-14 h-14 rounded-2xl bg-gradient-to-tr from-emerald-500 to-cyan-500 items-center justify-center font-black text-2xl text-slate-950 shadow-xl shadow-emerald-500/20 mb-4">
                Q
            </div>
            <h1 class="text-2xl font-black text-white tracking-tight">Q4I Financial Engine</h1>
            <p class="text-xs text-slate-400 mt-1">Operator Representative & Administration Portal</p>
        </div>

        <!-- Login Card -->
        <div class="bg-slate-900/90 border border-slate-800 rounded-3xl p-8 shadow-2xl backdrop-blur-xl">
            @if ($errors->any())
                <div class="mb-6 p-4 rounded-xl bg-rose-500/10 border border-rose-500/30 text-rose-400 text-xs">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('login.post') }}" class="space-y-5">
                @csrf

                <div>
                    <label for="email" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2">Corporate Email</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-500 text-sm">
                            <i class="fa-solid fa-envelope"></i>
                        </span>
                        <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus
                            placeholder="admin@q4iltd.com or rep@modernlottery.ng"
                            class="w-full bg-slate-950 border border-slate-800 rounded-xl pl-10 pr-4 py-3 text-sm text-slate-100 placeholder-slate-600 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition">
                    </div>
                </div>

                <div>
                    <label for="password" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2">Password</label>
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
                    <span class="text-slate-500">SSL 256-bit Encrypted</span>
                </div>

                <button type="submit"
                    class="w-full py-3.5 px-4 rounded-xl bg-gradient-to-r from-emerald-500 to-teal-500 hover:from-emerald-400 hover:to-teal-400 font-bold text-sm text-slate-950 shadow-lg shadow-emerald-500/20 transition duration-150 flex items-center justify-center gap-2">
                    <span>Sign In to Dashboard</span>
                    <i class="fa-solid fa-arrow-right"></i>
                </button>
            </form>

            <!-- Quick Demo Credentials Box -->
            <div class="mt-8 pt-6 border-t border-slate-800 text-xs">
                <p class="text-slate-500 font-semibold uppercase tracking-wider text-[10px] mb-3">Pre-Configured Operator Accounts</p>
                <div class="space-y-2">
                    <button type="button" onclick="fillCreds('admin@q4iltd.com', 'admin123456')" class="w-full text-left p-2.5 rounded-xl bg-slate-950/60 hover:bg-slate-800 border border-slate-800/80 text-slate-300 flex justify-between items-center transition">
                        <div>
                            <span class="font-semibold text-emerald-400">Q4I Super Admin:</span> admin@q4iltd.com
                        </div>
                        <span class="text-[10px] bg-slate-800 px-2 py-0.5 rounded text-slate-400">admin123456</span>
                    </button>
                    <button type="button" onclick="fillCreds('rep@modernlottery.ng', 'rep123456')" class="w-full text-left p-2.5 rounded-xl bg-slate-950/60 hover:bg-slate-800 border border-slate-800/80 text-slate-300 flex justify-between items-center transition">
                        <div>
                            <span class="font-semibold text-purple-400">Modern Lottery Rep:</span> rep@modernlottery.ng
                        </div>
                        <span class="text-[10px] bg-slate-800 px-2 py-0.5 rounded text-slate-400">rep123456</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        function fillCreds(email, password) {
            document.getElementById('email').value = email;
            document.getElementById('password').value = password;
        }
    </script>
</body>
</html>
