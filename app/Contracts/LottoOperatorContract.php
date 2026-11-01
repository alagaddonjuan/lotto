<?php

namespace App\Contracts;

interface LottoOperatorContract
{
    /**
     * Get unique identifier for this operator driver.
     */
    public function getOperatorCode(): string;

    /**
     * Check if a player exists and retrieve their wallet balance & status.
     * 
     * @return array{success: bool, balance: float, currency: string, is_registered: bool, message?: string}
     */
    public function checkWalletBalance(string $phone): array;

    /**
     * Verify the player's 4-digit security PIN.
     */
    public function verifyPin(string $phone, string $pin): bool;

    /**
     * Fetch active lottery draws / games available right now.
     */
    public function getActiveDraws(): array;

    /**
     * Commit a real bet placement on the operator's engine with strict idempotency.
     *
     * @param string $phone Player's phone number
     * @param string $gameCode e.g. "KENO_20_80", "5_90", etc.
     * @param array $numbers Selected numbers (e.g. [5, 12, 33])
     * @param float $stake Amount in local currency (e.g. NGN 200)
     * @param string $idempotencyKey Unique UUIDv4 key to prevent double-billing
     * @return array{success: bool, ticket_id?: string, draw_id?: string, message: string, balance_after?: float}
     */
    public function placeBet(string $phone, string $gameCode, array $numbers, float $stake, string $idempotencyKey): array;

    /**
     * Credit a player's wallet from automated deposit (e.g. DVA transfer).
     */
    public function creditWallet(string $phone, float $amount, string $reference): array;

    /**
     * Fetch latest winning results for a specific game or draw.
     */
    public function getResults(string $gameCode, ?string $drawDate = null): array;
}

