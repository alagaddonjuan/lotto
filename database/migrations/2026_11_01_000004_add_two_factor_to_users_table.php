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
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone')->nullable()->after('email');
            $table->boolean('two_factor_enabled')->default(false)->after('password');
            $table->string('two_factor_secret')->nullable()->after('two_factor_enabled');
            $table->string('two_factor_code')->nullable()->after('two_factor_secret');
            $table->timestamp('two_factor_expires_at')->nullable()->after('two_factor_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'phone',
                'two_factor_enabled',
                'two_factor_secret',
                'two_factor_code',
                'two_factor_expires_at',
            ]);
        });
    }
};
