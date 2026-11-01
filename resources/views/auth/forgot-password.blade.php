<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password | Q4I Financial Engine</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-slate-950 text-slate-100 min-h-screen flex items-center justify-center p-4">

    <div class="w-full max-w-md">
        <!-- Logo & Title -->
        <div class="text-center mb-8">
            <div class="inline-flex w-14 h-14 rounded-2xl bg-gradient-to-tr from-emerald-500 to-cyan-500 items-center justify-center font-black text-2xl text-slate-950 shadow-xl shadow-emerald-500/20 mb-4">
                <i class="fa-solid fa-key text-2xl"></i>
            </div>
            <h1 class="text-2xl font-black text-white tracking-tight">Reset Password</h1>
            <p class="text-xs text-slate-400 mt-1">Recover your operator portal access</p>
        </div>

        <!-- Forgot Password Card -->
        <div class="bg-slate-900/90 border border-slate-800 rounded-3xl p-8 shadow-2xl backdrop-blur-xl">
            <p class="text-xs text-slate-300 mb-6 leading-relaxed">
                Enter your registered corporate email below. We'll generate a secure token to reset your password.
            </p>

            @if (session('status'))
                <div class="mb-5 p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 text-xs space-y-2">
                    <div class="flex items-center gap-2">
                        <i class="fa-solid fa-circle-check"></i>
                        <span>{{ session('status') }}</span>
                    </div>
                    @if (session('reset_url'))
                        <div class="pt-2 border-t border-emerald-500/20">
                            <a href="{{ session('reset_url') }}" class="inline-flex items-center gap-1.5 font-bold text-white bg-emerald-500 hover:bg-emerald-400 px-3 py-1.5 rounded-lg text-xs transition">
                                <span>Open Reset Password Link</span>
                                <i class="fa-solid fa-arrow-up-right-from-square text-[10px]"></i>
                            </a>
                        </div>
                    @endif
                </div>
            @endif

            @if ($errors->any())
                <div class="mb-5 p-4 rounded-xl bg-rose-500/10 border border-rose-500/30 text-rose-400 text-xs flex items-center gap-2">
                    <i class="fa-solid fa-circle-exclamation"></i>
                    <span>{{ $errors->first() }}</span>
                </div>
            @endif

            <form method="POST" action="{{ route('password.email') }}" class="space-y-5">
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

                <button type="submit"
                    class="w-full py-3.5 px-4 rounded-xl bg-gradient-to-r from-emerald-500 to-teal-500 hover:from-emerald-400 hover:to-teal-400 font-bold text-sm text-slate-950 shadow-lg shadow-emerald-500/20 transition duration-150 flex items-center justify-center gap-2">
                    <span>Send Reset Instructions</span>
                    <i class="fa-solid fa-paper-plane"></i>
                </button>
            </form>

            <div class="mt-6 text-center text-xs text-slate-400">
                Remembered your password?
                <a href="{{ route('login') }}" class="text-emerald-400 hover:text-emerald-300 font-bold ml-1 transition">
                    Back to Login &rarr;
                </a>
            </div>
        </div>
    </div>

</body>
</html>
