<script lang="ts">
    import { Link, page, router, setLayoutProps } from '@inertiajs/svelte';
    import { onMount } from 'svelte';
    import Plus from '@lucide/svelte/icons/plus';
    import Settings from '@lucide/svelte/icons/settings';
    import { dndzone, setKeyboardDragTrigger, TRIGGERS, type DndEvent } from 'svelte-dnd-action';
    import { update } from '@/actions/App/Http/Controllers/IssueController';
    import AppHead from '@/components/AppHead.svelte';
    import EmptyState from '@/components/EmptyState.svelte';
    import IssueCard from '@/components/board/IssueCard.svelte';
    import IssueFilters from '@/components/board/IssueFilters.svelte';
    import IssueDialog from '@/components/board/IssueDialog.svelte';
    import SprintNav from '@/components/board/SprintNav.svelte';
    import { Button } from '@/components/ui/button';
    import { getEcho } from '@/lib/echo';
    import {
        applyFilters,
        filtersFromSearch,
        filtersToQuery,
        noFilters,
    } from '@/lib/filters';
    import { sortBetween } from '@/lib/sort';
    import { backlog, edit, index, show } from '@/routes/boards';
    import { show as showSprint } from '@/routes/boards/sprints';
    import type { Board, Issue, IssueRole, Label, Member, Sprint } from '@/types';

    let {
        board,
        issues,
        members,
        labels,
        sprint,
        sprints,
        openSprints,
        previousSprint,
        nextSprint,
        olderClosedCount,
        withOlderClosed,
        closedIssueDays,
    }: {
        board: { data: Board };
        issues: { data: Issue[] };
        members: { data: Member[] };
        labels: { data: Label[] };
        sprint: { data: Sprint } | null;
        sprints: { data: Sprint[] };
        openSprints: { data: Sprint[] };
        previousSprint: { data: Sprint } | null;
        nextSprint: { data: Sprint } | null;
        olderClosedCount: number;
        withOlderClosed: boolean;
        closedIssueDays: number;
    } = $props();

    const current = $derived(board.data);
    const statuses = $derived(current.statuses ?? []);
    const currentUserId = $derived(page.props.auth.user.id);

    // The filters live in the query string so a filtered view can be shared or reloaded.
    // svelte-ignore state_referenced_locally
    let filters = $state(filtersFromSearch(page.url.includes('?') ? page.url.slice(page.url.indexOf('?')) : ''));

    $effect(() => {
        const params = new URLSearchParams(window.location.search);
        for (const key of ['q', 'assignee', 'label', 'mine']) {
            params.delete(key);
        }
        for (const [key, value] of Object.entries(filtersToQuery(filters))) {
            params.set(key, value);
        }
        const search = params.size > 0 ? `?${params}` : '';
        if (search !== window.location.search) {
            window.history.replaceState(window.history.state, '', `${window.location.pathname}${search}`);
        }
    });

    const visibleIssues = $derived(applyFilters(issues.data, filters, currentUserId));
    const allStories = $derived(issues.data.filter((issue) => issue.role === 'story'));
    const stories = $derived(visibleIssues.filter((issue) => issue.role === 'story'));
    const tasks = $derived(visibleIssues.filter((issue) => issue.role !== 'story'));
    const totalTasks = $derived(issues.data.filter((issue) => issue.role !== 'story').length);
    const gridStyle = $derived(
        `grid-template-columns: repeat(${statuses.length + (current.has_stories ? 1 : 0)}, minmax(16rem, 1fr))`,
    );

    // Counted once per change rather than per cell, so large boards stay cheap to render.
    const countBy = (list: Issue[], key: (issue: Issue) => number | null) => {
        const counts = new Map<number | null, number>();
        for (const issue of list) {
            counts.set(key(issue), (counts.get(key(issue)) ?? 0) + 1);
        }
        return counts;
    };
    const tasksPerStatus = $derived(countBy(tasks, (task) => task.status_id));
    const tasksPerStory = $derived(countBy(tasks, (task) => task.parent_id));
    const storyNames = $derived(new Map(allStories.map((story) => [story.id, story.name])));
    const statusNames = $derived(new Map(statuses.map((status) => [status.id, status.name])));

    // Kanban boards and backlogs hide long-closed issues; this toggles them.
    const closedToggleUrl = $derived.by(() => {
        const options = { query: { ...filtersToQuery(filters), ...(withOlderClosed ? {} : { closed: 'all' }) } };
        return current.has_sprints ? backlog(current.id, options) : show(current.id, options);
    });

    // Space starts a keyboard drag, leaving Enter free to open the focused card.
    setKeyboardDragTrigger('space');

    // Each draggable cell (a status column, split by story on story boards)
    // keeps a local copy of its issues so dnd-action can preview drags.
    const zoneKey = (statusId: number, parentId: number | null) =>
        `${current.has_stories ? (parentId ?? 'none') : 'all'}:${statusId}`;

    const buildZones = () => {
        const built: Record<string, Issue[]> = {};
        const ordered = [...(current.has_stories ? tasks : visibleIssues)].sort(
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

    // On sprint boards this page shows one sprint (or the backlog), so issues
    // that move in or out of it should appear or disappear.
    const belongsToView = (issue: Issue) =>
        !current.has_sprints || issue.sprint_id === (sprint?.data.id ?? null);

    // With both stories and sprints, which stories are shown (and each story's
    // task total) depends on its tasks in other sprints, which the client
    // doesn't have. Changes that could affect that are refetched instead.
    const derivesStoryView = $derived(current.has_stories && current.has_sprints);

    const refetchIssues = () => router.reload({ only: ['issues', 'olderClosedCount'] });

    const upsertIssue = (list: Issue[], issue: Issue, sorts?: Record<number, number> | null) => {
        const existing = list.find((candidate) => candidate.id === issue.id);

        // A story can be shown because of its tasks even when it isn't assigned to this view.
        if (!belongsToView(issue) && !(existing && issue.role === 'story')) {
            return list.filter((candidate) => candidate.id !== issue.id);
        }

        const merged = existing
            ? list.map((candidate) => (candidate.id === issue.id ? { ...candidate, ...issue } : candidate))
            : [...list, issue];

        return sorts
            ? merged.map((candidate) => (candidate.id in sorts ? { ...candidate, sort: sorts[candidate.id] } : candidate))
            : merged;
    };

    // Deleting a story leaves its tasks without one, as the server does.
    const withoutIssue = (list: Issue[], id: number) =>
        list
            .filter((issue) => issue.id !== id)
            .map((issue) => (issue.parent_id === id ? { ...issue, parent_id: null } : issue));

    const handleIssueUpdated = (issue: Issue, sorts: Record<number, number> | null) => {
        const existing = issues.data.find((candidate) => candidate.id === issue.id);
        const changesStructure = existing
            ? existing.sprint_id !== issue.sprint_id || existing.parent_id !== issue.parent_id
            : belongsToView(issue);

        if (derivesStoryView && changesStructure) {
            refetchIssues();
        } else {
            router.replaceProp('issues.data', (list: Issue[]) => upsertIssue(list, issue, sorts));
        }
    };

    onMount(() => {
        const echo = getEcho();

        if (!echo) {
            return;
        }

        const channelName = `boards.${current.id}`;

        echo.private(channelName)
            .listen('.issue.created', ({ issue }: { issue: Issue }) =>
                derivesStoryView
                    ? refetchIssues()
                    : router.replaceProp('issues.data', (list: Issue[]) => upsertIssue(list, issue)),
            )
            .listen('.issue.updated', ({ issue, sorts }: { issue: Issue; sorts: Record<number, number> | null }) =>
                handleIssueUpdated(issue, sorts),
            )
            .listen('.issue.deleted', ({ id }: { id: number }) =>
                derivesStoryView
                    ? refetchIssues()
                    : router.replaceProp('issues.data', (list: Issue[]) => withoutIssue(list, id)),
            )
            .listen('.board.updated', () =>
                router.reload({
                    only: ['board', 'issues', 'members', 'labels', 'sprint', 'sprints', 'openSprints', 'previousSprint', 'nextSprint'],
                }),
            )
            .listen('.board.deleted', () => router.visit(index()));

        return () => echo.leave(channelName);
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
                ...(current.has_sprints
                    ? [{ title: sprint?.data.slug ?? 'Backlog', href: sprint ? showSprint([current.id, sprint.data.slug]) : backlog(current.id) }]
                    : []),
            ],
        });
    });
