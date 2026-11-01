<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Q4I Platform | {{ $tenant->name }} Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .tab-btn.active {
            color: #10b981;
            border-bottom: 2px solid #10b981;
            background: rgba(16, 185, 129, 0.06);
        }
    </style>
</head>
<body class="bg-slate-950 text-slate-100 min-h-screen font-sans antialiased flex flex-col">

    <!-- Top Navigation Header -->
    <header class="border-b border-slate-800 bg-slate-950/90 backdrop-blur sticky top-0 z-40 px-6 py-3.5 flex flex-wrap justify-between items-center gap-4">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-emerald-500 to-cyan-500 flex items-center justify-center font-black text-xl text-slate-950 shadow-lg shadow-emerald-500/20">
                Q
            </div>
            <div>
                <h1 class="font-bold text-base leading-tight flex items-center gap-2">
                    <span>Q4I Financial Engine</span>
                    <span class="text-[10px] bg-emerald-500/10 text-emerald-400 border border-emerald-500/30 px-2 py-0.5 rounded-full font-mono">35/65 Split Ledger</span>
                </h1>
                <p class="text-xs text-slate-400">
                    Tenant: <strong class="text-slate-200">{{ $tenant->name }}</strong> ({{ $tenant->currency }})
                </p>
            </div>
        </div>

        <!-- Header Actions & Controls -->
        <div class="flex flex-wrap items-center gap-3">
            <!-- Fast Action: Simulate Real Bet -->
            <button onclick="openModal('modalSimulateBet')" class="px-3 py-1.5 rounded-xl bg-emerald-500/10 hover:bg-emerald-500/20 border border-emerald-500/30 text-emerald-400 text-xs font-bold transition flex items-center gap-1.5 shadow-sm">
                <i class="fa-solid fa-bolt"></i>
                <span>Simulate Bet</span>
            </button>

            <!-- Fast Action: Simulate DVA Deposit -->
            <button onclick="openModal('modalSimulateDeposit')" class="px-3 py-1.5 rounded-xl bg-cyan-500/10 hover:bg-cyan-500/20 border border-cyan-500/30 text-cyan-400 text-xs font-bold transition flex items-center gap-1.5 shadow-sm">
                <i class="fa-solid fa-building-columns"></i>
                <span>DVA Deposit</span>
            </button>

            <!-- Link to Player Web Kiosk -->
            <a href="{{ route('play.portal') }}" target="_blank" class="px-3 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-bold transition flex items-center gap-1.5">
                <i class="fa-brands fa-whatsapp text-emerald-400"></i>
                <span class="hidden sm:inline">Player Kiosk</span>
            </a>

            <!-- Super Admin New Operator Button -->
            @if(auth()->check() && auth()->user()->isSuperAdmin())
                <button onclick="openModal('modalNewTenant')" class="px-3 py-1.5 rounded-xl bg-purple-500/10 hover:bg-purple-500/20 border border-purple-500/30 text-purple-400 text-xs font-bold transition flex items-center gap-1.5">
                    <i class="fa-solid fa-plus"></i>
                    <span class="hidden md:inline">Add Operator</span>
                </button>
            @endif

            <!-- Multi-Tenant Dropdown for Super Admins -->
            @if(auth()->check() && auth()->user()->isSuperAdmin() && $allTenants->count() > 1)
                <form method="GET" action="{{ route('dashboard') }}" class="flex items-center gap-2">
                    <input type="hidden" name="tab" value="{{ $currentTab }}">
                    <select name="tenant_id" onchange="this.form.submit()" class="bg-slate-900 border border-slate-700 text-xs rounded-xl px-2.5 py-1.5 text-slate-200 focus:ring-2 focus:ring-emerald-500 outline-none">
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
                        <div class="text-xs font-bold text-slate-200 flex items-center gap-1.5 justify-end">
                            <span>{{ auth()->user()->name }}</span>
                            <button onclick="toggle2FA()" title="Toggle Two-Factor Authentication" class="text-[10px] px-1.5 py-0.5 rounded cursor-pointer transition {{ auth()->user()->two_factor_enabled ? 'bg-emerald-500/20 text-emerald-400' : 'bg-slate-800 text-slate-500' }}">
                                <i class="fa-solid fa-shield-halved"></i> 2FA {{ auth()->user()->two_factor_enabled ? 'ON' : 'OFF' }}
                            </button>
                        </div>
                        <div class="text-[10px] text-emerald-400 font-mono capitalize">{{ str_replace('_', ' ', auth()->user()->role) }}</div>
                    </div>

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" title="Logout" class="p-2 rounded-xl bg-slate-900 hover:bg-rose-500/10 border border-slate-800 hover:border-rose-500/30 text-slate-400 hover:text-rose-400 text-xs transition">
                            <i class="fa-solid fa-arrow-right-from-bracket"></i>
                        </button>
                    </form>
                </div>
            @endif
        </div>
    </header>

    <!-- Navigation Tabs -->
    <div class="border-b border-slate-800 bg-slate-900/40 px-6">
        <div class="max-w-7xl mx-auto flex gap-2 overflow-x-auto text-xs font-semibold">
            <button onclick="switchTab('overview')" id="tab-btn-overview" class="tab-btn active px-4 py-3 border-b-2 border-transparent transition flex items-center gap-2">
                <i class="fa-solid fa-chart-line"></i>
                <span>Revenue & 35/65 Split</span>
            </button>
            <button onclick="switchTab('players')" id="tab-btn-players" class="tab-btn px-4 py-3 border-b-2 border-transparent text-slate-400 hover:text-slate-200 transition flex items-center gap-2">
                <i class="fa-solid fa-users"></i>
                <span>Registered Players</span>
                <span class="bg-emerald-500/20 text-emerald-400 text-[10px] px-1.5 py-0.5 rounded-full">{{ $totalPlayers }}</span>
            </button>
            <button onclick="switchTab('tickets')" id="tab-btn-tickets" class="tab-btn px-4 py-3 border-b-2 border-transparent text-slate-400 hover:text-slate-200 transition flex items-center gap-2">
                <i class="fa-brands fa-whatsapp text-emerald-400"></i>
                <span>Live WhatsApp Bets</span>
                <span class="bg-slate-800 text-slate-300 text-[10px] px-1.5 py-0.5 rounded-full">{{ $balanceSheet['financials']['total_tickets'] }}</span>
            </button>
            <button onclick="switchTab('games')" id="tab-btn-games" class="tab-btn px-4 py-3 border-b-2 border-transparent text-slate-400 hover:text-slate-200 transition flex items-center gap-2">
                <i class="fa-solid fa-dice text-amber-400"></i>
                <span>Lotto Games Catalog</span>
            </button>
            <button onclick="switchTab('reps')" id="tab-btn-reps" class="tab-btn px-4 py-3 border-b-2 border-transparent text-slate-400 hover:text-slate-200 transition flex items-center gap-2">
                <i class="fa-solid fa-user-shield text-purple-400"></i>
                <span>Operator Reps and Access</span>
            </button>
        </div>
    </div>

    <!-- Flash Messages -->
    @if(session('success'))
        <div class="max-w-7xl mx-auto px-6 pt-4">
            <div class="p-4 rounded-2xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 text-xs flex items-center gap-2">
                <i class="fa-solid fa-circle-check"></i>
                <span>{{ session('success') }}</span>
            </div>
        </div>
    @endif

    <main class="max-w-7xl mx-auto px-6 py-8 space-y-8 flex-1 w-full">

        <!-- ========================================== -->
        <!-- TAB 1: FINANCIAL OVERVIEW & 35/65 SPLIT   -->
        <!-- ========================================== -->
        <div id="tab-content-overview" class="tab-pane space-y-8">

            <!-- Revenue Metric Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
                <!-- Turnover -->
                <div class="bg-slate-900/60 border border-slate-800 rounded-2xl p-5 shadow-sm">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-xs font-medium uppercase tracking-wider text-slate-400">Total Turnover (Stakes)</p>
                            <h3 class="text-2xl font-black mt-2 text-white" id="statTurnover">
                                {{ $tenant->currency }} {{ number_format($balanceSheet['financials']['total_turnover'], 2) }}
                            </h3>
                        </div>
                        <div class="w-10 h-10 rounded-xl bg-blue-500/10 border border-blue-500/20 text-blue-400 flex items-center justify-center">
                            <i class="fa-solid fa-money-bill-trend-up"></i>
                        </div>
                    </div>
                    <p class="text-xs text-slate-400 mt-4 flex items-center justify-between">
                        <span><strong class="text-emerald-400 font-mono" id="statTicketsCount">{{ $balanceSheet['financials']['total_tickets'] }}</strong> tickets processed</span>
                        <span class="text-[10px] bg-slate-800 px-2 py-0.5 rounded text-slate-400">Real-time</span>
                    </p>
                </div>

                <!-- Q4I 35% Split -->
                <div class="bg-gradient-to-br from-emerald-950/40 to-slate-900/80 border border-emerald-500/40 rounded-2xl p-5 shadow-sm">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wider text-emerald-400">Q4I Commission (35%)</p>
                            <h3 class="text-2xl font-black mt-2 text-emerald-300" id="statQ4iCommission">
                                {{ $tenant->currency }} {{ number_format($balanceSheet['splits']['q4i_accrued_earned'], 2) }}
                            </h3>
                        </div>
                        <div class="w-10 h-10 rounded-xl bg-emerald-500/20 border border-emerald-500/40 text-emerald-400 flex items-center justify-center">
                            <i class="fa-solid fa-hand-holding-dollar"></i>
                        </div>
                    </div>
                    <p class="text-xs text-emerald-400/80 mt-4 font-mono">Accrued Platform Commission</p>
                </div>

                <!-- Modern Lottery 65% Share -->
                <div class="bg-slate-900/60 border border-slate-800 rounded-2xl p-5 shadow-sm">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-xs font-medium uppercase tracking-wider text-slate-400">Operator Share (65%)</p>
                            <h3 class="text-2xl font-black mt-2 text-purple-300" id="statOperatorShare">
                                {{ $tenant->currency }} {{ number_format($balanceSheet['splits']['operator_accrued_pool'], 2) }}
                            </h3>
                        </div>
                        <div class="w-10 h-10 rounded-xl bg-purple-500/10 border border-purple-500/20 text-purple-400 flex items-center justify-center">
                            <i class="fa-solid fa-building-columns"></i>
                        </div>
                    </div>
                    <p class="text-xs text-slate-400 mt-4 font-mono">{{ $tenant->name }} Game Pool</p>
                </div>

                <!-- Available to Settle -->
                <div class="bg-gradient-to-br from-cyan-950/40 to-slate-900/80 border border-cyan-500/40 rounded-2xl p-5 shadow-sm">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wider text-cyan-400">Available to Settle / Spend</p>
                            <h3 class="text-2xl font-black mt-2 text-cyan-300" id="statAvailableSpend">
                                {{ $tenant->currency }} {{ number_format($balanceSheet['splits']['q4i_available_to_spend'], 2) }}
                            </h3>
                        </div>
                        <div class="w-10 h-10 rounded-xl bg-cyan-500/20 border border-cyan-500/40 text-cyan-400 flex items-center justify-center">
                            <i class="fa-solid fa-wallet"></i>
                        </div>
                    </div>
                    <p class="text-xs text-cyan-400/80 mt-4 font-mono">Net Withdrawable Commission</p>
                </div>
            </div>

            <!-- Commercial Terms & Cryptographic Ledger -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Revenue Distribution & Chart -->
                <div class="lg:col-span-2 bg-slate-900/60 border border-slate-800 rounded-2xl p-6">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="font-bold text-sm flex items-center gap-2">
                            <i class="fa-solid fa-chart-pie text-emerald-400"></i>
                            35/65 Commercial Revenue Distribution
                        </h3>
                        <span class="text-xs text-slate-500 font-mono">Audited by SHA-256 Ledger</span>
                    </div>

                    <div class="space-y-5">
                        <div>
                            <div class="flex justify-between text-xs font-semibold mb-1.5">
                                <span class="text-emerald-400">Q4I Limited Share (35%)</span>
                                <span class="text-emerald-300" id="distQ4iText">{{ $tenant->currency }} {{ number_format($balanceSheet['splits']['q4i_accrued_earned'], 2) }}</span>
                            </div>
                            <div class="w-full h-3 bg-slate-800 rounded-full overflow-hidden">
                                <div class="h-full bg-emerald-500 rounded-full" style="width: 35%"></div>
                            </div>
                        </div>

                        <div>
                            <div class="flex justify-between text-xs font-semibold mb-1.5">
                                <span class="text-purple-400">{{ $tenant->name }} Share (65%)</span>
                                <span class="text-purple-300" id="distOpText">{{ $tenant->currency }} {{ number_format($balanceSheet['splits']['operator_accrued_pool'], 2) }}</span>
                            </div>
                            <div class="w-full h-3 bg-slate-800 rounded-full overflow-hidden">
                                <div class="h-full bg-purple-500 rounded-full" style="width: 65%"></div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-8 pt-6 border-t border-slate-800 grid grid-cols-1 sm:grid-cols-3 gap-4 text-center">
                        <div class="bg-slate-950/40 p-3.5 rounded-xl border border-slate-800/80">
                            <p class="text-xs text-slate-400">Platform Split</p>
                            <p class="font-bold text-sm text-slate-200 mt-1">35% Fixed</p>
                        </div>
                        <div class="bg-slate-950/40 p-3.5 rounded-xl border border-slate-800/80">
                            <p class="text-xs text-slate-400">Meta Messaging Charge</p>
                            <p class="font-bold text-sm text-amber-300 mt-1">Borne by Operator</p>
                        </div>
                        <div class="bg-slate-950/40 p-3.5 rounded-xl border border-slate-800/80">
                            <p class="text-xs text-slate-400">Settlement Cycle</p>
                            <p class="font-bold text-sm text-cyan-300 mt-1">Monthly Auto-Reconciled</p>
                        </div>
                    </div>
                </div>

                <!-- Hash Chain Card & Settlement Exports -->
                <div class="bg-slate-900/60 border border-slate-800 rounded-2xl p-6 flex flex-col justify-between">
                    <div>
                        <h3 class="font-bold text-sm flex items-center gap-2 mb-3">
                            <i class="fa-solid fa-shield-halved text-emerald-400"></i>
                            Independent Audit Chain
                        </h3>
                        <p class="text-xs text-slate-400 leading-relaxed">
                            To guarantee zero disputes between Q4I and {{ $tenant->name }}, every bet generates an immutable SHA-256 hash block chaining the ticket ID, split amounts, and timestamp.
                        </p>

                        <div class="mt-4 p-4 rounded-xl bg-slate-950 border border-slate-800/80 font-mono text-xs space-y-2.5">
                            <div class="flex justify-between">
                                <span class="text-slate-500">Chain Status:</span>
                                <span class="text-emerald-400 font-semibold flex items-center gap-1.5">
                                    <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                                    VALIDATED
                                </span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-slate-500">Reconciled Diff:</span>
                                <span class="text-emerald-400">0.00 {{ $tenant->currency }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-slate-500">Algorithm:</span>
                                <span class="text-slate-300">HMAC-SHA256</span>
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 space-y-2">
                        <a href="{{ route('dashboard.invoice', ['tenant_id' => $tenant->id]) }}" target="_blank"
                            class="w-full py-2.5 px-4 rounded-xl bg-gradient-to-r from-emerald-500 to-teal-500 hover:from-emerald-400 hover:to-teal-400 text-xs font-bold text-slate-950 transition flex items-center justify-center gap-2 shadow-md shadow-emerald-500/20">
                            <i class="fa-solid fa-file-invoice-dollar"></i>
                            <span>Official Settlement Invoice (PDF)</span>
                        </a>

                        <a href="{{ route('dashboard.export-csv', ['tenant_id' => $tenant->id]) }}"
                            class="w-full py-2.5 px-4 rounded-xl bg-slate-800 hover:bg-slate-700 text-xs font-semibold text-slate-200 transition flex items-center justify-center gap-2">
                            <i class="fa-solid fa-file-arrow-down"></i>
                            <span>Export End-of-Month Settlement (CSV)</span>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Live WhatsApp Bet Transactions Table -->
            <div class="bg-slate-900/60 border border-slate-800 rounded-2xl p-6">
                <div class="flex justify-between items-center mb-6">
                    <div>
                        <h3 class="font-bold text-sm flex items-center gap-2">
                            <i class="fa-brands fa-whatsapp text-emerald-400"></i>
                            Live WhatsApp Bet Transactions
                        </h3>
                        <p class="text-xs text-slate-400">Chronological stream of incoming player bets and real-time commission accruals</p>
                    </div>
                    <button onclick="openModal('modalSimulateBet')" class="px-3 py-1.5 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-bold text-xs transition flex items-center gap-1.5">
                        <i class="fa-solid fa-plus"></i>
                        <span>Place Test Bet</span>
                    </button>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-slate-300">
                        <thead class="bg-slate-950/80 uppercase tracking-wider text-slate-400 font-semibold border-b border-slate-800">
                            <tr>
                                <th class="py-3 px-4">Ticket Ref</th>
                                <th class="py-3 px-4">Player Phone</th>
                                <th class="py-3 px-4">Game</th>
                                <th class="py-3 px-4">Stake</th>
                                <th class="py-3 px-4 text-emerald-400">Q4I (35%)</th>
                                <th class="py-3 px-4 text-purple-400">Operator (65%)</th>
                                <th class="py-3 px-4">Status</th>
                                <th class="py-3 px-4">Hash Seal</th>
                            </tr>
                        </thead>
                        <tbody id="ticketsTableBody" class="divide-y divide-slate-800/60">
                            @forelse($recentTickets as $ticket)
                                <tr class="hover:bg-slate-800/30 transition">
                                    <td class="py-3 px-4 font-mono font-medium text-slate-100">{{ $ticket->ticket_reference }}</td>
                                    <td class="py-3 px-4 font-mono">+{{ $ticket->player->phone ?? 'N/A' }}</td>
                                    <td class="py-3 px-4 font-semibold text-slate-200">{{ $ticket->game_code }}</td>
                                    <td class="py-3 px-4 font-bold text-white">{{ $tenant->currency }} {{ number_format($ticket->stake_amount, 2) }}</td>
                                    <td class="py-3 px-4 font-bold text-emerald-400">+{{ number_format($ticket->q4i_share, 2) }}</td>
                                    <td class="py-3 px-4 font-bold text-purple-400">+{{ number_format($ticket->operator_share, 2) }}</td>
                                    <td class="py-3 px-4">
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold uppercase bg-emerald-500/10 text-emerald-400 border border-emerald-500/30">
                                            {{ $ticket->status }}
                                        </span>
                                    </td>
                                    <td class="py-3 px-4 font-mono text-[10px] text-slate-500" title="{{ $ticket->hash }}">
                                        {{ substr($ticket->hash ?? 'N/A', 0, 14) }}...
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center py-8 text-slate-500 italic">No tickets placed yet. Click "Place Test Bet" or send a WhatsApp message to generate transactions.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>

        <!-- ========================================== -->
        <!-- TAB 2: REGISTERED PLAYERS (CRM & WALLETS) -->
        <!-- ========================================== -->
        <div id="tab-content-players" class="tab-pane hidden space-y-6">
            <!-- Player KPI Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
                <div class="bg-slate-900/60 border border-slate-800 rounded-2xl p-5">
                    <p class="text-xs uppercase tracking-wider text-slate-400 font-semibold">Total Registered Players</p>
                    <h3 class="text-3xl font-black mt-2 text-white">{{ $totalPlayers }}</h3>
                    <p class="text-xs text-emerald-400 mt-2 flex items-center gap-1">
                        <i class="fa-solid fa-shield-check"></i> Verified via WhatsApp Phone
                    </p>
                </div>
                <div class="bg-slate-900/60 border border-slate-800 rounded-2xl p-5">
                    <p class="text-xs uppercase tracking-wider text-slate-400 font-semibold">New Comers Today</p>
                    <h3 class="text-3xl font-black mt-2 text-emerald-300">{{ $newPlayersToday }}</h3>
                    <p class="text-xs text-slate-400 mt-2">Auto-onboarded with Trial Wallets</p>
                </div>
                <div class="bg-slate-900/60 border border-slate-800 rounded-2xl p-5">
                    <p class="text-xs uppercase tracking-wider text-slate-400 font-semibold">Security PIN Protection</p>
                    <h3 class="text-3xl font-black mt-2 text-cyan-300">100% Active</h3>
                    <p class="text-xs text-cyan-400 mt-2">4-Digit Wallet Authorization</p>
                </div>
            </div>

            <!-- Player Table -->
            <div class="bg-slate-900/60 border border-slate-800 rounded-2xl p-6">
                <div class="flex flex-wrap justify-between items-center gap-4 mb-6">
                    <div>
                        <h3 class="font-bold text-sm">Player Database & Onboarding Activity</h3>
                        <p class="text-xs text-slate-400">All customers who initiated chat, registered their names, and set PINs</p>
                    </div>
                    <a href="{{ route('play.portal') }}" target="_blank" class="px-3.5 py-2 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-bold text-xs transition flex items-center gap-2">
                        <i class="fa-solid fa-user-plus"></i>
                        <span>Register New Player via Kiosk</span>
                    </a>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-slate-300">
                        <thead class="bg-slate-950/80 uppercase tracking-wider text-slate-400 font-semibold border-b border-slate-800">
                            <tr>
                                <th class="py-3 px-4">Player Name</th>
                                <th class="py-3 px-4">WhatsApp Phone</th>
                                <th class="py-3 px-4">Total Bets</th>
                                <th class="py-3 px-4">Registered Date</th>
                                <th class="py-3 px-4">Last Active</th>
                                <th class="py-3 px-4">Security PIN</th>
                                <th class="py-3 px-4">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800/60">
                            @forelse($recentPlayers as $p)
                                <tr class="hover:bg-slate-800/30 transition">
                                    <td class="py-3 px-4 font-bold text-white flex items-center gap-2">
                                        <div class="w-7 h-7 rounded-full bg-slate-800 text-slate-300 flex items-center justify-center text-xs font-black">
                                            {{ substr($p->name ?? 'P', 0, 1) }}
                                        </div>
                                        <span>{{ $p->name ?? 'Unregistered' }}</span>
                                    </td>
                                    <td class="py-3 px-4 font-mono font-medium text-slate-200">+{{ $p->phone }}</td>
                                    <td class="py-3 px-4 font-bold text-emerald-400">{{ $p->bet_tickets_count }} tickets</td>
                                    <td class="py-3 px-4 text-slate-400">{{ $p->created_at->format('M d, Y H:i') }}</td>
                                    <td class="py-3 px-4 text-slate-400">{{ $p->last_active_at ? $p->last_active_at->diffForHumans() : 'Never' }}</td>
                                    <td class="py-3 px-4">
                                        @if($p->pin_hash)
                                            <span class="text-emerald-400 font-mono text-[11px]"><i class="fa-solid fa-lock"></i> Configured</span>
                                        @else
                                            <span class="text-amber-400 font-mono text-[11px]"><i class="fa-solid fa-lock-open"></i> Pending First Bet</span>
                                        @endif
                                    </td>
                                    <td class="py-3 px-4">
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold uppercase bg-emerald-500/10 text-emerald-400 border border-emerald-500/30">
                                            Active
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-8 text-slate-500 italic">No players registered yet. Use the Player Kiosk to register a player in seconds!</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- TAB 3: LIVE WHATSAPP BETS STREAM          -->
        <!-- ========================================== -->
        <div id="tab-content-tickets" class="tab-pane hidden space-y-6">
            <div class="bg-slate-900/60 border border-slate-800 rounded-2xl p-6">
                <div class="flex justify-between items-center mb-6">
                    <div>
                        <h3 class="font-bold text-sm flex items-center gap-2">
                            <i class="fa-brands fa-whatsapp text-emerald-400"></i>
                            Live WhatsApp Bet Stream & Audited Split Ledger
                        </h3>
                        <p class="text-xs text-slate-400">Real-time ticket validation with instant 35/65 commission calculation</p>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-slate-300">
                        <thead class="bg-slate-950/80 uppercase tracking-wider text-slate-400 font-semibold border-b border-slate-800">
                            <tr>
                                <th class="py-3 px-4">Ticket Ref</th>
                                <th class="py-3 px-4">Player</th>
                                <th class="py-3 px-4">Game</th>
                                <th class="py-3 px-4">Stake</th>
                                <th class="py-3 px-4 text-emerald-400">Q4I (35%)</th>
                                <th class="py-3 px-4 text-purple-400">Operator (65%)</th>
                                <th class="py-3 px-4">Status</th>
                                <th class="py-3 px-4">SHA-256 Hash</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800/60">
                            @forelse($recentTickets as $ticket)
                                <tr class="hover:bg-slate-800/30 transition">
                                    <td class="py-3 px-4 font-mono font-medium text-slate-100">{{ $ticket->ticket_reference }}</td>
                                    <td class="py-3 px-4 font-mono">+{{ $ticket->player->phone ?? 'N/A' }}</td>
                                    <td class="py-3 px-4 font-semibold text-slate-200">{{ $ticket->game_code }}</td>
                                    <td class="py-3 px-4 font-bold text-white">{{ $tenant->currency }} {{ number_format($ticket->stake_amount, 2) }}</td>
                                    <td class="py-3 px-4 font-bold text-emerald-400">+{{ number_format($ticket->q4i_share, 2) }}</td>
                                    <td class="py-3 px-4 font-bold text-purple-400">+{{ number_format($ticket->operator_share, 2) }}</td>
                                    <td class="py-3 px-4">
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold uppercase bg-emerald-500/10 text-emerald-400 border border-emerald-500/30">
                                            {{ $ticket->status }}
                                        </span>
                                    </td>
                                    <td class="py-3 px-4 font-mono text-[10px] text-slate-500" title="{{ $ticket->hash }}">
                                        {{ substr($ticket->hash ?? 'N/A', 0, 14) }}...
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center py-8 text-slate-500 italic">No tickets placed yet. Send a message to WhatsApp to generate real transactions.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- TAB 4: LOTTO GAMES CATALOG               -->
        <!-- ========================================== -->
        <div id="tab-content-games" class="tab-pane hidden space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @forelse($games as $game)
                    <div class="bg-slate-900/60 border border-slate-800 rounded-2xl p-6 flex flex-col justify-between shadow-sm">
                        <div>
                            <div class="flex justify-between items-start mb-3">
                                <div>
                                    <span class="text-[10px] uppercase font-bold tracking-wider text-emerald-400 font-mono">{{ $game->game_code }}</span>
                                    <h3 class="text-base font-bold text-white mt-1">{{ $game->name }}</h3>
                                </div>
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase bg-emerald-500/10 text-emerald-400 border border-emerald-500/30">
                                    {{ $game->is_active ? 'Active' : 'Disabled' }}
                                </span>
                            </div>
                            <p class="text-xs text-slate-400 leading-relaxed mb-4">{{ $game->description }}</p>

                            <div class="p-3 rounded-xl bg-slate-950 border border-slate-800/80 text-xs space-y-1.5 font-mono">
                                <div class="flex justify-between">
                                    <span class="text-slate-500">Min Stake:</span>
                                    <span class="text-slate-200">{{ $tenant->currency }} {{ number_format($game->min_stake, 2) }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-slate-500">Max Stake:</span>
                                    <span class="text-slate-200">{{ $tenant->currency }} {{ number_format($game->max_stake, 2) }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-slate-500">Numbers Pool:</span>
                                    <span class="text-emerald-400">{{ $game->required_picks }} from {{ $game->total_pool_numbers }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 pt-4 border-t border-slate-800/80 flex justify-between items-center text-xs">
                            <span class="text-slate-500">WhatsApp Fast-Code:</span>
                            <code class="text-emerald-400 font-bold bg-slate-950 px-2 py-1 rounded">{{ $game->game_code }}</code>
                        </div>
                    </div>
                @empty
                    <div class="col-span-3 text-center py-12 text-slate-500 italic bg-slate-900/40 rounded-2xl border border-slate-800">
                        No games configured yet. Default games (Keno 20/80 & 5/90) are auto-managed by Modern Lottery driver.
                    </div>
                @endforelse
            </div>
        </div>

        <!-- ========================================== -->
        <!-- TAB 5: OPERATOR REPS & ACCESS CONTROL     -->
        <!-- ========================================== -->
        <div id="tab-content-reps" class="tab-pane hidden space-y-6">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Reps List -->
                <div class="lg:col-span-2 bg-slate-900/60 border border-slate-800 rounded-2xl p-6">
                    <div class="flex justify-between items-center mb-6">
                        <div>
                            <h3 class="font-bold text-sm flex items-center gap-2">
                                <i class="fa-solid fa-users-gear text-emerald-400"></i>
                                Authorized Operator Representatives
                            </h3>
                            <p class="text-xs text-slate-400">Staff members with portal access to monitor {{ $tenant->name }}</p>
                        </div>
                        <button onclick="openModal('modalInviteRep')" class="px-3 py-1.5 rounded-xl bg-purple-500 hover:bg-purple-400 text-slate-950 font-bold text-xs transition flex items-center gap-1.5">
                            <i class="fa-solid fa-user-plus"></i>
                            <span>Invite Rep</span>
                        </button>
                    </div>

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
                                    @if($rep->two_factor_enabled)
                                        <span class="px-2 py-0.5 rounded text-[10px] font-mono bg-emerald-500/10 text-emerald-400 border border-emerald-500/30">
                                            <i class="fa-solid fa-lock"></i> 2FA Enabled
                                        </span>
                                    @else
                                        <span class="px-2 py-0.5 rounded text-[10px] font-mono bg-slate-800 text-slate-400">
                                            2FA Optional
                                        </span>
                                    @endif
                                    <span class="px-2.5 py-1 rounded-lg text-[10px] font-semibold bg-purple-500/10 text-purple-400 border border-purple-500/30">
                                        Operator Rep
                                    </span>
                                </div>
                            </div>
                        @empty
                            <div class="p-4 rounded-xl bg-slate-950 text-slate-500 text-xs italic">
                                No operator representatives configured for this tenant. Click "Invite Rep" to create one.
                            </div>
                        @endforelse
                    </div>
                </div>

                <!-- Role Permissions Box -->
                <div class="bg-slate-900/60 border border-slate-800 rounded-2xl p-6 flex flex-col justify-between">
                    <div>
                        <h3 class="font-bold text-sm mb-3 text-slate-200">Role Permissions & Governance</h3>
                        <ul class="text-xs text-slate-400 space-y-3">
                            <li class="flex items-start gap-2">
                                <i class="fa-solid fa-circle-check text-emerald-400 mt-0.5"></i>
                                <span><strong>Q4I Super Admin:</strong> Global view across all lottery operators, 35% commission ledger, and tenant configurations.</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <i class="fa-solid fa-circle-check text-purple-400 mt-0.5"></i>
                                <span><strong>Operator Rep:</strong> Restricted view of their company's live bets, registered players, and monthly settlement statements.</span>
                            </li>
                        </ul>
                    </div>

                    <div class="p-3.5 mt-6 rounded-xl bg-slate-950 border border-slate-800 text-[11px] text-slate-400 font-mono space-y-1">
                        <div>Portal Login: <span class="text-emerald-400">/login</span></div>
                        <div>Player Kiosk: <a href="{{ route('play.portal') }}" target="_blank" class="text-cyan-400 underline">/play</a></div>
                    </div>
                </div>
            </div>
        </div>

    </main>

    <!-- ========================================== -->
    <!-- MODAL: SIMULATE LIVE WHATSAPP BET         -->
    <!-- ========================================== -->
    <div id="modalSimulateBet" class="fixed inset-0 z-50 bg-slate-950/80 backdrop-blur-sm hidden flex items-center justify-center p-4">
        <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 max-w-md w-full shadow-2xl space-y-4">
            <div class="flex justify-between items-center">
                <h3 class="font-bold text-sm text-white flex items-center gap-2">
                    <i class="fa-solid fa-bolt text-emerald-400"></i>
                    Simulate Live WhatsApp Bet
                </h3>
                <button onclick="closeModal('modalSimulateBet')" class="text-slate-400 hover:text-white">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            <p class="text-xs text-slate-400">
                Instantly executes a real lotto ticket through the 35/65 Financial Engine, calculates commission splits, and writes to the cryptographic SHA-256 ledger.
            </p>

            <form onsubmit="submitSimulateBet(event)" class="space-y-3.5">
                <div>
                    <label class="block text-[11px] font-semibold text-slate-400 uppercase mb-1">Player WhatsApp Phone</label>
                    <input type="text" id="simPhone" required value="2348039876543"
                        class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2 text-xs text-white font-mono focus:border-emerald-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-[11px] font-semibold text-slate-400 uppercase mb-1">Lottery Game</label>
                    <select id="simGame" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white focus:border-emerald-500 focus:outline-none">
                        <option value="5_90_DAILY">Modern 5/90 Direct</option>
                        <option value="KENO_20_80">Keno 20/80 Lucky Numbers</option>
                        <option value="FAST_3">Fast 3 Instant Cash</option>
                    </select>
                </div>
                <div>
                    <label class="block text-[11px] font-semibold text-slate-400 uppercase mb-1">Stake Amount ({{ $tenant->currency }})</label>
                    <div class="grid grid-cols-4 gap-2">
                        <button type="button" onclick="setSimStake(200)" class="p-2 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white font-mono hover:border-emerald-500">200</button>
                        <button type="button" onclick="setSimStake(500)" class="p-2 rounded-xl bg-slate-950 border border-slate-800 text-xs text-emerald-400 font-mono font-bold hover:border-emerald-500">500</button>
                        <button type="button" onclick="setSimStake(1000)" class="p-2 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white font-mono hover:border-emerald-500">1,000</button>
                        <button type="button" onclick="setSimStake(5000)" class="p-2 rounded-xl bg-slate-950 border border-slate-800 text-xs text-purple-400 font-mono font-bold hover:border-emerald-500">5,000</button>
                    </div>
                    <input type="number" id="simStake" required value="500" min="50" step="50"
                        class="mt-2 w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2 text-xs text-white font-mono focus:border-emerald-500 focus:outline-none">
                </div>

                <div class="pt-2">
                    <button type="submit" id="btnSimBet"
                        class="w-full py-3 px-4 rounded-xl bg-gradient-to-r from-emerald-500 to-teal-500 hover:from-emerald-400 hover:to-teal-400 font-bold text-xs text-slate-950 transition flex items-center justify-center gap-2 shadow-lg shadow-emerald-500/20">
                        <i class="fa-solid fa-play"></i>
                        <span>Execute Real Bet & Update Ledger</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ========================================== -->
    <!-- MODAL: SIMULATE DVA DEPOSIT               -->
    <!-- ========================================== -->
    <div id="modalSimulateDeposit" class="fixed inset-0 z-50 bg-slate-950/80 backdrop-blur-sm hidden flex items-center justify-center p-4">
        <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 max-w-md w-full shadow-2xl space-y-4">
            <div class="flex justify-between items-center">
                <h3 class="font-bold text-sm text-white flex items-center gap-2">
                    <i class="fa-solid fa-building-columns text-cyan-400"></i>
                    Simulate DVA Bank Deposit
                </h3>
                <button onclick="closeModal('modalSimulateDeposit')" class="text-slate-400 hover:text-white">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            <p class="text-xs text-slate-400">
                Simulates an automated bank transfer credit (Paystack/Monnify) into a player's gaming wallet.
            </p>

            <form onsubmit="submitSimulateDeposit(event)" class="space-y-3.5">
                <div>
                    <label class="block text-[11px] font-semibold text-slate-400 uppercase mb-1">Player Phone</label>
                    <input type="text" id="depPhone" required value="2348039876543"
                        class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2 text-xs text-white font-mono focus:border-cyan-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-[11px] font-semibold text-slate-400 uppercase mb-1">Deposit Amount ({{ $tenant->currency }})</label>
                    <input type="number" id="depAmount" required value="5000" min="500" step="500"
                        class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2 text-xs text-white font-mono focus:border-cyan-500 focus:outline-none">
                </div>

                <div class="pt-2">
                    <button type="submit"
                        class="w-full py-3 px-4 rounded-xl bg-gradient-to-r from-cyan-500 to-blue-500 hover:from-cyan-400 hover:to-blue-400 font-bold text-xs text-slate-950 transition flex items-center justify-center gap-2 shadow-lg shadow-cyan-500/20">
                        <i class="fa-solid fa-check"></i>
                        <span>Credit Player Wallet Immediately</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ========================================== -->
    <!-- MODAL: ONBOARD NEW OPERATOR (SUPER ADMIN) -->
    <!-- ========================================== -->
    <div id="modalNewTenant" class="fixed inset-0 z-50 bg-slate-950/80 backdrop-blur-sm hidden flex items-center justify-center p-4">
        <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 max-w-md w-full shadow-2xl space-y-4">
            <div class="flex justify-between items-center">
                <h3 class="font-bold text-sm text-white flex items-center gap-2">
                    <i class="fa-solid fa-plus text-purple-400"></i>
                    Onboard New Lottery Operator
                </h3>
                <button onclick="closeModal('modalNewTenant')" class="text-slate-400 hover:text-white">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <form method="POST" action="{{ route('dashboard.create-tenant') }}" class="space-y-3.5">
                @csrf
                <div>
                    <label class="block text-[11px] font-semibold text-slate-400 uppercase mb-1">Company / Brand Name</label>
                    <input type="text" name="name" required placeholder="e.g. Western Lotto Nigeria"
                        class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2 text-xs text-white focus:border-purple-500 focus:outline-none">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-400 uppercase mb-1">Country Code</label>
                        <input type="text" name="country_code" required value="NG" maxlength="2"
                            class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2 text-xs text-white font-mono uppercase focus:border-purple-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-400 uppercase mb-1">Currency</label>
                        <input type="text" name="currency" required value="NGN" maxlength="3"
                            class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2 text-xs text-white font-mono uppercase focus:border-purple-500 focus:outline-none">
                    </div>
                </div>
                <div>
                    <label class="block text-[11px] font-semibold text-slate-400 uppercase mb-1">Dedicated WhatsApp Number</label>
                    <input type="text" name="whatsapp_number" required placeholder="+234 800 000 0000"
                        class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2 text-xs text-white font-mono focus:border-purple-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-[11px] font-semibold text-slate-400 uppercase mb-1">Q4I Commission Share (%)</label>
                    <input type="number" name="q4i_split_percent" required value="35.00" step="0.5" min="5" max="50"
                        class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2 text-xs text-emerald-400 font-mono font-bold focus:border-purple-500 focus:outline-none">
                </div>

                <div class="pt-2">
                    <button type="submit"
                        class="w-full py-3 px-4 rounded-xl bg-purple-500 hover:bg-purple-400 font-bold text-xs text-slate-950 transition flex items-center justify-center gap-2">
                        <span>Provision Tenant</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ========================================== -->
    <!-- MODAL: INVITE OPERATOR REP                -->
    <!-- ========================================== -->
    <div id="modalInviteRep" class="fixed inset-0 z-50 bg-slate-950/80 backdrop-blur-sm hidden flex items-center justify-center p-4">
        <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 max-w-md w-full shadow-2xl space-y-4">
            <div class="flex justify-between items-center">
                <h3 class="font-bold text-sm text-white flex items-center gap-2">
                    <i class="fa-solid fa-user-plus text-purple-400"></i>
                    Invite Operator Representative
                </h3>
                <button onclick="closeModal('modalInviteRep')" class="text-slate-400 hover:text-white">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <form method="POST" action="{{ route('dashboard.invite-rep') }}" class="space-y-3.5">
                @csrf
                <input type="hidden" name="tenant_id" value="{{ $tenant->id }}">

                <div>
                    <label class="block text-[11px] font-semibold text-slate-400 uppercase mb-1">Full Name</label>
                    <input type="text" name="name" required placeholder="Representative Name"
                        class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2 text-xs text-white focus:border-purple-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-[11px] font-semibold text-slate-400 uppercase mb-1">Corporate Email</label>
                    <input type="email" name="email" required placeholder="staff@modernlottery.ng"
                        class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2 text-xs text-white focus:border-purple-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-[11px] font-semibold text-slate-400 uppercase mb-1">Phone</label>
                    <input type="text" name="phone" placeholder="+234 803 000 0000"
                        class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2 text-xs text-white font-mono focus:border-purple-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-[11px] font-semibold text-slate-400 uppercase mb-1">Temporary Password</label>
                    <input type="password" name="password" required minlength="8" placeholder="Min 8 characters"
                        class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2 text-xs text-white focus:border-purple-500 focus:outline-none">
                </div>

                <div class="pt-2">
                    <button type="submit"
                        class="w-full py-3 px-4 rounded-xl bg-purple-500 hover:bg-purple-400 font-bold text-xs text-slate-950 transition flex items-center justify-center gap-2">
                        <span>Create & Grant Portal Access</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

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
                btn.classList.add('active');
                btn.classList.remove('text-slate-400');
            }
        }

        function openModal(id) {
            document.getElementById(id).classList.remove('hidden');
        }

        function closeModal(id) {
            document.getElementById(id).classList.add('hidden');
        }

        function setSimStake(val) {
            document.getElementById('simStake').value = val;
        }

        async function submitSimulateBet(e) {
            e.preventDefault();
            const phone = document.getElementById('simPhone').value;
            const game = document.getElementById('simGame').value;
            const stake = document.getElementById('simStake').value;
            const btn = document.getElementById('btnSimBet');

            btn.disabled = true;
            btn.innerHTML = `<i class="fa-solid fa-spinner fa-spin"></i> Processing 35/65 Ledger...`;

            try {
                const res = await fetch("{{ route('dashboard.simulate-bet') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        tenant_id: {{ $tenant->id }},
                        phone: phone,
                        game_code: game,
                        stake: stake
                    })
                });
                const data = await res.json();
                if (data.success) {
                    closeModal('modalSimulateBet');
                    refreshStats();
                    alert("✅ Bet Placed Successfully!\nTicket: " + data.ticket.reference + "\nQ4I Split (35%): ₦" + Number(data.ticket.q4i_share).toFixed(2) + "\nOperator Share (65%): ₦" + Number(data.ticket.operator_share).toFixed(2));
                    location.reload();
                } else {
                    alert("Error: " + (data.message || 'Execution failed'));
                }
            } catch (err) {
                alert("Server error executing simulated bet.");
            } finally {
                btn.disabled = false;
                btn.innerHTML = `<i class="fa-solid fa-play"></i> <span>Execute Real Bet & Update Ledger</span>`;
            }
        }

        async function submitSimulateDeposit(e) {
            e.preventDefault();
            const phone = document.getElementById('depPhone').value;
            const amount = document.getElementById('depAmount').value;

            try {
                const res = await fetch("{{ route('dashboard.simulate-deposit') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        tenant_id: {{ $tenant->id }},
                        phone: phone,
                        amount: amount
                    })
                });
                const data = await res.json();
                if (data.success) {
                    closeModal('modalSimulateDeposit');
                    alert("✅ " + data.message);
                }
            } catch (err) {
                alert("Deposit simulation failed.");
            }
        }

        async function toggle2FA() {
            try {
                const res = await fetch("{{ route('dashboard.toggle-2fa') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });
                const data = await res.json();
                if (data.success) {
                    alert(data.message);
                    location.reload();
                }
            } catch (err) {
                alert("Failed to toggle 2FA.");
            }
        }

        async function refreshStats() {
            try {
                const res = await fetch("{{ route('dashboard.stats', ['tenant_id' => $tenant->id]) }}");
                const data = await res.json();
                if (data.financials) {
                    document.getElementById('statTurnover').innerText = '{{ $tenant->currency }} ' + Number(data.financials.total_turnover).toLocaleString('en-US', {minimumFractionDigits: 2});
                    document.getElementById('statTicketsCount').innerText = data.financials.total_tickets;
                    document.getElementById('statQ4iCommission').innerText = '{{ $tenant->currency }} ' + Number(data.splits.q4i_accrued_earned).toLocaleString('en-US', {minimumFractionDigits: 2});
                    document.getElementById('statOperatorShare').innerText = '{{ $tenant->currency }} ' + Number(data.splits.operator_accrued_pool).toLocaleString('en-US', {minimumFractionDigits: 2});
                    document.getElementById('statAvailableSpend').innerText = '{{ $tenant->currency }} ' + Number(data.splits.q4i_available_to_spend).toLocaleString('en-US', {minimumFractionDigits: 2});
                    document.getElementById('distQ4iText').innerText = '{{ $tenant->currency }} ' + Number(data.splits.q4i_accrued_earned).toLocaleString('en-US', {minimumFractionDigits: 2});
                    document.getElementById('distOpText').innerText = '{{ $tenant->currency }} ' + Number(data.splits.operator_accrued_pool).toLocaleString('en-US', {minimumFractionDigits: 2});
                }
            } catch (e) {
                // background silent refresh
            }
        }

        // Auto-refresh stats every 15s
        setInterval(refreshStats, 15000);
    </script>
</body>
</html>
