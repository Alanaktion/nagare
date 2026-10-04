<script lang="ts">
    import { Form } from '@inertiajs/svelte';
    import { destroy, store, update } from '@/actions/App/Http/Controllers/CommentController';
    import EmptyState from '@/components/EmptyState.svelte';
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
    import { Skeleton } from '@/components/ui/skeleton';
    import UserAvatar from '@/components/UserAvatar.svelte';
    import { describeActivity } from '@/lib/activity';
    import { fullDate, timeAgo } from '@/lib/time';
    import type { TimelineComment, TimelineEntry } from '@/types';

    let {
        issueId,
        timeline,
        limit,
    }: {
        issueId: number;
        /** Undefined while the timeline is loading. */
        timeline: TimelineEntry[] | undefined;
        limit: number;
    } = $props();

    const options = { preserveScroll: true, only: ['timeline'] };
    const textareaClass =
        'min-h-24 w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-sm focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none';

    let editingId = $state<number>();
    let commentToDelete = $state<TimelineComment>();
    let deleteOpen = $state(false);

    // Cmd/Ctrl+Enter sends the comment from inside the text box.
    const submitOnShortcut = (event: KeyboardEvent) => {
        if (event.key === 'Enter' && (event.metaKey || event.ctrlKey)) {
            event.preventDefault();
            (event.currentTarget as HTMLTextAreaElement).form?.requestSubmit();
        }
    };

    const confirmDelete = (comment: TimelineComment) => {
        commentToDelete = comment;
        deleteOpen = true;
    };
</script>

<section class="space-y-4" aria-labelledby="activity-heading">
    <h2 id="activity-heading" class="text-sm font-medium text-muted-foreground">Activity</h2>

    {#if timeline === undefined}
        <div class="space-y-3" aria-busy="true" aria-label="Loading activity">
            <Skeleton class="h-10 w-full" />
            <Skeleton class="h-16 w-full" />
            <Skeleton class="h-10 w-3/4" />
        </div>
    {:else}
        {#if timeline.length >= limit}
            <p class="text-xs text-muted-foreground">Showing the latest {limit} entries.</p>
        {/if}

        {#if timeline.length === 0}
            <EmptyState message="Nothing here yet. Start the conversation below." />
        {:else}
            <ol class="space-y-4" aria-label="Comments and changes">
                {#each timeline as entry (`${entry.kind}-${entry.id}`)}
                    {#if entry.kind === 'activity'}
                        <li class="flex items-center gap-2 text-sm text-muted-foreground">
                            {#if entry.user}
                                <UserAvatar user={entry.user} />
                            {:else}
                                <span class="size-6 shrink-0 rounded-full bg-muted" aria-hidden="true"></span>
                            {/if}
                            <p class="min-w-0">
                                <span class="font-medium text-foreground">{entry.user?.name ?? 'Someone'}</span>
                                {describeActivity(entry)}
                                <time datetime={entry.created_at} title={fullDate(entry.created_at)}>
                                    · {timeAgo(entry.created_at)}
                                </time>
                            </p>
                        </li>
                    {:else}
                        <li class="flex gap-3">
                            {#if entry.user}
                                <UserAvatar user={entry.user} class="mt-0.5 size-8 text-xs" />
                            {:else}
                                <span class="mt-0.5 size-8 shrink-0 rounded-full bg-muted" aria-hidden="true"></span>
                            {/if}
                            <div class="min-w-0 flex-1 space-y-1.5 rounded-lg border p-3">
                                <div class="flex flex-wrap items-center justify-between gap-2 text-sm">
                                    <p class="text-muted-foreground">
                                        <span class="font-medium text-foreground">{entry.user?.name ?? 'Deleted user'}</span>
                                        <time datetime={entry.created_at} title={fullDate(entry.created_at)}>
                                            · {timeAgo(entry.created_at)}
                                        </time>
                                        {#if entry.edited_at}
                                            <span title={fullDate(entry.edited_at)}>(edited)</span>
                                        {/if}
                                    </p>
                                    {#if editingId !== entry.id && (entry.can_update || entry.can_delete)}
                                        <div class="flex gap-1">
                                            {#if entry.can_update}
                                                <Button
                                                    variant="ghost"
                                                    size="sm"
                                                    class="h-7"
                                                    onclick={() => (editingId = entry.id)}
                                                >
                                                    Edit
                                                </Button>
                                            {/if}
                                            {#if entry.can_delete}
                                                <Button variant="ghost" size="sm" class="h-7" onclick={() => confirmDelete(entry)}>
                                                    Delete
                                                </Button>
                                            {/if}
                                        </div>
                                    {/if}
                                </div>

                                {#if editingId === entry.id}
                                    <Form
                                        {...update.form(entry.id)}
                                        class="space-y-2"
                                        {options}
                                        onSuccess={() => (editingId = undefined)}
                                    >
                                        {#snippet children({ errors, processing })}
                                            <textarea
                                                name="body"
                                                class={textareaClass}
                                                aria-label="Edit comment"
                                                required
                                                value={entry.body}
                                                onkeydown={submitOnShortcut}
                                            ></textarea>
                                            <InputError message={errors.body} />
                                            <div class="flex gap-2">
                                                <Button type="submit" size="sm" disabled={processing}>Save</Button>
                                                <Button type="button" size="sm" variant="secondary" onclick={() => (editingId = undefined)}>
                                                    Cancel
                                                </Button>
                                            </div>
                                        {/snippet}
                                    </Form>
                                {:else}
                                    <!-- eslint-disable-next-line svelte/no-at-html-tags -->
                                    <div class="markdown text-sm">{@html entry.body_html}</div>
                                {/if}
                            </div>
                        </li>
                    {/if}
                {/each}
            </ol>
        {/if}

        <Form {...store.form(issueId)} class="space-y-2" {options} resetOnSuccess>
            {#snippet children({ errors, processing })}
                <label for="new-comment" class="text-sm font-medium">Add a comment</label>
                <textarea
                    id="new-comment"
                    name="body"
                    class={textareaClass}
                    placeholder="Write a comment. Markdown is supported."
                    required
                    onkeydown={submitOnShortcut}
                ></textarea>
                <InputError message={errors.body} />
                <div class="flex items-center justify-between gap-2">
                    <p class="text-xs text-muted-foreground">Markdown is supported. Cmd or Ctrl + Enter to send.</p>
                    <Button type="submit" size="sm" disabled={processing}>Comment</Button>
                </div>
            {/snippet}
        </Form>
    {/if}
</section>

<Dialog bind:open={deleteOpen}>
    <DialogContent>
        {#if commentToDelete}
            <Form
                {...destroy.form(commentToDelete.id)}
                class="space-y-6"
                {options}
                onSuccess={() => (deleteOpen = false)}
            >
                {#snippet children({ processing })}
                    <div class="space-y-3">
                        <DialogTitle>Delete this comment?</DialogTitle>
                        <DialogDescription>It will be removed for everyone on the board.</DialogDescription>
                    </div>
                    <DialogFooter class="gap-2">
                        <DialogClose>
                            <Button type="button" variant="secondary">Cancel</Button>
                        </DialogClose>
                        <Button type="submit" variant="destructive" disabled={processing}>Delete comment</Button>
                    </DialogFooter>
                {/snippet}
            </Form>
        {/if}
    </DialogContent>
</Dialog>
