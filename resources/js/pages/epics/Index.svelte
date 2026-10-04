<script lang="ts">
    import { Link, router, setLayoutProps } from '@inertiajs/svelte';
    import { onMount } from 'svelte';
    import Plus from '@lucide/svelte/icons/plus';
    import AppHead from '@/components/AppHead.svelte';
    import EmptyState from '@/components/EmptyState.svelte';
    import IssueDialog from '@/components/board/IssueDialog.svelte';
    import { Button } from '@/components/ui/button';
    import { getEcho } from '@/lib/echo';
    import { index, show } from '@/routes/boards';
    import { show as showIssue } from '@/routes/issues';
    import type { Board, Epic, Label, Member } from '@/types';

    let {
        board,
        epics,
        members,
        labels,
    }: {
        board: { data: Board };
        epics: { data: Epic[] };
        members: { data: Member[] };
        labels: { data: Label[] };
    } = $props();

    const current = $derived(board.data);
    let dialogOpen = $state(false);
    let dialogKey = $state(0);

    const openCreate = () => {
        dialogKey++;
        dialogOpen = true;
    };

    const percent = (done: number, total: number) => (total === 0 ? 0 : Math.round((done / total) * 100));

    // Progress changes whenever any issue on the board does.
    onMount(() => {
        const echo = getEcho();

        if (!echo) {
            return;
        }

        const channelName = `boards.${current.id}`;
        const refresh = () => router.reload({ only: ['epics'] });

        echo.private(channelName)
            .listen('.issue.created', refresh)
            .listen('.issue.updated', refresh)
            .listen('.issue.deleted', refresh)
            .listen('.board.deleted', () => router.visit(index()));

        return () => echo.leave(channelName);
    });

    $effect(() => {
        setLayoutProps({
            breadcrumbs: [
                { title: 'Boards', href: index() },
                { title: current.name, href: show(current.id) },
                { title: 'Epics', href: '#' },
            ],
        });
    });
</script>

<AppHead title={`Epics · ${current.name}`} />

<div class="flex flex-1 flex-col gap-4 p-4">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <h1 class="text-xl font-semibold">{current.name} epics</h1>
        <Button size="sm" onclick={openCreate}><Plus class="size-4" /> New epic</Button>
    </div>

    {#if epics.data.length === 0}
        <EmptyState message="No epics yet. An epic groups related stories and shows how far along their tasks are.">
            <Button size="sm" onclick={openCreate}><Plus class="size-4" /> New epic</Button>
        </EmptyState>
    {:else}
        <ul class="space-y-4">
            {#each epics.data as epic (epic.id)}
                <li class="space-y-3 rounded-lg border bg-card p-4 text-card-foreground">
                    <div class="flex items-baseline justify-between gap-4">
                        <Link href={showIssue(epic.id)} class="font-medium underline-offset-4 hover:underline">
                            {epic.name}
                        </Link>
                        <span class="shrink-0 text-sm text-muted-foreground">
                            {epic.tasks_done} of {epic.tasks_total} tasks done
                        </span>
                    </div>
                    <div
                        class="h-2 overflow-hidden rounded-full bg-muted"
                        role="progressbar"
                        aria-label={`${epic.name} progress`}
                        aria-valuemin={0}
                        aria-valuemax={100}
                        aria-valuenow={percent(epic.tasks_done, epic.tasks_total)}
                    >
                        <div class="h-full bg-primary" style={`width: ${percent(epic.tasks_done, epic.tasks_total)}%`}></div>
                    </div>
                    {#if epic.stories.length > 0}
                        <ul class="divide-y text-sm">
                            {#each epic.stories as story (story.id)}
                                <li class="flex items-baseline justify-between gap-4 py-1.5">
                                    <Link href={showIssue(story.id)} class="underline-offset-4 hover:underline">
                                        {story.name}
                                    </Link>
                                    <span class="shrink-0 text-muted-foreground">
                                        {story.tasks_done}/{story.tasks_total}
                                    </span>
                                </li>
                            {/each}
                        </ul>
                    {:else}
                        <p class="text-sm text-muted-foreground">No stories yet. Add one to this epic from the story's edit dialog.</p>
                    {/if}
                </li>
            {/each}
        </ul>
    {/if}
</div>

{#key dialogKey}
    <IssueDialog bind:open={dialogOpen} board={current} members={members.data} labels={labels.data} role="epic" />
{/key}
