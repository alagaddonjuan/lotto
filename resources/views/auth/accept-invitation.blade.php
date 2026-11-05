<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accept Staff Invitation | Q4I Financial Engine</title>
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
            <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Staff Onboarding</h1>
            <p class="text-sm text-slate-500 mt-1">Accept Authorized Operator Role Invitation</p>
        </div>

        <!-- Acceptance Card -->
        <div class="bg-white border border-slate-200/90 rounded-2xl p-8 shadow-xl shadow-slate-200/50">
            <!-- Bound Organization & Role Badge -->
            <div class="mb-6 p-4 rounded-xl bg-slate-50 border border-slate-200 space-y-2.5">
                <div class="flex justify-between items-center text-xs">
                    <span class="text-slate-500 font-medium">Assigned Operator:</span>
                    <span class="font-bold text-slate-900">{{ $tenant->name ?? 'Q4I Clearinghouse' }}</span>
                </div>
                <div class="flex justify-between items-center text-xs">
                    <span class="text-slate-500 font-medium">Assigned Role:</span>
                    <span class="px-2.5 py-0.5 rounded-md bg-blue-50 text-[#1d5ba6] border border-blue-200 font-mono font-bold text-[11px] uppercase">{{ str_replace('_', ' ', $invitation->role) }}</span>
                </div>
                <div class="flex justify-between items-center text-xs">
                    <span class="text-slate-500 font-medium">Authorized Email:</span>
                    <span class="font-mono font-semibold text-slate-700">{{ $invitation->email }}</span>
                </div>
            </div>

            @if ($errors->any())
                <div class="mb-6 p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 text-xs font-medium flex items-center gap-2">
                    <i class="fa-solid fa-circle-exclamation text-rose-600"></i>
                    <span>{{ $errors->first() }}</span>
                </div>
            @endif

            <form method="POST" action="{{ request()->fullUrl() }}" class="space-y-5">
                @csrf

                <div>
                    <label for="name" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Full Legal Name</label>
                    <input type="text" id="name" name="name" value="{{ old('name') }}" required autofocus
                        placeholder="e.g. John Doe"
                        class="w-full bg-slate-50 border border-slate-300 rounded-xl px-4 py-3 text-sm text-slate-900 placeholder-slate-400 focus:bg-white focus:outline-none focus:border-[#1d5ba6] focus:ring-2 focus:ring-[#1d5ba6]/15 transition">
                </div>

                <div>
                    <label for="password" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Create Password</label>
                    <input type="password" id="password" name="password" required
                        placeholder="Minimum 8 characters"
                        class="w-full bg-slate-50 border border-slate-300 rounded-xl px-4 py-3 text-sm text-slate-900 placeholder-slate-400 focus:bg-white focus:outline-none focus:border-[#1d5ba6] focus:ring-2 focus:ring-[#1d5ba6]/15 transition">
                </div>

                <div>
                    <label for="password_confirmation" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Confirm Password</label>
                    <input type="password" id="password_confirmation" name="password_confirmation" required
                        placeholder="Re-type password"
                        class="w-full bg-slate-50 border border-slate-300 rounded-xl px-4 py-3 text-sm text-slate-900 placeholder-slate-400 focus:bg-white focus:outline-none focus:border-[#1d5ba6] focus:ring-2 focus:ring-[#1d5ba6]/15 transition">
                </div>

                <div class="p-3.5 rounded-xl bg-blue-50 border border-blue-200 text-xs text-slate-700 flex items-start gap-2.5">
                    <i class="fa-solid fa-shield-halved mt-0.5 text-[#1d5ba6]"></i>
                    <span>Institutional Security Policy: Upon account activation, you will verify your corporate email via Two-Factor Authentication (2FA).</span>
                </div>

                <button type="submit"
                    class="w-full py-3.5 px-4 rounded-xl bg-[#1d5ba6] hover:bg-[#154682] font-bold text-sm text-white shadow-md shadow-[#1d5ba6]/20 transition duration-150 flex items-center justify-center gap-2">
                    <span>Activate Account &amp; Continue</span>
                    <i class="fa-solid fa-arrow-right text-xs"></i>
                </button>
            </form>
        </div>

        <!-- Footer -->
        <div class="text-center mt-6 text-slate-400 text-xs">
            <span>&copy; {{ date('Y') }} Q4 International &bull; Non-transferable signed invite</span>
        </div>
    </div>

</body>
</html>
