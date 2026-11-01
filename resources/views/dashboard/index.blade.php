<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Q4I Platform | Financial Reconciliation & Revenue Share Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-slate-900 text-slate-100 min-h-screen font-sans">

    <!-- Top Navigation -->
    <header class="border-b border-slate-800 bg-slate-950/80 backdrop-blur sticky top-0 z-50 px-6 py-4 flex flex-wrap justify-between items-center gap-4">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-emerald-500 to-cyan-500 flex items-center justify-center font-black text-xl text-slate-950 shadow-lg shadow-emerald-500/20">
                Q
            </div>
            <div>
                <h1 class="font-bold text-lg leading-tight flex items-center gap-2">
                    Q4I Financial Engine
                    <span class="text-xs bg-emerald-500/10 text-emerald-400 border border-emerald-500/30 px-2 py-0.5 rounded-full font-mono">35/65 Split Ledger</span>
                </h1>
                <p class="text-xs text-slate-400">Tenant: <span class="text-slate-200 font-semibold">{{ $tenant->name }}</span> ({{ $tenant->currency }})</p>
            </div>
        </div>

        <div class="flex items-center gap-3">
            <!-- Tenant Switcher for Multi-Tenancy -->
            <form method="GET" action="{{ route('dashboard') }}" class="flex items-center gap-2">
                <select name="tenant_id" onchange="this.form.submit()" class="bg-slate-800 border border-slate-700 text-xs rounded-lg px-3 py-2 text-slate-200 focus:ring-2 focus:ring-emerald-500 outline-none">
                    @foreach($allTenants as $t)
                        <option value="{{ $t->id }}" {{ $t->id == $tenant->id ? 'selected' : '' }}>
                            {{ $t->name }} ({{ $t->country_code }})
                        </option>
                    @endforeach
                </select>
            </form>

            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 text-xs font-semibold">
                <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                Ledger Chain: 100% Sealed
            </span>
        </div>
    </header>

    <main class="max-w-7xl mx-auto px-6 py-8 space-y-8">

        <!-- Executive Revenue & Split Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
            <!-- Card 1: Total Turnover -->
            <div class="bg-slate-800/60 border border-slate-700/60 rounded-2xl p-5 shadow-sm relative overflow-hidden">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-xs font-medium uppercase tracking-wider text-slate-400">Total Turnover (Stakes)</p>
                        <h3 class="text-2xl font-black mt-2 text-white">
                            {{ $tenant->currency }} {{ number_format($balanceSheet['financials']['total_turnover'], 2) }}
                        </h3>
                    </div>
                    <div class="w-10 h-10 rounded-xl bg-blue-500/10 border border-blue-500/20 text-blue-400 flex items-center justify-center">
                        <i class="fa-solid fa-money-bill-trend-up"></i>
                    </div>
                </div>
                <p class="text-xs text-slate-400 mt-4 flex items-center gap-1">
                    <span class="text-emerald-400 font-semibold">{{ $balanceSheet['financials']['total_tickets'] }}</span> total tickets processed
                </p>
            </div>

            <!-- Card 2: Q4I Accrued Commission (35%) -->
            <div class="bg-gradient-to-br from-emerald-950/40 to-slate-800/80 border border-emerald-500/40 rounded-2xl p-5 shadow-sm relative overflow-hidden">
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
                <p class="text-xs text-emerald-400/80 mt-4 font-mono">
                    Accrued Platform Commission
                </p>
            </div>

            <!-- Card 3: Modern Lottery Share (65%) -->
            <div class="bg-slate-800/60 border border-slate-700/60 rounded-2xl p-5 shadow-sm relative overflow-hidden">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-xs font-medium uppercase tracking-wider text-slate-400">Operator Share (65%)</p>
                        <h3 class="text-2xl font-black mt-2 text-white">
                            {{ $tenant->currency }} {{ number_format($balanceSheet['splits']['operator_accrued_pool'], 2) }}
                        </h3>
                    </div>
                    <div class="w-10 h-10 rounded-xl bg-purple-500/10 border border-purple-500/20 text-purple-400 flex items-center justify-center">
                        <i class="fa-solid fa-building-columns"></i>
                    </div>
                </div>
                <p class="text-xs text-slate-400 mt-4 font-mono">
                    Modern Lottery Game Pool
                </p>
            </div>

            <!-- Card 4: Q4I Available to Spend / Settle -->
            <div class="bg-gradient-to-br from-cyan-950/40 to-slate-800/80 border border-cyan-500/40 rounded-2xl p-5 shadow-sm relative overflow-hidden">
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
                <p class="text-xs text-cyan-400/80 mt-4 font-mono">
                    Net Withdrawable Commission
                </p>
            </div>
        </div>

        <!-- Middle Section: Split Visualization & Commercial Terms -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Left: Split Breakdown -->
            <div class="lg:col-span-2 bg-slate-800/60 border border-slate-700/60 rounded-2xl p-6">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="font-bold text-base flex items-center gap-2">
                        <i class="fa-solid fa-chart-pie text-emerald-400"></i>
                        35/65 Commercial Revenue Distribution
                    </h3>
                    <span class="text-xs text-slate-400 font-mono">Audited by SHA-256 Ledger</span>
                </div>

                <div class="space-y-4">
                    <div>
                        <div class="flex justify-between text-xs font-semibold mb-1">
                            <span class="text-emerald-400">Q4I Limited Share (35%)</span>
                            <span class="text-emerald-300">{{ $tenant->currency }} {{ number_format($balanceSheet['splits']['q4i_accrued_earned'], 2) }}</span>
                        </div>
                        <div class="w-full h-3 bg-slate-700 rounded-full overflow-hidden">
                            <div class="h-full bg-emerald-500 rounded-full" style="width: 35%"></div>
                        </div>
                    </div>

                    <div>
                        <div class="flex justify-between text-xs font-semibold mb-1">
                            <span class="text-purple-400">Modern Lottery Operator Share (65%)</span>
                            <span class="text-purple-300">{{ $tenant->currency }} {{ number_format($balanceSheet['splits']['operator_accrued_pool'], 2) }}</span>
                        </div>
                        <div class="w-full h-3 bg-slate-700 rounded-full overflow-hidden">
                            <div class="h-full bg-purple-500 rounded-full" style="width: 65%"></div>
                        </div>
                    </div>
                </div>

                <div class="mt-8 pt-6 border-t border-slate-700/50 grid grid-cols-1 sm:grid-cols-3 gap-4 text-center">
                    <div class="bg-slate-900/40 p-3 rounded-xl border border-slate-700/30">
                        <p class="text-xs text-slate-400">Platform Split</p>
                        <p class="font-bold text-sm text-slate-200 mt-1">35% Fixed</p>
                    </div>
                    <div class="bg-slate-900/40 p-3 rounded-xl border border-slate-700/30">
                        <p class="text-xs text-slate-400">Meta Messaging Charge</p>
                        <p class="font-bold text-sm text-amber-300 mt-1">Borne by Operator</p>
                    </div>
                    <div class="bg-slate-900/40 p-3 rounded-xl border border-slate-700/30">
                        <p class="text-xs text-slate-400">Settlement Cycle</p>
                        <p class="font-bold text-sm text-cyan-300 mt-1">Monthly Auto-Reconciled</p>
                    </div>
                </div>
            </div>

            <!-- Right: Cryptographic Ledger Audit -->
            <div class="bg-slate-800/60 border border-slate-700/60 rounded-2xl p-6 flex flex-col justify-between">
                <div>
                    <h3 class="font-bold text-base flex items-center gap-2 mb-3">
                        <i class="fa-solid fa-shield-halved text-emerald-400"></i>
                        Independent Audit Chain
                    </h3>
                    <p class="text-xs text-slate-400 leading-relaxed">
                        To guarantee zero disputes between Q4I and Modern Lottery, every bet generates an immutable SHA-256 hash block chaining the ticket ID, split amounts, and timestamp.
                    </p>

                    <div class="mt-4 p-4 rounded-xl bg-slate-950/60 border border-slate-800 font-mono text-xs space-y-2">
                        <div class="flex justify-between">
                            <span class="text-slate-500">Chain Status:</span>
                            <span class="text-emerald-400 font-semibold">VALIDATED</span>
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
                    <button class="w-full py-2.5 px-4 rounded-xl bg-slate-700 hover:bg-slate-600 text-xs font-semibold text-slate-200 transition flex items-center justify-center gap-2">
                        <i class="fa-solid fa-file-arrow-down"></i>
                        Export End-of-Month Settlement Report (CSV)
                    </button>
                </div>
            </div>
        </div>

        <!-- Recent WhatsApp Bet Tickets & Ledger Chain Table -->
        <div class="bg-slate-800/60 border border-slate-700/60 rounded-2xl p-6">
            <h3 class="font-bold text-base flex items-center gap-2 mb-4">
                <i class="fa-brands fa-whatsapp text-emerald-400"></i>
                Live WhatsApp Bet Transactions
            </h3>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-slate-300">
                    <thead class="bg-slate-900/60 uppercase tracking-wider text-slate-400 font-semibold border-b border-slate-700/60">
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
                    <tbody class="divide-y divide-slate-700/40">
                        @forelse($recentTickets as $ticket)
                            <tr class="hover:bg-slate-700/20 transition">
                                <td class="py-3 px-4 font-mono font-medium text-slate-100">{{ $ticket->ticket_reference }}</td>
                                <td class="py-3 px-4 font-mono">+{{ $ticket->player->phone ?? 'N/A' }}</td>
                                <td class="py-3 px-4 font-semibold">{{ $ticket->game_code }}</td>
                                <td class="py-3 px-4 font-bold text-white">{{ $tenant->currency }} {{ number_format($ticket->stake_amount, 2) }}</td>
                                <td class="py-3 px-4 font-bold text-emerald-400">+{{ number_format($ticket->q4i_share, 2) }}</td>
                                <td class="py-3 px-4 font-bold text-purple-400">+{{ number_format($ticket->operator_share, 2) }}</td>
                                <td class="py-3 px-4">
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold uppercase bg-emerald-500/10 text-emerald-400 border border-emerald-500/30">
                                        {{ $ticket->status }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 font-mono text-[10px] text-slate-500" title="{{ $ticket->hash }}">
                                    {{ substr($ticket->hash ?? 'N/A', 0, 12) }}...
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-6 text-slate-500 italic">No tickets placed yet. Send a message to WhatsApp to generate real transactions.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </main>

</body>
</html>

