<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ussd_menus', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('menu_name');
            $table->string('service_code')->default('*347*102#');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('ussd_menu_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ussd_menu_id')->constrained('ussd_menus')->cascadeOnDelete();
            $table->unsignedBigInteger('parent_item_id')->nullable();
            $table->string('option_trigger'); // "ROOT", "1", "2", "0", etc.
            $table->string('response_type');  // "CON" (continue menu) or "API" (action)
            $table->text('response_text');
            $table->timestamps();

            $table->index(['ussd_menu_id', 'parent_item_id', 'option_trigger']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ussd_menu_items');
        Schema::dropIfExists('ussd_menus');
    }
};

