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
        Schema::create('boards', function (Blueprint $table) {
            $table->id();
            $table->string('type')->default('kanban');
            $table->string('sprint_cycle')->nullable();
            $table->string('name');
            $table->foreignId('user_id')->constrained('users');
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('sprints', function (Blueprint $table) {
            $table->id();
            $table->foreignId('board_id')->constrained('boards');
            $table->string('slug'); // Slug should be determined by start date, according to board's sprint_cycle
            $table->date('start_date');
            $table->date('end_date');
            $table->timestamps();
            $table->timestamp('closed_at')->nullable();
            $table->unique(['board_id', 'slug']);
        });
        Schema::create('statuses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('board_id')->contrained('boards');
            $table->string('name');
            $table->float('sort');
            $table->boolean('is_closed');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('statuses');
        Schema::dropIfExists('sprints');
        Schema::dropIfExists('boards');
    }
};
