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
        // 1. Tenants (Betting & Lottery Companies)
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->string('name');                          // e.g. "Modern Lottery Limited"
            $table->string('slug')->unique();                // e.g. "modern-lottery"
            $table->string('country_code', 2)->default('NG');// "NG", "KE", etc.
            $table->string('currency', 3)->default('NGN');    // "NGN", "KES", etc.
            $table->string('whatsapp_number')->unique();     // e.g. "+14155238886" or "+234..."
            $table->string('whatsapp_driver')->default('twilio'); // "twilio" or "meta"
            $table->json('driver_config')->nullable();       // API credentials & phone IDs
            $table->string('operator_driver')->default('modern_lottery'); // Backend integration driver
            $table->string('operator_api_url')->nullable();  // Partner whitelisted endpoint
            $table->text('operator_api_key')->nullable();    // Partner secret token
            $table->decimal('q4i_split_percent', 5, 2)->default(35.00);      // 35%
            $table->decimal('operator_split_percent', 5, 2)->default(65.00); // 65%
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 2. Players (Identified by phone within a tenant)
        Schema::create('players', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('phone');
            $table->string('name')->nullable();
            $table->string('pin_hash')->nullable();          // 4-digit security PIN
            $table->unsignedInteger('failed_pin_attempts')->default(0);
            $table->boolean('is_locked')->default(false);
            $table->timestamp('locked_until')->nullable();
            $table->timestamp('last_active_at')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'phone']);
        });

        // 3. Supported Lotto Games
        Schema::create('lotto_games', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('game_code');                     // "KENO_20_80", "5_90", etc.
            $table->string('name');                          // "Keno 20/80"
            $table->text('description')->nullable();
            $table->decimal('min_stake', 12, 2)->default(50.00);
            $table->decimal('max_stake', 12, 2)->default(50000.00);
            $table->unsignedInteger('total_pool_numbers')->default(80); // e.g. 80 for Keno, 90 for 5/90
            $table->unsignedInteger('required_picks')->default(10);     // How many numbers to pick
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['tenant_id', 'game_code']);
        });

        // 4. Bet Tickets (Audit Trail & Idempotency)
        Schema::create('bet_tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('player_id')->constrained()->cascadeOnDelete();
            $table->string('ticket_reference')->unique();    // e.g. "TKT-20261101-ABCD"
            $table->string('operator_ticket_id')->nullable();// ID assigned by Modern Lottery's server
            $table->string('game_code');
            $table->json('selected_numbers');
            $table->decimal('stake_amount', 12, 2);
            $table->decimal('q4i_share', 12, 2);             // 35% of stake
            $table->decimal('operator_share', 12, 2);        // 65% of stake
            $table->string('status')->default('pending');    // "pending", "accepted", "rejected", "won", "lost"
            $table->decimal('payout_amount', 12, 2)->default(0.00);
            $table->string('idempotency_key')->unique();     // UUIDv4 preventing double-bets
            $table->string('hash', 64)->nullable();          // Cryptographic SHA-256 seal
            $table->text('raw_operator_response')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
        });

        // 5. Cryptographic Double-Entry Revenue Ledger (Immutable)
        Schema::create('ledger_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('bet_ticket_id')->nullable()->constrained()->nullOnDelete();
            $table->string('entry_type');                    // "BET_STAKE", "Q4I_COMMISSION", "OPERATOR_SHARE", "WINNING_PAYOUT"
            $table->string('debit_account');                 // e.g. "PLAYER_WALLET", "OPERATOR_ESCROW"
            $table->string('credit_account');                // e.g. "Q4I_REVENUE", "OPERATOR_POOL"
            $table->decimal('amount', 14, 2);
            $table->string('currency', 3);
            $table->string('previous_hash', 64)->nullable(); // Blockchain-style hash chain
            $table->string('hash', 64)->unique();            // Current entry hash
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['tenant_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ledger_entries');
        Schema::dropIfExists('bet_tickets');
        Schema::dropIfExists('lotto_games');
        Schema::dropIfExists('players');
        Schema::dropIfExists('tenants');
    }
};

