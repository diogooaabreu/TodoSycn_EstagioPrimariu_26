<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('todos', function (Blueprint $table) {
            // É um toodo repetido?
            $table->boolean('is_recurring')->default(false)->after('is_completed');
            // Dias da semana: "1,2,3,4,5" (1=Seg, 2=Ter, 3=Qua, 4=Qui, 5=Sex, 6=Sab, 7=Dom)
            $table->string('recurring_days')->nullable()->after('is_recurring');
        });
    }

    public function down(): void
    {
        Schema::table('todos', function (Blueprint $table) {
            $table->dropColumn(['is_recurring', 'recurring_days']);
        });
    }
};
