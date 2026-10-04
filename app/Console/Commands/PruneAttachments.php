<?php

namespace App\Console\Commands;

use App\Actions\Attachments\PruneAttachments as PruneDeletedAttachments;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('attachments:prune {--days= : Remove files deleted more than this many days ago}')]
#[Description('Remove the files of attachments that were deleted a while ago')]
class PruneAttachments extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(PruneDeletedAttachments $prune): int
    {
        $days = $this->option('days') === null ? (int) config('attachments.prune_after_days') : (int) $this->option('days');

        $this->info("Removed {$prune->handle($days)} deleted attachment(s).");

        return self::SUCCESS;
    }
}
