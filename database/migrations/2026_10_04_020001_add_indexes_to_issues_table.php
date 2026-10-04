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
        Schema::table('issues', function (Blueprint $table) {
            $table->index(['board_id', 'sprint_id']);
            $table->index(['parent_id', 'sprint_id']);
            $table->index(['assigned_id', 'closed_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('issues', function (Blueprint $table) {
            $table->dropIndex(['board_id', 'sprint_id']);
            $table->dropIndex(['parent_id', 'sprint_id']);
            $table->dropIndex(['assigned_id', 'closed_at']);
        });
    }
};
