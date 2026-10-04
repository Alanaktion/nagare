<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('issues', function (Blueprint $table) {
            $table->text('label_names')->nullable()->after('description');
        });

        DB::table('issues')
            ->whereIn('id', DB::table('issue_label')->select('issue_id'))
            ->orderBy('id')
            ->chunkById(500, function ($issues): void {
                foreach ($issues as $issue) {
                    $names = DB::table('labels')
                        ->join('issue_label', 'labels.id', '=', 'issue_label.label_id')
                        ->where('issue_label.issue_id', $issue->id)
                        ->orderBy('labels.name')
                        ->pluck('labels.name');

                    DB::table('issues')->where('id', $issue->id)->update(['label_names' => $names->implode(' ')]);
                }
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('issues', function (Blueprint $table) {
            $table->dropColumn('label_names');
        });
    }
};
