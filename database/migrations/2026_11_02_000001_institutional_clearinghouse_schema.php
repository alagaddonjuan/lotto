<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Update players table with Dual-Wallet Kobo & Withdrawal PIN
        Schema::table('players', function (Blueprint $table) {
            $table->unsignedBigInteger('cash_balance_kobo')->default(0)->after('name');
            $table->unsignedBigInteger('bonus_balance_kobo')->default(0)->after('cash_balance_kobo');
            $table->string('withdrawal_pin_hash', 255)->nullable()->after('pin_hash');
        });

        // 2. Create game_draws table for scheduled draw lifecycle & maker-checker
        Schema::create('game_draws', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('lotto_game_id')->constrained('lotto_games')->cascadeOnDelete();
            $table->string('draw_code', 64)->unique();
            $table->string('status', 32)->default('SCHEDULED'); // 'SCHEDULED', 'OPEN', 'LOCKED', 'RESULT_PENDING', 'SETTLED', 'VOID'
            $table->timestamp('opens_at')->nullable();
            $table->timestamp('closes_at')->nullable();
            $table->json('winning_numbers')->nullable();
            $table->foreignId('result_entered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('result_approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('settled_at')->nullable();
            $table->timestamps();

            $table->index(['lotto_game_id', 'status', 'closes_at']);
            $table->index(['tenant_id', 'status']);
        });

        // 3. Update bet_tickets table for Draw Binding, Dual-Wallet isolation, and Kobo calculations
        Schema::table('bet_tickets', function (Blueprint $table) {
            $table->foreignId('game_draw_id')->nullable()->after('player_id')->constrained('game_draws')->nullOnDelete();
            $table->string('wallet_source', 16)->default('CASH')->after('game_code'); // 'CASH', 'BONUS'
            $table->unsignedBigInteger('stake_kobo')->default(0)->after('stake_amount');
            $table->unsignedBigInteger('q4i_commission_kobo')->default(0)->after('q4i_share');
            $table->unsignedBigInteger('operator_pool_kobo')->default(0)->after('operator_share');
            $table->unsignedBigInteger('payout_kobo')->default(0)->after('payout_amount');
            $table->string('lifecycle_status', 32)->default('OPEN')->after('status'); // 'OPEN', 'WON', 'LOST', 'VOIDED', 'REFUNDED'
        });

        // 4. Create webhook_events table for Strict Webhook Idempotency
        Schema::create('webhook_events', function (Blueprint $table) {
            $table->id();
            $table->string('provider', 32); // 'paystack', 'monnify', 'flutterwave', 'meta'
            $table->string('external_reference', 128);
            $table->json('payload');
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->unique(['provider', 'external_reference']);
        });

        // 5. Create operator_invitations table for Secure Admin/Rep Invitations
        Schema::create('operator_invitations', function (Blueprint $table) {
            $table->id();
            $table->string('email', 255)->unique();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('role', 32)->default('operator_rep'); // 'super_admin', 'operator_admin', 'operator_rep'
            $table->string('token', 64)->unique();
            $table->timestamp('expires_at');
            $table->timestamp('accepted_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('operator_invitations');
        Schema::dropIfExists('webhook_events');

        Schema::table('bet_tickets', function (Blueprint $table) {
            $table->dropForeign(['game_draw_id']);
            $table->dropColumn([
                'game_draw_id',
                'wallet_source',
                'stake_kobo',
                'q4i_commission_kobo',
                'operator_pool_kobo',
                'payout_kobo',
                'lifecycle_status',
            ]);
        });

        Schema::dropIfExists('game_draws');

        Schema::table('players', function (Blueprint $table) {
            $table->dropColumn([
                'cash_balance_kobo',
                'bonus_balance_kobo',
                'withdrawal_pin_hash',
            ]);
        });
    }
};

