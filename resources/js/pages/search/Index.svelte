<script module lang="ts">
    import { search } from '@/routes';

    export const layout = {
        breadcrumbs: [{ title: 'Search', href: search() }],
    };
</script>

<script lang="ts">
    import { Link, router } from '@inertiajs/svelte';
    import AppHead from '@/components/AppHead.svelte';
    import EmptyState from '@/components/EmptyState.svelte';
    import Heading from '@/components/Heading.svelte';
    import LabelBadge from '@/components/LabelBadge.svelte';
    import { Badge } from '@/components/ui/badge';
    import { Button } from '@/components/ui/button';
    import { Input } from '@/components/ui/input';
    import { cn } from '@/lib/utils';
    import { show as showBoard } from '@/routes/boards';
    import { show as showIssue } from '@/routes/issues';
    import type { Board, Issue, Label, Paginated } from '@/types';

    type SearchFilters = {
        board: number | null;
        label: number | null;
        state: 'open' | 'closed' | null;
        mine: boolean;
    };

    let {
        query,
        minimumLength,
        filters,
        boardOptions,
        labelOptions,
        boards,
        issues,
    }: {
        query: string;
        minimumLength: number;
        filters: SearchFilters;
        boardOptions: Pick<Board, 'id' | 'name'>[];
        labelOptions: { data: Label[] };
        boards: { data: Board[] };
        issues: Paginated<Issue>;
    } = $props();

    const selectClass =
        'h-9 rounded-md border border-input bg-background px-2 text-sm shadow-sm focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none';

    // The input owns the search text after the first render.
    // svelte-ignore state_referenced_locally
    let text = $state(query);
    let timer: ReturnType<typeof setTimeout> | undefined;

    const isSearching = $derived(query.length >= minimumLength);
    const hasResults = $derived(boards.data.length > 0 || issues.data.length > 0);

    const hasFilters = $derived(
        filters.board !== null || filters.label !== null || filters.state !== null || filters.mine,
    );

    // Visit the search with the text and the given filters, leaving out the unset ones.
    const visit = (next: SearchFilters) => {
        const data: Record<string, string> = {};
        if (text.trim()) data.q = text.trim();
        if (next.board !== null) data.board = String(next.board);
        if (next.board !== null && next.label !== null) data.label = String(next.label);
        if (next.state !== null) data.state = next.state;
        if (next.mine) data.mine = '1';

        router.get(search.url(), data, {
            preserveState: true,
            replace: true,
            only: ['query', 'filters', 'labelOptions', 'boards', 'issues'],
        });
    };

    const searchFor = () => {
        clearTimeout(timer);
        timer = setTimeout(() => visit(filters), 250);
    };
</script>

<AppHead title="Search" />

