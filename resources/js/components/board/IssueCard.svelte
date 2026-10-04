<script lang="ts">
    import { Link } from '@inertiajs/svelte';
    import LabelBadge from '@/components/LabelBadge.svelte';
    import UserAvatar from '@/components/UserAvatar.svelte';
    import { cn } from '@/lib/utils';
    import { show } from '@/routes/issues';
    import type { Issue } from '@/types';

    let { issue }: { issue: Issue } = $props();
</script>

<Link
    href={show(issue.id)}
    class={cn(
        'flex items-start justify-between gap-2 rounded-md border bg-card p-2.5 text-sm text-card-foreground shadow-xs transition-colors hover:bg-accent focus-visible:ring-2 focus-visible:ring-ring',
        issue.closed_at && 'text-muted-foreground',
    )}
    data-issue={issue.id}
    draggable={false}
>
    <span class="min-w-0">
        <span class={cn('block break-words', issue.closed_at && 'line-through')}>
            {issue.name}
            {#if issue.closed_at}<span class="sr-only">(closed)</span>{/if}
            {#if issue.assignee}<span class="sr-only">, assigned to {issue.assignee.name}</span>{/if}
        </span>
        {#if issue.labels && issue.labels.length > 0}
            <span class="mt-1.5 flex flex-wrap gap-1">
                <span class="sr-only">Labels:</span>
                {#each issue.labels as label (label.id)}
                    <LabelBadge {label} />
                {/each}
            </span>
        {/if}
    </span>
    {#if issue.assignee}
        <span aria-hidden="true" class="shrink-0">
            <UserAvatar user={issue.assignee} />
        </span>
    {/if}
</Link>
