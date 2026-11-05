<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('players', function (Blueprint $table) {
            $table->string('language', 10)->default('en')->after('name');
            $table->index(['tenant_id', 'language']);
        });
    }

    public function down(): void
    {
        Schema::table('players', function (Blueprint $table) {
            $table->dropIndex(['tenant_id', 'language']);
            $table->dropColumn('language');
        });
    }
};
