<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('board_id')->index();
            $table->foreignId('author_id')->constrained('users');
            $table->foreignId('assigned_id')->constrained('users')->nullable();
            $table->foreignId('status_id')->index()->nullable();
            $table->string('name');
            $table->text('description')->nullable();
            $table->float('sort');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('cards');
    }
};
