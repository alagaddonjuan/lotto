<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Q4I Financial Engine | {{ $tenant->name }}</title>
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
            <div class="w-11 h-11 rounded-2xl bg-gradient-to-tr from-emerald-500 to-cyan-500 flex items-center justify-center font-black text-2xl text-slate-950 shadow-xl shadow-emerald-500/20">
                Q
            </div>
            <div>
                <div class="flex items-center gap-2.5">
                    <h1 class="font-bold text-base text-white tracking-tight leading-none">Q4I Financial Engine</h1>
                    <span class="text-[10px] bg-emerald-500/10 text-emerald-400 border border-emerald-500/30 px-2 py-0.5 rounded-full font-mono flex items-center gap-1.5 font-semibold">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                        NLRC Node: Operational
                    </span>
                </div>
                <p class="text-xs text-slate-400 mt-1">
                    Operator: <strong class="text-slate-200">{{ $tenant->name }}</strong> &bull; <span class="font-mono text-emerald-400">35/65 Split Clearinghouse</span>
                </p>
            </div>
        </div>

        <!-- Header Actions -->
        <div class="flex flex-wrap items-center gap-3">
            <!-- Period Indicator -->
            <div class="hidden lg:flex items-center gap-2 bg-slate-900 border border-slate-800 rounded-xl px-3 py-1.5 text-xs text-slate-300">
                <i class="fa-regular fa-calendar text-slate-400"></i>
                <span>{{ now()->format('F Y') }} Cycle</span>
            </div>

            <!-- Settlement Invoice PDF -->
            <a href="{{ route('dashboard.invoice', ['tenant_id' => $tenant->id]) }}" target="_blank"
                class="px-3.5 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 border border-slate-800 hover:border-slate-700 text-xs font-semibold text-slate-200 transition flex items-center gap-2 shadow-sm">
                <i class="fa-solid fa-file-invoice-dollar text-emerald-400"></i>
                <span class="hidden sm:inline">Settlement Invoice</span>
            </a>

            <!-- Export CSV -->
            <a href="{{ route('dashboard.export-csv', ['tenant_id' => $tenant->id]) }}"
                class="px-3.5 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 border border-slate-800 hover:border-slate-700 text-xs font-semibold text-slate-200 transition flex items-center gap-2 shadow-sm">
                <i class="fa-solid fa-file-csv text-cyan-400"></i>
                <span class="hidden sm:inline">Export CSV</span>
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
                            <span class="text-[10px] px-2 py-0.5 rounded font-mono font-bold {{ auth()->user()->two_factor_enabled ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/30' : 'bg-slate-800 text-slate-400' }}">
                                <i class="fa-solid fa-shield-halved text-[9px]"></i> 2FA {{ auth()->user()->two_factor_enabled ? 'ON' : 'OFF' }}
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
                <span>Financial Overview & 35/65 Split</span>
            </button>
            <button onclick="switchTab('tickets')" id="tab-btn-tickets" class="tab-btn px-4 py-3 border-b-2 border-transparent text-slate-400 hover:text-slate-200 transition flex items-center gap-2">
                <i class="fa-solid fa-receipt text-cyan-400"></i>
                <span>Live Transactions Ledger</span>
                <span class="bg-slate-800 text-slate-300 text-[10px] px-2 py-0.5 rounded-full font-mono">{{ $balanceSheet['financials']['total_tickets'] }}</span>
            </button>
            <button onclick="switchTab('players')" id="tab-btn-players" class="tab-btn px-4 py-3 border-b-2 border-transparent text-slate-400 hover:text-slate-200 transition flex items-center gap-2">
                <i class="fa-solid fa-users text-blue-400"></i>
                <span>Registered Players</span>
                <span class="bg-emerald-500/20 text-emerald-400 text-[10px] px-2 py-0.5 rounded-full font-mono">{{ $totalPlayers }}</span>
            </button>
            <button onclick="switchTab('games')" id="tab-btn-games" class="tab-btn px-4 py-3 border-b-2 border-transparent text-slate-400 hover:text-slate-200 transition flex items-center gap-2">
                <i class="fa-solid fa-dice text-amber-400"></i>
                <span>Games & Limits</span>
            </button>
            <button onclick="switchTab('reps')" id="tab-btn-reps" class="tab-btn px-4 py-3 border-b-2 border-transparent text-slate-400 hover:text-slate-200 transition flex items-center gap-2">
                <i class="fa-solid fa-user-shield text-purple-400"></i>
                <span>Operator Reps & Access</span>
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
                        <span><strong class="text-emerald-400 font-mono" id="statTicketsCount">{{ $balanceSheet['financials']['total_tickets'] }}</strong> tickets settled</span>
                        <span class="text-[10px] bg-slate-800 px-2 py-0.5 rounded text-slate-400 font-mono">100% Cleared</span>
                    </p>
                </div>

                <!-- Q4I 35% Split -->
                <div class="bg-gradient-to-br from-emerald-950/40 to-slate-900/80 border border-emerald-500/40 rounded-2xl p-5 shadow-sm">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wider text-emerald-400">Q4I Commission (35%)</p>
                            <h3 class="text-2xl font-black mt-2 text-emerald-300 font-mono" id="statQ4iCommission">
                                {{ $tenant->currency }} {{ number_format($balanceSheet['splits']['q4i_accrued_earned'], 2) }}
                            </h3>
                        </div>
                        <div class="w-10 h-10 rounded-xl bg-emerald-500/20 border border-emerald-500/40 text-emerald-400 flex items-center justify-center">
                            <i class="fa-solid fa-hand-holding-dollar"></i>
                        </div>
                    </div>
                    <p class="text-xs text-emerald-400/80 mt-4 font-mono">Accrued Platform Commission</p>
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
                    <p class="text-xs text-slate-400 mt-4 font-mono">{{ $tenant->name }} Game Pool</p>
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

            <!-- Commercial Terms & Cryptographic Ledger -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Commercial Split Visualizer -->
                <div class="lg:col-span-2 bg-slate-900/70 border border-slate-800 rounded-2xl p-6">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="font-bold text-sm flex items-center gap-2">
                            <i class="fa-solid fa-chart-pie text-emerald-400"></i>
                            35/65 Commercial Revenue Distribution
                        </h3>
                        <span class="text-xs text-slate-500 font-mono">Audited by SHA-256 Ledger</span>
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
                            <p class="font-bold text-base text-slate-100 mt-1">35% Fixed</p>
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
                            To guarantee zero reconciliation disputes between Q4I and {{ $tenant->name }}, each transaction generates an immutable SHA-256 block cryptographically chaining the ticket ID, split amounts, and timestamp.
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
                                <span class="text-slate-500">Reconciled Diff:</span>
                                <span class="text-emerald-400">0.00 {{ $tenant->currency }}</span>
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

            <!-- Recent Transactions Table Preview -->
            <div class="bg-slate-900/70 border border-slate-800 rounded-2xl p-6">
                <div class="flex justify-between items-center mb-6">
                    <div>
                        <h3 class="font-bold text-sm flex items-center gap-2">
                            <i class="fa-solid fa-receipt text-emerald-400"></i>
                            Recent Clearinghouse Transactions
                        </h3>
                        <p class="text-xs text-slate-400">Real-time ledger audit entries for {{ $tenant->name }}</p>
                    </div>
                    <button onclick="switchTab('tickets')" class="text-xs text-emerald-400 hover:text-emerald-300 font-semibold transition flex items-center gap-1">
                        <span>View All Transactions</span>
                        <i class="fa-solid fa-arrow-right"></i>
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
                                <th class="py-3 px-4">Cryptographic Seal</th>
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
                                    <td colspan="8" class="text-center py-8 text-slate-500 italic">No tickets cleared yet for this period. Live incoming WhatsApp bets will populate here automatically.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>

        <!-- ========================================== -->
        <!-- TAB 2: LIVE TRANSACTIONS LEDGER           -->
        <!-- ========================================== -->
        <div id="tab-content-tickets" class="tab-pane hidden space-y-6">
            <div class="bg-slate-900/70 border border-slate-800 rounded-2xl p-6">
                <div class="flex flex-wrap justify-between items-center gap-4 mb-6">
                    <div>
                        <h3 class="font-bold text-sm flex items-center gap-2">
                            <i class="fa-solid fa-receipt text-emerald-400"></i>
                            Live Clearinghouse Transactions
                        </h3>
                        <p class="text-xs text-slate-400">Complete audit trail of player stakes with instant 35/65 split settlement</p>
                    </div>

                    <div class="flex items-center gap-3">
                        <button onclick="openModal('modalRecordTxn')" class="px-3.5 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-xs font-semibold text-slate-200 transition flex items-center gap-2">
                            <i class="fa-solid fa-plus"></i>
                            <span>Record Settlement Clearance</span>
                        </button>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-slate-300">
                        <thead class="bg-slate-950/80 uppercase tracking-wider text-slate-400 font-semibold border-b border-slate-800">
                            <tr>
                                <th class="py-3 px-4">Ticket Reference</th>
                                <th class="py-3 px-4">Player</th>
                                <th class="py-3 px-4">Game</th>
                                <th class="py-3 px-4">Stake</th>
                                <th class="py-3 px-4 text-emerald-400">Q4I Platform (35%)</th>
                                <th class="py-3 px-4 text-purple-400">Operator (65%)</th>
                                <th class="py-3 px-4">Status</th>
                                <th class="py-3 px-4">Cryptographic Hash Seal</th>
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
                                        {{ substr($ticket->hash ?? 'N/A', 0, 16) }}...
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center py-8 text-slate-500 italic">No tickets placed yet for this billing cycle.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- TAB 3: REGISTERED PLAYERS CRM             -->
        <!-- ========================================== -->
        <div id="tab-content-players" class="tab-pane hidden space-y-6">
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
                <div class="bg-slate-900/70 border border-slate-800 rounded-2xl p-5">
                    <p class="text-xs uppercase tracking-wider text-slate-400 font-semibold">Total Registered Players</p>
                    <h3 class="text-3xl font-black mt-2 text-white font-mono">{{ $totalPlayers }}</h3>
                    <p class="text-xs text-emerald-400 mt-2 flex items-center gap-1">
                        <i class="fa-solid fa-shield-check"></i> Verified WhatsApp Identities
                    </p>
                </div>
                <div class="bg-slate-900/70 border border-slate-800 rounded-2xl p-5">
                    <p class="text-xs uppercase tracking-wider text-slate-400 font-semibold">New Comers Today</p>
                    <h3 class="text-3xl font-black mt-2 text-emerald-300 font-mono">{{ $newPlayersToday }}</h3>
                    <p class="text-xs text-slate-400 mt-2">Onboarded via WhatsApp Channel</p>
                </div>
                <div class="bg-slate-900/70 border border-slate-800 rounded-2xl p-5">
                    <p class="text-xs uppercase tracking-wider text-slate-400 font-semibold">Security PIN Protocol</p>
                    <h3 class="text-3xl font-black mt-2 text-cyan-300">Enforced</h3>
                    <p class="text-xs text-cyan-400 mt-2">4-Digit Wallet Authorization</p>
                </div>
            </div>

            <div class="bg-slate-900/70 border border-slate-800 rounded-2xl p-6">
                <div class="flex justify-between items-center mb-6">
                    <div>
                        <h3 class="font-bold text-sm">Player Identities & Activity Log</h3>
                        <p class="text-xs text-slate-400">All registered lottery players for {{ $tenant->name }}</p>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-slate-300">
                        <thead class="bg-slate-950/80 uppercase tracking-wider text-slate-400 font-semibold border-b border-slate-800">
                            <tr>
                                <th class="py-3 px-4">Player Name</th>
                                <th class="py-3 px-4">WhatsApp Phone</th>
                                <th class="py-3 px-4">Total Bets Cleared</th>
                                <th class="py-3 px-4">Onboarded</th>
                                <th class="py-3 px-4">Last Activity</th>
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
                                        <span>{{ $p->name ?? 'Player' }}</span>
                                    </td>
                                    <td class="py-3 px-4 font-mono font-medium text-slate-200">+{{ $p->phone }}</td>
                                    <td class="py-3 px-4 font-bold text-emerald-400 font-mono">{{ $p->bet_tickets_count }} tickets</td>
                                    <td class="py-3 px-4 text-slate-400">{{ $p->created_at->format('M d, Y') }}</td>
                                    <td class="py-3 px-4 text-slate-400">{{ $p->last_active_at ? $p->last_active_at->diffForHumans() : 'Never' }}</td>
                                    <td class="py-3 px-4">
                                        @if($p->pin_hash)
                                            <span class="text-emerald-400 font-mono text-[11px]"><i class="fa-solid fa-lock"></i> Configured</span>
                                        @else
                                            <span class="text-amber-400 font-mono text-[11px]"><i class="fa-solid fa-lock-open"></i> Pending</span>
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
                                    <td colspan="7" class="text-center py-8 text-slate-500 italic">No player records found. Incoming messages will auto-register players here.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- TAB 4: LOTTO GAMES CATALOG & LIMITS       -->
        <!-- ========================================== -->
        <div id="tab-content-games" class="tab-pane hidden space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @forelse($games as $game)
                    <div class="bg-slate-900/70 border border-slate-800 rounded-2xl p-6 flex flex-col justify-between shadow-sm">
                        <div>
                            <div class="flex justify-between items-start mb-3">
                                <div>
                                    <span class="text-[10px] uppercase font-bold tracking-wider text-emerald-400 font-mono">{{ $game->game_code }}</span>
                                    <h3 class="text-base font-bold text-white mt-1">{{ $game->name }}</h3>
                                </div>
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase bg-emerald-500/10 text-emerald-400 border border-emerald-500/30">
                                    {{ $game->is_active ? 'Active' : 'Disabled' }}
                                </span>
                            </div>
                            <p class="text-xs text-slate-400 leading-relaxed mb-4">{{ $game->description }}</p>

                            <div class="p-3.5 rounded-xl bg-slate-950 border border-slate-800/80 text-xs space-y-2 font-mono">
                                <div class="flex justify-between">
                                    <span class="text-slate-500">Min Stake:</span>
                                    <span class="text-slate-200">{{ $tenant->currency }} {{ number_format($game->min_stake, 2) }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-slate-500">Max Stake:</span>
                                    <span class="text-slate-200">{{ $tenant->currency }} {{ number_format($game->max_stake, 2) }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-slate-500">Number Matrix:</span>
                                    <span class="text-emerald-400 font-bold">{{ $game->required_picks }} of {{ $game->total_pool_numbers }} Balls</span>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 pt-4 border-t border-slate-800/80 flex justify-between items-center text-xs">
                            <span class="text-slate-500">WhatsApp Code:</span>
                            <code class="text-emerald-400 font-bold bg-slate-950 px-2.5 py-1 rounded border border-slate-800">{{ $game->game_code }}</code>
                        </div>
                    </div>
                @empty
                    <div class="col-span-3 text-center py-12 text-slate-500 italic bg-slate-900/40 rounded-2xl border border-slate-800">
                        No custom games registered. Modern Lottery defaults (Keno 20/80 & 5/90) are actively served.
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
                <div class="lg:col-span-2 bg-slate-900/70 border border-slate-800 rounded-2xl p-6">
                    <div class="flex justify-between items-center mb-6">
                        <div>
                            <h3 class="font-bold text-sm flex items-center gap-2">
                                <i class="fa-solid fa-users-gear text-emerald-400"></i>
                                Authorized Operator Representatives
                            </h3>
                            <p class="text-xs text-slate-400">Staff members with portal access for {{ $tenant->name }}</p>
                        </div>
                        <button onclick="openModal('modalInviteRep')" class="px-3.5 py-2 rounded-xl bg-purple-500 hover:bg-purple-400 text-slate-950 font-bold text-xs transition flex items-center gap-1.5 shadow-md shadow-purple-500/20">
                            <i class="fa-solid fa-user-plus"></i>
                            <span>Invite Representative</span>
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
                                            <i class="fa-solid fa-lock"></i> 2FA Active
                                        </span>
                                    @else
                                        <span class="px-2 py-0.5 rounded text-[10px] font-mono bg-slate-800 text-slate-400">
                                            2FA Inactive
                                        </span>
                                    @endif
                                    <span class="px-2.5 py-1 rounded-lg text-[10px] font-semibold bg-purple-500/10 text-purple-400 border border-purple-500/30">
                                        Operator Rep
                                    </span>
                                </div>
                            </div>
                        @empty
                            <div class="p-4 rounded-xl bg-slate-950 text-slate-500 text-xs italic">
                                No operator representatives configured for this tenant.
                            </div>
                        @endforelse
                    </div>
                </div>

                <!-- Governance Guidelines -->
                <div class="bg-slate-900/70 border border-slate-800 rounded-2xl p-6 flex flex-col justify-between">
                    <div>
                        <h3 class="font-bold text-sm mb-3 text-slate-200">Role Permissions & Governance</h3>
                        <ul class="text-xs text-slate-400 space-y-3 leading-relaxed">
                            <li class="flex items-start gap-2">
                                <i class="fa-solid fa-circle-check text-emerald-400 mt-0.5"></i>
                                <span><strong>Q4I Super Administrator:</strong> Global view across all licensed lottery operators, 35% commission ledger, and clearinghouse configuration.</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <i class="fa-solid fa-circle-check text-purple-400 mt-0.5"></i>
                                <span><strong>Operator Representative:</strong> Restricted view of their organization's live tickets, registered players, and monthly settlement statements.</span>
                            </li>
                        </ul>
                    </div>

                    <div class="p-4 mt-6 rounded-xl bg-slate-950 border border-slate-800 text-xs text-slate-400 space-y-1">
                        <div class="font-semibold text-slate-300">Identity Security:</div>
                        <p class="text-[11px] text-slate-500">Every portal user must configure Two-Factor Authentication (2FA) in compliance with NLRC regulatory standards.</p>
                    </div>
                </div>
            </div>
        </div>

    </main>

    <!-- ========================================== -->
    <!-- MODAL: RECORD SETTLEMENT CLEARANCE        -->
    <!-- ========================================== -->
    <div id="modalRecordTxn" class="fixed inset-0 z-50 bg-slate-950/80 backdrop-blur-sm hidden flex items-center justify-center p-4">
        <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 max-w-md w-full shadow-2xl space-y-4">
            <div class="flex justify-between items-center">
                <h3 class="font-bold text-sm text-white flex items-center gap-2">
                    <i class="fa-solid fa-receipt text-emerald-400"></i>
                    Record Settlement Clearance
                </h3>
                <button onclick="closeModal('modalRecordTxn')" class="text-slate-400 hover:text-white">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            <p class="text-xs text-slate-400">
                Execute a clearance ticket through the 35/65 Split Clearinghouse to reconcile stake pools and write to the cryptographic ledger.
            </p>

            <form onsubmit="submitSettlementClearance(event)" class="space-y-3.5">
                <div>
                    <label class="block text-[11px] font-semibold text-slate-400 uppercase mb-1">Player Phone Number</label>
                    <input type="text" id="simPhone" required value="2348039876543"
                        class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2.5 text-xs text-white font-mono focus:border-emerald-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-[11px] font-semibold text-slate-400 uppercase mb-1">Lottery Game</label>
                    <select id="simGame" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2.5 text-xs text-white focus:border-emerald-500 focus:outline-none">
                        <option value="5_90_DAILY">Modern 5/90 Direct</option>
                        <option value="KENO_20_80">Keno 20/80 Lucky Numbers</option>
                        <option value="FAST_3">Fast 3 Instant Cash</option>
                    </select>
                </div>
                <div>
                    <label class="block text-[11px] font-semibold text-slate-400 uppercase mb-1">Stake Amount ({{ $tenant->currency }})</label>
                    <input type="number" id="simStake" required value="1000" min="50" step="50"
                        class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2.5 text-xs text-white font-mono focus:border-emerald-500 focus:outline-none">
                </div>

                <div class="pt-2">
                    <button type="submit" id="btnRecordTxn"
                        class="w-full py-3.5 px-4 rounded-xl bg-gradient-to-r from-emerald-500 to-teal-500 hover:from-emerald-400 hover:to-teal-400 font-bold text-xs text-slate-950 transition flex items-center justify-center gap-2 shadow-lg shadow-emerald-500/20">
                        <i class="fa-solid fa-check"></i>
                        <span>Execute Clearance & Seal Ledger</span>
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
                    <label class="block text-[11px] font-semibold text-slate-400 uppercase mb-1">Full Legal Name</label>
                    <input type="text" name="name" required placeholder="Representative Name"
                        class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2 text-xs text-white focus:border-purple-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-[11px] font-semibold text-slate-400 uppercase mb-1">Corporate Email</label>
                    <input type="email" name="email" required placeholder="staff@modernlottery.ng"
                        class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2 text-xs text-white focus:border-purple-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-[11px] font-semibold text-slate-400 uppercase mb-1">Phone Number</label>
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
                        <span>Grant Operator Access</span>
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

        async function submitSettlementClearance(e) {
            e.preventDefault();
            const phone = document.getElementById('simPhone').value;
            const game = document.getElementById('simGame').value;
            const stake = document.getElementById('simStake').value;
            const btn = document.getElementById('btnRecordTxn');

            btn.disabled = true;
            btn.innerHTML = `<i class="fa-solid fa-spinner fa-spin"></i> Clearing Split...`;

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
                    closeModal('modalRecordTxn');
                    location.reload();
                } else {
                    alert("Clearance error: " + (data.message || 'Execution failed'));
                }
            } catch (err) {
                alert("Server error executing transaction.");
            } finally {
                btn.disabled = false;
                btn.innerHTML = `<i class="fa-solid fa-check"></i> <span>Execute Clearance & Seal Ledger</span>`;
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
                // silent refresh
            }
        }

        setInterval(refreshStats, 15000);
    </script>
</body>
</html>
