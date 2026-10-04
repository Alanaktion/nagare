<script lang="ts">
    import { Form } from '@inertiajs/svelte';
    import Paperclip from '@lucide/svelte/icons/paperclip';
    import { destroy, store } from '@/actions/App/Http/Controllers/AttachmentController';
    import AttachmentItem from '@/components/issue/AttachmentItem.svelte';
    import InputError from '@/components/InputError.svelte';
    import { Button } from '@/components/ui/button';
    import {
        Dialog,
        DialogClose,
        DialogContent,
        DialogDescription,
        DialogFooter,
        DialogTitle,
    } from '@/components/ui/dialog';
    import { cn } from '@/lib/utils';
    import type { Attachment } from '@/types';

    let {
        issueId,
        attachments,
    }: {
        issueId: number;
        attachments: Attachment[];
    } = $props();

    const options = { preserveScroll: true, only: ['attachments', 'timeline'] };

    let input: HTMLInputElement | undefined = $state();
    let isDragging = $state(false);
    let attachmentToDelete = $state<Attachment>();
    let deleteOpen = $state(false);

    const submit = () => input?.form?.requestSubmit();

    // Dropping files fills the file input, then sends the form like a normal pick.
    const handleDrop = (event: DragEvent) => {
        event.preventDefault();
        isDragging = false;

        if (input && event.dataTransfer?.files.length) {
            input.files = event.dataTransfer.files;
            submit();
        }
    };

    const confirmDelete = (attachment: Attachment) => {
        attachmentToDelete = attachment;
        deleteOpen = true;
    };
</script>

<section class="space-y-3" aria-labelledby="attachments-heading">
    <h2 id="attachments-heading" class="text-sm font-medium text-muted-foreground">
        Attachments{attachments.length > 0 ? ` (${attachments.length})` : ''}
    </h2>

    {#if attachments.length > 0}
        <ul class="grid gap-2 sm:grid-cols-2">
            {#each attachments as attachment (attachment.id)}
                <li><AttachmentItem {attachment} ondelete={confirmDelete} /></li>
            {/each}
        </ul>
    {/if}

    <Form {...store.form(issueId)} {options} resetOnSuccess>
        {#snippet children({ errors, processing })}
            <!-- svelte-ignore a11y_no_static_element_interactions -->
            <div
                class={cn(
                    'flex flex-wrap items-center gap-3 rounded-lg border border-dashed p-3 text-sm text-muted-foreground transition-colors',
                    isDragging && 'border-primary bg-accent/50',
                )}
                ondragover={(event) => {
                    event.preventDefault();
                    isDragging = true;
                }}
                ondragleave={() => (isDragging = false)}
                ondrop={handleDrop}
            >
                <Paperclip class="size-4" aria-hidden="true" />
                <span>Drop files here, or</span>
                <input
                    bind:this={input}
                    id="issue-files"
                    type="file"
                    name="files[]"
                    multiple
                    class="sr-only"
                    onchange={submit}
                />
                <Button type="button" size="sm" variant="outline" disabled={processing} onclick={() => input?.click()}>
                    {processing ? 'Uploading…' : 'Choose files'}
                </Button>
            </div>
            {#each Object.entries(errors).filter(([key]) => key.startsWith('files')) as [key, message] (key)}
                <InputError {message} />
            {/each}
        {/snippet}
    </Form>
</section>

<Dialog bind:open={deleteOpen}>
    <DialogContent>
        {#if attachmentToDelete}
            <Form {...destroy.form(attachmentToDelete.id)} class="space-y-6" {options} onSuccess={() => (deleteOpen = false)}>
                {#snippet children({ processing })}
                    <div class="space-y-3">
                        <DialogTitle>Delete this attachment?</DialogTitle>
                        <DialogDescription>
                            "{attachmentToDelete?.name}" will be removed from the issue.
                        </DialogDescription>
                    </div>
                    <DialogFooter class="gap-2">
                        <DialogClose>
                            <Button type="button" variant="secondary">Cancel</Button>
                        </DialogClose>
                        <Button type="submit" variant="destructive" disabled={processing}>Delete</Button>
                    </DialogFooter>
                {/snippet}
            </Form>
        {/if}
    </DialogContent>
</Dialog>
