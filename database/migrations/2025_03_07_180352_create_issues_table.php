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
        Schema::create('issues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('board_id')->constrained('boards');
            $table->foreignId('sprint_id')->nullable()->constrained('sprints')
                ->onDelete('set null');
            $table->foreignId('parent_id')->nullable()->constrained('issues')
                ->onDelete('set null');
            $table->foreignId('author_id')->constrained('users');
            $table->foreignId('assigned_id')->nullable()->constrained('users');
            $table->foreignId('status_id')->constrained('statuses');
            $table->string('name');
            $table->text('description')->nullable();
            $table->float('sort')->default(0);
            $table->timestamps();
            $table->timestamp('closed_at')->nullable();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('issues');
    }
};
