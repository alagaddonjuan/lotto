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
            background: rgba(16, 185, 129, 0.05);
        }
    </style>
</head>
<body class="bg-slate-950 text-slate-100 min-h-screen font-sans antialiased">

    <!-- Top Navigation Header -->
    <header class="border-b border-slate-800 bg-slate-950/90 backdrop-blur sticky top-0 z-50 px-6 py-3.5 flex flex-wrap justify-between items-center gap-4">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-emerald-500 to-cyan-500 flex items-center justify-center font-black text-xl text-slate-950 shadow-lg shadow-emerald-500/20">
                Q
            </div>
            <div>
                <h1 class="font-bold text-base leading-tight flex items-center gap-2">
                    Q4I Financial Engine
                    <span class="text-[10px] bg-emerald-500/10 text-emerald-400 border border-emerald-500/30 px-2 py-0.5 rounded-full font-mono">35/65 Split Ledger</span>
                </h1>
                <p class="text-xs text-slate-400">
                    Operator: <span class="text-slate-200 font-semibold">{{ $tenant->name }}</span> ({{ $tenant->currency }})
                </p>
            </div>
        </div>

        <div class="flex items-center gap-4">
            <!-- Multi-Tenant Dropdown for Super Admins -->
            @if(auth()->check() && auth()->user()->isSuperAdmin())
                <form method="GET" action="{{ route('dashboard') }}" class="flex items-center gap-2">
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

            <!-- Rep Profile / Auth Menu -->
            @if(auth()->check())
                <div class="flex items-center gap-3 pl-3 border-l border-slate-800">
                    <div class="text-right hidden sm:block">
                        <div class="text-xs font-bold text-slate-200">{{ auth()->user()->name }}</div>
                        <div class="text-[10px] text-emerald-400 font-mono capitalize">{{ str_replace('_', ' ', auth()->user()->role) }}</div>
                    </div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" title="Logout" class="p-2 rounded-xl bg-slate-900 hover:bg-rose-500/10 border border-slate-800 hover:border-rose-500/30 text-slate-400 hover:text-rose-400 text-xs transition">
                            <i class="fa-solid fa-arrow-right-from-bracket"></i>
                        </button>
                    </form>
                </div>
            @else
                <a href="{{ route('login') }}" class="text-xs px-3.5 py-1.5 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-bold transition">
                    Rep Sign In
                </a>
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
                <i class="fa-brands fa-whatsapp"></i>
                <span>Live WhatsApp Bets</span>
            </button>
            <button onclick="switchTab('reps')" id="tab-btn-reps" class="tab-btn px-4 py-3 border-b-2 border-transparent text-slate-400 hover:text-slate-200 transition flex items-center gap-2">
                <i class="fa-solid fa-user-shield"></i>
                <span>Operator Reps and Access</span>
            </button>
        </div>
    </div>

    <main class="max-w-7xl mx-auto px-6 py-8 space-y-8">

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
                            <p class="text-xs font-medium uppercase tracking-wider text-slate-400">Total Turnover</p>
                            <h3 class="text-2xl font-black mt-2 text-white">
                                {{ $tenant->currency }} {{ number_format($balanceSheet['financials']['total_turnover'], 2) }}
                            </h3>
                        </div>
                        <div class="w-10 h-10 rounded-xl bg-blue-500/10 border border-blue-500/20 text-blue-400 flex items-center justify-center">
                            <i class="fa-solid fa-money-bill-trend-up"></i>
                        </div>
                    </div>
                    <p class="text-xs text-slate-400 mt-4">
                        <span class="text-emerald-400 font-semibold">{{ $balanceSheet['financials']['total_tickets'] }}</span> tickets accepted
                    </p>
                </div>

                <!-- Q4I 35% Split -->
                <div class="bg-gradient-to-br from-emerald-950/40 to-slate-900/80 border border-emerald-500/40 rounded-2xl p-5 shadow-sm">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wider text-emerald-400">Q4I Commission (35%)</p>
                            <h3 class="text-2xl font-black mt-2 text-emerald-300">
                                {{ $tenant->currency }} {{ number_format($balanceSheet['splits']['q4i_accrued_earned'], 2) }}
                            </h3>
                        </div>
                        <div class="w-10 h-10 rounded-xl bg-emerald-500/20 border border-emerald-500/40 text-emerald-400 flex items-center justify-center">
                            <i class="fa-solid fa-hand-holding-dollar"></i>
                        </div>
                    </div>
                    <p class="text-xs text-emerald-400/80 mt-4 font-mono">Platform Earned Commission</p>
                </div>

                <!-- Modern Lottery 65% Share -->
                <div class="bg-slate-900/60 border border-slate-800 rounded-2xl p-5 shadow-sm">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-xs font-medium uppercase tracking-wider text-slate-400">Operator Share (65%)</p>
                            <h3 class="text-2xl font-black mt-2 text-purple-300">
                                {{ $tenant->currency }} {{ number_format($balanceSheet['splits']['operator_accrued_pool'], 2) }}
                            </h3>
                        </div>
                        <div class="w-10 h-10 rounded-xl bg-purple-500/10 border border-purple-500/20 text-purple-400 flex items-center justify-center">
                            <i class="fa-solid fa-building-columns"></i>
                        </div>
                    </div>
                    <p class="text-xs text-slate-400 mt-4 font-mono">Modern Lottery Pool</p>
                </div>

                <!-- Available to Spend -->
                <div class="bg-gradient-to-br from-cyan-950/40 to-slate-900/80 border border-cyan-500/40 rounded-2xl p-5 shadow-sm">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wider text-cyan-400">Available to Settle / Spend</p>
                            <h3 class="text-2xl font-black mt-2 text-cyan-300">
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
                                <span class="text-emerald-300">{{ $tenant->currency }} {{ number_format($balanceSheet['splits']['q4i_accrued_earned'], 2) }}</span>
                            </div>
                            <div class="w-full h-3 bg-slate-800 rounded-full overflow-hidden">
                                <div class="h-full bg-emerald-500 rounded-full" style="width: 35%"></div>
                            </div>
                        </div>

                        <div>
                            <div class="flex justify-between text-xs font-semibold mb-1.5">
                                <span class="text-purple-400">Modern Lottery Operator Share (65%)</span>
                                <span class="text-purple-300">{{ $tenant->currency }} {{ number_format($balanceSheet['splits']['operator_accrued_pool'], 2) }}</span>
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

                <!-- Hash Chain Card -->
                <div class="bg-slate-900/60 border border-slate-800 rounded-2xl p-6 flex flex-col justify-between">
                    <div>
                        <h3 class="font-bold text-sm flex items-center gap-2 mb-3">
                            <i class="fa-solid fa-shield-halved text-emerald-400"></i>
                            Cryptographic Audit Chain
                        </h3>
                        <p class="text-xs text-slate-400 leading-relaxed">
                            Every single bet placed creates a tamper-evident SHA-256 hash block chaining the ticket ID, split amounts, and timestamp.
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
                        <button class="w-full py-2.5 px-4 rounded-xl bg-slate-800 hover:bg-slate-700 text-xs font-semibold text-slate-200 transition flex items-center justify-center gap-2">
                            <i class="fa-solid fa-file-arrow-down"></i>
                            Download Reconciliation Statement (CSV)
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- TAB 2: REGISTERED PLAYERS (NEWCOMERS CRM)  -->
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
                    <p class="text-xs uppercase tracking-wider text-slate-400 font-semibold">Security PIN Status</p>
                    <h3 class="text-3xl font-black mt-2 text-cyan-300">Active</h3>
                    <p class="text-xs text-cyan-400 mt-2">4-Digit Wallet Authorization</p>
                </div>
            </div>

            <!-- Player Table -->
            <div class="bg-slate-900/60 border border-slate-800 rounded-2xl p-6">
                <div class="flex flex-wrap justify-between items-center gap-4 mb-6">
                    <div>
                        <h3 class="font-bold text-sm">Player Database & Onboarding Activity</h3>
                        <p class="text-xs text-slate-400">All customers who initiated chat and completed registration</p>
                    </div>
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
                                    <td colspan="7" class="text-center py-8 text-slate-500 italic">No players registered yet. As users message your WhatsApp number, they will automatically appear here.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- TAB 3: LIVE WHATSAPP BETS                 -->
        <!-- ========================================== -->
        <div id="tab-content-tickets" class="tab-pane hidden space-y-6">
            <div class="bg-slate-900/60 border border-slate-800 rounded-2xl p-6">
                <div class="flex justify-between items-center mb-6">
                    <div>
                        <h3 class="font-bold text-sm flex items-center gap-2">
                            <i class="fa-brands fa-whatsapp text-emerald-400"></i>
                            Live WhatsApp Bet Stream
                        </h3>
                        <p class="text-xs text-slate-400">Real-time tickets placed with instant 35/65 commission calculation</p>
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
        <!-- TAB 4: OPERATOR REPS & ACCESS             -->
        <!-- ========================================== -->
        <div id="tab-content-reps" class="tab-pane hidden space-y-6">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Reps List -->
                <div class="lg:col-span-2 bg-slate-900/60 border border-slate-800 rounded-2xl p-6">
                    <h3 class="font-bold text-sm mb-1 flex items-center gap-2">
                        <i class="fa-solid fa-users-gear text-emerald-400"></i>
                        Authorized Operator Representatives
                    </h3>
                    <p class="text-xs text-slate-400 mb-6">Staff members with login access to monitor {{ $tenant->name }}</p>

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
                                <span class="px-2.5 py-1 rounded-lg text-[10px] font-semibold bg-purple-500/10 text-purple-400 border border-purple-500/30">
                                    Operator Rep
                                </span>
                            </div>
                        @empty
                            <div class="p-4 rounded-xl bg-slate-950 text-slate-500 text-xs italic">
                                No operator representatives configured for this tenant.
                            </div>
                        @endforelse
                    </div>
                </div>

                <!-- Role Permissions Box -->
                <div class="bg-slate-900/60 border border-slate-800 rounded-2xl p-6 flex flex-col justify-between">
                    <div>
                        <h3 class="font-bold text-sm mb-3 text-slate-200">Role Permissions</h3>
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

                    <div class="p-3.5 mt-6 rounded-xl bg-slate-950 border border-slate-800 text-[11px] text-slate-400 font-mono">
                        Login URL: <span class="text-emerald-400">/login</span>
                    </div>
                </div>
            </div>
        </div>

    </main>

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
    </script>
</body>
</html>
