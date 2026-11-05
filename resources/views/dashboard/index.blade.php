<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Q4I Financial Engine | Institutional Clearinghouse</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@500;600;700&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .font-mono { font-family: 'JetBrains Mono', monospace; }
        .sidebar-link.active {
            background-color: #1d5ba6;
            color: #ffffff;
            box-shadow: 0 4px 12px rgba(29, 91, 166, 0.35);
        }
        .top-tab.active {
            border-bottom-color: #1d5ba6;
            color: #1d5ba6;
            font-weight: 700;
        }
    </style>
</head>
<body class="bg-slate-50 text-slate-900 min-h-screen flex antialiased">

    <!-- ========================================== -->
    <!-- LEFT ENTERPRISE SIDEBAR NAVIGATION         -->
    <!-- ========================================== -->
    <aside class="hidden lg:flex lg:w-64 xl:w-72 bg-[#0b1f3a] text-slate-300 flex-col justify-between shrink-0 border-r border-slate-800 select-none">
        <div>
            <!-- Official Q4 Logo Header on Pure White Surface -->
            <div class="bg-white px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                <a href="{{ route('dashboard') }}" class="flex items-center gap-3">
                    <img src="{{ asset('images/q4i-logo.png') }}" onerror="this.src='https://q4iltd.com/color@6x.png'" alt="Q4 International" class="h-10 w-auto object-contain">
                </a>
                <span class="text-[10px] font-mono font-bold uppercase px-2 py-0.5 rounded bg-blue-50 text-[#1d5ba6] border border-blue-200">
                    NODE v2.4
                </span>
            </div>

            <!-- Operator Context Banner -->
            <div class="px-5 py-4 border-b border-white/10 bg-[#0f294a]">
                <p class="text-[10px] font-mono uppercase tracking-widest text-blue-300">Active Clearinghouse Node</p>
                <p class="text-sm font-bold text-white truncate mt-0.5">{{ $tenant->name }}</p>
                <div class="flex items-center gap-2 mt-2">
                    <span class="inline-flex items-center gap-1 text-[10px] font-mono px-2 py-0.5 rounded bg-emerald-500/20 text-emerald-300 border border-emerald-400/30">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                        35/65 Split Active
                    </span>
                    <span class="text-[10px] font-mono text-slate-300">{{ $tenant->currency }}</span>
                </div>
            </div>

            <!-- Navigation Links -->
            <nav class="p-4 space-y-6 text-xs font-semibold">
                <div>
                    <p class="px-3 text-[10px] font-mono uppercase tracking-wider text-slate-400 mb-2">Clearinghouse Core</p>
                    <div class="space-y-1">
                        <button onclick="switchTab('overview')" id="side-btn-overview" class="sidebar-link active w-full flex items-center justify-between px-3.5 py-2.5 rounded-xl text-left transition hover:bg-white/10">
                            <span class="flex items-center gap-3">
                                <i class="fa-solid fa-chart-pie w-4 text-center"></i>
                                <span>Clearinghouse Overview</span>
                            </span>
                        </button>

                        <button onclick="switchTab('operations')" id="side-btn-operations" class="sidebar-link w-full flex items-center justify-between px-3.5 py-2.5 rounded-xl text-left transition hover:bg-white/10">
                            <span class="flex items-center gap-3">
                                <i class="fa-solid fa-bolt w-4 text-center"></i>
                                <span>Live Draws &amp; Operations</span>
                            </span>
                            @if(isset($pendingMakerChecker) && $pendingMakerChecker->count() > 0)
                                <span class="bg-amber-500 text-slate-950 text-[10px] px-2 py-0.5 rounded-full font-mono font-bold">{{ $pendingMakerChecker->count() }}</span>
                            @endif
                        </button>

                        <button onclick="switchTab('tickets')" id="side-btn-tickets" class="sidebar-link w-full flex items-center justify-between px-3.5 py-2.5 rounded-xl text-left transition hover:bg-white/10">
                            <span class="flex items-center gap-3">
                                <i class="fa-solid fa-receipt w-4 text-center"></i>
                                <span>Transactions &amp; Hash Seals</span>
                            </span>
                            <span class="bg-white/10 text-white text-[10px] px-2 py-0.5 rounded-full font-mono">{{ $balanceSheet['financials']['total_tickets'] }}</span>
                        </button>

                        <button onclick="switchTab('players')" id="side-btn-players" class="sidebar-link w-full flex items-center justify-between px-3.5 py-2.5 rounded-xl text-left transition hover:bg-white/10">
                            <span class="flex items-center gap-3">
                                <i class="fa-solid fa-users w-4 text-center"></i>
                                <span>Registered Players</span>
                            </span>
                            <span class="bg-white/10 text-white text-[10px] px-2 py-0.5 rounded-full font-mono">{{ $totalPlayers }}</span>
                        </button>

                        <button onclick="switchTab('reps')" id="side-btn-reps" class="sidebar-link w-full flex items-center justify-between px-3.5 py-2.5 rounded-xl text-left transition hover:bg-white/10">
                            <span class="flex items-center gap-3">
                                <i class="fa-solid fa-user-shield w-4 text-center"></i>
                                <span>Staff Invitations &amp; Access</span>
                            </span>
                        </button>
                    </div>
                </div>

                <div>
                    <p class="px-3 text-[10px] font-mono uppercase tracking-wider text-slate-400 mb-2">Operations &amp; Audit</p>
                    <div class="space-y-1">
                        <a href="{{ route('dashboard.draws.index') }}" class="w-full flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-slate-300 hover:bg-white/10 hover:text-white transition">
                            <i class="fa-solid fa-clock-rotate-left w-4 text-center text-amber-400"></i>
                            <span>Draw Lifecycle Console</span>
                        </a>
                        <a href="{{ route('dashboard.invoice', ['tenant_id' => $tenant->id]) }}" target="_blank" class="w-full flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-slate-300 hover:bg-white/10 hover:text-white transition">
                            <i class="fa-solid fa-file-invoice-dollar w-4 text-center text-emerald-400"></i>
                            <span>Settlement Statement (PDF)</span>
                        </a>
                        <a href="{{ route('dashboard.export-csv', ['tenant_id' => $tenant->id]) }}" class="w-full flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-slate-300 hover:bg-white/10 hover:text-white transition">
                            <i class="fa-solid fa-file-csv w-4 text-center text-blue-400"></i>
                            <span>Export Ledger (CSV)</span>
                        </a>
                    </div>
                </div>
            </nav>
        </div>

        <!-- Bottom Telemetry Status -->
        <div class="p-4 border-t border-white/10 space-y-2.5 bg-[#08172b] text-[11px]">
            <div class="flex items-center justify-between">
                <span class="text-slate-400 flex items-center gap-1.5">
                    <i class="fa-brands fa-whatsapp text-emerald-400"></i> Meta Cloud API
                </span>
                <span class="font-mono font-bold text-emerald-400">v20.0 Direct</span>
            </div>
            <div class="flex items-center justify-between">
                <span class="text-slate-400 flex items-center gap-1.5">
                    <i class="fa-solid fa-link text-blue-400"></i> Audit Chain
                </span>
                <span class="font-mono font-bold text-blue-300">HMAC-SHA256</span>
            </div>
        </div>
    </aside>

    <!-- ========================================== -->
    <!-- MAIN WORKSPACE COLUMN                      -->
    <!-- ========================================== -->
    <div class="flex-1 flex flex-col min-w-0">

        <!-- Top Enterprise Header Bar -->
        <header class="bg-white border-b border-slate-200 sticky top-0 z-30 px-6 py-3.5 flex flex-wrap justify-between items-center gap-4 shadow-sm">
            <!-- Left: Title & Mobile Logo -->
            <div class="flex items-center gap-4">
                <img src="{{ asset('images/q4i-logo.png') }}" onerror="this.src='https://q4iltd.com/color@6x.png'" alt="Q4 International" class="h-8 w-auto object-contain lg:hidden">
                <div>
                    <div class="flex items-center gap-2.5">
                        <h1 class="font-extrabold text-base text-slate-900 tracking-tight leading-none">Q4I Financial Engine</h1>
                        <span class="hidden sm:inline-flex items-center gap-1.5 text-[10px] bg-emerald-50 text-emerald-700 border border-emerald-200 px-2.5 py-0.5 rounded-full font-mono font-semibold">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                            HMAC-SHA256 Sealed
                        </span>
                        <span class="hidden md:inline-flex items-center gap-1.5 text-[10px] bg-blue-50 text-[#1d5ba6] border border-blue-200 px-2.5 py-0.5 rounded-full font-mono font-semibold">
                            <i class="fa-brands fa-whatsapp text-emerald-600"></i>
                            Meta Cloud API v20.0
                        </span>
                    </div>
                    <p class="text-xs text-slate-500 mt-1">
                        Operator Node: <strong class="text-slate-800">{{ $tenant->name }}</strong> &bull; <span class="font-mono text-[#1d5ba6] font-semibold">35% Platform / 65% Operator Split</span>
                    </p>
                </div>
            </div>

            <!-- Right: Filters, Actions & Profile -->
            <div class="flex flex-wrap items-center gap-3">
                <!-- Date-Range Filter Form -->
                <form method="GET" action="{{ route('dashboard') }}" class="flex items-center gap-2 bg-slate-50 border border-slate-200 rounded-xl px-3 py-1.5 text-xs text-slate-700">
                    <input type="hidden" name="tenant_id" value="{{ $tenant->id }}">
                    <input type="hidden" name="tab" value="{{ $currentTab }}">
                    <i class="fa-regular fa-calendar text-slate-400"></i>
                    <input type="date" name="start_date" value="{{ $startDate->toDateString() }}" class="bg-transparent border-0 text-xs text-slate-800 focus:outline-none font-mono">
                    <span class="text-slate-400">&rarr;</span>
                    <input type="date" name="end_date" value="{{ $endDate->toDateString() }}" class="bg-transparent border-0 text-xs text-slate-800 focus:outline-none font-mono">
                    <button type="submit" class="px-2.5 py-1 bg-[#1d5ba6] hover:bg-[#154682] text-white rounded-lg text-[11px] font-bold transition">
                        Filter
                    </button>
                </form>

                <!-- Quick Links -->
                <a href="{{ route('dashboard.draws.index') }}"
                    class="px-3.5 py-2 rounded-xl bg-white hover:bg-slate-50 border border-slate-200 text-xs font-bold text-slate-700 transition flex items-center gap-2 shadow-sm">
                    <i class="fa-solid fa-clock-rotate-left text-amber-600"></i>
                    <span class="hidden sm:inline">Draw Operations</span>
                </a>

                <a href="{{ route('dashboard.invoice', ['tenant_id' => $tenant->id]) }}" target="_blank"
                    class="px-3.5 py-2 rounded-xl bg-white hover:bg-slate-50 border border-slate-200 text-xs font-bold text-slate-700 transition flex items-center gap-2 shadow-sm">
                    <i class="fa-solid fa-file-invoice-dollar text-[#1d5ba6]"></i>
                    <span class="hidden sm:inline">Invoice</span>
                </a>

                <a href="{{ route('dashboard.export-csv', ['tenant_id' => $tenant->id]) }}"
                    class="px-3.5 py-2 rounded-xl bg-white hover:bg-slate-50 border border-slate-200 text-xs font-bold text-slate-700 transition flex items-center gap-2 shadow-sm">
                    <i class="fa-solid fa-file-csv text-emerald-600"></i>
                    <span class="hidden sm:inline">Export</span>
                </a>

                <!-- Multi-Tenant Dropdown for Super Admins -->
                @if(auth()->check() && auth()->user()->isSuperAdmin() && $allTenants->count() > 1)
                    <form method="GET" action="{{ route('dashboard') }}" class="flex items-center">
                        <input type="hidden" name="tab" value="{{ $currentTab }}">
                        <select name="tenant_id" onchange="this.form.submit()" class="bg-slate-50 border border-slate-300 text-xs font-semibold rounded-xl px-3 py-2 text-slate-800 focus:border-[#1d5ba6] outline-none">
                            @foreach($allTenants as $t)
                                <option value="{{ $t->id }}" {{ $t->id == $tenant->id ? 'selected' : '' }}>
                                    {{ $t->name }} ({{ $t->country_code }})
                                </option>
                            @endforeach
                        </select>
                    </form>
                @endif

                <!-- Authenticated Operator Profile & Logout -->
                @if(auth()->check())
                    <div class="flex items-center gap-3 pl-3 border-l border-slate-200">
                        <div class="text-right hidden md:block">
                            <div class="text-xs font-bold text-slate-900 flex items-center gap-2 justify-end">
                                <span>{{ auth()->user()->name }}</span>
                                <span class="text-[10px] px-2 py-0.5 rounded font-mono font-bold bg-blue-50 text-[#1d5ba6] border border-blue-200">
                                    <i class="fa-solid fa-shield-halved text-[9px]"></i> 2FA ACTIVE
                                </span>
                            </div>
                            <div class="text-[10px] text-slate-500 font-mono uppercase font-semibold">{{ str_replace('_', ' ', auth()->user()->role) }}</div>
                        </div>

                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" title="Sign Out" class="p-2.5 rounded-xl bg-slate-100 hover:bg-rose-50 border border-slate-200 hover:border-rose-200 text-slate-600 hover:text-rose-600 text-xs transition">
                                <i class="fa-solid fa-arrow-right-from-bracket"></i>
                            </button>
                        </form>
                    </div>
                @endif
            </div>
        </header>

        <!-- Horizontal Navigation Bar (Mobile & Quick Switch) -->
        <div class="border-b border-slate-200 bg-white px-6">
            <div class="max-w-7xl mx-auto flex gap-4 overflow-x-auto text-xs font-semibold text-slate-500">
                <button onclick="switchTab('overview')" id="tab-btn-overview" class="top-tab active px-3 py-3.5 border-b-2 border-transparent transition flex items-center gap-2 whitespace-nowrap">
                    <i class="fa-solid fa-chart-line"></i>
                    <span>Clearinghouse Overview</span>
                </button>
                <button onclick="switchTab('operations')" id="tab-btn-operations" class="top-tab px-3 py-3.5 border-b-2 border-transparent hover:text-slate-900 transition flex items-center gap-2 whitespace-nowrap">
                    <i class="fa-solid fa-gauge-high text-amber-600"></i>
                    <span>Live Draws &amp; Operations</span>
                    @if(isset($pendingMakerChecker) && $pendingMakerChecker->count() > 0)
                        <span class="bg-amber-100 text-amber-800 border border-amber-300 text-[10px] px-2 py-0.5 rounded-full font-mono font-bold">{{ $pendingMakerChecker->count() }} PENDING</span>
                    @endif
                </button>
                <button onclick="switchTab('tickets')" id="tab-btn-tickets" class="top-tab px-3 py-3.5 border-b-2 border-transparent hover:text-slate-900 transition flex items-center gap-2 whitespace-nowrap">
                    <i class="fa-solid fa-receipt text-[#1d5ba6]"></i>
                    <span>Transactions &amp; Hash Seals</span>
                    <span class="bg-slate-100 text-slate-700 text-[10px] px-2 py-0.5 rounded-full font-mono">{{ $balanceSheet['financials']['total_tickets'] }}</span>
                </button>
                <button onclick="switchTab('players')" id="tab-btn-players" class="top-tab px-3 py-3.5 border-b-2 border-transparent hover:text-slate-900 transition flex items-center gap-2 whitespace-nowrap">
                    <i class="fa-solid fa-users text-emerald-600"></i>
                    <span>Registered Players</span>
                    <span class="bg-emerald-50 text-emerald-700 border border-emerald-200 text-[10px] px-2 py-0.5 rounded-full font-mono">{{ $totalPlayers }}</span>
                </button>
                <button onclick="switchTab('reps')" id="tab-btn-reps" class="top-tab px-3 py-3.5 border-b-2 border-transparent hover:text-slate-900 transition flex items-center gap-2 whitespace-nowrap">
                    <i class="fa-solid fa-user-shield text-indigo-600"></i>
                    <span>Staff Invitations &amp; Access</span>
                </button>
            </div>
        </div>

        <!-- Main Workspace Content -->
        <main class="max-w-7xl mx-auto px-6 py-8 space-y-8 flex-1 w-full">

            @if(session('success'))
                <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-semibold flex items-center gap-2.5 shadow-sm">
                    <i class="fa-solid fa-circle-check text-emerald-600 text-sm"></i>
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            @if(session('invitation_url'))
                <div class="p-5 rounded-2xl bg-blue-50 border border-blue-200 text-slate-800 text-xs space-y-2.5 shadow-sm">
                    <div class="flex items-center gap-2 font-bold text-[#1d5ba6]">
                        <i class="fa-solid fa-link"></i>
                        <span>Temporary Cryptographic Signed Invitation URL Generated:</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <input type="text" readonly value="{{ session('invitation_url') }}" id="signedInviteUrl" class="w-full bg-white border border-slate-300 rounded-xl px-3.5 py-2.5 text-xs font-mono text-slate-900">
                        <button onclick="navigator.clipboard.writeText(document.getElementById('signedInviteUrl').value); alert('Signed URL copied to clipboard!');" class="px-4 py-2.5 bg-[#1d5ba6] hover:bg-[#154682] text-white font-bold rounded-xl whitespace-nowrap text-xs transition">
                            Copy Link
                        </button>
                    </div>
                </div>
            @endif

            <!-- ========================================== -->
            <!-- TAB 1: FINANCIAL OVERVIEW & 35/65 SPLIT   -->
            <!-- ========================================== -->
            <div id="tab-content-overview" class="tab-pane space-y-8">

                <!-- 4 Executive KPI Cards -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
                    <!-- Gross Turnover -->
                    <div class="bg-white border border-slate-200/90 border-t-4 border-t-slate-800 rounded-2xl p-5 shadow-sm">
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="text-xs font-bold uppercase tracking-wider text-slate-500">Gross Turnover (Stakes)</p>
                                <h3 class="text-2xl font-extrabold mt-2 text-slate-900 font-mono" id="statTurnover">
                                    {{ $tenant->currency }} {{ number_format($balanceSheet['financials']['total_turnover'], 2) }}
                                </h3>
                            </div>
                            <div class="w-10 h-10 rounded-xl bg-slate-100 text-slate-700 flex items-center justify-center">
                                <i class="fa-solid fa-money-bill-trend-up"></i>
                            </div>
                        </div>
                        <p class="text-xs text-slate-500 mt-4 flex items-center justify-between pt-3 border-t border-slate-100">
                            <span><strong class="text-slate-900 font-mono" id="statTicketsCount">{{ $balanceSheet['financials']['total_tickets'] }}</strong> total tickets</span>
                            <span class="text-[10px] bg-slate-100 px-2 py-0.5 rounded text-slate-600 font-mono font-semibold">Real Cash Only</span>
                        </p>
                    </div>

                    <!-- Q4I 35% Split -->
                    <div class="bg-white border border-slate-200/90 border-t-4 border-t-[#1d5ba6] rounded-2xl p-5 shadow-sm">
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="text-xs font-bold uppercase tracking-wider text-[#1d5ba6]">Q4I Clearinghouse (35%)</p>
                                <h3 class="text-2xl font-extrabold mt-2 text-slate-900 font-mono" id="statQ4iCommission">
                                    {{ $tenant->currency }} {{ number_format($balanceSheet['splits']['q4i_accrued_earned'], 2) }}
                                </h3>
                            </div>
                            <div class="w-10 h-10 rounded-xl bg-blue-50 text-[#1d5ba6] border border-blue-100 flex items-center justify-center">
                                <i class="fa-solid fa-hand-holding-dollar"></i>
                            </div>
                        </div>
                        <div class="mt-4 flex items-center justify-between text-xs pt-3 border-t border-slate-100">
                            <span class="text-slate-500 font-medium">Settled Realized</span>
                            @if(($balanceSheet['splits']['q4i_escrow_pending'] ?? 0) > 0)
                                <span class="text-[10px] bg-amber-50 text-amber-700 border border-amber-200 px-2 py-0.5 rounded font-mono font-bold">
                                    +{{ $tenant->currency }} {{ number_format($balanceSheet['splits']['q4i_escrow_pending'], 2) }} escrow
                                </span>
                            @else
                                <span class="text-[10px] text-emerald-600 font-mono font-semibold">100% Realized</span>
                            @endif
                        </div>
                    </div>

                    <!-- Operator Share 65% -->
                    <div class="bg-white border border-slate-200/90 border-t-4 border-t-indigo-600 rounded-2xl p-5 shadow-sm">
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="text-xs font-bold uppercase tracking-wider text-indigo-600">Operator Share (65%)</p>
                                <h3 class="text-2xl font-extrabold mt-2 text-slate-900 font-mono" id="statOperatorShare">
                                    {{ $tenant->currency }} {{ number_format($balanceSheet['splits']['operator_accrued_pool'], 2) }}
                                </h3>
                            </div>
                            <div class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-600 border border-indigo-100 flex items-center justify-center">
                                <i class="fa-solid fa-building-columns"></i>
                            </div>
                        </div>
                        <div class="mt-4 flex items-center justify-between text-xs pt-3 border-t border-slate-100">
                            <span class="text-slate-500 font-medium">{{ $tenant->name }} Reserve</span>
                            @if(($balanceSheet['splits']['operator_escrow_pending'] ?? 0) > 0)
                                <span class="text-[10px] bg-indigo-50 text-indigo-700 border border-indigo-200 px-2 py-0.5 rounded font-mono font-bold">
                                    +{{ $tenant->currency }} {{ number_format($balanceSheet['splits']['operator_escrow_pending'], 2) }} escrow
                                </span>
                            @else
                                <span class="text-[10px] text-slate-500 font-mono">Net Pool</span>
                            @endif
                        </div>
                    </div>

                    <!-- Available to Settle -->
                    <div class="bg-white border border-slate-200/90 border-t-4 border-t-emerald-600 rounded-2xl p-5 shadow-sm">
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="text-xs font-bold uppercase tracking-wider text-emerald-700">Available to Settle</p>
                                <h3 class="text-2xl font-extrabold mt-2 text-emerald-700 font-mono" id="statAvailableSpend">
                                    {{ $tenant->currency }} {{ number_format($balanceSheet['splits']['q4i_available_to_spend'], 2) }}
                                </h3>
                            </div>
                            <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 border border-emerald-100 flex items-center justify-center">
                                <i class="fa-solid fa-wallet"></i>
                            </div>
                        </div>
                        <p class="text-xs text-emerald-700 font-semibold mt-4 pt-3 border-t border-slate-100 font-mono">
                            Cleared &amp; Ready for Disbursement
                        </p>
                    </div>
                </div>

                <!-- Open Draw Liability vs Settled Revenue -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Open Draw Liability -->
                    <div class="bg-white border border-slate-200/90 rounded-2xl p-6 shadow-sm">
                        <div class="flex justify-between items-start">
                            <div>
                                <span class="px-2.5 py-0.5 rounded-md text-[10px] font-mono font-bold bg-amber-50 text-amber-700 border border-amber-200 uppercase">In Escrow Circulation</span>
                                <h4 class="text-xs font-bold text-slate-500 uppercase tracking-wider mt-2.5">Open Draw Liability</h4>
                                <p class="text-2xl font-extrabold font-mono text-slate-900 mt-1">
                                    {{ $tenant->currency }} {{ number_format($balanceSheet['financials']['open_draw_liability'] ?? 0, 2) }}
                                </p>
                                <p class="text-xs text-slate-500 mt-2">
                                    <strong class="text-slate-800 font-mono">{{ $balanceSheet['financials']['open_tickets_count'] ?? 0 }}</strong> active ticket(s) awaiting scheduled draw cut-off &amp; outcome.
                                </p>
                            </div>
                            <div class="w-11 h-11 rounded-xl bg-amber-50 border border-amber-200 text-amber-600 flex items-center justify-center">
                                <i class="fa-solid fa-scale-balanced"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Settled Gross Revenue -->
                    <div class="bg-white border border-slate-200/90 rounded-2xl p-6 shadow-sm">
                        <div class="flex justify-between items-start">
                            <div>
                                <span class="px-2.5 py-0.5 rounded-md text-[10px] font-mono font-bold bg-emerald-50 text-emerald-700 border border-emerald-200 uppercase">Settled &amp; Audited</span>
                                <h4 class="text-xs font-bold text-slate-500 uppercase tracking-wider mt-2.5">Settled Commercial Revenue</h4>
                                <p class="text-2xl font-extrabold font-mono text-slate-900 mt-1">
                                    {{ $tenant->currency }} {{ number_format($balanceSheet['financials']['settled_revenue'] ?? 0, 2) }}
                                </p>
                                <p class="text-xs text-slate-500 mt-2">
                                    <strong class="text-slate-800 font-mono">{{ $balanceSheet['financials']['settled_tickets_count'] ?? 0 }}</strong> ticket(s) verified by independent Maker-Checker settlement.
                                </p>
                            </div>
                            <div class="w-11 h-11 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-600 flex items-center justify-center">
                                <i class="fa-solid fa-clipboard-check"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Commercial Split Visualizer & Cryptographic Ledger -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Commercial Split Visualizer -->
                    <div class="lg:col-span-2 bg-white border border-slate-200/90 rounded-2xl p-6 shadow-sm">
                        <div class="flex justify-between items-center mb-6">
                            <h3 class="font-bold text-sm text-slate-900 flex items-center gap-2">
                                <i class="fa-solid fa-chart-pie text-[#1d5ba6]"></i>
                                35/65 Commercial Revenue Distribution
                            </h3>
                            <span class="text-xs text-slate-500 font-mono">Audited by HMAC-SHA256 Ledger</span>
                        </div>

                        <div class="space-y-6">
                            <div>
                                <div class="flex justify-between text-xs font-bold mb-2">
                                    <span class="text-[#1d5ba6]">Q4 International Share (35.00%)</span>
                                    <span class="text-slate-900 font-mono" id="distQ4iText">{{ $tenant->currency }} {{ number_format($balanceSheet['splits']['q4i_accrued_earned'], 2) }}</span>
                                </div>
                                <div class="w-full h-3.5 bg-slate-100 rounded-full overflow-hidden border border-slate-200">
                                    <div class="h-full bg-[#1d5ba6] rounded-full" style="width: 35%"></div>
                                </div>
                            </div>

                            <div>
                                <div class="flex justify-between text-xs font-bold mb-2">
                                    <span class="text-indigo-600">{{ $tenant->name }} Share (65.00%)</span>
                                    <span class="text-slate-900 font-mono" id="distOpText">{{ $tenant->currency }} {{ number_format($balanceSheet['splits']['operator_accrued_pool'], 2) }}</span>
                                </div>
                                <div class="w-full h-3.5 bg-slate-100 rounded-full overflow-hidden border border-slate-200">
                                    <div class="h-full bg-indigo-600 rounded-full" style="width: 65%"></div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-8 pt-6 border-t border-slate-100 grid grid-cols-1 sm:grid-cols-3 gap-4 text-center">
                            <div class="bg-slate-50 p-4 rounded-xl border border-slate-200">
                                <p class="text-[11px] text-slate-500 uppercase font-bold">Platform Clearing Fee</p>
                                <p class="font-extrabold text-base text-[#1d5ba6] mt-1 font-mono">35% Fixed</p>
                            </div>
                            <div class="bg-slate-50 p-4 rounded-xl border border-slate-200">
                                <p class="text-[11px] text-slate-500 uppercase font-bold">Meta WhatsApp Fee</p>
                                <p class="font-extrabold text-base text-slate-800 mt-1">Borne by Operator</p>
                            </div>
                            <div class="bg-slate-50 p-4 rounded-xl border border-slate-200">
                                <p class="text-[11px] text-slate-500 uppercase font-bold">Settlement Cadence</p>
                                <p class="font-extrabold text-base text-emerald-700 mt-1">Auto-Reconciled</p>
                            </div>
                        </div>
                    </div>

                    <!-- Hash Chain Card -->
                    <div class="bg-white border border-slate-200/90 rounded-2xl p-6 shadow-sm flex flex-col justify-between">
                        <div>
                            <h3 class="font-bold text-sm text-slate-900 flex items-center gap-2 mb-3">
                                <i class="fa-solid fa-shield-halved text-[#1d5ba6]"></i>
                                Cryptographic Audit Chain
                            </h3>
                            <p class="text-xs text-slate-600 leading-relaxed">
                                To guarantee zero reconciliation disputes between Q4 International and {{ $tenant->name }}, each cash transaction is appended via an isolated single-threaded queue with a Redis atomic lock.
                            </p>

                            <div class="mt-4 p-4 rounded-xl bg-slate-50 border border-slate-200 font-mono text-xs space-y-2.5">
                                <div class="flex justify-between">
                                    <span class="text-slate-500">Chain Status:</span>
                                    <span class="text-emerald-700 font-bold flex items-center gap-1.5">
                                        <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                        100% SEALED
                                    </span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-slate-500">Pipeline Queue:</span>
                                    <span class="text-[#1d5ba6] font-semibold">ledger-serial</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-slate-500">Algorithm:</span>
                                    <span class="text-slate-800 font-semibold">HMAC-SHA256</span>
                                </div>
                            </div>
                        </div>

                        <div class="mt-6">
                            <a href="{{ route('dashboard.invoice', ['tenant_id' => $tenant->id]) }}" target="_blank"
                                class="w-full py-3 px-4 rounded-xl bg-[#1d5ba6] hover:bg-[#154682] font-bold text-xs text-white transition flex items-center justify-center gap-2 shadow-md shadow-[#1d5ba6]/20">
                                <i class="fa-solid fa-file-invoice-dollar"></i>
                                <span>View Settlement Statement (PDF)</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ========================================== -->
            <!-- TAB 2: LIVE DRAWS & OPERATIONS MONITOR    -->
            <!-- ========================================== -->
            <div id="tab-content-operations" class="tab-pane hidden space-y-6">
                <div class="bg-white border border-slate-200/90 rounded-2xl p-6 shadow-sm">
                    <div class="flex justify-between items-center mb-6">
                        <div>
                            <h3 class="font-bold text-sm text-slate-900 flex items-center gap-2">
                                <i class="fa-solid fa-bolt text-amber-500"></i>
                                Live Draw Operations &amp; Maker-Checker Queue
                            </h3>
                            <p class="text-xs text-slate-500">Automated Cut-Off Scheduler (T-60s) and Maker-Checker Dual Officer Verification</p>
                        </div>
                        <a href="{{ route('dashboard.draws.index') }}" class="px-3.5 py-2 bg-[#1d5ba6] hover:bg-[#154682] rounded-xl text-xs font-bold text-white transition flex items-center gap-1.5 shadow-sm">
                            <i class="fa-solid fa-arrow-up-right-from-square text-[10px]"></i>
                            <span>Open Draw Management</span>
                        </a>
                    </div>

                    <div class="space-y-4">
                        @forelse($activeDraws ?? [] as $draw)
                            <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 flex flex-wrap justify-between items-center gap-4">
                                <div>
                                    <div class="flex items-center gap-2.5">
                                        <span class="font-mono font-bold text-sm text-slate-900">{{ $draw->draw_code }}</span>
                                        @php
                                            $badgeColor = match($draw->status) {
                                                'OPEN' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                                'LOCKED' => 'bg-amber-50 text-amber-700 border-amber-200',
                                                'RESULT_PENDING' => 'bg-blue-50 text-[#1d5ba6] border-blue-200',
                                                default => 'bg-indigo-50 text-indigo-700 border-indigo-200',
                                            };
                                        @endphp
                                        <span class="px-2.5 py-0.5 rounded-md text-[10px] font-mono font-bold border {{ $badgeColor }}">
                                            {{ $draw->status }}
                                        </span>
                                    </div>
                                    <p class="text-xs text-slate-500 mt-1">
                                        Game: <strong class="text-slate-800">{{ $draw->lottoGame->name ?? $draw->lotto_game_id }}</strong> &bull;
                                        Cut-off: <span class="font-mono text-amber-700 font-semibold">{{ $draw->closes_at ? $draw->closes_at->format('H:i') . ' WAT' : 'N/A' }}</span>
                                    </p>
                                </div>

                                <div class="flex items-center gap-3">
                                    @if($draw->status === 'LOCKED')
                                        <form method="POST" action="{{ route('dashboard.draws.enter-results', $draw->id) }}" class="flex items-center gap-1">
                                            @csrf
                                            <input type="text" name="winning_numbers[]" placeholder="B1" required class="w-9 py-1.5 bg-white border border-slate-300 rounded-lg text-center text-xs font-mono text-slate-900">
                                            <input type="text" name="winning_numbers[]" placeholder="B2" required class="w-9 py-1.5 bg-white border border-slate-300 rounded-lg text-center text-xs font-mono text-slate-900">
                                            <input type="text" name="winning_numbers[]" placeholder="B3" required class="w-9 py-1.5 bg-white border border-slate-300 rounded-lg text-center text-xs font-mono text-slate-900">
                                            <input type="text" name="winning_numbers[]" placeholder="B4" required class="w-9 py-1.5 bg-white border border-slate-300 rounded-lg text-center text-xs font-mono text-slate-900">
                                            <input type="text" name="winning_numbers[]" placeholder="B5" required class="w-9 py-1.5 bg-white border border-slate-300 rounded-lg text-center text-xs font-mono text-slate-900">
                                            <button type="submit" class="px-3 py-1.5 bg-[#1d5ba6] hover:bg-[#154682] text-white font-bold rounded-lg text-xs ml-1">
                                                Enter Results (Maker)
                                            </button>
                                        </form>
                                    @elseif($draw->status === 'RESULT_PENDING')
                                        @if($draw->result_entered_by === auth()->id())
                                            <span class="text-xs text-amber-700 font-mono italic font-semibold">
                                                Maker Entered. Waiting 2nd Officer.
                                            </span>
                                        @else
                                            <form method="POST" action="{{ route('dashboard.draws.approve-results', $draw->id) }}">
                                                @csrf
                                                <button type="submit" class="px-3.5 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl text-xs flex items-center gap-1.5 shadow-sm">
                                                    <i class="fa-solid fa-check-double"></i>
                                                    <span>Approve &amp; Settle Payouts (Checker)</span>
                                                </button>
                                            </form>
                                        @endif
                                    @else
                                        <span class="text-xs text-emerald-700 font-mono font-semibold">
                                            Open for bets (Closes {{ $draw->closes_at ? $draw->closes_at->diffForHumans() : '' }})
                                        </span>
                                    @endif

                                    @if(!in_array($draw->status, ['SETTLED', 'VOID']))
                                        <form method="POST" action="{{ route('dashboard.draws.void', $draw->id) }}" onsubmit="return confirm('Are you sure you want to void draw {{ $draw->draw_code }}? All open tickets will be refunded with 0 commission leakage.');">
                                            @csrf
                                            <button type="submit" class="px-2.5 py-1.5 rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 text-xs font-bold transition flex items-center gap-1" title="Void Draw & Refund All Stakes">
                                                <i class="fa-solid fa-ban text-[10px]"></i>
                                                <span>Void</span>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <div class="p-8 rounded-xl bg-slate-50 border border-slate-200 text-center text-slate-500 text-xs italic">
                                No active draws right now. Visit the Draw Operations page to schedule a draw.
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>

            <!-- ========================================== -->
            <!-- TAB 3: TRANSACTIONS & AUDIT CHAIN         -->
            <!-- ========================================== -->
            <div id="tab-content-tickets" class="tab-pane hidden space-y-6">
                <div class="bg-white border border-slate-200/90 rounded-2xl overflow-hidden shadow-sm">
                    <div class="p-6 border-b border-slate-200 flex justify-between items-center">
                        <div>
                            <h3 class="font-bold text-sm text-slate-900 flex items-center gap-2">
                                <i class="fa-solid fa-list-check text-[#1d5ba6]"></i>
                                Live WhatsApp Bet Clearinghouse Ledger
                            </h3>
                            <p class="text-xs text-slate-500">Dual-wallet tickets with 35% Q4I Clearinghouse / 65% Operator Split</p>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs">
                            <thead class="bg-slate-50 border-b border-slate-200 text-slate-500 uppercase font-mono">
                                <tr>
                                    <th class="px-6 py-3.5">Ticket Ref</th>
                                    <th class="px-6 py-3.5">Player Phone</th>
                                    <th class="px-6 py-3.5">Game</th>
                                    <th class="px-6 py-3.5">Wallet</th>
                                    <th class="px-6 py-3.5">Stake</th>
                                    <th class="px-6 py-3.5">Q4I (35%)</th>
                                    <th class="px-6 py-3.5">Operator (65%)</th>
                                    <th class="px-6 py-3.5">Status</th>
                                    <th class="px-6 py-3.5">HMAC Seal</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200/70">
                                @forelse($recentTickets as $ticket)
                                    <tr class="hover:bg-slate-50/80 transition">
                                        <td class="px-6 py-4 font-mono font-bold text-slate-900">{{ $ticket->ticket_reference }}</td>
                                        <td class="px-6 py-4 font-mono text-slate-600">+{{ $ticket->player->phone ?? 'N/A' }}</td>
                                        <td class="px-6 py-4 font-mono font-semibold text-slate-800">{{ $ticket->game_code }}</td>
                                        <td class="px-6 py-4">
                                            <span class="px-2 py-0.5 rounded text-[10px] font-mono font-bold {{ $ticket->wallet_source === 'CASH' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-slate-100 text-slate-600' }}">
                                                {{ $ticket->wallet_source }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 font-mono font-bold text-slate-900">{{ $tenant->currency }} {{ number_format($ticket->stake_kobo ? $ticket->stake_kobo / 100 : $ticket->stake_amount, 2) }}</td>
                                        <td class="px-6 py-4 font-mono font-semibold text-[#1d5ba6]">{{ $tenant->currency }} {{ number_format($ticket->q4i_commission_kobo ? $ticket->q4i_commission_kobo / 100 : $ticket->q4i_share, 2) }}</td>
                                        <td class="px-6 py-4 font-mono font-semibold text-indigo-600">{{ $tenant->currency }} {{ number_format($ticket->operator_pool_kobo ? $ticket->operator_pool_kobo / 100 : $ticket->operator_share, 2) }}</td>
                                        <td class="px-6 py-4">
                                            <span class="px-2.5 py-1 rounded-full text-[10px] font-mono font-bold uppercase {{ in_array($ticket->lifecycle_status, ['WON', 'settled']) ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : ($ticket->lifecycle_status === 'LOST' ? 'bg-slate-100 text-slate-600' : 'bg-amber-50 text-amber-700 border border-amber-200') }}">
                                                {{ $ticket->lifecycle_status }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 font-mono text-slate-400 text-[11px]" title="{{ $ticket->hash }}">
                                            {{ $ticket->hash ? substr($ticket->hash, 0, 12) . '...' : 'pending_chain' }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="text-center py-12 text-slate-400 italic">No tickets placed yet. Incoming WhatsApp messages will stream live transactions here.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- ========================================== -->
            <!-- TAB 4: REGISTERED PLAYERS                 -->
            <!-- ========================================== -->
            <div id="tab-content-players" class="tab-pane hidden space-y-6">

                <!-- Nigerian Language Distribution KPI -->
                <div class="grid grid-cols-2 sm:grid-cols-5 gap-3">
                    <div class="bg-white border border-slate-200 rounded-xl p-4 flex items-center justify-between shadow-sm">
                        <div>
                            <p class="text-[10px] uppercase font-mono font-bold text-slate-500">🇬🇧 English</p>
                            <p class="text-lg font-extrabold text-slate-900 font-mono mt-0.5">{{ $languageStats['en'] ?? 0 }}</p>
                        </div>
                        <span class="text-[10px] bg-slate-100 text-slate-700 px-2 py-0.5 rounded font-mono font-bold">en</span>
                    </div>
                    <div class="bg-white border border-emerald-200 rounded-xl p-4 flex items-center justify-between shadow-sm">
                        <div>
                            <p class="text-[10px] uppercase font-mono font-bold text-emerald-700">🇳🇬 Pidgin</p>
                            <p class="text-lg font-extrabold text-slate-900 font-mono mt-0.5">{{ $languageStats['pcm'] ?? 0 }}</p>
                        </div>
                        <span class="text-[10px] bg-emerald-50 text-emerald-700 border border-emerald-200 px-2 py-0.5 rounded font-mono font-bold">pcm</span>
                    </div>
                    <div class="bg-white border border-amber-200 rounded-xl p-4 flex items-center justify-between shadow-sm">
                        <div>
                            <p class="text-[10px] uppercase font-mono font-bold text-amber-700">🟡 Yorùbá</p>
                            <p class="text-lg font-extrabold text-slate-900 font-mono mt-0.5">{{ $languageStats['yo'] ?? 0 }}</p>
                        </div>
                        <span class="text-[10px] bg-amber-50 text-amber-700 border border-amber-200 px-2 py-0.5 rounded font-mono font-bold">yo</span>
                    </div>
                    <div class="bg-white border border-blue-200 rounded-xl p-4 flex items-center justify-between shadow-sm">
                        <div>
                            <p class="text-[10px] uppercase font-mono font-bold text-[#1d5ba6]">🟢 Hausa</p>
                            <p class="text-lg font-extrabold text-slate-900 font-mono mt-0.5">{{ $languageStats['ha'] ?? 0 }}</p>
                        </div>
                        <span class="text-[10px] bg-blue-50 text-[#1d5ba6] border border-blue-200 px-2 py-0.5 rounded font-mono font-bold">ha</span>
                    </div>
                    <div class="bg-white border border-rose-200 rounded-xl p-4 flex items-center justify-between shadow-sm">
                        <div>
                            <p class="text-[10px] uppercase font-mono font-bold text-rose-700">🔴 Igbo</p>
                            <p class="text-lg font-extrabold text-slate-900 font-mono mt-0.5">{{ $languageStats['ig'] ?? 0 }}</p>
                        </div>
                        <span class="text-[10px] bg-rose-50 text-rose-700 border border-rose-200 px-2 py-0.5 rounded font-mono font-bold">ig</span>
                    </div>
                </div>

                <div class="bg-white border border-slate-200/90 rounded-2xl overflow-hidden shadow-sm">
                    <div class="p-6 border-b border-slate-200 flex justify-between items-center">
                        <div>
                            <h3 class="font-bold text-sm text-slate-900 flex items-center gap-2">
                                <i class="fa-solid fa-address-book text-[#1d5ba6]"></i>
                                Registered WhatsApp Players
                            </h3>
                            <p class="text-xs text-slate-500">Total Players: <strong class="text-slate-900 font-mono">{{ $totalPlayers }}</strong> &bull; New Today: <strong class="text-emerald-700 font-mono">{{ $newPlayersToday }}</strong></p>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs">
                            <thead class="bg-slate-50 border-b border-slate-200 text-slate-500 uppercase font-mono">
                                <tr>
                                    <th class="px-6 py-3.5">Player Name</th>
                                    <th class="px-6 py-3.5">Phone</th>
                                    <th class="px-6 py-3.5">Language</th>
                                    <th class="px-6 py-3.5">Cash Wallet (Real)</th>
                                    <th class="px-6 py-3.5">Bonus Wallet (Promo)</th>
                                    <th class="px-6 py-3.5">Total Bets</th>
                                    <th class="px-6 py-3.5">Withdrawal PIN</th>
                                    <th class="px-6 py-3.5">Registered Date</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200/70">
                                @forelse($recentPlayers as $p)
                                    <tr class="hover:bg-slate-50/80 transition">
                                        <td class="px-6 py-4 font-bold text-slate-900">{{ $p->name }}</td>
                                        <td class="px-6 py-4 font-mono text-slate-600">+{{ $p->phone }}</td>
                                        <td class="px-6 py-4">
                                            @php
                                                $langMap = [
                                                    'en' => ['🇬🇧 English', 'bg-slate-100 text-slate-700'],
                                                    'pcm' => ['🇳🇬 Pidgin', 'bg-emerald-50 text-emerald-700 border border-emerald-200'],
                                                    'yo' => ['🟡 Yorùbá', 'bg-amber-50 text-amber-700 border border-amber-200'],
                                                    'ha' => ['🟢 Hausa', 'bg-blue-50 text-[#1d5ba6] border border-blue-200'],
                                                    'ig' => ['🔴 Igbo', 'bg-rose-50 text-rose-700 border border-rose-200'],
                                                ];
                                                $l = $langMap[$p->language ?? 'en'] ?? ['🇬🇧 English', 'bg-slate-100 text-slate-700'];
                                            @endphp
                                            <span class="px-2 py-0.5 rounded text-[10px] font-mono font-semibold {{ $l[1] }}">
                                                {{ $l[0] }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 font-mono font-bold text-emerald-700">
                                            {{ $tenant->currency }} {{ number_format(($p->cash_balance_kobo ?? 0) / 100, 2) }}
                                        </td>
                                        <td class="px-6 py-4 font-mono text-slate-500">
                                            {{ $tenant->currency }} {{ number_format(($p->bonus_balance_kobo ?? 0) / 100, 2) }}
                                        </td>
                                        <td class="px-6 py-4 font-mono font-bold text-slate-800">{{ $p->bet_tickets_count ?? 0 }}</td>
                                        <td class="px-6 py-4">
                                            <span class="px-2 py-0.5 rounded text-[10px] font-mono font-bold {{ $p->withdrawal_pin_hash ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-slate-100 text-slate-500' }}">
                                                {{ $p->withdrawal_pin_hash ? 'SET' : 'NOT SET' }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 font-mono text-slate-500">{{ $p->created_at->format('Y-m-d') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center py-12 text-slate-400 italic">No registered players yet.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- ========================================== -->
            <!-- TAB 5: STAFF INVITATIONS & ACCESS CONTROL -->
            <!-- ========================================== -->
            <div id="tab-content-reps" class="tab-pane hidden space-y-6">
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Invite Form -->
                    <div class="bg-white border border-slate-200/90 rounded-2xl p-6 shadow-sm">
                        <h3 class="font-bold text-sm text-slate-900 flex items-center gap-2 mb-2">
                            <i class="fa-solid fa-envelope-open-text text-[#1d5ba6]"></i>
                            Issue Cryptographic Staff Invitation
                        </h3>
                        <p class="text-xs text-slate-500 mb-6">
                            Self-registration is disabled. Generate an authorized temporary signed URL to onboard operator staff with strict role binding.
                        </p>

                        <form method="POST" action="{{ route('dashboard.invitations.create') }}" class="space-y-4">
                            @csrf
                            <input type="hidden" name="tenant_id" value="{{ $tenant->id }}">

                            <div>
                                <label class="block text-[11px] font-bold text-slate-700 uppercase mb-1.5">Staff Corporate Email</label>
                                <input type="email" name="email" required placeholder="rep@operator.com or staff@company.com" class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2.5 text-xs text-slate-900 focus:bg-white focus:border-[#1d5ba6] focus:outline-none">
                            </div>

                            <div>
                                <label class="block text-[11px] font-bold text-slate-700 uppercase mb-1.5">Authorized Role</label>
                                <select name="role" required class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3 py-2.5 text-xs text-slate-900 focus:bg-white focus:border-[#1d5ba6] focus:outline-none">
                                    <option value="operator_rep">Operator Representative</option>
                                    <option value="operator_admin">Operator Administrator</option>
                                </select>
                            </div>

                            <button type="submit" class="w-full py-3 px-4 rounded-xl bg-[#1d5ba6] hover:bg-[#154682] font-bold text-xs text-white transition flex items-center justify-center gap-2 shadow-md shadow-[#1d5ba6]/20">
                                <i class="fa-solid fa-key"></i>
                                <span>Generate Signed Invitation Link</span>
                            </button>
                        </form>
                    </div>

                    <!-- Existing Reps -->
                    <div class="lg:col-span-2 bg-white border border-slate-200/90 rounded-2xl p-6 shadow-sm">
                        <h3 class="font-bold text-sm text-slate-900 flex items-center gap-2 mb-4">
                            <i class="fa-solid fa-users text-[#1d5ba6]"></i>
                            Active Operator Representatives ({{ $tenant->name }})
                        </h3>

                        <div class="space-y-3">
                            @forelse($tenantReps as $rep)
                                <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 flex justify-between items-center">
                                    <div class="flex items-center gap-3">
                                        <div class="w-9 h-9 rounded-full bg-blue-100 text-[#1d5ba6] flex items-center justify-center font-bold text-xs">
                                            {{ substr($rep->name, 0, 1) }}
                                        </div>
                                        <div>
                                            <p class="text-xs font-bold text-slate-900">{{ $rep->name }}</p>
                                            <p class="text-[11px] text-slate-500 font-mono">{{ $rep->email }}</p>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <span class="px-2.5 py-0.5 rounded text-[10px] font-mono font-bold {{ $rep->two_factor_enabled ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-slate-200 text-slate-600' }}">
                                            2FA {{ $rep->two_factor_enabled ? 'ACTIVE' : 'INACTIVE' }}
                                        </span>
                                        <span class="px-2.5 py-1 rounded-lg text-[10px] font-bold bg-blue-50 text-[#1d5ba6] border border-blue-200 font-mono uppercase">
                                            {{ str_replace('_', ' ', $rep->role) }}
                                        </span>
                                    </div>
                                </div>
                            @empty
                                <div class="p-6 rounded-xl bg-slate-50 border border-slate-200 text-slate-500 text-xs italic text-center">
                                    No operator representatives registered. Use the invitation form to onboard staff.
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>

        </main>

        <footer class="border-t border-slate-200 bg-white py-4 px-6 text-center text-xs text-slate-500 font-mono">
            &copy; {{ date('Y') }} Q4 International Limited &bull; Q4I Financial Engine &bull; 35% Platform / 65% Operator Real Clearinghouse Ledger
        </footer>
    </div>

    <script>
        function switchTab(tabName) {
            document.querySelectorAll('.tab-pane').forEach(el => el.classList.add('hidden'));

            document.querySelectorAll('.top-tab').forEach(el => {
                el.classList.remove('active');
            });
            document.querySelectorAll('.sidebar-link').forEach(el => {
                el.classList.remove('active');
            });

            const content = document.getElementById('tab-content-' + tabName);
            const topBtn = document.getElementById('tab-btn-' + tabName);
            const sideBtn = document.getElementById('side-btn-' + tabName);

            if (content) content.classList.remove('hidden');
            if (topBtn) topBtn.classList.add('active');
            if (sideBtn) sideBtn.classList.add('active');
        }
    </script>
</body>
</html>
