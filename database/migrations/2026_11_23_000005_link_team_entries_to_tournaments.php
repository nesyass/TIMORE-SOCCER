<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('records', function (Blueprint $table) {
            $table->foreignId('tournament_id')->nullable()->constrained('records')->restrictOnDelete();
            $table->char('team_entry_key', 64)->nullable()->unique();
        });
    }

    public function down(): void
    {
        Schema::table('records', function (Blueprint $table) {
            $table->dropForeign(['tournament_id']);
            $table->dropUnique(['team_entry_key']);
            $table->dropColumn(['tournament_id', 'team_entry_key']);
        });
    }
};