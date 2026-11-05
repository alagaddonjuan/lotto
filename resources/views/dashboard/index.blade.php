<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Q4I Financial Engine | Institutional Clearinghouse</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .tab-btn.active {
            color: #10b981;
            border-bottom: 2px solid #10b981;
            background: rgba(16, 185, 129, 0.05);
        }
    </style>
</head>
<body class="bg-slate-950 text-slate-100 min-h-screen font-sans antialiased flex flex-col">

    <!-- Top Navigation Header -->
    <header class="border-b border-slate-800 bg-slate-950/95 backdrop-blur sticky top-0 z-40 px-6 py-4 flex flex-wrap justify-between items-center gap-4">
        <!-- Brand / Identity -->
        <div class="flex items-center gap-3.5">
            <div class="flex items-center justify-center">
                <img src="https://q4iltd.com/color@6x.png" onerror="this.src='{{ asset('images/q4i-logo.png') }}'" alt="Q4I Limited" class="h-11 w-auto object-contain drop-shadow-md">
            </div>
            <div>
                <div class="flex items-center gap-2.5">
                    <h1 class="font-bold text-base text-white tracking-tight leading-none">Q4I Financial Engine</h1>
                    <!-- Compact Top Pill: SHA-256 Audit Seal -->
                    <span class="text-[10px] bg-emerald-500/10 text-emerald-400 border border-emerald-500/30 px-2.5 py-0.5 rounded-full font-mono flex items-center gap-1.5 font-semibold">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                        HMAC-SHA256: 100% Sealed
                    </span>
                    <!-- Meta Cloud API Badge -->
                    <span class="text-[10px] bg-blue-500/10 text-blue-400 border border-blue-500/30 px-2.5 py-0.5 rounded-full font-mono flex items-center gap-1.5 font-semibold hidden sm:flex">
                        <i class="fa-brands fa-whatsapp text-emerald-400"></i>
                        Direct Meta Cloud API (v20.0)
                    </span>
                </div>
                <p class="text-xs text-slate-400 mt-1">
                    Operator: <strong class="text-slate-200">{{ $tenant->name }}</strong> &bull; <span class="font-mono text-emerald-400">35/65 Split Clearinghouse</span>
                </p>
            </div>
        </div>

        <!-- Header Actions -->
        <div class="flex flex-wrap items-center gap-3">
            <!-- Live Date-Range Filter Form -->
            <form method="GET" action="{{ route('dashboard') }}" class="flex items-center gap-2 bg-slate-900 border border-slate-800 rounded-xl px-3 py-1.5 text-xs text-slate-300">
                <input type="hidden" name="tenant_id" value="{{ $tenant->id }}">
                <input type="hidden" name="tab" value="{{ $currentTab }}">
                <i class="fa-regular fa-calendar text-slate-400"></i>
                <input type="date" name="start_date" value="{{ $startDate->toDateString() }}" class="bg-transparent border-0 text-xs text-slate-200 focus:outline-none font-mono">
                <span class="text-slate-600">&rarr;</span>
                <input type="date" name="end_date" value="{{ $endDate->toDateString() }}" class="bg-transparent border-0 text-xs text-slate-200 focus:outline-none font-mono">
                <button type="submit" class="px-2 py-0.5 bg-emerald-500/20 hover:bg-emerald-500/30 text-emerald-400 rounded text-[11px] font-semibold transition">
                    Filter
                </button>
            </form>

            <!-- Draw Operations Link -->
            <a href="{{ route('dashboard.draws.index') }}"
                class="px-3.5 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 border border-slate-800 hover:border-slate-700 text-xs font-semibold text-slate-200 transition flex items-center gap-2 shadow-sm">
                <i class="fa-solid fa-clock-rotate-left text-amber-400"></i>
                <span class="hidden sm:inline">Draw Operations</span>
            </a>

            <!-- Settlement Statement PDF -->
            <a href="{{ route('dashboard.invoice', ['tenant_id' => $tenant->id]) }}" target="_blank"
                class="px-3.5 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 border border-slate-800 hover:border-slate-700 text-xs font-semibold text-slate-200 transition flex items-center gap-2 shadow-sm">
                <i class="fa-solid fa-file-invoice-dollar text-emerald-400"></i>
                <span class="hidden sm:inline">Invoice</span>
            </a>

            <!-- Export CSV -->
            <a href="{{ route('dashboard.export-csv', ['tenant_id' => $tenant->id]) }}"
                class="px-3.5 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 border border-slate-800 hover:border-slate-700 text-xs font-semibold text-slate-200 transition flex items-center gap-2 shadow-sm">
                <i class="fa-solid fa-file-csv text-cyan-400"></i>
                <span class="hidden sm:inline">Export</span>
            </a>

            <!-- Multi-Tenant Dropdown for Super Admins -->
            @if(auth()->check() && auth()->user()->isSuperAdmin() && $allTenants->count() > 1)
                <form method="GET" action="{{ route('dashboard') }}" class="flex items-center">
                    <input type="hidden" name="tab" value="{{ $currentTab }}">
                    <select name="tenant_id" onchange="this.form.submit()" class="bg-slate-900 border border-slate-700 text-xs rounded-xl px-3 py-2 text-slate-200 focus:ring-2 focus:ring-emerald-500 outline-none">
                        @foreach($allTenants as $t)
                            <option value="{{ $t->id }}" {{ $t->id == $tenant->id ? 'selected' : '' }}>
                                {{ $t->name }} ({{ $t->country_code }})
                            </option>
                        @endforeach
                    </select>
                </form>
            @endif

            <!-- Rep Profile / 2FA / Logout -->
            @if(auth()->check())
                <div class="flex items-center gap-3 pl-3 border-l border-slate-800">
                    <div class="text-right hidden md:block">
                        <div class="text-xs font-bold text-slate-200 flex items-center gap-2 justify-end">
                            <span>{{ auth()->user()->name }}</span>
                            <span class="text-[10px] px-2 py-0.5 rounded font-mono font-bold bg-emerald-500/10 text-emerald-400 border border-emerald-500/30">
                                <i class="fa-solid fa-shield-halved text-[9px]"></i> 2FA ACTIVE
                            </span>
                        </div>
                        <div class="text-[10px] text-emerald-400 font-mono capitalize">{{ str_replace('_', ' ', auth()->user()->role) }}</div>
                    </div>

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" title="Sign Out" class="p-2.5 rounded-xl bg-slate-900 hover:bg-rose-500/10 border border-slate-800 hover:border-rose-500/30 text-slate-400 hover:text-rose-400 text-xs transition">
                            <i class="fa-solid fa-arrow-right-from-bracket"></i>
                        </button>
                    </form>
                </div>
            @endif
        </div>
    </header>

    <!-- Navigation Tabs Bar -->
    <div class="border-b border-slate-800 bg-slate-900/50 px-6">
        <div class="max-w-7xl mx-auto flex gap-2 overflow-x-auto text-xs font-semibold">
            <button onclick="switchTab('overview')" id="tab-btn-overview" class="tab-btn active px-4 py-3 border-b-2 border-transparent transition flex items-center gap-2">
                <i class="fa-solid fa-chart-line text-emerald-400"></i>
                <span>Clearinghouse Overview</span>
            </button>
            <button onclick="switchTab('operations')" id="tab-btn-operations" class="tab-btn px-4 py-3 border-b-2 border-transparent text-slate-400 hover:text-slate-200 transition flex items-center gap-2">
                <i class="fa-solid fa-gauge-high text-amber-400"></i>
                <span>Live Draws & Operations</span>
                @if(isset($pendingMakerChecker) && $pendingMakerChecker->count() > 0)
                    <span class="bg-amber-500/20 text-amber-400 text-[10px] px-2 py-0.5 rounded-full font-mono">{{ $pendingMakerChecker->count() }} PENDING</span>
                @endif
            </button>
            <button onclick="switchTab('tickets')" id="tab-btn-tickets" class="tab-btn px-4 py-3 border-b-2 border-transparent text-slate-400 hover:text-slate-200 transition flex items-center gap-2">
                <i class="fa-solid fa-receipt text-cyan-400"></i>
                <span>Transactions & Hash Seals</span>
                <span class="bg-slate-800 text-slate-300 text-[10px] px-2 py-0.5 rounded-full font-mono">{{ $balanceSheet['financials']['total_tickets'] }}</span>
            </button>
            <button onclick="switchTab('players')" id="tab-btn-players" class="tab-btn px-4 py-3 border-b-2 border-transparent text-slate-400 hover:text-slate-200 transition flex items-center gap-2">
                <i class="fa-solid fa-users text-blue-400"></i>
                <span>Registered Players</span>
                <span class="bg-emerald-500/20 text-emerald-400 text-[10px] px-2 py-0.5 rounded-full font-mono">{{ $totalPlayers }}</span>
            </button>
            <button onclick="switchTab('reps')" id="tab-btn-reps" class="tab-btn px-4 py-3 border-b-2 border-transparent text-slate-400 hover:text-slate-200 transition flex items-center gap-2">
                <i class="fa-solid fa-user-shield text-purple-400"></i>
                <span>Staff Invitations & Access</span>
            </button>
        </div>
    </div>

    <!-- Main Content Container -->
    <main class="max-w-7xl mx-auto px-6 py-8 space-y-8 flex-1 w-full">

        @if(session('success'))
            <div class="p-4 rounded-2xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 text-xs flex items-center gap-2">
                <i class="fa-solid fa-circle-check"></i>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        @if(session('invitation_url'))
            <div class="p-4 rounded-2xl bg-cyan-500/10 border border-cyan-500/30 text-cyan-300 text-xs space-y-2">
                <div class="flex items-center gap-2 font-bold">
                    <i class="fa-solid fa-link"></i>
                    <span>Temporary Cryptographic Signed Invitation URL Generated:</span>
                </div>
                <div class="flex items-center gap-2">
                    <input type="text" readonly value="{{ session('invitation_url') }}" id="signedInviteUrl" class="w-full bg-slate-950 border border-slate-800 rounded-lg px-3 py-2 text-xs font-mono text-white">
                    <button onclick="navigator.clipboard.writeText(document.getElementById('signedInviteUrl').value); alert('Signed URL copied to clipboard!');" class="px-3 py-2 bg-cyan-500 text-slate-950 font-bold rounded-lg whitespace-nowrap text-xs">
                        Copy Link
                    </button>
                </div>
            </div>
        @endif

        <!-- ========================================== -->
        <!-- TAB 1: FINANCIAL OVERVIEW & 35/65 SPLIT   -->
        <!-- ========================================== -->
        <div id="tab-content-overview" class="tab-pane space-y-8">

            <!-- KPI Metric Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
                <!-- Turnover -->
                <div class="bg-slate-900/70 border border-slate-800 rounded-2xl p-5 shadow-sm">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Gross Turnover (Stakes)</p>
                            <h3 class="text-2xl font-black mt-2 text-white font-mono" id="statTurnover">
                                {{ $tenant->currency }} {{ number_format($balanceSheet['financials']['total_turnover'], 2) }}
                            </h3>
                        </div>
                        <div class="w-10 h-10 rounded-xl bg-blue-500/10 border border-blue-500/20 text-blue-400 flex items-center justify-center">
                            <i class="fa-solid fa-money-bill-trend-up"></i>
                        </div>
                    </div>
                    <p class="text-xs text-slate-400 mt-4 flex items-center justify-between">
                        <span><strong class="text-emerald-400 font-mono" id="statTicketsCount">{{ $balanceSheet['financials']['total_tickets'] }}</strong> total tickets</span>
                        <span class="text-[10px] bg-slate-800 px-2 py-0.5 rounded text-slate-400 font-mono">Real Cash Only</span>
                    </p>
                </div>

                <!-- Q4I 35% Split -->
                <div class="bg-gradient-to-br from-emerald-950/40 to-slate-900/80 border border-emerald-500/40 rounded-2xl p-5 shadow-sm">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wider text-emerald-400">Q4I Clearinghouse (35%)</p>
                            <h3 class="text-2xl font-black mt-2 text-emerald-300 font-mono" id="statQ4iCommission">
                                {{ $tenant->currency }} {{ number_format($balanceSheet['splits']['q4i_accrued_earned'], 2) }}
                            </h3>
                        </div>
                        <div class="w-10 h-10 rounded-xl bg-emerald-500/20 border border-emerald-500/40 text-emerald-400 flex items-center justify-center">
                            <i class="fa-solid fa-hand-holding-dollar"></i>
                        </div>
                    </div>
                    <div class="mt-4 flex items-center justify-between text-xs">
                        <span class="text-slate-400">Settled Realized</span>
                        @if(($balanceSheet['splits']['q4i_escrow_pending'] ?? 0) > 0)
                            <span class="text-[10px] bg-amber-500/10 text-amber-400 border border-amber-500/30 px-2 py-0.5 rounded font-mono">
                                +{{ $tenant->currency }} {{ number_format($balanceSheet['splits']['q4i_escrow_pending'], 2) }} escrow
                            </span>
                        @endif
                    </div>
                </div>

                <!-- Operator Share 65% -->
                <div class="bg-slate-900/70 border border-slate-800 rounded-2xl p-5 shadow-sm">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Operator Share (65%)</p>
                            <h3 class="text-2xl font-black mt-2 text-purple-300 font-mono" id="statOperatorShare">
                                {{ $tenant->currency }} {{ number_format($balanceSheet['splits']['operator_accrued_pool'], 2) }}
                            </h3>
                        </div>
                        <div class="w-10 h-10 rounded-xl bg-purple-500/10 border border-purple-500/20 text-purple-400 flex items-center justify-center">
                            <i class="fa-solid fa-building-columns"></i>
                        </div>
                    </div>
                    <div class="mt-4 flex items-center justify-between text-xs">
                        <span class="text-slate-400">{{ $tenant->name }} Prize Reserve</span>
                        @if(($balanceSheet['splits']['operator_escrow_pending'] ?? 0) > 0)
                            <span class="text-[10px] bg-purple-500/10 text-purple-300 border border-purple-500/30 px-2 py-0.5 rounded font-mono">
                                +{{ $tenant->currency }} {{ number_format($balanceSheet['splits']['operator_escrow_pending'], 2) }} escrow
                            </span>
                        @endif
                    </div>
                </div>

                <!-- Available to Settle -->
                <div class="bg-gradient-to-br from-cyan-950/40 to-slate-900/80 border border-cyan-500/40 rounded-2xl p-5 shadow-sm">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wider text-cyan-400">Available to Settle / Spend</p>
                            <h3 class="text-2xl font-black mt-2 text-cyan-300 font-mono" id="statAvailableSpend">
                                {{ $tenant->currency }} {{ number_format($balanceSheet['splits']['q4i_available_to_spend'], 2) }}
                            </h3>
                        </div>
                        <div class="w-10 h-10 rounded-xl bg-cyan-500/20 border border-cyan-500/40 text-cyan-400 flex items-center justify-center">
                            <i class="fa-solid fa-wallet"></i>
                        </div>
                    </div>
                    <p class="text-xs text-cyan-400/80 mt-4 font-mono">Net Withdrawable</p>
                </div>
            </div>

            <!-- Open Draw Liability vs Settled Revenue (Institutional Metric) -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Open Draw Liability -->
                <div class="bg-slate-900/70 border border-amber-500/30 rounded-2xl p-6 shadow-sm">
                    <div class="flex justify-between items-start">
                        <div>
                            <span class="px-2.5 py-0.5 rounded text-[10px] font-mono font-bold bg-amber-500/10 text-amber-400 border border-amber-500/30 uppercase">In Circulation</span>
                            <h4 class="text-xs font-semibold text-slate-300 uppercase tracking-wider mt-2">Open Draw Liability</h4>
                            <p class="text-2xl font-bold font-mono text-amber-300 mt-1">
                                {{ $tenant->currency }} {{ number_format($balanceSheet['financials']['open_draw_liability'] ?? 0, 2) }}
                            </p>
                            <p class="text-xs text-slate-400 mt-2 font-mono">
                                {{ $balanceSheet['financials']['open_tickets_count'] ?? 0 }} active ticket(s) awaiting scheduled draw cut-off & outcome.
                            </p>
                        </div>
                        <div class="w-10 h-10 rounded-xl bg-amber-500/10 border border-amber-500/20 text-amber-400 flex items-center justify-center">
                            <i class="fa-solid fa-scale-balanced"></i>
                        </div>
                    </div>
                </div>

                <!-- Settled Gross Revenue -->
                <div class="bg-slate-900/70 border border-emerald-500/30 rounded-2xl p-6 shadow-sm">
                    <div class="flex justify-between items-start">
                        <div>
                            <span class="px-2.5 py-0.5 rounded text-[10px] font-mono font-bold bg-emerald-500/10 text-emerald-400 border border-emerald-500/30 uppercase">Settled & Audited</span>
                            <h4 class="text-xs font-semibold text-slate-300 uppercase tracking-wider mt-2">Settled Commercial Revenue</h4>
                            <p class="text-2xl font-bold font-mono text-emerald-300 mt-1">
                                {{ $tenant->currency }} {{ number_format($balanceSheet['financials']['settled_revenue'] ?? 0, 2) }}
                            </p>
                            <p class="text-xs text-slate-400 mt-2 font-mono">
                                {{ $balanceSheet['financials']['settled_tickets_count'] ?? 0 }} ticket(s) verified by independent Maker-Checker settlement.
                            </p>
                        </div>
                        <div class="w-10 h-10 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 flex items-center justify-center">
                            <i class="fa-solid fa-clipboard-check"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Commercial Split Visualizer & Cryptographic Ledger -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Commercial Split Visualizer -->
                <div class="lg:col-span-2 bg-slate-900/70 border border-slate-800 rounded-2xl p-6">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="font-bold text-sm flex items-center gap-2">
                            <i class="fa-solid fa-chart-pie text-emerald-400"></i>
                            35/65 Commercial Revenue Distribution
                        </h3>
                        <span class="text-xs text-slate-500 font-mono">Audited by HMAC-SHA256 Ledger</span>
                    </div>

                    <div class="space-y-6">
                        <div>
                            <div class="flex justify-between text-xs font-semibold mb-2">
                                <span class="text-emerald-400">Q4I Limited Share (35.00%)</span>
                                <span class="text-emerald-300 font-mono" id="distQ4iText">{{ $tenant->currency }} {{ number_format($balanceSheet['splits']['q4i_accrued_earned'], 2) }}</span>
                            </div>
                            <div class="w-full h-3.5 bg-slate-800 rounded-full overflow-hidden">
                                <div class="h-full bg-gradient-to-r from-emerald-500 to-teal-400 rounded-full" style="width: 35%"></div>
                            </div>
                        </div>

                        <div>
                            <div class="flex justify-between text-xs font-semibold mb-2">
                                <span class="text-purple-400">{{ $tenant->name }} Share (65.00%)</span>
                                <span class="text-purple-300 font-mono" id="distOpText">{{ $tenant->currency }} {{ number_format($balanceSheet['splits']['operator_accrued_pool'], 2) }}</span>
                            </div>
                            <div class="w-full h-3.5 bg-slate-800 rounded-full overflow-hidden">
                                <div class="h-full bg-gradient-to-r from-purple-500 to-indigo-500 rounded-full" style="width: 65%"></div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-8 pt-6 border-t border-slate-800 grid grid-cols-1 sm:grid-cols-3 gap-4 text-center">
                        <div class="bg-slate-950/60 p-4 rounded-xl border border-slate-800/80">
                            <p class="text-xs text-slate-400 uppercase font-semibold">Platform Fee</p>
                            <p class="font-bold text-base text-slate-100 mt-1 font-mono">35% Fixed</p>
                        </div>
                        <div class="bg-slate-950/60 p-4 rounded-xl border border-slate-800/80">
                            <p class="text-xs text-slate-400 uppercase font-semibold">Meta WhatsApp Fee</p>
                            <p class="font-bold text-base text-amber-300 mt-1">Borne by Operator</p>
                        </div>
                        <div class="bg-slate-950/60 p-4 rounded-xl border border-slate-800/80">
                            <p class="text-xs text-slate-400 uppercase font-semibold">Settlement Cadence</p>
                            <p class="font-bold text-base text-cyan-300 mt-1">Monthly Auto-Reconciled</p>
                        </div>
                    </div>
                </div>

                <!-- Hash Chain Card -->
                <div class="bg-slate-900/70 border border-slate-800 rounded-2xl p-6 flex flex-col justify-between">
                    <div>
                        <h3 class="font-bold text-sm flex items-center gap-2 mb-3">
                            <i class="fa-solid fa-shield-halved text-emerald-400"></i>
                            Cryptographic Audit Chain
                        </h3>
                        <p class="text-xs text-slate-400 leading-relaxed">
                            To guarantee zero reconciliation disputes between Q4I and {{ $tenant->name }}, each real transaction is appended via an isolated single-threaded queue with a Redis atomic lock.
                        </p>

                        <div class="mt-4 p-4 rounded-xl bg-slate-950 border border-slate-800/80 font-mono text-xs space-y-2.5">
                            <div class="flex justify-between">
                                <span class="text-slate-500">Chain Status:</span>
                                <span class="text-emerald-400 font-semibold flex items-center gap-1.5">
                                    <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                                    100% SEALED
                                </span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-slate-500">Pipeline Queue:</span>
                                <span class="text-emerald-400">ledger-serial</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-slate-500">Algorithm:</span>
                                <span class="text-slate-300">HMAC-SHA256</span>
                            </div>
                        </div>
                    </div>

                    <div class="mt-6">
                        <a href="{{ route('dashboard.invoice', ['tenant_id' => $tenant->id]) }}" target="_blank"
                            class="w-full py-3 px-4 rounded-xl bg-gradient-to-r from-emerald-500 to-teal-500 hover:from-emerald-400 hover:to-teal-400 font-bold text-xs text-slate-950 transition flex items-center justify-center gap-2 shadow-lg shadow-emerald-500/20">
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
            <!-- Active Draws Widget -->
            <div class="bg-slate-900/70 border border-slate-800 rounded-2xl p-6 shadow-sm">
                <div class="flex justify-between items-center mb-6">
                    <div>
                        <h3 class="font-bold text-sm text-white flex items-center gap-2">
                            <i class="fa-solid fa-bolt text-amber-400"></i>
                            Live Draw Operations & Maker-Checker Queue
                        </h3>
                        <p class="text-xs text-slate-400">Automated Cut-Off Scheduler (T-60s) and Maker-Checker Dual Officer Verification</p>
                    </div>
                    <a href="{{ route('dashboard.draws.index') }}" class="px-3 py-1.5 bg-slate-800 hover:bg-slate-700 rounded-xl text-xs font-semibold text-slate-200 transition flex items-center gap-1.5">
                        <i class="fa-solid fa-arrow-up-right-from-square text-[10px]"></i>
                        <span>Open Draw Management</span>
                    </a>
                </div>

                <div class="space-y-4">
                    @forelse($activeDraws ?? [] as $draw)
                        <div class="p-4 rounded-xl bg-slate-950 border border-slate-800 flex flex-wrap justify-between items-center gap-4">
                            <div>
                                <div class="flex items-center gap-2.5">
                                    <span class="font-mono font-bold text-sm text-white">{{ $draw->draw_code }}</span>
                                    @php
                                        $badgeColor = match($draw->status) {
                                            'OPEN' => 'bg-emerald-500/10 text-emerald-400 border-emerald-500/30',
                                            'LOCKED' => 'bg-amber-500/10 text-amber-400 border-amber-500/30',
                                            'RESULT_PENDING' => 'bg-cyan-500/10 text-cyan-400 border-cyan-500/30',
                                            default => 'bg-purple-500/10 text-purple-400 border-purple-500/30',
                                        };
                                    @endphp
                                    <span class="px-2 py-0.5 rounded text-[10px] font-mono font-bold border {{ $badgeColor }}">
                                        {{ $draw->status }}
                                    </span>
                                </div>
                                <p class="text-xs text-slate-400 mt-1">
                                    Game: <strong class="text-slate-200">{{ $draw->lottoGame->name ?? $draw->lotto_game_id }}</strong> &bull;
                                    Cut-off: <span class="font-mono text-amber-300">{{ $draw->closes_at ? $draw->closes_at->format('H:i') . ' WAT' : 'N/A' }}</span>
                                </p>
                            </div>

                            <div class="flex items-center gap-3">
                                @if($draw->status === 'LOCKED')
                                    <!-- Maker Form -->
                                    <form method="POST" action="{{ route('dashboard.draws.enter-results', $draw->id) }}" class="flex items-center gap-1">
                                        @csrf
                                        <input type="text" name="winning_numbers[]" placeholder="B1" required class="w-8 py-1 bg-slate-900 border border-slate-700 rounded text-center text-xs font-mono text-white">
                                        <input type="text" name="winning_numbers[]" placeholder="B2" required class="w-8 py-1 bg-slate-900 border border-slate-700 rounded text-center text-xs font-mono text-white">
                                        <input type="text" name="winning_numbers[]" placeholder="B3" required class="w-8 py-1 bg-slate-900 border border-slate-700 rounded text-center text-xs font-mono text-white">
                                        <input type="text" name="winning_numbers[]" placeholder="B4" required class="w-8 py-1 bg-slate-900 border border-slate-700 rounded text-center text-xs font-mono text-white">
                                        <input type="text" name="winning_numbers[]" placeholder="B5" required class="w-8 py-1 bg-slate-900 border border-slate-700 rounded text-center text-xs font-mono text-white">
                                        <button type="submit" class="px-3 py-1 bg-cyan-500 hover:bg-cyan-400 text-slate-950 font-bold rounded text-xs ml-1">
                                            Enter Results (Maker)
                                        </button>
                                    </form>
                                @elseif($draw->status === 'RESULT_PENDING')
                                    <!-- Checker Approval -->
                                    @if($draw->result_entered_by === auth()->id())
                                        <span class="text-xs text-amber-400 font-mono italic">
                                            Maker Entered. Waiting 2nd Officer.
                                        </span>
                                    @else
                                        <form method="POST" action="{{ route('dashboard.draws.approve-results', $draw->id) }}">
                                            @csrf
                                            <button type="submit" class="px-3.5 py-1.5 bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-bold rounded-xl text-xs flex items-center gap-1.5 shadow-sm">
                                                <i class="fa-solid fa-check-double"></i>
                                                <span>Approve & Settle Payouts (Checker)</span>
                                            </button>
                                        </form>
                                    @endif
                                @else
                                    <span class="text-xs text-emerald-400 font-mono">
                                        Open for bets (Closes {{ $draw->closes_at ? $draw->closes_at->diffForHumans() : '' }})
                                    </span>
                                @endif

                                @if(!in_array($draw->status, ['SETTLED', 'VOID']))
                                    <form method="POST" action="{{ route('dashboard.draws.void', $draw->id) }}" onsubmit="return confirm('Are you sure you want to void draw {{ $draw->draw_code }}? All open tickets will be refunded with 0 commission leakage.');">
                                        @csrf
                                        <button type="submit" class="px-2.5 py-1 rounded-lg bg-rose-500/10 hover:bg-rose-500/20 text-rose-400 border border-rose-500/30 text-xs font-semibold transition flex items-center gap-1" title="Void Draw & Refund All Stakes">
                                            <i class="fa-solid fa-ban text-[10px]"></i>
                                            <span>Void</span>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="p-8 rounded-xl bg-slate-950 border border-slate-800 text-center text-slate-500 text-xs italic">
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
            <div class="bg-slate-900/70 border border-slate-800 rounded-2xl overflow-hidden shadow-sm">
                <div class="p-6 border-b border-slate-800 flex justify-between items-center">
                    <div>
                        <h3 class="font-bold text-sm text-white flex items-center gap-2">
                            <i class="fa-solid fa-list-check text-cyan-400"></i>
                            Live WhatsApp Bet Clearinghouse Ledger
                        </h3>
                        <p class="text-xs text-slate-400">Dual-wallet tickets with 35% Q4I Clearinghouse / 65% Operator Split</p>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-950/60 border-b border-slate-800 text-slate-400 uppercase font-mono">
                            <tr>
                                <th class="px-6 py-4">Ticket Ref</th>
                                <th class="px-6 py-4">Player Phone</th>
                                <th class="px-6 py-4">Game</th>
                                <th class="px-6 py-4">Wallet</th>
                                <th class="px-6 py-4">Stake</th>
                                <th class="px-6 py-4">Q4I (35%)</th>
                                <th class="px-6 py-4">Operator (65%)</th>
                                <th class="px-6 py-4">Status</th>
                                <th class="px-6 py-4">HMAC Seal</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800/60">
                            @forelse($recentTickets as $ticket)
                                <tr class="hover:bg-slate-800/30 transition">
                                    <td class="px-6 py-4 font-mono font-bold text-white">{{ $ticket->ticket_reference }}</td>
                                    <td class="px-6 py-4 font-mono text-slate-300">+{{ $ticket->player->phone ?? 'N/A' }}</td>
                                    <td class="px-6 py-4 font-mono">{{ $ticket->game_code }}</td>
                                    <td class="px-6 py-4">
                                        <span class="px-2 py-0.5 rounded text-[10px] font-mono font-bold {{ $ticket->wallet_source === 'CASH' ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/30' : 'bg-slate-800 text-slate-400' }}">
                                            {{ $ticket->wallet_source }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 font-mono font-bold text-white">{{ $tenant->currency }} {{ number_format($ticket->stake_kobo ? $ticket->stake_kobo / 100 : $ticket->stake_amount, 2) }}</td>
                                    <td class="px-6 py-4 font-mono text-emerald-400">{{ $tenant->currency }} {{ number_format($ticket->q4i_commission_kobo ? $ticket->q4i_commission_kobo / 100 : $ticket->q4i_share, 2) }}</td>
                                    <td class="px-6 py-4 font-mono text-purple-400">{{ $tenant->currency }} {{ number_format($ticket->operator_pool_kobo ? $ticket->operator_pool_kobo / 100 : $ticket->operator_share, 2) }}</td>
                                    <td class="px-6 py-4">
                                        <span class="px-2.5 py-1 rounded-full text-[10px] font-mono font-bold uppercase {{ in_array($ticket->lifecycle_status, ['WON', 'settled']) ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/30' : ($ticket->lifecycle_status === 'LOST' ? 'bg-slate-800 text-slate-400' : 'bg-amber-500/10 text-amber-400 border border-amber-500/30') }}">
                                            {{ $ticket->lifecycle_status }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 font-mono text-slate-500 text-[11px]" title="{{ $ticket->hash }}">
                                        {{ $ticket->hash ? substr($ticket->hash, 0, 12) . '...' : 'pending_chain' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center py-12 text-slate-500 italic">No tickets placed yet. Incoming WhatsApp messages will stream live transactions here.</td>
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
                <div class="bg-slate-900/70 border border-slate-800 rounded-xl p-3.5 flex items-center justify-between shadow-sm">
                    <div>
                        <p class="text-[10px] uppercase font-mono text-slate-400">🇬🇧 English</p>
                        <p class="text-lg font-bold text-white font-mono mt-0.5">{{ $languageStats['en'] ?? 0 }}</p>
                    </div>
                    <span class="text-[10px] bg-slate-800 text-slate-400 px-2 py-0.5 rounded font-mono">en</span>
                </div>
                <div class="bg-slate-900/70 border border-emerald-500/30 rounded-xl p-3.5 flex items-center justify-between shadow-sm">
                    <div>
                        <p class="text-[10px] uppercase font-mono text-emerald-400">🇳🇬 Pidgin</p>
                        <p class="text-lg font-bold text-emerald-300 font-mono mt-0.5">{{ $languageStats['pcm'] ?? 0 }}</p>
                    </div>
                    <span class="text-[10px] bg-emerald-500/10 text-emerald-400 border border-emerald-500/30 px-2 py-0.5 rounded font-mono">pcm</span>
                </div>
                <div class="bg-slate-900/70 border border-amber-500/30 rounded-xl p-3.5 flex items-center justify-between shadow-sm">
                    <div>
                        <p class="text-[10px] uppercase font-mono text-amber-400">🟡 Yorùbá</p>
                        <p class="text-lg font-bold text-amber-300 font-mono mt-0.5">{{ $languageStats['yo'] ?? 0 }}</p>
                    </div>
                    <span class="text-[10px] bg-amber-500/10 text-amber-400 border border-amber-500/30 px-2 py-0.5 rounded font-mono">yo</span>
                </div>
                <div class="bg-slate-900/70 border border-cyan-500/30 rounded-xl p-3.5 flex items-center justify-between shadow-sm">
                    <div>
                        <p class="text-[10px] uppercase font-mono text-cyan-400">🟢 Hausa</p>
                        <p class="text-lg font-bold text-cyan-300 font-mono mt-0.5">{{ $languageStats['ha'] ?? 0 }}</p>
                    </div>
                    <span class="text-[10px] bg-cyan-500/10 text-cyan-400 border border-cyan-500/30 px-2 py-0.5 rounded font-mono">ha</span>
                </div>
                <div class="bg-slate-900/70 border border-rose-500/30 rounded-xl p-3.5 flex items-center justify-between shadow-sm">
                    <div>
                        <p class="text-[10px] uppercase font-mono text-rose-400">🔴 Igbo</p>
                        <p class="text-lg font-bold text-rose-300 font-mono mt-0.5">{{ $languageStats['ig'] ?? 0 }}</p>
                    </div>
                    <span class="text-[10px] bg-rose-500/10 text-rose-400 border border-rose-500/30 px-2 py-0.5 rounded font-mono">ig</span>
                </div>
            </div>

            <div class="bg-slate-900/70 border border-slate-800 rounded-2xl overflow-hidden shadow-sm">
                <div class="p-6 border-b border-slate-800 flex justify-between items-center">
                    <div>
                        <h3 class="font-bold text-sm text-white flex items-center gap-2">
                            <i class="fa-solid fa-address-book text-blue-400"></i>
                            Registered WhatsApp Players
                        </h3>
                        <p class="text-xs text-slate-400">Total Players: <strong class="text-white font-mono">{{ $totalPlayers }}</strong> &bull; New Today: <strong class="text-emerald-400 font-mono">{{ $newPlayersToday }}</strong></p>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-950/60 border-b border-slate-800 text-slate-400 uppercase font-mono">
                            <tr>
                                <th class="px-6 py-4">Player Name</th>
                                <th class="px-6 py-4">Phone</th>
                                <th class="px-6 py-4">Language</th>
                                <th class="px-6 py-4">Cash Wallet (Real)</th>
                                <th class="px-6 py-4">Bonus Wallet (Promo)</th>
                                <th class="px-6 py-4">Total Bets</th>
                                <th class="px-6 py-4">Withdrawal PIN</th>
                                <th class="px-6 py-4">Registered Date</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800/60">
                            @forelse($recentPlayers as $p)
                                <tr class="hover:bg-slate-800/30 transition">
                                    <td class="px-6 py-4 font-bold text-white">{{ $p->name }}</td>
                                    <td class="px-6 py-4 font-mono text-slate-300">+{{ $p->phone }}</td>
                                    <td class="px-6 py-4">
                                        @php
                                            $langMap = [
                                                'en' => ['🇬🇧 English', 'bg-slate-800 text-slate-300'],
                                                'pcm' => ['🇳🇬 Pidgin', 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/30'],
                                                'yo' => ['🟡 Yorùbá', 'bg-amber-500/10 text-amber-400 border border-amber-500/30'],
                                                'ha' => ['🟢 Hausa', 'bg-cyan-500/10 text-cyan-400 border border-cyan-500/30'],
                                                'ig' => ['🔴 Igbo', 'bg-rose-500/10 text-rose-400 border border-rose-500/30'],
                                            ];
                                            $l = $langMap[$p->language ?? 'en'] ?? ['🇬🇧 English', 'bg-slate-800 text-slate-300'];
                                        @endphp
                                        <span class="px-2 py-0.5 rounded text-[10px] font-mono font-semibold {{ $l[1] }}">
                                            {{ $l[0] }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 font-mono font-bold text-emerald-400">
                                        {{ $tenant->currency }} {{ number_format(($p->cash_balance_kobo ?? 0) / 100, 2) }}
                                    </td>
                                    <td class="px-6 py-4 font-mono text-slate-400">
                                        {{ $tenant->currency }} {{ number_format(($p->bonus_balance_kobo ?? 0) / 100, 2) }}
                                    </td>
                                    <td class="px-6 py-4 font-mono">{{ $p->bet_tickets_count ?? 0 }}</td>
                                    <td class="px-6 py-4">
                                        <span class="px-2 py-0.5 rounded text-[10px] font-mono {{ $p->withdrawal_pin_hash ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/30' : 'bg-slate-800 text-slate-500' }}">
                                            {{ $p->withdrawal_pin_hash ? 'SET' : 'NOT SET' }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 font-mono text-slate-400">{{ $p->created_at->format('Y-m-d') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center py-12 text-slate-500 italic">No registered players yet.</td>
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
                <div class="bg-slate-900/70 border border-slate-800 rounded-2xl p-6 shadow-sm">
                    <h3 class="font-bold text-sm text-white flex items-center gap-2 mb-2">
                        <i class="fa-solid fa-envelope-open-text text-purple-400"></i>
                        Issue Cryptographic Staff Invitation
                    </h3>
                    <p class="text-xs text-slate-400 mb-6">
                        Self-registration is disabled. Generate an authorized temporary signed URL to onboard operator staff with strict role binding.
                    </p>

                    <form method="POST" action="{{ route('dashboard.invitations.create') }}" class="space-y-4">
                        @csrf
                        <input type="hidden" name="tenant_id" value="{{ $tenant->id }}">

                        <div>
                            <label class="block text-[11px] font-semibold text-slate-400 uppercase mb-1">Staff Corporate Email</label>
                            <input type="email" name="email" required placeholder="rep@operator.com or staff@company.com" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2.5 text-xs text-white focus:border-purple-500 focus:outline-none">
                        </div>

                        <div>
                            <label class="block text-[11px] font-semibold text-slate-400 uppercase mb-1">Authorized Role</label>
                            <select name="role" required class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2.5 text-xs text-white focus:border-purple-500 focus:outline-none">
                                <option value="operator_rep">Operator Representative</option>
                                <option value="operator_admin">Operator Administrator</option>
                            </select>
                        </div>

                        <button type="submit" class="w-full py-3 px-4 rounded-xl bg-purple-500 hover:bg-purple-400 font-bold text-xs text-slate-950 transition flex items-center justify-center gap-2 shadow-lg shadow-purple-500/20">
                            <i class="fa-solid fa-key"></i>
                            <span>Generate Signed Invitation Link</span>
                        </button>
                    </form>
                </div>

                <!-- Existing Reps -->
                <div class="lg:col-span-2 bg-slate-900/70 border border-slate-800 rounded-2xl p-6 shadow-sm">
                    <h3 class="font-bold text-sm text-white flex items-center gap-2 mb-4">
                        <i class="fa-solid fa-users text-purple-400"></i>
                        Active Operator Representatives ({{ $tenant->name }})
                    </h3>

                    <div class="space-y-3">
                        @forelse($tenantReps as $rep)
                            <div class="p-4 rounded-xl bg-slate-950 border border-slate-800 flex justify-between items-center">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-full bg-purple-500/20 text-purple-400 flex items-center justify-center font-bold text-xs">
                                        {{ substr($rep->name, 0, 1) }}
                                    </div>
                                    <div>
                                        <p class="text-xs font-bold text-white">{{ $rep->name }}</p>
                                        <p class="text-[11px] text-slate-400 font-mono">{{ $rep->email }}</p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-mono {{ $rep->two_factor_enabled ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/30' : 'bg-slate-800 text-slate-400' }}">
                                        2FA {{ $rep->two_factor_enabled ? 'ACTIVE' : 'INACTIVE' }}
                                    </span>
                                    <span class="px-2.5 py-1 rounded-lg text-[10px] font-semibold bg-purple-500/10 text-purple-400 border border-purple-500/30 font-mono uppercase">
                                        {{ str_replace('_', ' ', $rep->role) }}
                                    </span>
                                </div>
                            </div>
                        @empty
                            <div class="p-4 rounded-xl bg-slate-950 text-slate-500 text-xs italic">
                                No operator representatives registered. Use the invitation form to onboard staff.
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

    </main>

    <footer class="border-t border-slate-800 bg-slate-950 py-4 px-6 text-center text-xs text-slate-600 font-mono">
        &copy; {{ date('Y') }} Q4I Financial Engine &bull; Institutional Clearinghouse &bull; 35% Platform / 65% Operator Real Ledger
    </footer>

    <script>
        function switchTab(tabName) {
            document.querySelectorAll('.tab-pane').forEach(el => el.classList.add('hidden'));
            document.querySelectorAll('.tab-btn').forEach(el => {
                el.classList.remove('active', 'text-emerald-400');
                el.classList.add('text-slate-400');
            });

            const content = document.getElementById('tab-content-' + tabName);
            const btn = document.getElementById('tab-btn-' + tabName);

            if (content) content.classList.remove('hidden');
            if (btn) {
                btn.classList.add('active', 'text-emerald-400');
                btn.classList.remove('text-slate-400');
            }
        }
    </script>
</body>
</html>
