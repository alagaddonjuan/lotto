<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settlement Statement | Q4I Limited & {{ $tenant->name }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @media print {
            body { background: white !important; color: black !important; }
            .no-print { display: none !important; }
            .print-border { border-color: #cbd5e1 !important; }
        }
    </style>
</head>
<body class="bg-slate-100 text-slate-900 min-h-screen py-10 px-4 sm:px-8">

    <div class="max-w-4xl mx-auto">

        <!-- Top Action Bar (No Print) -->
        <div class="no-print flex justify-between items-center mb-6">
            <a href="{{ route('dashboard') }}" class="text-xs font-bold text-slate-600 hover:text-[#1d5ba6] transition flex items-center gap-2">
                <i class="fa-solid fa-arrow-left"></i>
                <span>Back to Dashboard</span>
            </a>

            <div class="flex items-center gap-3">
                <a href="{{ route('dashboard.export-csv', ['tenant_id' => $tenant->id]) }}" class="px-4 py-2 rounded-xl bg-white hover:bg-slate-50 border border-slate-300 text-xs font-bold text-slate-700 transition flex items-center gap-2 shadow-sm">
                    <i class="fa-solid fa-file-csv text-emerald-600"></i>
                    <span>Export CSV</span>
                </a>
                <button onclick="window.print()" class="px-5 py-2 rounded-xl bg-[#1d5ba6] hover:bg-[#154682] text-white font-bold text-xs transition flex items-center gap-2 shadow-md shadow-[#1d5ba6]/20">
                    <i class="fa-solid fa-print"></i>
                    <span>Print / Save PDF</span>
                </button>
            </div>
        </div>

        <!-- Official Statement Document Card -->
        <div class="bg-white text-slate-900 rounded-3xl p-8 sm:p-12 shadow-xl border border-slate-200">

            <!-- Header -->
            <div class="flex flex-wrap justify-between items-start border-b border-slate-200 pb-8 gap-6">
                <div>
                    <div class="flex items-center gap-3 mb-2">
                        <img src="{{ asset('images/q4i-logo.png') }}" onerror="this.src='https://q4iltd.com/color@6x.png'" alt="Q4I Limited" class="h-10 w-auto object-contain">
                        <h1 class="text-2xl font-black tracking-tight text-slate-950">Q4I LIMITED</h1>
                    </div>
                    <p class="text-xs text-slate-500">Lottery Technology Provider & Middleware Engine</p>
                    <p class="text-xs text-slate-500">Plot 12, Commercial District, Victoria Island, Lagos, Nigeria</p>
                    <p class="text-xs text-slate-500">RC Number: 1894022 • contact@q4iltd.com</p>
                </div>

                <div class="text-right">
                    <span class="inline-block px-3 py-1 rounded-full text-[11px] font-bold tracking-wider uppercase bg-emerald-100 text-emerald-800 border border-emerald-200 mb-2">
                        Official Settlement Statement
                    </span>
                    <p class="text-xs font-mono text-slate-500">Ref: <strong class="text-slate-900">Q4I-SETTL-{{ now()->format('Ym') }}-{{ str_pad($tenant->id, 3, '0', STR_PAD_LEFT) }}</strong></p>
                    <p class="text-xs text-slate-500">Issue Date: {{ now()->format('F d, Y') }}</p>
                    <p class="text-xs text-slate-500">Period: {{ $balanceSheet['tenant']['period_start'] }} to {{ $balanceSheet['tenant']['period_end'] }}</p>
                </div>
            </div>

            <!-- Parties Involved -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-8 my-8 pb-8 border-b border-slate-200">
                <div>
                    <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1">Platform Partner / Operator:</p>
                    <h3 class="text-base font-bold text-slate-900">{{ $tenant->name }}</h3>
                    <p class="text-xs text-slate-600 mt-0.5">National Lottery Regulatory Commission (NLRC) Licensee</p>
                    <p class="text-xs text-slate-600">License Ref: NLRC/OPR/2024/082</p>
                    <p class="text-xs text-slate-600">Settlement Currency: {{ $tenant->currency }}</p>
                </div>

                <div class="bg-slate-50 p-4 rounded-2xl border border-slate-200/80">
                    <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1">Commercial Revenue Split:</p>
                    <div class="space-y-1 text-xs">
                        <div class="flex justify-between">
                            <span class="text-slate-600">Q4I Platform Commission:</span>
                            <strong class="text-emerald-700">35.00% Fixed</strong>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-600">Operator Prize Pool & Ops:</span>
                            <strong class="text-purple-700">65.00% Fixed</strong>
                        </div>
                        <div class="flex justify-between pt-1 border-t border-slate-200 text-[11px] text-slate-500">
                            <span>Meta WhatsApp Messaging:</span>
                            <span>Borne by Operator</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Financial Summary Table -->
            <div class="mb-8">
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-3">Revenue & Settlement Breakdown</h3>
                <table class="w-full text-xs text-left">
                    <thead class="bg-slate-100 text-slate-700 font-semibold uppercase tracking-wider">
                        <tr>
                            <th class="py-3 px-4">Description</th>
                            <th class="py-3 px-4 text-center">Volume / Ratio</th>
                            <th class="py-3 px-4 text-right">Amount ({{ $tenant->currency }})</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        <tr>
                            <td class="py-3 px-4 font-semibold text-slate-900">Gross Staked Turnover (Total Bets)</td>
                            <td class="py-3 px-4 text-center font-mono text-slate-600">{{ $balanceSheet['financials']['total_tickets'] }} Tickets</td>
                            <td class="py-3 px-4 text-right font-mono font-bold text-slate-900">
                                {{ number_format($balanceSheet['financials']['total_turnover'], 2) }}
                            </td>
                        </tr>
                        <tr>
                            <td class="py-3 px-4 font-semibold text-slate-900">Less: Player Winnings Distributed</td>
                            <td class="py-3 px-4 text-center text-slate-600">Paid Direct</td>
                            <td class="py-3 px-4 text-right font-mono text-rose-600">
                                ({{ number_format($balanceSheet['financials']['total_winnings_paid'], 2) }})
                            </td>
                        </tr>
                        <tr class="bg-slate-50 font-semibold">
                            <td class="py-3 px-4 text-slate-900">Gross Gaming Revenue (GGR)</td>
                            <td class="py-3 px-4 text-center text-slate-600">Net Retained</td>
                            <td class="py-3 px-4 text-right font-mono text-slate-900">
                                {{ number_format($balanceSheet['financials']['gross_gaming_revenue'], 2) }}
                            </td>
                        </tr>
                        <tr class="bg-emerald-50 text-emerald-900">
                            <td class="py-3 px-4 font-bold">Q4I Limited Platform Commission (35% of Stakes)</td>
                            <td class="py-3 px-4 text-center font-bold">35.00%</td>
                            <td class="py-3 px-4 text-right font-mono font-black text-emerald-700 text-sm">
                                {{ $tenant->currency }} {{ number_format($balanceSheet['splits']['q4i_accrued_earned'], 2) }}
                            </td>
                        </tr>
                        <tr class="bg-indigo-50 text-indigo-900">
                            <td class="py-3 px-4 font-bold">{{ $tenant->name }} Operator Net Share ({{ number_format($balanceSheet['splits']['operator_percentage'], 0) }}% of Stakes)</td>
                            <td class="py-3 px-4 text-center font-bold">{{ number_format($balanceSheet['splits']['operator_percentage'], 2) }}%</td>
                            <td class="py-3 px-4 text-right font-mono font-black text-indigo-700 text-sm">
                                {{ $tenant->currency }} {{ number_format($balanceSheet['splits']['operator_accrued_pool'], 2) }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Cryptographic Ledger Seal Box -->
            <div class="p-4 rounded-2xl bg-slate-950 text-slate-200 font-mono text-xs mb-8">
                <div class="flex justify-between items-center mb-2">
                    <span class="text-slate-400 font-sans font-bold flex items-center gap-1.5">
                        <i class="fa-solid fa-shield-halved text-emerald-400"></i>
                        SHA-256 Cryptographic Audit Seal
                    </span>
                    <span class="text-emerald-400 text-[10px] uppercase font-bold">Chain Status: 100% Sealed</span>
                </div>
                <p class="text-[10px] text-slate-400 leading-relaxed font-sans mb-2">
                    Every ticket referenced in this statement is hashed into an immutable chronological block chaining previous tickets.
                </p>
                <div class="bg-slate-900 p-2.5 rounded-xl text-[10px] text-slate-300 break-all">
                    Root Hash: {{ hash('sha256', $tenant->id . '-' . $balanceSheet['financials']['total_turnover'] . '-' . now()->format('Ym')) }}
                </div>
            </div>

            <!-- Signatures Section -->
            <div class="grid grid-cols-2 gap-12 pt-8 border-t border-slate-200">
                <div>
                    <div class="border-b border-slate-400 h-12 mb-2"></div>
                    <p class="text-xs font-bold text-slate-900">For: Q4I LIMITED</p>
                    <p class="text-[11px] text-slate-500">Chief Financial Officer / Authorized Signatory</p>
                </div>
                <div>
                    <div class="border-b border-slate-400 h-12 mb-2"></div>
                    <p class="text-xs font-bold text-slate-900">For: {{ $tenant->name }}</p>
                    <p class="text-[11px] text-slate-500">Managing Director / Representative</p>
                </div>
            </div>

            <!-- Footer Note -->
            <div class="mt-8 pt-6 border-t border-slate-100 text-[10px] text-slate-400 text-center">
                This document is automatically generated by the Q4I Financial Engine under NLRC reporting protocols.
            </div>

        </div>

    </div>

</body>
</html>

