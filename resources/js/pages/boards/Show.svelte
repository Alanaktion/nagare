<script lang="ts">
    import { Link, setLayoutProps } from '@inertiajs/svelte';
    import Settings from '@lucide/svelte/icons/settings';
    import AppHead from '@/components/AppHead.svelte';
    import { Button } from '@/components/ui/button';
    import { edit, index, show } from '@/routes/boards';
    import type { Board } from '@/types';

    let { board }: { board: { data: Board } } = $props();

    const current = $derived(board.data);

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

<div class="flex h-full flex-col gap-4 p-4">
    <div class="flex items-center justify-between gap-4">
        <h1 class="text-xl font-semibold">{current.name}</h1>
        <Button variant="outline" size="sm" asChild>
            {#snippet children(props)}
                <Link {...props} href={edit(current.id)}><Settings class="size-4" /> Settings</Link>
            {/snippet}
        </Button>
    </div>

    <div class="flex flex-1 gap-4 overflow-x-auto">
        {#each current.statuses ?? [] as status (status.id)}
            <section class="w-72 shrink-0 rounded-lg border bg-muted/40 p-3">
                <h2 class="text-sm font-medium">{status.name}</h2>
            </section>
        {/each}
    </div>
</div>
