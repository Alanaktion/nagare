<script lang="ts">
    import { Link, router, setLayoutProps } from '@inertiajs/svelte';
    import { onMount } from 'svelte';
    import Plus from '@lucide/svelte/icons/plus';
    import Settings from '@lucide/svelte/icons/settings';
    import { dndzone, setKeyboardDragTrigger, TRIGGERS, type DndEvent } from 'svelte-dnd-action';
    import { update } from '@/actions/App/Http/Controllers/IssueController';
    import AppHead from '@/components/AppHead.svelte';
    import IssueCard from '@/components/board/IssueCard.svelte';
    import IssueDialog from '@/components/board/IssueDialog.svelte';
    import { Button } from '@/components/ui/button';
    import { getEcho } from '@/lib/echo';
    import { sortBetween } from '@/lib/sort';
    import { edit, index, show } from '@/routes/boards';
    import type { Board, Issue, IssueRole, Member } from '@/types';

    let {
        board,
        issues,
        members,
    }: {
        board: { data: Board };
        issues: { data: Issue[] };
        members: { data: Member[] };
    } = $props();

    const current = $derived(board.data);
    const statuses = $derived(current.statuses ?? []);
    const stories = $derived(issues.data.filter((issue) => issue.role === 'story'));
    const tasks = $derived(issues.data.filter((issue) => issue.role !== 'story'));
    const gridStyle = $derived(
        `grid-template-columns: repeat(${statuses.length + (current.has_stories ? 1 : 0)}, minmax(16rem, 1fr))`,
    );

    const tasksIn = (list: Issue[], statusId: number) =>
        list.filter((issue) => issue.status_id === statusId);

    // Space starts a keyboard drag, leaving Enter free to open the focused card.
    setKeyboardDragTrigger('space');

    // Each draggable cell (a status column, split by story on story boards)
    // keeps a local copy of its issues so dnd-action can preview drags.
    const zoneKey = (statusId: number, parentId: number | null) =>
        `${current.has_stories ? (parentId ?? 'none') : 'all'}:${statusId}`;

    const buildZones = () => {
        const built: Record<string, Issue[]> = {};
        const ordered = [...(current.has_stories ? tasks : issues.data)].sort(
            (a, b) => a.sort - b.sort || a.id - b.id,
        );
        for (const issue of ordered) {
            (built[zoneKey(issue.status_id, issue.parent_id)] ??= []).push(issue);
        }
        return built;
    };

    let zones: Record<string, Issue[]> = $state({});
    let isDragging = false;
    let hasPendingRebuild = false;

    $effect(() => {
        const rebuilt = buildZones();
        if (isDragging) {
            hasPendingRebuild = true;
        } else {
            zones = rebuilt;
        }
    });

    const handleConsider = (key: string) => (event: CustomEvent<DndEvent<Issue>>) => {
        isDragging = true;
        zones[key] = event.detail.items;
    };

    const handleFinalize =
        (key: string, statusId: number, parentId: number | null) =>
        (event: CustomEvent<DndEvent<Issue>>) => {
            const { items, info } = event.detail;
            zones[key] = items;
            isDragging = false;

            if (info.trigger === TRIGGERS.DROPPED_INTO_ZONE) {
                const index = items.findIndex((item) => item.id === Number(info.id));
                persistMove(items[index], items[index - 1]?.sort, items[index + 1]?.sort, statusId, parentId);
            } else if (hasPendingRebuild) {
                zones = buildZones();
            }
            hasPendingRebuild = false;
        };

    const persistMove = (
        moved: Issue,
        previousSort: number | undefined,
        nextSort: number | undefined,
        statusId: number,
        parentId: number | null,
    ) => {
        const columnEnd = Math.max(0, ...issues.data.filter((issue) => issue.status_id === statusId).map((issue) => issue.sort)) + 1;
        const sort = sortBetween(previousSort, nextSort, columnEnd);
        const isClosing = statuses.find((status) => status.id === statusId)?.is_closed ?? false;
        const changes = {
            status_id: statusId,
            sort,
            ...(current.has_stories ? { parent_id: parentId } : {}),
        };

        router
            .optimistic<{ issues: { data: Issue[] } }>((props) => ({
                issues: {
                    ...props.issues,
                    data: props.issues.data.map((issue) =>
                        issue.id === moved.id
                            ? {
                                  ...issue,
                                  ...changes,
                                  closed_at: isClosing ? (issue.closed_at ?? new Date().toISOString()) : null,
                              }
                            : issue,
                    ),
                },
            }))
            .patch(update.url(moved.id), changes, {
                async: true,
                preserveScroll: true,
                only: ['issues'],
            });
    };

    // Apply other people's changes straight to the page props, so the board
    // updates live without a request. Only changes made in other tabs or by
    // other users arrive here; the server skips the sender.
    const upsertIssue = (list: Issue[], issue: Issue, sorts?: Record<number, number> | null) => {
        const exists = list.some((existing) => existing.id === issue.id);
        const merged = exists
            ? list.map((existing) => (existing.id === issue.id ? issue : existing))
            : [...list, issue];

        return sorts
            ? merged.map((existing) => (existing.id in sorts ? { ...existing, sort: sorts[existing.id] } : existing))
            : merged;
    };

    onMount(() => {
        const channelName = `boards.${current.id}`;

        getEcho()
            .private(channelName)
            .listen('.issue.created', ({ issue }: { issue: Issue }) =>
                router.replaceProp('issues.data', (list: Issue[]) => upsertIssue(list, issue)),
            )
            .listen('.issue.updated', ({ issue, sorts }: { issue: Issue; sorts: Record<number, number> | null }) =>
                router.replaceProp('issues.data', (list: Issue[]) => upsertIssue(list, issue, sorts)),
            )
            .listen('.issue.deleted', ({ id }: { id: number }) =>
                router.replaceProp('issues.data', (list: Issue[]) => list.filter((issue) => issue.id !== id)),
            )
            .listen('.board.updated', () =>
                router.reload({ only: ['board', 'issues', 'members'] }),
            )
            .listen('.board.deleted', () => router.visit(index()));

        return () => getEcho().leave(channelName);
    });

    let dialogOpen = $state(false);
    let dialogKey = $state(0);
    let dialogRole: IssueRole = $state('task');
    let dialogStatusId: number | undefined = $state();
    let dialogParentId: number | undefined = $state();

    const openCreate = (role: IssueRole, statusId?: number, parentId?: number) => {
        dialogRole = role;
        dialogStatusId = statusId;
        dialogParentId = parentId;
        dialogKey++;
        dialogOpen = true;
    };

    $effect(() => {
        setLayoutProps({
            breadcrumbs: [
                { title: 'Boards', href: index() },
                { title: current.name, href: show(current.id) },
            ],
        });
    });