<div class="max-w-3xl space-y-6 p-4">
    <Heading title="Search" description="Find boards and issues on your boards, including by label." />

    <Input
        type="search"
        placeholder="Search issues and boards"
        aria-label="Search issues and boards"
        autofocus
        bind:value={text}
        oninput={searchFor}
    />

    <div class="flex flex-wrap items-center gap-2" role="group" aria-label="Filters">
        <select
            class={selectClass}
            aria-label="Board"
            value={filters.board === null ? '' : String(filters.board)}
            onchange={(event) => {
                const board = event.currentTarget.value;
                visit({ ...filters, board: board === '' ? null : Number(board), label: null });
            }}
        >
            <option value="">All boards</option>
            {#each boardOptions as option (option.id)}
                <option value={String(option.id)}>{option.name}</option>
            {/each}
        </select>

        {#if filters.board !== null && labelOptions.data.length > 0}
            <select
                class={selectClass}
                aria-label="Label"
                value={filters.label === null ? '' : String(filters.label)}
                onchange={(event) => {
                    const label = event.currentTarget.value;
                    visit({ ...filters, label: label === '' ? null : Number(label) });
                }}
            >
                <option value="">Any label</option>
                {#each labelOptions.data as label (label.id)}
                    <option value={String(label.id)}>{label.name}</option>
                {/each}
            </select>
        {/if}

        <select
            class={selectClass}
            aria-label="State"
            value={filters.state ?? ''}
            onchange={(event) => {
                const state = event.currentTarget.value;
                visit({ ...filters, state: state === '' ? null : (state as 'open' | 'closed') });
            }}
        >
            <option value="">Open and closed</option>
            <option value="open">Open</option>
            <option value="closed">Closed</option>
        </select>

        <Button
            size="sm"
            class="h-9"
            variant={filters.mine ? 'default' : 'outline'}
            aria-pressed={filters.mine}
            onclick={() => visit({ ...filters, mine: !filters.mine })}
        >
            Assigned to me
        </Button>

        {#if hasFilters}
            <Button
                size="sm"
                variant="ghost"
                class="h-9"
                onclick={() => visit({ board: null, label: null, state: null, mine: false })}
            >
                Clear filters
            </Button>
        {/if}
    </div>

    {#if !isSearching}
        <p class="text-sm text-muted-foreground">Type at least {minimumLength} characters to search.</p>
    {:else if !hasResults}
        <EmptyState message={`Nothing found for "${query}".`} />
    {:else}
        {#if boards.data.length > 0}
            <section class="space-y-2" aria-labelledby="search-boards">
                <h2 id="search-boards" class="text-sm font-medium text-muted-foreground">Boards</h2>
                <ul class="divide-y rounded-lg border">
                    {#each boards.data as board (board.id)}
                        <li>
                            <Link href={showBoard(board.id)} class="block p-3 text-sm font-medium hover:bg-accent">
                                {board.name}
                            </Link>
                        </li>
                    {/each}
                </ul>
            </section>
        {/if}

        {#if issues.data.length > 0}
            <section class="space-y-2" aria-labelledby="search-issues">
                <h2 id="search-issues" class="text-sm font-medium text-muted-foreground">
                    Issues ({issues.meta.total})
                </h2>
                <ul class="divide-y rounded-lg border">
                    {#each issues.data as issue (issue.id)}
                        <li>
                            <Link href={showIssue(issue.id)} class="flex items-center justify-between gap-3 p-3 hover:bg-accent">
                                <div class="min-w-0 space-y-1">
                                    <p class={cn('truncate text-sm font-medium', issue.closed_at && 'text-muted-foreground line-through')}>
                                        {issue.name}
                                    </p>
                                    <p class="truncate text-xs text-muted-foreground">{issue.board?.name}</p>
                                    {#if issue.labels && issue.labels.length > 0}
                                        <div class="flex flex-wrap gap-1">
                                            {#each issue.labels as label (label.id)}
                                                <LabelBadge {label} />
                                            {/each}
                                        </div>
                                    {/if}
                                </div>
                                {#if issue.status}
                                    <Badge variant={issue.closed_at ? 'secondary' : 'outline'}>{issue.status.name}</Badge>
                                {/if}
                            </Link>
                        </li>
                    {/each}
                </ul>

                {#if issues.meta.last_page > 1}
                    <nav class="flex items-center justify-between text-sm text-muted-foreground" aria-label="Pagination">
                        <span>Showing {issues.meta.from}–{issues.meta.to} of {issues.meta.total}</span>
                        <div class="flex gap-2">
                            <Button variant="outline" size="sm" disabled={!issues.links.prev} asChild={!!issues.links.prev}>
                                {#snippet children(props)}
                                    {#if issues.links.prev}
                                        <Link {...props} href={issues.links.prev} preserveState>Previous</Link>
                                    {:else}
                                        Previous
                                    {/if}
                                {/snippet}
                            </Button>
                            <Button variant="outline" size="sm" disabled={!issues.links.next} asChild={!!issues.links.next}>
                                {#snippet children(props)}
                                    {#if issues.links.next}
                                        <Link {...props} href={issues.links.next} preserveState>Next</Link>
                                    {:else}
                                        Next
                                    {/if}
                                {/snippet}
                            </Button>
                        </div>
                    </nav>
                {/if}
            </section>
        {/if}
    {/if}
</div>
