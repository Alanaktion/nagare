<script lang="ts">
    import { Link, setLayoutProps } from '@inertiajs/svelte';
    import AppHead from '@/components/AppHead.svelte';
    import EmptyState from '@/components/EmptyState.svelte';
    import Heading from '@/components/Heading.svelte';
    import { Badge } from '@/components/ui/badge';
    import { Skeleton } from '@/components/ui/skeleton';
    import UserAvatar from '@/components/UserAvatar.svelte';
    import { cn } from '@/lib/utils';
    import { show as showBoard } from '@/routes/boards';
    import { show as showIssue } from '@/routes/issues';
    import { index, show } from '@/routes/users';
    import type { Board, Issue, Member } from '@/types';

    let {
        profile,
        boards,
        assignedIssues,
    }: {
        profile: { data: Member };
        boards: { data: Board[] };
        assignedIssues?: { data: Issue[] };
    } = $props();

    const user = $derived(profile.data);

    $effect(() => {
        setLayoutProps({
            breadcrumbs: [
                { title: 'Users', href: index() },
                { title: user.name, href: show(user.id) },
            ],
        });
    });
</script>

<AppHead title={user.name} />

<div class="max-w-3xl space-y-8 p-4">
    <div class="flex items-center gap-4">
        <UserAvatar {user} class="size-14 text-lg" />
        <div class="min-w-0">
            <h1 class="truncate text-2xl font-semibold">{user.name}</h1>
            <p class="truncate text-sm text-muted-foreground">{user.email}</p>
        </div>
    </div>

    <section class="space-y-3">
        <Heading variant="small" title="Boards" description="Boards you share with this user." />
        {#if boards.data.length === 0}
            <EmptyState message="No boards in common." />
        {:else}
            <ul class="divide-y rounded-lg border">
                {#each boards.data as board (board.id)}
                    <li>
                        <Link href={showBoard(board.id)} class="flex items-center justify-between gap-3 p-3 hover:bg-accent">
                            <span class="truncate text-sm font-medium">{board.name}</span>
                            {#if board.role}<Badge variant="secondary" class="capitalize">{board.role}</Badge>{/if}
                        </Link>
                    </li>
                {/each}
            </ul>
        {/if}
    </section>

    <section class="space-y-3">
        <Heading variant="small" title="Assigned issues" description="Issues assigned to this user on shared boards." />
        {#if assignedIssues === undefined}
            <div class="space-y-2" aria-busy="true" aria-label="Loading assigned issues">
                <Skeleton class="h-12 w-full" />
                <Skeleton class="h-12 w-full" />
                <Skeleton class="h-12 w-full" />
            </div>
        {:else if assignedIssues.data.length === 0}
            <EmptyState message="Nothing assigned on your shared boards." />
        {:else}
            <ul class="divide-y rounded-lg border">
                {#each assignedIssues.data as issue (issue.id)}
                    <li>
                        <Link href={showIssue(issue.id)} class="flex items-center justify-between gap-3 p-3 hover:bg-accent">
                            <div class="min-w-0">
                                <p class={cn('truncate text-sm font-medium', issue.closed_at && 'text-muted-foreground line-through')}>
                                    {issue.name}
                                </p>
                                <p class="truncate text-xs text-muted-foreground">{issue.board?.name}</p>
                            </div>
                            {#if issue.status}
                                <Badge variant={issue.closed_at ? 'secondary' : 'outline'}>{issue.status.name}</Badge>
                            {/if}
                        </Link>
                    </li>
                {/each}
            </ul>
        {/if}
    </section>
</div>
