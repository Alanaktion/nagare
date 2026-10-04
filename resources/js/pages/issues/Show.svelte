<script lang="ts">
    import { Form, Link, router, setLayoutProps } from '@inertiajs/svelte';
    import { onMount } from 'svelte';
    import { destroy } from '@/actions/App/Http/Controllers/IssueController';
    import AppHead from '@/components/AppHead.svelte';
    import IssueDialog from '@/components/board/IssueDialog.svelte';
    import UserAvatar from '@/components/UserAvatar.svelte';
    import { Badge } from '@/components/ui/badge';
    import { Button } from '@/components/ui/button';
    import {
        Dialog,
        DialogClose,
        DialogContent,
        DialogDescription,
        DialogFooter,
        DialogTitle,
    } from '@/components/ui/dialog';
    import { getEcho } from '@/lib/echo';
    import { cn } from '@/lib/utils';
    import { index, show as showBoard } from '@/routes/boards';
    import { show } from '@/routes/issues';
    import type { Board, Issue, Member } from '@/types';

    let {
        issue,
        parent,
        board,
        members,
        stories,
    }: {
        issue: { data: Issue };
        parent: { data: Issue } | null;
        board: { data: Board };
        members: { data: Member[] };
        stories: { data: Issue[] };
    } = $props();

    const current = $derived(issue.data);
    const status = $derived(board.data.statuses?.find((s) => s.id === current.status_id));

    // Show other people's edits live; the server doesn't echo our own back.
    onMount(() => {
        const channelName = `boards.${board.data.id}`;

        getEcho()
            .private(channelName)
            .listen('.issue.updated', ({ issue: updated }: { issue: Issue }) => {
                if (updated.id === current.id) {
                    router.replaceProp('issue.data', updated);
                }
            })
            .listen('.issue.deleted', ({ id }: { id: number }) => {
                if (id === current.id) {
                    router.visit(showBoard(board.data.id));
                }
            })
            .listen('.board.updated', () => router.reload({ only: ['board', 'members', 'stories'] }))
            .listen('.board.deleted', () => router.visit(index()));

        return () => getEcho().leave(channelName);
    });

    let editOpen = $state(false);
    let deleteOpen = $state(false);

    $effect(() => {
        setLayoutProps({
            breadcrumbs: [
                { title: 'Boards', href: index() },
                { title: board.data.name, href: showBoard(board.data.id) },
                { title: current.name, href: show(current.id) },
            ],
        });
    });
</script>

<AppHead title={current.name} />

<div class="max-w-3xl space-y-6 p-4">
    <div class="flex items-start justify-between gap-4">
        <div class="space-y-1">
            <p class="text-sm text-muted-foreground capitalize">
                {current.role} in
                <Link href={showBoard(board.data.id)} class="underline-offset-4 hover:underline">
                    {board.data.name}
                </Link>
            </p>
            <h1 class={cn('text-2xl font-semibold', current.closed_at && 'line-through')}>{current.name}</h1>
        </div>
        <div class="flex shrink-0 gap-2">
            <Button variant="outline" size="sm" onclick={() => (editOpen = true)}>Edit</Button>
            <Button variant="destructive" size="sm" onclick={() => (deleteOpen = true)}>Delete</Button>
        </div>
    </div>

    <dl class="grid gap-4 text-sm sm:grid-cols-3">
        <div class="space-y-1">
            <dt class="text-muted-foreground">Status</dt>
            <dd>
                <Badge variant={current.closed_at ? 'secondary' : 'outline'}>{status?.name}</Badge>
            </dd>
        </div>
        <div class="space-y-1">
            <dt class="text-muted-foreground">Assignee</dt>
            <dd class="flex items-center gap-2">
                {#if current.assignee}
                    <UserAvatar user={current.assignee} />
                    {current.assignee.name}
                {:else}
                    <span class="text-muted-foreground">Unassigned</span>
                {/if}
            </dd>
        </div>
        {#if parent}
            <div class="space-y-1">
                <dt class="text-muted-foreground">Story</dt>
                <dd>
                    <Link href={show(parent.data.id)} class="underline-offset-4 hover:underline">
                        {parent.data.name}
                    </Link>
                </dd>
            </div>
        {/if}
    </dl>

    <section class="space-y-2">
        <h2 class="text-sm font-medium text-muted-foreground">Description</h2>
        {#if current.description}
            <p class="whitespace-pre-wrap">{current.description}</p>
        {:else}
            <p class="text-sm text-muted-foreground">No description.</p>
        {/if}
    </section>
</div>

<IssueDialog bind:open={editOpen} board={board.data} members={members.data} stories={stories.data} issue={current} />

<Dialog bind:open={deleteOpen}>
    <DialogContent>
        <Form {...destroy.form(current.id)} class="space-y-6">
            {#snippet children({ processing })}
                <div class="space-y-3">
                    <DialogTitle>Delete this {current.role}?</DialogTitle>
                    <DialogDescription>
                        "{current.name}" will be removed from the board.
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
    </DialogContent>
</Dialog>
