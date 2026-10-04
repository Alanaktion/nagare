<script lang="ts">
    import { Link, setLayoutProps } from '@inertiajs/svelte';
    import Plus from '@lucide/svelte/icons/plus';
    import Settings from '@lucide/svelte/icons/settings';
    import AppHead from '@/components/AppHead.svelte';
    import IssueCard from '@/components/board/IssueCard.svelte';
    import IssueDialog from '@/components/board/IssueDialog.svelte';
    import { Button } from '@/components/ui/button';
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
    const orphanTasks = $derived(
        tasks.filter((task) => task.parent_id === null),
    );
    const gridStyle = $derived(
        `grid-template-columns: repeat(${statuses.length + (current.has_stories ? 1 : 0)}, minmax(16rem, 1fr))`,
    );

    const tasksIn = (list: Issue[], statusId: number) =>
        list.filter((issue) => issue.status_id === statusId);

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

{#snippet addButton(label: string, role: IssueRole, statusId?: number, parentId?: number)}
    <button
        type="button"
        class="flex w-full items-center justify-center gap-1 rounded-md p-1 text-xs text-muted-foreground hover:bg-accent hover:text-accent-foreground"
        aria-label={label}
        onclick={() => openCreate(role, statusId, parentId)}
    >
        <Plus class="size-3.5" /> Add
    </button>
{/snippet}

{#snippet taskCell(cellTasks: Issue[], statusId: number, parentId?: number)}
    <div class="flex min-h-16 flex-col gap-2 p-2" data-status={statusId}>
        {#each cellTasks as task (task.id)}
            <IssueCard issue={task} />
        {/each}
        {@render addButton('Add task', 'task', statusId, parentId)}
    </div>
{/snippet}

<div class="flex h-full flex-col gap-4 p-4">
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

    <div class="flex-1 overflow-x-auto">
        <div class="grid min-w-max gap-px overflow-hidden rounded-lg border bg-border" style={gridStyle}>
            {#if current.has_stories}
                <h2 class="bg-muted px-3 py-2 text-sm font-medium">Story</h2>
            {/if}
            {#each statuses as status (status.id)}
                <h2 class="bg-muted px-3 py-2 text-sm font-medium">
                    {status.name}
                    <span class="ml-1 text-xs font-normal text-muted-foreground">
                        {tasksIn(current.has_stories ? tasks : issues.data, status.id).length}
                    </span>
                </h2>
            {/each}

            {#if current.has_stories}
                {#each stories as story (story.id)}
                    <div class="bg-background p-2" data-story={story.id}>
                        <IssueCard issue={story} />
                    </div>
                    {#each statuses as status (status.id)}
                        <div class="bg-background">
                            {@render taskCell(
                                tasksIn(
                                    tasks.filter((task) => task.parent_id === story.id),
                                    status.id,
                                ),
                                status.id,
                                story.id,
                            )}
                        </div>
                    {/each}
                {/each}

                <div class="bg-background p-3 text-sm text-muted-foreground">No story</div>
                {#each statuses as status (status.id)}
                    <div class="bg-background">
                        {@render taskCell(tasksIn(orphanTasks, status.id), status.id)}
                    </div>
                {/each}
            {:else}
                {#each statuses as status (status.id)}
                    <div class="bg-background">
                        {@render taskCell(tasksIn(issues.data, status.id), status.id)}
                    </div>
                {/each}
            {/if}
        </div>
    </div>
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
