<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('stories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('board_id')->index();
            $table->foreignId('author_id')->constrained('users');
            $table->foreignId('assigned_id')->constrained('users')->nullable();
            $table->string('sprint')->nullable();
            $table->string('name');
            $table->text('description')->nullable();
            $table->float('sort');
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::table('cards', function (Blueprint $table) {
            $table->foreignId('story_id')->index()->nullable()->after('board_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('cards', function (Blueprint $table) {
            $table->dropForeign(['story_id']);
            $table->dropColumn('story_id');
        });
        Schema::dropIfExists('stories');
    }
}
