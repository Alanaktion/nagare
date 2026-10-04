<script lang="ts">
    import X from '@lucide/svelte/icons/x';
    import { Button } from '@/components/ui/button';
    import { Input } from '@/components/ui/input';
    import { hasFilters, noFilters, type IssueFilters } from '@/lib/filters';
    import type { Label, Member } from '@/types';

    let {
        filters = $bindable(),
        members,
        labels,
        shown,
        total,
    }: {
        filters: IssueFilters;
        members: Member[];
        labels: Label[];
        shown: number;
        total: number;
    } = $props();

    const selectClass =
        'h-8 rounded-md border border-input bg-background px-2 text-sm shadow-sm focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none';
</script>

<search class="flex flex-wrap items-center gap-2" aria-label="Filter issues">
    <Input
        type="search"
        class="h-8 w-48"
        placeholder="Filter issues"
        aria-label="Filter issues by text"
        bind:value={filters.text}
    />

    <select class={selectClass} aria-label="Filter by assignee" bind:value={filters.assignee}>
        <option value="">Any assignee</option>
        <option value="none">Unassigned</option>
        {#each members as member (member.id)}
            <option value={String(member.id)}>{member.name}</option>
        {/each}
    </select>

    {#if labels.length > 0}
        <select class={selectClass} aria-label="Filter by label" bind:value={filters.label}>
            <option value="">Any label</option>
            {#each labels as label (label.id)}
                <option value={String(label.id)}>{label.name}</option>
            {/each}
        </select>
    {/if}

    <Button
        size="sm"
        class="h-8"
        variant={filters.mine ? 'default' : 'outline'}
        aria-pressed={filters.mine}
        onclick={() => (filters.mine = !filters.mine)}
    >
        Only mine
    </Button>

    {#if hasFilters(filters)}
        <p class="text-sm text-muted-foreground" role="status">
            Showing {shown} of {total}
        </p>
        <Button size="sm" variant="ghost" class="h-8" onclick={() => (filters = { ...noFilters })}>
            <X class="size-4" /> Clear
        </Button>
    {/if}
</search>
