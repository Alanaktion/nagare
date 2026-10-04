<?php

namespace App\Http\Controllers;

use App\Actions\Attachments\DeleteAttachment;
use App\Actions\Attachments\StoreAttachments;
use App\Http\Requests\Attachments\StoreAttachmentsRequest;
use App\Models\Attachment;
use App\Models\Issue;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttachmentController extends Controller
{
    public function store(StoreAttachmentsRequest $request, #[CurrentUser] User $user, Issue $issue, StoreAttachments $storeAttachments): RedirectResponse
    {
        /** @var list<UploadedFile> $files */
        $files = $request->file('files', []);

        $attachments = $storeAttachments->handle($issue, $user, $files);

        Inertia::flash('toast', ['type' => 'success', 'message' => trans_choice('{1} File attached.|[2,*] :count files attached.', count($attachments))]);

        return back();
    }

    /**
     * Send the file. Pictures are shown in the browser; everything else is
     * downloaded, and never run or rendered as a page.
     */
    public function show(Attachment $attachment): StreamedResponse
    {
        Gate::authorize('view', $attachment);

        return $this->send($attachment, $attachment->path, $attachment->mime_type, $attachment->isImage());
    }

    /**
     * Send the picture's thumbnail.
     */
    public function thumbnail(Attachment $attachment): StreamedResponse
    {
        Gate::authorize('view', $attachment);

        abort_if($attachment->thumbnail_path === null, 404);

        return $this->send($attachment, $attachment->thumbnail_path, 'image/webp', true);
    }

    public function destroy(#[CurrentUser] User $user, Attachment $attachment, DeleteAttachment $deleteAttachment): RedirectResponse
    {
        Gate::authorize('delete', $attachment);

        $deleteAttachment->handle($attachment, $user);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Attachment deleted.')]);

        return back();
    }

    private function send(Attachment $attachment, string $path, string $mimeType, bool $inline): StreamedResponse
    {
        $disk = Storage::disk($attachment->disk);

        abort_unless($disk->exists($path), 404);

        return $disk->response($path, $attachment->name, [
            'Content-Type' => $inline ? $mimeType : 'application/octet-stream',
            'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => "default-src 'none'; sandbox",
            'Cache-Control' => 'private, max-age=3600',
        ], $inline ? 'inline' : 'attachment');
    }
}
