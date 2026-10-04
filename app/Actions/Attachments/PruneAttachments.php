<?php

namespace App\Actions\Attachments;

use App\Models\Attachment;

class PruneAttachments
{
    /**
     * Remove the files of attachments deleted more than `$days` days ago, and
     * then the attachments themselves.
     *
     * @return int How many attachments were removed.
     */
    public function handle(int $days): int
    {
        $removed = 0;

        Attachment::onlyTrashed()
            ->where('deleted_at', '<', now()->subDays($days))
            ->chunkById(200, function ($attachments) use (&$removed): void {
                foreach ($attachments as $attachment) {
                    $attachment->deleteFiles();
                    $attachment->forceDelete();
                    $removed++;
                }
            });

        return $removed;
    }
}
