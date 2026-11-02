<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Jobs\SettleDrawTicketsJob;
use App\Models\GameDraw;
use App\Models\LottoGame;
use App\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class DrawManagementController extends Controller
{
    /**
     * Display live draws and operations monitoring.
     */
    public function index(Request $request): View
    {
        $tenantId = Auth::user()->isSuperAdmin() ? $request->query('tenant_id') : Auth::user()->tenant_id;

        $drawsQuery = GameDraw::with(['lottoGame', 'enteredBy', 'approvedBy', 'betTickets'])
            ->orderByDesc('id');

        if ($tenantId) {
            $drawsQuery->where('tenant_id', $tenantId);
        }

        $draws = $drawsQuery->paginate(15);
        $activeGames = LottoGame::where('is_active', true)->get();

        return view('dashboard.draws.index', compact('draws', 'activeGames'));
    }

    /**
     * Schedule a new GameDraw.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'lotto_game_id' => ['required', 'exists:lotto_games,id'],
            'draw_code' => ['required', 'string', 'max:64', 'unique:game_draws,draw_code'],
            'opens_at' => ['required', 'date'],
            'closes_at' => ['required', 'date', 'after:opens_at'],
        ]);

        $game = LottoGame::findOrFail($validated['lotto_game_id']);

        GameDraw::create([
            'tenant_id' => $game->tenant_id,
            'lotto_game_id' => $game->id,
            'draw_code' => $validated['draw_code'],
            'status' => GameDraw::STATUS_OPEN,
            'opens_at' => $validated['opens_at'],
            'closes_at' => $validated['closes_at'],
        ]);

        return back()->with('success', "Draw {$validated['draw_code']} created and opened for betting.");
    }

    /**
     * Maker: Enter winning numbers for a LOCKED draw.
     */
    public function enterResults(Request $request, GameDraw $draw): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'winning_numbers' => ['required', 'array', 'min:1'],
            'winning_numbers.*' => ['integer', 'min:1', 'max:90'],
        ]);

        if (!in_array($draw->status, [GameDraw::STATUS_LOCKED, GameDraw::STATUS_RESULT_PENDING])) {
            throw ValidationException::withMessages([
                'draw' => "Draw must be in LOCKED status before entering results. Current status: {$draw->status}",
            ]);
        }

        $numbers = array_values(array_unique(array_map('intval', $validated['winning_numbers'])));
        sort($numbers);

        $draw->update([
            'winning_numbers' => $numbers,
            'result_entered_by' => Auth::id(),
            'status' => GameDraw::STATUS_RESULT_PENDING,
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Results submitted. Awaiting second officer approval (Maker-Checker).',
                'draw' => $draw,
            ]);
        }

        return back()->with('success', 'Winning numbers submitted. Awaiting independent supervisor approval (Maker-Checker).');
    }

    /**
     * Checker: Approve winning numbers and trigger automated financial settlement.
     * Enforces Maker-Checker segregation of duties.
     */
    public function approveResults(Request $request, GameDraw $draw): RedirectResponse|JsonResponse
    {
        if ($draw->status !== GameDraw::STATUS_RESULT_PENDING) {
            throw ValidationException::withMessages([
                'draw' => "Cannot approve draw results: Draw is not in RESULT_PENDING status.",
            ]);
        }

        $currentUserId = Auth::id();

        // STRICT MAKER-CHECKER ENFORCEMENT
        if ($draw->result_entered_by === $currentUserId) {
            abort(403, 'Maker-Checker Segregation Policy Violation: The officer who entered draw results cannot approve them. A second independent supervisor must review and verify.');
        }

        $draw->update([
            'result_approved_by' => $currentUserId,
        ]);

        // Dispatch automated financial payout and settlement
        if (config('queue.default') === 'sync') {
            SettleDrawTicketsJob::dispatchSync($draw);
        } else {
            SettleDrawTicketsJob::dispatch($draw);
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Draw results approved and financial settlement executed.',
                'draw' => $draw->fresh(),
            ]);
        }

        return back()->with('success', "Draw {$draw->draw_code} approved! Payout calculations and wallet settlements executed.");
    }
}