</script>

<AppHead title={current.name} />

{#snippet dropZone(statusId: number, parentId: number | null, class_: string)}
    {@const key = zoneKey(statusId, parentId)}
    <div
        class={['flex flex-col gap-2 rounded-md p-2', class_]}
        data-status={statusId}
        use:dndzone={{
            items: zones[key] ?? [],
            type: 'task',
            flipDurationMs: 0,
            delayTouchStart: true,
            dropTargetClasses: ['bg-accent/50'],
            dropTargetStyle: {},
        }}
        onconsider={handleConsider(key)}
        onfinalize={handleFinalize(key, statusId, parentId)}
    >
        {#each zones[key] ?? [] as task (task.id)}
            <IssueCard issue={task} />
        {/each}
    </div>
{/snippet}

{#snippet addTaskButton(label: string, parentId?: number)}
    <Button
        variant="ghost"
        size="icon"
        class="size-7 shrink-0"
        aria-label={label}
        onclick={() => openCreate('task', undefined, parentId)}
    >
        <Plus class="size-4" />
    </Button>
{/snippet}

<div class="flex flex-1 flex-col gap-4 p-4">
    <div class="flex items-center justify-between gap-4">
        <h1 class="text-xl font-semibold">{current.name}</h1>
        <div class="flex items-center gap-2">
            {#if current.has_stories}
                <Button size="sm" variant="outline" onclick={() => openCreate('story')}>
                    <Plus class="size-4" /> New story
                </Button>
            {/if}
            <Button size="sm" onclick={() => openCreate('task')}>
                <Plus class="size-4" /> New task
            </Button>
            <Button variant="outline" size="sm" asChild>
                {#snippet children(props)}
                    <Link {...props} href={edit(current.id)}><Settings class="size-4" /> Settings</Link>
                {/snippet}
            </Button>
        </div>
    </div>

    {#if current.has_stories}
        <div class="overflow-x-auto">
            <div class="grid min-w-max gap-px overflow-hidden rounded-lg border bg-border" style={gridStyle}>
                <h2 class="bg-muted px-3 py-2 text-sm font-medium">Story</h2>
                {#each statuses as status (status.id)}
                    <h2 class="bg-muted px-3 py-2 text-sm font-medium">
                        {status.name}
                        <span class="ml-1 text-xs font-normal text-muted-foreground">
                            {tasksIn(tasks, status.id).length}
                        </span>
                    </h2>
                {/each}

                {#each stories as story (story.id)}
                    <div class="flex items-start gap-1 bg-background p-2" data-story={story.id}>
                        <div class="min-w-0 flex-1">
                            <IssueCard issue={story} />
                        </div>
                        {@render addTaskButton(`Add task to ${story.name}`, story.id)}
                    </div>
                    {#each statuses as status (status.id)}
                        <div class="flex bg-background">
                            {@render dropZone(status.id, story.id, 'min-h-16 flex-1')}
                        </div>
                    {/each}
                {/each}

                <div class="flex items-start justify-between gap-1 bg-background p-2 pl-3">
                    <p class="py-1 text-sm text-muted-foreground">No story</p>
                    {@render addTaskButton('Add task without a story')}
                </div>
                {#each statuses as status (status.id)}
                    <div class="flex bg-background">
                        {@render dropZone(status.id, null, 'min-h-16 flex-1')}
                    </div>
                {/each}
            </div>
        </div>
    {:else}
        <div class="flex min-h-96 flex-1 gap-3 overflow-x-auto">
            {#each statuses as status (status.id)}
                {@const isEmpty = (zones[zoneKey(status.id, null)] ?? []).length === 0}
                <section class="flex min-w-64 flex-1 flex-col rounded-lg border bg-muted/40">
                    <header class="flex items-center justify-between gap-2 px-3 py-2">
                        <h2 class="text-sm font-medium">
                            {status.name}
                            <span class="ml-1 text-xs font-normal text-muted-foreground">
                                {(zones[zoneKey(status.id, null)] ?? []).length}
                            </span>
                        </h2>
                        <Button
                            variant="ghost"
                            size="icon"
                            class="size-7"
                            aria-label="Add task to {status.name}"
                            onclick={() => openCreate('task', status.id)}
                        >
                            <Plus class="size-4" />
                        </Button>
                    </header>
                    <div class="relative flex flex-1 flex-col">
                        {@render dropZone(status.id, null, 'flex-1')}
                        {#if isEmpty}
                            <div class="pointer-events-none absolute inset-x-0 top-0 p-2">
                                <button
                                    type="button"
                                    class="pointer-events-auto flex w-full items-center justify-center gap-1 rounded-md border border-dashed p-3 text-xs text-muted-foreground hover:bg-accent hover:text-accent-foreground"
                                    onclick={() => openCreate('task', status.id)}
                                >
                                    <Plus class="size-3.5" /> Add task
                                </button>
                            </div>
                        {/if}
                    </div>
                </section>
            {/each}
        </div>
    {/if}
</div>

{#key dialogKey}
    <IssueDialog
        bind:open={dialogOpen}
        board={current}
        members={members.data}
        {stories}
        role={dialogRole}
        statusId={dialogStatusId}
        parentId={dialogParentId}
    />
{/key}
