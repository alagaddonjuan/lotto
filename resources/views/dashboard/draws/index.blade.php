<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Draw Lifecycle &amp; Maker-Checker Operations | Q4I Financial Engine</title>
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
<body class="bg-slate-50 text-slate-900 min-h-screen antialiased flex flex-col">

    <!-- Enterprise Top Bar -->
    <header class="border-b border-slate-200 bg-white sticky top-0 z-40 px-6 py-4 flex flex-wrap justify-between items-center gap-4 shadow-sm">
        <div class="flex items-center gap-4">
            <a href="{{ route('dashboard') }}" class="flex items-center gap-3 pr-4 border-r border-slate-200">
                <img src="{{ asset('images/q4i-logo.png') }}" onerror="this.src='https://q4iltd.com/color@6x.png'" alt="Q4 International" class="h-9 w-auto object-contain">
            </a>
            <div>
                <h1 class="font-extrabold text-base text-slate-900 tracking-tight leading-none flex items-center gap-2">
                    <span>Draw Lifecycle &amp; Operations Engine</span>
                    <span class="text-[10px] bg-blue-50 text-[#1d5ba6] border border-blue-200 px-2.5 py-0.5 rounded-full font-mono font-bold">Maker-Checker Active</span>
                </h1>
                <p class="text-xs text-slate-500 mt-1">Multi-Operator Draw Cut-Off, Segregation of Duties &amp; Automated Payout Engine</p>
            </div>
        </div>

        <div class="flex items-center gap-3">
            <span class="text-xs text-slate-500 font-mono">Logged in as: <strong class="text-slate-900">{{ auth()->user()->name }}</strong> ({{ auth()->user()->role }})</span>
            <a href="{{ route('dashboard') }}" class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 border border-slate-200 text-xs font-bold text-slate-800 transition flex items-center gap-2">
                <i class="fa-solid fa-arrow-left text-xs"></i>
                <span>Back to Financial Dashboard</span>
            </a>
        </div>
    </header>

    <main class="max-w-7xl mx-auto px-6 py-8 space-y-8 flex-1 w-full">

        @if(session('success'))
            <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-semibold flex items-center gap-2">
                <i class="fa-solid fa-circle-check text-emerald-600"></i>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        @if($errors->any())
            <div class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 text-xs font-semibold flex items-center gap-2">
                <i class="fa-solid fa-circle-exclamation text-rose-600"></i>
                <span>{{ $errors->first() }}</span>
            </div>
        @endif

        <!-- Controls: Schedule New Draw -->
        <div class="bg-white border border-slate-200/90 rounded-2xl p-6 shadow-sm">
            <h2 class="text-sm font-bold text-slate-900 flex items-center gap-2 mb-4">
                <i class="fa-regular fa-calendar-plus text-[#1d5ba6]"></i>
                Schedule &amp; Open New Draw
            </h2>
            <form method="POST" action="{{ route('dashboard.draws.store') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                @csrf
                <div>
                    <label class="block text-[11px] font-bold text-slate-600 uppercase mb-1.5">Game</label>
                    <select name="lotto_game_id" required class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3 py-2.5 text-xs text-slate-900 focus:bg-white focus:border-[#1d5ba6] focus:outline-none">
                        @foreach($activeGames as $game)
                            <option value="{{ $game->id }}">{{ $game->name }} ({{ $game->game_code }})</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-slate-600 uppercase mb-1.5">Draw Code</label>
                    <input type="text" name="draw_code" required value="M590-{{ date('Ymd') }}-EVE" class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3 py-2.5 text-xs text-slate-900 font-mono focus:bg-white focus:border-[#1d5ba6] focus:outline-none">
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-slate-600 uppercase mb-1.5">Opens At</label>
                    <input type="datetime-local" name="opens_at" required value="{{ now()->format('Y-m-d\TH:i') }}" class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3 py-2.5 text-xs text-slate-900 focus:bg-white focus:border-[#1d5ba6] focus:outline-none">
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-slate-600 uppercase mb-1.5">Closes At (Cut-off)</label>
                    <div class="flex gap-2">
                        <input type="datetime-local" name="closes_at" required value="{{ now()->addHours(2)->format('Y-m-d\TH:i') }}" class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3 py-2.5 text-xs text-slate-900 focus:bg-white focus:border-[#1d5ba6] focus:outline-none">
                        <button type="submit" class="px-4 py-2.5 bg-[#1d5ba6] hover:bg-[#154682] text-white font-bold rounded-xl text-xs whitespace-nowrap transition shadow-sm">
                            Create Draw
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Draws Table -->
        <div class="bg-white border border-slate-200/90 rounded-2xl overflow-hidden shadow-sm">
            <div class="p-6 border-b border-slate-200 flex justify-between items-center">
                <h3 class="text-sm font-bold text-slate-900 flex items-center gap-2">
                    <i class="fa-solid fa-clock-rotate-left text-[#1d5ba6]"></i>
                    Scheduled, Active &amp; Settled Game Draws
                </h3>
                <span class="text-xs text-slate-500 font-mono">Auto Cut-Off: T-60s before closes_at</span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-50 border-b border-slate-200 text-slate-500 uppercase font-mono">
                        <tr>
                            <th class="px-6 py-3.5">Draw Code</th>
                            <th class="px-6 py-3.5">Game</th>
                            <th class="px-6 py-3.5">Status</th>
                            <th class="px-6 py-3.5">Cut-off (Closes At)</th>
                            <th class="px-6 py-3.5">Winning Numbers</th>
                            <th class="px-6 py-3.5">Maker (Entered By)</th>
                            <th class="px-6 py-3.5">Checker (Approved By)</th>
                            <th class="px-6 py-3.5 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200/70">
                        @forelse($draws as $draw)
                            <tr class="hover:bg-slate-50/80 transition">
                                <td class="px-6 py-4 font-mono font-bold text-slate-900">{{ $draw->draw_code }}</td>
                                <td class="px-6 py-4 font-semibold text-slate-700">{{ $draw->lottoGame->name ?? $draw->lotto_game_id }}</td>
                                <td class="px-6 py-4">
                                    @php
                                        $badgeColor = match($draw->status) {
                                            'OPEN' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                            'LOCKED' => 'bg-amber-50 text-amber-700 border-amber-200',
                                            'RESULT_PENDING' => 'bg-blue-50 text-[#1d5ba6] border-blue-200',
                                            'SETTLED' => 'bg-indigo-50 text-indigo-700 border-indigo-200',
                                            default => 'bg-slate-100 text-slate-600 border-slate-200',
                                        };
                                    @endphp
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-mono font-bold border {{ $badgeColor }}">
                                        {{ $draw->status }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 font-mono text-slate-700">
                                    {{ $draw->closes_at ? $draw->closes_at->format('Y-m-d H:i') : 'N/A' }}
                                    @if($draw->isOpen())
                                        <span class="text-[10px] text-emerald-700 font-semibold block">Closes in {{ now()->diffForHumans($draw->closes_at, true) }}</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 font-mono font-bold text-[#1d5ba6]">
                                    {{ $draw->winning_numbers ? implode(' - ', $draw->winning_numbers) : '—' }}
                                </td>
                                <td class="px-6 py-4 text-slate-600">
                                    {{ $draw->enteredBy->name ?? '—' }}
                                </td>
                                <td class="px-6 py-4 text-slate-600">
                                    {{ $draw->approvedBy->name ?? '—' }}
                                </td>
                                <td class="px-6 py-4 text-right">
                                    @if(in_array($draw->status, ['LOCKED', 'RESULT_PENDING']))
                                        @if($draw->status === 'LOCKED')
                                            <form method="POST" action="{{ route('dashboard.draws.enter-results', $draw->id) }}" class="inline-flex gap-1 items-center">
                                                @csrf
                                                <input type="text" name="winning_numbers[]" placeholder="B1" required class="w-9 px-1 py-1 bg-white border border-slate-300 rounded text-center text-xs font-mono text-slate-900">
                                                <input type="text" name="winning_numbers[]" placeholder="B2" required class="w-9 px-1 py-1 bg-white border border-slate-300 rounded text-center text-xs font-mono text-slate-900">
                                                <input type="text" name="winning_numbers[]" placeholder="B3" required class="w-9 px-1 py-1 bg-white border border-slate-300 rounded text-center text-xs font-mono text-slate-900">
                                                <input type="text" name="winning_numbers[]" placeholder="B4" required class="w-9 px-1 py-1 bg-white border border-slate-300 rounded text-center text-xs font-mono text-slate-900">
                                                <input type="text" name="winning_numbers[]" placeholder="B5" required class="w-9 px-1 py-1 bg-white border border-slate-300 rounded text-center text-xs font-mono text-slate-900">
                                                <button type="submit" class="px-2.5 py-1 bg-[#1d5ba6] hover:bg-[#154682] text-white font-bold rounded text-xs ml-1">
                                                    Submit (Maker)
                                                </button>
                                            </form>
                                        @elseif($draw->status === 'RESULT_PENDING')
                                            @if($draw->result_entered_by === auth()->id())
                                                <span class="text-[10px] text-amber-700 font-semibold italic">Waiting 2nd Officer (Maker-Checker Rule)</span>
                                            @else
                                                <form method="POST" action="{{ route('dashboard.draws.approve-results', $draw->id) }}" class="inline-block">
                                                    @csrf
                                                    <button type="submit" class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-lg text-xs">
                                                        <i class="fa-solid fa-check-double mr-1"></i> Approve &amp; Settle (Checker)
                                                    </button>
                                                </form>
                                            @endif
                                        @endif
                                    @elseif($draw->status === 'SETTLED')
                                        <span class="text-xs text-indigo-700 font-mono font-bold flex items-center justify-end gap-1">
                                            <i class="fa-solid fa-check"></i> Settled
                                        </span>
                                    @else
                                        <span class="text-xs text-slate-400 italic">Betting Open</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-12 text-slate-400 italic">No draws recorded yet. Use the form above to schedule your first draw.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($draws->hasPages())
                <div class="p-4 border-t border-slate-200">
                    {{ $draws->links() }}
                </div>
            @endif
        </div>

    </main>

</body>
</html>