</script>

<AppHead title={current.name} />

{#snippet dropZone(statusId: number, parentId: number | null, class_: string)}
    {@const key = zoneKey(statusId, parentId)}
    {@const statusName = statusNames.get(statusId)}
    {@const storyName = current.has_stories ? ((parentId && storyNames.get(parentId)) ?? 'No story') : null}
    <div
        class={['flex flex-col gap-2 rounded-md p-2', class_]}
        data-status={statusId}
        aria-label={storyName ? `${storyName}, ${statusName}` : statusName}
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

{#snippet storyTaskCount(story: Issue)}
    {@const total = story.children_count ?? 0}
    {#if current.has_sprints && total > 0}
        {@const here = tasksPerStory.get(story.id) ?? 0}
        <p class="px-1 pt-1 text-xs text-muted-foreground">
            {#if here === total}
                {total} {total === 1 ? 'task' : 'tasks'}
            {:else}
                {here} of {total} tasks in {sprint ? 'this sprint' : 'the backlog'}
            {/if}
        </p>
    {/if}
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
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div class="flex flex-wrap items-center gap-4">
            <h1 class="text-xl font-semibold">{current.name}</h1>
            {#if current.has_sprints}
                <SprintNav
                    board={current}
                    sprint={sprint?.data ?? null}
                    previous={previousSprint?.data ?? null}
                    next={nextSprint?.data ?? null}
                    sprints={sprints.data}
                    openSprints={openSprints.data}
                />
            {/if}
        </div>
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

    {#if issues.data.length > 0}
        <IssueFilters bind:filters members={members.data} labels={labels.data} shown={tasks.length} total={totalTasks} />
    {/if}

    {#if !sprint && (olderClosedCount > 0 || withOlderClosed)}
        <p class="text-sm text-muted-foreground">
            {#if withOlderClosed}
                Showing all closed issues.
                <Link href={closedToggleUrl} class="text-foreground underline underline-offset-4" preserveScroll>
                    Hide issues closed over {closedIssueDays} days ago
                </Link>
            {:else}
                {olderClosedCount} {olderClosedCount === 1 ? 'issue' : 'issues'} closed over {closedIssueDays} days ago
                {olderClosedCount === 1 ? 'is' : 'are'} hidden.
                <Link href={closedToggleUrl} class="text-foreground underline underline-offset-4" preserveScroll>
                    Show {olderClosedCount === 1 ? 'it' : 'them'}
                </Link>
            {/if}
        </p>
    {/if}

    {#if current.has_stories && issues.data.length === 0}
        <EmptyState
            message={current.has_sprints
                ? `Nothing in ${sprint ? 'this sprint' : 'the backlog'} yet. Start with a story, or add a task on its own.`
                : 'No issues yet. Start with a story, or add a task on its own.'}
            class="p-8"
        >
            <div class="flex gap-2">
                <Button size="sm" onclick={() => openCreate('story')}><Plus class="size-4" /> New story</Button>
                <Button size="sm" variant="outline" onclick={() => openCreate('task')}><Plus class="size-4" /> New task</Button>
            </div>
        </EmptyState>
    {:else if current.has_stories && visibleIssues.length === 0}
        <EmptyState message="No issues match the filters.">
            <Button size="sm" variant="outline" onclick={() => (filters = { ...noFilters })}>Clear filters</Button>
        </EmptyState>
    {:else if current.has_stories}
        <div class="overflow-x-auto">
            <div class="grid min-w-max gap-px overflow-hidden rounded-lg border bg-border" style={gridStyle}>
                <h2 class="bg-muted px-3 py-2 text-sm font-medium">Story</h2>
                {#each statuses as status (status.id)}
                    <h2 class="bg-muted px-3 py-2 text-sm font-medium">
                        {status.name}
                        <span class="ml-1 text-xs font-normal text-muted-foreground">
                            {tasksPerStatus.get(status.id) ?? 0}<span class="sr-only"> tasks</span>
                        </span>
                    </h2>
                {/each}

                {#each stories as story (story.id)}
                    <div class="flex items-start gap-1 bg-background p-2" data-story={story.id}>
                        <div class="min-w-0 flex-1">
                            <IssueCard issue={story} />
                            {@render storyTaskCount(story)}
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
                <section
                    class="flex min-w-64 flex-1 flex-col rounded-lg border bg-muted/40"
                    aria-labelledby="status-{status.id}-heading"
                >
                    <header class="flex items-center justify-between gap-2 px-3 py-2">
                        <h2 id="status-{status.id}-heading" class="text-sm font-medium">
                            {status.name}
                            <span class="ml-1 text-xs font-normal text-muted-foreground">
                                {(zones[zoneKey(status.id, null)] ?? []).length}<span class="sr-only"> issues</span>
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
        stories={allStories}
        labels={labels.data}
        sprints={openSprints.data}
        sprintId={sprint && !sprint.data.closed_at ? sprint.data.id : undefined}
        role={dialogRole}
        statusId={dialogStatusId}
        parentId={dialogParentId}
    />
{/key}
