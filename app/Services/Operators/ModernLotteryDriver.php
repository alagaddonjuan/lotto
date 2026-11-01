<?php

namespace App\Services\Operators;

use App\Contracts\LottoOperatorContract;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ModernLotteryDriver implements LottoOperatorContract
{
    protected string $apiUrl;
    protected string $apiKey;

    public function __construct(array $config = [])
    {
        $this->apiUrl = $config['api_url'] ?? config('services.modern_lottery.url', 'https://api.modernlottery.ng/v1');
        $this->apiKey = $config['api_key'] ?? config('services.modern_lottery.key', 'mock_key');
    }

    public function getOperatorCode(): string
    {
        return 'modern_lottery';
    }

    public function checkWalletBalance(string $phone): array
    {
        // Mock response if in local testing or external API not configured
        if ($this->apiKey === 'mock_key' || app()->environment('local')) {
            return [
                'success' => true,
                'balance' => 4500.00,
                'currency' => 'NGN',
                'is_registered' => true,
                'message' => 'Wallet balance fetched successfully',
            ];
        }

        try {
            $response = Http::withToken($this->apiKey)
                ->timeout(5)
                ->get("{$this->apiUrl}/wallets/balance", ['phone' => $phone]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'balance' => (float)$response->json('data.balance', 0),
                    'currency' => (string)$response->json('data.currency', 'NGN'),
                    'is_registered' => true,
                ];
            }
        } catch (\Throwable $e) {
            Log::error('[ModernLotteryDriver] Wallet check failed: ' . $e->getMessage());
        }

        return [
            'success' => false,
            'balance' => 0.00,
            'currency' => 'NGN',
            'is_registered' => false,
            'message' => 'Unable to reach Modern Lottery wallet service.',
        ];
    }

    public function verifyPin(string $phone, string $pin): bool
    {
        // Mock verification for testing: PIN '1234' is valid
        if ($this->apiKey === 'mock_key' || app()->environment('local')) {
            return $pin === '1234';
        }

        try {
            $response = Http::withToken($this->apiKey)
                ->timeout(5)
                ->post("{$this->apiUrl}/auth/verify-pin", [
                    'phone' => $phone,
                    'pin' => $pin,
                ]);

            return $response->successful() && $response->json('data.valid', false) === true;
        } catch (\Throwable $e) {
            Log::error('[ModernLotteryDriver] PIN verification error: ' . $e->getMessage());
            return false;
        }
    }

    public function getActiveDraws(): array
    {
        return [
            [
                'game_code' => 'KENO_20_80',
                'name' => 'Keno 20/80',
                'min_stake' => 50,
                'max_stake' => 50000,
                'total_numbers' => 80,
                'pick_count' => 10,
                'next_draw' => now()->addMinutes(15)->format('H:i'),
            ],
            [
                'game_code' => '5_90_DAILY',
                'name' => 'Modern 5/90 Direct',
                'min_stake' => 100,
                'max_stake' => 100000,
                'total_numbers' => 90,
                'pick_count' => 5,
                'next_draw' => now()->addHours(2)->format('H:i'),
            ],
        ];
    }

    public function placeBet(string $phone, string $gameCode, array $numbers, float $stake, string $idempotencyKey): array
    {
        // In local/sandbox mode, generate a mock success ticket
        if ($this->apiKey === 'mock_key' || app()->environment('local')) {
            return [
                'success' => true,
                'ticket_id' => 'ML-' . strtoupper(Str::random(8)),
                'draw_id' => 'DRW-' . date('Ymd-Hi'),
                'message' => 'Bet accepted by Modern Lottery engine.',
                'balance_after' => 4500.00 - $stake,
            ];
        }

        try {
            $response = Http::withToken($this->apiKey)
                ->withHeaders(['X-Idempotency-Key' => $idempotencyKey])
                ->timeout(8)
                ->post("{$this->apiUrl}/bets/place", [
                    'phone' => $phone,
                    'game_code' => $gameCode,
                    'numbers' => $numbers,
                    'stake' => $stake,
                ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'ticket_id' => (string)$response->json('data.ticket_id'),
                    'draw_id' => (string)$response->json('data.draw_id'),
                    'message' => 'Bet successfully placed.',
                    'balance_after' => (float)$response->json('data.balance_after'),
                ];
            }

            return [
                'success' => false,
                'message' => $response->json('message', 'Modern Lottery rejected the bet placement.'),
            ];
        } catch (\Throwable $e) {
            Log::error('[ModernLotteryDriver] Bet placement API failed: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Partner API timeout. Please verify your balance before retrying.',
            ];
        }
    }

    public function getResults(string $gameCode, ?string $drawDate = null): array
    {
        return [
            'game_code' => $gameCode,
            'draw_date' => $drawDate ?: now()->format('Y-m-d'),
            'winning_numbers' => [7, 14, 21, 35, 42, 60, 77],
            'jackpot_amount' => 5000000.00,
        ];
    }
}

