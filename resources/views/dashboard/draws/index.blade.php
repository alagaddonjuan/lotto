<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Draw Lifecycle & Maker-Checker Operations | Q4I Financial Engine</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-slate-950 text-slate-100 min-h-screen font-sans antialiased flex flex-col">

    <!-- Header -->
    <header class="border-b border-slate-800 bg-slate-950/95 backdrop-blur sticky top-0 z-40 px-6 py-4 flex flex-wrap justify-between items-center gap-4">
        <div class="flex items-center gap-3.5">
            <a href="{{ route('dashboard') }}" class="w-10 h-10 rounded-2xl bg-slate-900 border border-slate-800 hover:border-slate-700 flex items-center justify-center text-slate-300 transition">
                <i class="fa-solid fa-arrow-left"></i>
            </a>
            <div>
                <h1 class="font-bold text-base text-white tracking-tight leading-none flex items-center gap-2">
                    <span>Draw Lifecycle & Operations Engine</span>
                    <span class="text-[10px] bg-emerald-500/10 text-emerald-400 border border-emerald-500/30 px-2 py-0.5 rounded-full font-mono">Maker-Checker Active</span>
                </h1>
                <p class="text-xs text-slate-400 mt-1">Multi-Operator Draw Cut-Off, Segregation of Duties & Automated Payout Engine</p>
            </div>
        </div>

        <div class="flex items-center gap-3">
            <span class="text-xs text-slate-400 font-mono">Logged in as: <strong class="text-white">{{ auth()->user()->name }}</strong> ({{ auth()->user()->role }})</span>
            <a href="{{ route('dashboard') }}" class="px-3.5 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 border border-slate-800 text-xs font-semibold text-slate-200 transition">
                Back to Financial Dashboard
            </a>
        </div>
    </header>

    <main class="max-w-7xl mx-auto px-6 py-8 space-y-8 flex-1 w-full">

        @if(session('success'))
            <div class="p-4 rounded-2xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 text-xs flex items-center gap-2">
                <i class="fa-solid fa-circle-check"></i>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        @if($errors->any())
            <div class="p-4 rounded-2xl bg-rose-500/10 border border-rose-500/30 text-rose-400 text-xs flex items-center gap-2">
                <i class="fa-solid fa-circle-exclamation"></i>
                <span>{{ $errors->first() }}</span>
            </div>
        @endif

        <!-- Controls: Schedule New Draw -->
        <div class="bg-slate-900/70 border border-slate-800 rounded-2xl p-6 shadow-sm">
            <h2 class="text-sm font-bold text-white flex items-center gap-2 mb-4">
                <i class="fa-regular fa-calendar-plus text-emerald-400"></i>
                Schedule & Open New Draw
            </h2>
            <form method="POST" action="{{ route('dashboard.draws.store') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                @csrf
                <div>
                    <label class="block text-[11px] font-semibold text-slate-400 uppercase mb-1">Game</label>
                    <select name="lotto_game_id" required class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white focus:border-emerald-500 focus:outline-none">
                        @foreach($activeGames as $game)
                            <option value="{{ $game->id }}">{{ $game->name }} ({{ $game->game_code }})</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-[11px] font-semibold text-slate-400 uppercase mb-1">Draw Code</label>
                    <input type="text" name="draw_code" required value="M590-{{ date('Ymd') }}-EVE" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white font-mono focus:border-emerald-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-[11px] font-semibold text-slate-400 uppercase mb-1">Opens At</label>
                    <input type="datetime-local" name="opens_at" required value="{{ now()->format('Y-m-d\TH:i') }}" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white focus:border-emerald-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-[11px] font-semibold text-slate-400 uppercase mb-1">Closes At (Cut-off)</label>
                    <div class="flex gap-2">
                        <input type="datetime-local" name="closes_at" required value="{{ now()->addHours(2)->format('Y-m-d\TH:i') }}" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white focus:border-emerald-500 focus:outline-none">
                        <button type="submit" class="px-4 py-2 bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-bold rounded-xl text-xs whitespace-nowrap transition">
                            Create Draw
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Draws Table -->
        <div class="bg-slate-900/70 border border-slate-800 rounded-2xl overflow-hidden shadow-sm">
            <div class="p-6 border-b border-slate-800 flex justify-between items-center">
                <h3 class="text-sm font-bold text-white flex items-center gap-2">
                    <i class="fa-solid fa-clock-rotate-left text-cyan-400"></i>
                    Scheduled, Active & Settled Game Draws
                </h3>
                <span class="text-xs text-slate-400 font-mono">Auto Cut-Off: T-60s before closes_at</span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-950/60 border-b border-slate-800 text-slate-400 uppercase font-mono">
                        <tr>
                            <th class="px-6 py-4">Draw Code</th>
                            <th class="px-6 py-4">Game</th>
                            <th class="px-6 py-4">Status</th>
                            <th class="px-6 py-4">Cut-off (Closes At)</th>
                            <th class="px-6 py-4">Winning Numbers</th>
                            <th class="px-6 py-4">Maker (Entered By)</th>
                            <th class="px-6 py-4">Checker (Approved By)</th>
                            <th class="px-6 py-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60">
                        @forelse($draws as $draw)
                            <tr class="hover:bg-slate-800/30 transition">
                                <td class="px-6 py-4 font-mono font-bold text-white">{{ $draw->draw_code }}</td>
                                <td class="px-6 py-4 text-slate-300">{{ $draw->lottoGame->name ?? $draw->lotto_game_id }}</td>
                                <td class="px-6 py-4">
                                    @php
                                        $badgeColor = match($draw->status) {
                                            'OPEN' => 'bg-emerald-500/10 text-emerald-400 border-emerald-500/30',
                                            'LOCKED' => 'bg-amber-500/10 text-amber-400 border-amber-500/30',
                                            'RESULT_PENDING' => 'bg-cyan-500/10 text-cyan-400 border-cyan-500/30',
                                            'SETTLED' => 'bg-purple-500/10 text-purple-400 border-purple-500/30',
                                            default => 'bg-slate-800 text-slate-400 border-slate-700',
                                        };
                                    @endphp
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-mono font-bold border {{ $badgeColor }}">
                                        {{ $draw->status }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 font-mono text-slate-300">
                                    {{ $draw->closes_at ? $draw->closes_at->format('Y-m-d H:i') : 'N/A' }}
                                    @if($draw->isOpen())
                                        <span class="text-[10px] text-emerald-400 block">Closes in {{ now()->diffForHumans($draw->closes_at, true) }}</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 font-mono font-bold text-emerald-400">
                                    {{ $draw->winning_numbers ? implode(' - ', $draw->winning_numbers) : '—' }}
                                </td>
                                <td class="px-6 py-4 text-slate-400">
                                    {{ $draw->enteredBy->name ?? '—' }}
                                </td>
                                <td class="px-6 py-4 text-slate-400">
                                    {{ $draw->approvedBy->name ?? '—' }}
                                </td>
                                <td class="px-6 py-4 text-right">
                                    @if(in_array($draw->status, ['LOCKED', 'RESULT_PENDING']))
                                        @if($draw->status === 'LOCKED')
                                            <!-- Maker Form -->
                                            <form method="POST" action="{{ route('dashboard.draws.enter-results', $draw->id) }}" class="inline-flex gap-1 items-center">
                                                @csrf
                                                <input type="text" name="winning_numbers[]" placeholder="B1" required class="w-9 px-1 py-1 bg-slate-950 border border-slate-700 rounded text-center text-xs font-mono text-white">
                                                <input type="text" name="winning_numbers[]" placeholder="B2" required class="w-9 px-1 py-1 bg-slate-950 border border-slate-700 rounded text-center text-xs font-mono text-white">
                                                <input type="text" name="winning_numbers[]" placeholder="B3" required class="w-9 px-1 py-1 bg-slate-950 border border-slate-700 rounded text-center text-xs font-mono text-white">
                                                <input type="text" name="winning_numbers[]" placeholder="B4" required class="w-9 px-1 py-1 bg-slate-950 border border-slate-700 rounded text-center text-xs font-mono text-white">
                                                <input type="text" name="winning_numbers[]" placeholder="B5" required class="w-9 px-1 py-1 bg-slate-950 border border-slate-700 rounded text-center text-xs font-mono text-white">
                                                <button type="submit" class="px-2.5 py-1 bg-cyan-500 hover:bg-cyan-400 text-slate-950 font-bold rounded text-xs ml-1">
                                                    Submit (Maker)
                                                </button>
                                            </form>
                                        @elseif($draw->status === 'RESULT_PENDING')
                                            <!-- Checker Form -->
                                            @if($draw->result_entered_by === auth()->id())
                                                <span class="text-[10px] text-amber-400 italic">Waiting 2nd Officer (Maker-Checker Rule)</span>
                                            @else
                                                <form method="POST" action="{{ route('dashboard.draws.approve-results', $draw->id) }}" class="inline-block">
                                                    @csrf
                                                    <button type="submit" class="px-3 py-1.5 bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-bold rounded-lg text-xs">
                                                        <i class="fa-solid fa-check-double mr-1"></i> Approve & Settle (Checker)
                                                    </button>
                                                </form>
                                            @endif
                                        @endif
                                    @elseif($draw->status === 'SETTLED')
                                        <span class="text-xs text-purple-400 font-mono flex items-center justify-end gap-1">
                                            <i class="fa-solid fa-check"></i> Settled
                                        </span>
                                    @else
                                        <span class="text-xs text-slate-500 italic">Betting Open</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-12 text-slate-500 italic">No draws recorded yet. Use the form above to schedule your first draw.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($draws->hasPages())
                <div class="p-4 border-t border-slate-800">
                    {{ $draws->links() }}
                </div>
            @endif
        </div>

    </main>

</body>
</html>

