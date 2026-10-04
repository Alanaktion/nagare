<script module lang="ts">
    import { index } from '@/routes/boards';

    export const layout = {
        breadcrumbs: [{ title: 'Boards', href: index() }],
    };
</script>

<script lang="ts">
    import { Link, router } from '@inertiajs/svelte';
    import Plus from '@lucide/svelte/icons/plus';
    import AppHead from '@/components/AppHead.svelte';
    import EmptyState from '@/components/EmptyState.svelte';
    import Heading from '@/components/Heading.svelte';
    import { Badge } from '@/components/ui/badge';
    import { Button } from '@/components/ui/button';
    import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
    import { create, restore, show } from '@/routes/boards';
    import type { Board } from '@/types';

    let {
        boards,
        archivedBoards,
    }: {
        boards: { data: Board[] };
        archivedBoards: { data: Board[] };
    } = $props();

    const describe = (board: Board) =>
        [board.has_stories ? 'Stories' : null, board.has_sprints ? `${board.sprint_cycle} sprints` : null]
            .filter(Boolean)
            .join(' · ') || 'Tasks only';
</script>

<AppHead title="Boards" />

<div class="space-y-8 p-4">
    <div class="flex items-start justify-between gap-4">
        <Heading title="Boards" description="Boards you are a member of." />
        <Button asChild>
            {#snippet children(props)}
                <Link {...props} href={create()}><Plus class="size-4" /> New board</Link>
            {/snippet}
        </Button>
    </div>

    {#if boards.data.length === 0}
        <EmptyState message="You aren't a member of any boards yet. Create one to get started." class="p-8">
            <Button asChild>
                {#snippet children(props)}
                    <Link {...props} href={create()}><Plus class="size-4" /> Create a board</Link>
                {/snippet}
            </Button>
        </EmptyState>
    {:else}
        <ul class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            {#each boards.data as board (board.id)}
                <li>
                    <Link href={show(board.id)} class="block rounded-xl focus-visible:ring-2 focus-visible:ring-ring">
                        <Card class="transition-colors hover:bg-accent">
                            <CardHeader>
                                <CardTitle class="flex items-center justify-between gap-2">
                                    {board.name}
                                    {#if board.role === 'admin'}<Badge variant="secondary">Admin</Badge>{/if}
                                </CardTitle>
                            </CardHeader>
                            <CardContent class="text-sm text-muted-foreground capitalize">
                                {describe(board)}
                            </CardContent>
                        </Card>
                    </Link>
                </li>
            {/each}
        </ul>
    {/if}

    {#if archivedBoards.data.length > 0}
        <section class="space-y-3">
            <Heading variant="small" title="Deleted boards" description="Admins can restore these boards." />
            <ul class="divide-y rounded-lg border">
                {#each archivedBoards.data as board (board.id)}
                    <li class="flex items-center justify-between gap-4 p-3">
                        <span class="text-sm">{board.name}</span>
                        <Button
                            variant="outline"
                            size="sm"
                            onclick={() => router.post(restore(board.id), {}, { preserveScroll: true })}
                        >
                            Restore
                        </Button>
                    </li>
                {/each}
            </ul>
        </section>
    {/if}
</div>
