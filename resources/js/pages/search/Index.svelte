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
    import type { Board, Issue, Paginated } from '@/types';

    let {
        query,
        minimumLength,
        boards,
        issues,
    }: {
        query: string;
        minimumLength: number;
        boards: { data: Board[] };
        issues: Paginated<Issue>;
    } = $props();

    // The input owns the search text after the first render.
    // svelte-ignore state_referenced_locally
    let text = $state(query);
    let timer: ReturnType<typeof setTimeout> | undefined;

    const isSearching = $derived(query.length >= minimumLength);
    const hasResults = $derived(boards.data.length > 0 || issues.data.length > 0);

    const searchFor = () => {
        clearTimeout(timer);
        timer = setTimeout(() => {
            router.get(
                search.url(),
                text.trim() ? { q: text.trim() } : {},
                { preserveState: true, replace: true, only: ['query', 'boards', 'issues'] },
            );
        }, 250);
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
