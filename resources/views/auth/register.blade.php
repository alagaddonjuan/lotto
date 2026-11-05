<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Operator Registration | Q4I Financial Engine</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-slate-950 text-slate-100 min-h-screen flex items-center justify-center p-4">

    <div class="w-full max-w-lg my-8">
        <!-- Logo & Header -->
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center mb-4">
                <img src="https://q4iltd.com/color@6x.png" onerror="this.src='{{ asset('images/q4i-logo.png') }}'" alt="Q4I Limited" class="h-16 w-auto object-contain drop-shadow-2xl">
            </div>
            <h1 class="text-2xl font-black text-white tracking-tight">Operator Onboarding</h1>
            <p class="text-xs text-slate-400 mt-1">Register as an Authorized Lottery Representative or Q4I Admin</p>
        </div>

        <!-- Registration Card -->
        <div class="bg-slate-900/90 border border-slate-800 rounded-3xl p-8 shadow-2xl backdrop-blur-xl">
            @if ($errors->any())
                <div class="mb-6 p-4 rounded-xl bg-rose-500/10 border border-rose-500/30 text-rose-400 text-xs space-y-1">
                    @foreach ($errors->all() as $error)
                        <div class="flex items-center gap-2">
                            <i class="fa-solid fa-circle-exclamation"></i>
                            <span>{{ $error }}</span>
                        </div>
                    @endforeach
                </div>
            @endif

            <form method="POST" action="{{ route('register.post') }}" class="space-y-4">
                @csrf

                <div>
                    <label for="name" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">Full Legal Name</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-500 text-sm">
                            <i class="fa-solid fa-user"></i>
                        </span>
                        <input type="text" id="name" name="name" value="{{ old('name') }}" required autofocus
                            placeholder="e.g. Babatunde Adeleke"
                            class="w-full bg-slate-950 border border-slate-800 rounded-xl pl-10 pr-4 py-2.5 text-sm text-slate-100 placeholder-slate-600 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="email" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">Corporate Email</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-500 text-sm">
                                <i class="fa-solid fa-envelope"></i>
                            </span>
                            <input type="email" id="email" name="email" value="{{ old('email') }}" required
                                placeholder="rep@operator.com"
                                class="w-full bg-slate-950 border border-slate-800 rounded-xl pl-10 pr-4 py-2.5 text-sm text-slate-100 placeholder-slate-600 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition">
                        </div>
                    </div>

                    <div>
                        <label for="phone" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">Phone (WhatsApp)</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-500 text-sm">
                                <i class="fa-solid fa-phone"></i>
                            </span>
                            <input type="text" id="phone" name="phone" value="{{ old('phone') }}"
                                placeholder="+234 803 123 4567"
                                class="w-full bg-slate-950 border border-slate-800 rounded-xl pl-10 pr-4 py-2.5 text-sm text-slate-100 placeholder-slate-600 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition">
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="tenant_id" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">Lottery Operator</label>
                        <div class="relative">
                            <select id="tenant_id" name="tenant_id" required
                                class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2.5 text-sm text-slate-100 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition">
                                @foreach($tenants as $t)
                                    <option value="{{ $t->id }}" {{ old('tenant_id') == $t->id ? 'selected' : '' }}>
                                        {{ $t->name }} ({{ $t->country_code }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div>
                        <label for="role" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">Portal Role</label>
                        <div class="relative">
                            <select id="role" name="role" required
                                class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2.5 text-sm text-slate-100 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition">
                                <option value="operator_rep" {{ old('role') == 'operator_rep' ? 'selected' : '' }}>Operator Representative</option>
                                <option value="super_admin" {{ old('role') == 'super_admin' ? 'selected' : '' }}>Q4I Super Administrator</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="password" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">Password</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-500 text-sm">
                                <i class="fa-solid fa-lock"></i>
                            </span>
                            <input type="password" id="password" name="password" required minlength="8"
                                placeholder="Min 8 characters"
                                class="w-full bg-slate-950 border border-slate-800 rounded-xl pl-10 pr-4 py-2.5 text-sm text-slate-100 placeholder-slate-600 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition">
                        </div>
                    </div>

                    <div>
                        <label for="password_confirmation" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">Confirm Password</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-500 text-sm">
                                <i class="fa-solid fa-shield-check"></i>
                            </span>
                            <input type="password" id="password_confirmation" name="password_confirmation" required minlength="8"
                                placeholder="Re-enter password"
                                class="w-full bg-slate-950 border border-slate-800 rounded-xl pl-10 pr-4 py-2.5 text-sm text-slate-100 placeholder-slate-600 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition">
                        </div>
                    </div>
                </div>

                <!-- 2FA Checkbox -->
                <div class="p-3.5 rounded-xl bg-slate-950/60 border border-slate-800/80 flex items-start gap-3">
                    <input type="checkbox" id="enable_2fa" name="enable_2fa" value="1" checked
                        class="mt-0.5 rounded bg-slate-800 border-slate-700 text-emerald-500 focus:ring-emerald-500">
                    <label for="enable_2fa" class="text-xs cursor-pointer">
                        <span class="font-bold text-white flex items-center gap-1.5">
                            <i class="fa-solid fa-shield-halved text-emerald-400"></i> Enable 2FA Security (Recommended)
                        </span>
                        <p class="text-[11px] text-slate-400 mt-0.5">Require a 6-digit one-time password code on every portal login to prevent unauthorized access.</p>
                    </label>
                </div>

                <div class="pt-2">
                    <button type="submit"
                        class="w-full py-3.5 px-4 rounded-xl bg-gradient-to-r from-emerald-500 to-teal-500 hover:from-emerald-400 hover:to-teal-400 font-bold text-sm text-slate-950 shadow-lg shadow-emerald-500/20 transition duration-150 flex items-center justify-center gap-2">
                        <span>Complete Operator Registration</span>
                        <i class="fa-solid fa-arrow-right"></i>
                    </button>
                </div>
            </form>

            <div class="mt-6 text-center text-xs text-slate-400">
                Already registered with credentials?
                <a href="{{ route('login') }}" class="text-emerald-400 hover:text-emerald-300 font-bold ml-1 transition">
                    Sign In Here &rarr;
                </a>
            </div>
        </div>
    </div>

</body>
</html>

