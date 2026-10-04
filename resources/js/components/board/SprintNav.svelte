<script lang="ts">
    import { Link, router } from '@inertiajs/svelte';
    import ChevronLeft from '@lucide/svelte/icons/chevron-left';
    import ChevronRight from '@lucide/svelte/icons/chevron-right';
    import Plus from '@lucide/svelte/icons/plus';
    import CloseSprintDialog from '@/components/board/CloseSprintDialog.svelte';
    import NewSprintDialog from '@/components/board/NewSprintDialog.svelte';
    import { Button } from '@/components/ui/button';
    import { sprintLabel } from '@/lib/sprints';
    import { backlog } from '@/routes/boards';
    import { show } from '@/routes/boards/sprints';
    import type { Board, Sprint } from '@/types';

    let {
        board,
        sprint,
        previous,
        next,
        sprints,
        openSprints,
    }: {
        board: Board;
        sprint: Sprint | null;
        previous: Sprint | null;
        next: Sprint | null;
        sprints: Sprint[];
        openSprints: Sprint[];
    } = $props();

    let newSprintOpen = $state(false);
    let closeSprintOpen = $state(false);

    const selected = $derived(sprint?.slug ?? 'backlog');

    const goTo = (slug: string) =>
        router.visit(slug === 'backlog' ? backlog(board.id) : show([board.id, slug]));
</script>

<nav class="flex flex-wrap items-center gap-2" aria-label="Sprints">
    <Button
        variant="outline"
        size="icon"
        class="size-8"
        aria-label="Previous sprint"
        disabled={!previous}
        asChild={!!previous}
    >
        {#snippet children(props)}
            {#if previous}
                <Link {...props} href={show([board.id, previous.slug])}><ChevronLeft class="size-4" /></Link>
            {:else}
                <ChevronLeft class="size-4" />
            {/if}
        {/snippet}
    </Button>

    <select
        aria-label="Sprint"
        class="h-8 rounded-md border border-input bg-background px-2 text-sm shadow-sm focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
        value={selected}
        onchange={(event) => goTo(event.currentTarget.value)}
    >
        <option value="backlog">Backlog</option>
        {#if sprint && !sprints.some((listed) => listed.id === sprint.id)}
            <option value={sprint.slug}>{sprintLabel(sprint)}</option>
        {/if}
        {#each sprints as listed (listed.id)}
            <option value={listed.slug}>{sprintLabel(listed)}</option>
        {/each}
    </select>

    <Button
        variant="outline"
        size="icon"
        class="size-8"
        aria-label="Next sprint"
        disabled={!next}
        asChild={!!next}
    >
        {#snippet children(props)}
            {#if next}
                <Link {...props} href={show([board.id, next.slug])}><ChevronRight class="size-4" /></Link>
            {:else}
                <ChevronRight class="size-4" />
            {/if}
        {/snippet}
    </Button>

    <Button variant="ghost" size="sm" onclick={() => (newSprintOpen = true)}>
        <Plus class="size-4" /> New sprint
    </Button>

    {#if sprint && !sprint.closed_at}
        <Button variant="ghost" size="sm" onclick={() => (closeSprintOpen = true)}>Close sprint</Button>
    {/if}
    {#if sprint?.closed_at}
        <span class="text-sm text-muted-foreground">This sprint is closed.</span>
    {/if}
</nav>

<NewSprintDialog bind:open={newSprintOpen} {board} />
{#if sprint}
    <CloseSprintDialog
        bind:open={closeSprintOpen}
        {sprint}
        otherOpenSprints={openSprints.filter((other) => other.id !== sprint.id)}
    />
{/if}
