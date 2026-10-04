<script module lang="ts">
    import { dashboard } from '@/routes';

    export const layout = {
        breadcrumbs: [
            {
                title: 'Dashboard',
                href: dashboard(),
            },
        ],
    };
</script>

<script lang="ts">
    import { Link, page } from '@inertiajs/svelte';
    import Plus from '@lucide/svelte/icons/plus';
    import AppHead from '@/components/AppHead.svelte';
    import EmptyState from '@/components/EmptyState.svelte';
    import Heading from '@/components/Heading.svelte';
    import { Badge } from '@/components/ui/badge';
    import { Button } from '@/components/ui/button';
    import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
    import { Skeleton } from '@/components/ui/skeleton';
    import { sprintLabel } from '@/lib/sprints';
    import { show as showBoard, create as createBoard } from '@/routes/boards';
    import { show as showSprint } from '@/routes/boards/sprints';
    import { show as showIssue } from '@/routes/issues';
    import type { Board, Issue, Sprint } from '@/types';

    type SprintSummary = {
        board: { data: Board };
        sprint: { data: Sprint };
        total: number;
        done: number;
    };

    let {
        boards,
        assignedIssues,
        sprintSummaries,
    }: {
        boards: { data: Board[] };
        assignedIssues?: { data: Issue[] };
        sprintSummaries?: SprintSummary[];
    } = $props();

    const firstName = $derived(page.props.auth.user.name.split(' ')[0]);
    const usesSprints = $derived(boards.data.some((board) => board.has_sprints));
    const percent = (summary: SprintSummary) =>
        summary.total === 0 ? 0 : Math.round((summary.done / summary.total) * 100);
</script>

<AppHead title="Dashboard" />

<div class="flex flex-1 flex-col gap-8 p-4">
    <Heading title="Welcome back, {firstName}" description="Here's what's on your plate." />

    <div class="grid gap-8 lg:grid-cols-2">
        <section class="space-y-3" aria-labelledby="assigned-heading">
            <h2 id="assigned-heading" class="text-sm font-medium text-muted-foreground">Assigned to you</h2>

            {#if assignedIssues === undefined}
                <div class="space-y-2" aria-busy="true" aria-label="Loading assigned issues">
                    <Skeleton class="h-14 w-full" />
                    <Skeleton class="h-14 w-full" />
                    <Skeleton class="h-14 w-full" />
                </div>
            {:else if assignedIssues.data.length === 0}
                <EmptyState message="Nothing assigned to you right now." />
            {:else}
                <ul class="divide-y rounded-lg border">
                    {#each assignedIssues.data as issue (issue.id)}
                        <li>
                            <Link href={showIssue(issue.id)} class="flex items-center justify-between gap-3 p-3 hover:bg-accent">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-medium">{issue.name}</p>
                                    <p class="truncate text-xs text-muted-foreground">{issue.board?.name}</p>
                                </div>
                                {#if issue.status}<Badge variant="outline">{issue.status.name}</Badge>{/if}
                            </Link>
                        </li>
                    {/each}
                </ul>
            {/if}
        </section>

        {#if sprintSummaries === undefined ? usesSprints : sprintSummaries.length > 0}
            <section class="space-y-3" aria-labelledby="sprints-heading">
                <h2 id="sprints-heading" class="text-sm font-medium text-muted-foreground">Current sprints</h2>

                {#if sprintSummaries === undefined}
                    <div class="space-y-2" aria-busy="true" aria-label="Loading sprint progress">
                        <Skeleton class="h-20 w-full" />
                        <Skeleton class="h-20 w-full" />
                    </div>
                {:else}
                    <ul class="space-y-2">
                        {#each sprintSummaries as summary (summary.sprint.data.id)}
                            <li>
                                <Link
                                    href={showSprint([summary.board.data.id, summary.sprint.data.slug])}
                                    class="block rounded-lg border p-3 hover:bg-accent"
                                >
                                    <div class="flex items-baseline justify-between gap-3">
                                        <p class="truncate text-sm font-medium">{summary.board.data.name}</p>
                                        <p class="shrink-0 text-xs text-muted-foreground">
                                            {summary.done} of {summary.total} done
                                        </p>
                                    </div>
                                    <p class="truncate text-xs text-muted-foreground">{sprintLabel(summary.sprint.data)}</p>
                                    <div
                                        class="mt-2 h-2 overflow-hidden rounded-full bg-muted"
                                        role="progressbar"
                                        aria-label="Sprint progress"
                                        aria-valuemin={0}
                                        aria-valuemax={100}
                                        aria-valuenow={percent(summary)}
                                    >
                                        <div class="h-full bg-primary" style="width: {percent(summary)}%"></div>
                                    </div>
                                </Link>
                            </li>
                        {/each}
                    </ul>
                {/if}
            </section>
        {/if}
    </div>

    <section class="space-y-3" aria-labelledby="boards-heading">
        <h2 id="boards-heading" class="text-sm font-medium text-muted-foreground">Recent boards</h2>

        {#if boards.data.length === 0}
            <EmptyState message="You aren't on any boards yet." class="p-8">
                <Button asChild>
                    {#snippet children(props)}
                        <Link {...props} href={createBoard()}><Plus class="size-4" /> Create a board</Link>
                    {/snippet}
                </Button>
            </EmptyState>
        {:else}
            <ul class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                {#each boards.data as board (board.id)}
                    <li>
                        <Link href={showBoard(board.id)} class="block rounded-xl focus-visible:ring-2 focus-visible:ring-ring">
                            <Card class="transition-colors hover:bg-accent">
                                <CardHeader>
                                    <CardTitle class="flex items-center justify-between gap-2">
                                        {board.name}
                                        {#if board.role === 'admin'}<Badge variant="secondary">Admin</Badge>{/if}
                                    </CardTitle>
                                </CardHeader>
                                <CardContent class="text-sm text-muted-foreground capitalize">
                                    {[board.has_stories ? 'Stories' : null, board.has_sprints ? `${board.sprint_cycle} sprints` : null]
                                        .filter(Boolean)
                                        .join(' · ') || 'Tasks only'}
                                </CardContent>
                            </Card>
                        </Link>
                    </li>
                {/each}
            </ul>
        {/if}
    </section>
</div>
