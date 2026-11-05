<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Set New Password | Q4I Financial Engine</title>
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
            <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Set New Password</h1>
            <p class="text-sm text-slate-500 mt-1">Choose a strong password to protect your account</p>
        </div>

        <!-- Reset Password Card -->
        <div class="bg-white border border-slate-200/90 rounded-2xl p-8 shadow-xl shadow-slate-200/50">
            @if ($errors->any())
                <div class="mb-5 p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 text-xs font-medium flex items-center gap-2">
                    <i class="fa-solid fa-circle-exclamation text-rose-600"></i>
                    <span>{{ $errors->first() }}</span>
                </div>
            @endif

            <form method="POST" action="{{ route('password.update') }}" class="space-y-4">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">

                <div>
                    <label for="email" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Corporate Email</label>
                    <input type="email" id="email" name="email" value="{{ old('email', $email) }}" required
                        class="w-full bg-slate-50 border border-slate-300 rounded-xl px-4 py-3 text-sm text-slate-900 focus:bg-white focus:outline-none focus:border-[#1d5ba6] focus:ring-2 focus:ring-[#1d5ba6]/15 transition">
                </div>

                <div>
                    <label for="password" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">New Password</label>
                    <input type="password" id="password" name="password" required minlength="8" autofocus
                        placeholder="Minimum 8 characters"
                        class="w-full bg-slate-50 border border-slate-300 rounded-xl px-4 py-3 text-sm text-slate-900 placeholder-slate-400 focus:bg-white focus:outline-none focus:border-[#1d5ba6] focus:ring-2 focus:ring-[#1d5ba6]/15 transition">
                </div>

                <div>
                    <label for="password_confirmation" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Confirm New Password</label>
                    <input type="password" id="password_confirmation" name="password_confirmation" required minlength="8"
                        placeholder="Repeat new password"
                        class="w-full bg-slate-50 border border-slate-300 rounded-xl px-4 py-3 text-sm text-slate-900 placeholder-slate-400 focus:bg-white focus:outline-none focus:border-[#1d5ba6] focus:ring-2 focus:ring-[#1d5ba6]/15 transition">
                </div>

                <button type="submit"
                    class="w-full py-3.5 px-4 mt-2 rounded-xl bg-[#1d5ba6] hover:bg-[#154682] font-bold text-sm text-white shadow-md shadow-[#1d5ba6]/20 transition duration-150 flex items-center justify-center gap-2">
                    <i class="fa-solid fa-check-double text-xs"></i>
                    <span>Update Password &amp; Sign In</span>
                </button>
            </form>
        </div>
    </div>

</body>
</html>
