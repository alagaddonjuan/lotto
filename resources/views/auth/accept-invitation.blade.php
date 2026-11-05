<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accept Staff Invitation | Q4I Financial Engine</title>
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
            <h1 class="text-2xl font-black text-white tracking-tight">Staff Onboarding</h1>
            <p class="text-xs text-slate-400 mt-1">Accept Authorized Operator Role Invitation</p>
        </div>

        <!-- Acceptance Card -->
        <div class="bg-slate-900/90 border border-slate-800 rounded-3xl p-8 shadow-2xl backdrop-blur-xl">
            <!-- Bound Organization & Role Badge -->
            <div class="mb-6 p-4 rounded-2xl bg-slate-950 border border-slate-800/80 space-y-2">
                <div class="flex justify-between items-center text-xs">
                    <span class="text-slate-400">Assigned Operator:</span>
                    <span class="font-bold text-white">{{ $tenant->name ?? 'Q4I Clearinghouse' }}</span>
                </div>
                <div class="flex justify-between items-center text-xs">
                    <span class="text-slate-400">Assigned Role:</span>
                    <span class="px-2 py-0.5 rounded bg-emerald-500/10 text-emerald-400 font-mono font-bold text-[11px] uppercase">{{ str_replace('_', ' ', $invitation->role) }}</span>
                </div>
                <div class="flex justify-between items-center text-xs">
                    <span class="text-slate-400">Authorized Email:</span>
                    <span class="font-mono text-slate-300">{{ $invitation->email }}</span>
                </div>
            </div>

            @if ($errors->any())
                <div class="mb-6 p-4 rounded-xl bg-rose-500/10 border border-rose-500/30 text-rose-400 text-xs flex items-center gap-2">
                    <i class="fa-solid fa-circle-exclamation"></i>
                    <span>{{ $errors->first() }}</span>
                </div>
            @endif

            <form method="POST" action="{{ request()->fullUrl() }}" class="space-y-5">
                @csrf

                <div>
                    <label for="name" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2">Full Legal Name</label>
                    <input type="text" id="name" name="name" value="{{ old('name') }}" required autofocus
                        placeholder="e.g. John Doe"
                        class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 text-sm text-slate-100 placeholder-slate-600 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition">
                </div>

                <div>
                    <label for="password" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2">Create Password</label>
                    <input type="password" id="password" name="password" required
                        placeholder="Minimum 8 characters"
                        class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 text-sm text-slate-100 placeholder-slate-600 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition">
                </div>

                <div>
                    <label for="password_confirmation" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2">Confirm Password</label>
                    <input type="password" id="password_confirmation" name="password_confirmation" required
                        placeholder="Re-type password"
                        class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 text-sm text-slate-100 placeholder-slate-600 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition">
                </div>

                <div class="p-3.5 rounded-xl bg-cyan-950/40 border border-cyan-800/50 text-xs text-cyan-300 flex items-start gap-2.5">
                    <i class="fa-solid fa-shield-halved mt-0.5 text-cyan-400"></i>
                    <span>Institutional Security Policy: Upon account activation, you will be required to configure mandatory Two-Factor Authentication (2FA).</span>
                </div>

                <button type="submit"
                    class="w-full py-3.5 px-4 rounded-xl bg-gradient-to-r from-emerald-500 to-teal-500 hover:from-emerald-400 hover:to-teal-400 font-bold text-sm text-slate-950 shadow-lg shadow-emerald-500/20 transition duration-150 flex items-center justify-center gap-2">
                    <span>Activate Account & Continue</span>
                    <i class="fa-solid fa-arrow-right"></i>
                </button>
            </form>
        </div>

        <!-- Footer -->
        <div class="text-center mt-6 text-slate-600 text-xs">
            <span>&copy; {{ date('Y') }} Q4I Financial Engine &bull; Non-transferable signed invite</span>
        </div>
    </div>

</body>
</html>

