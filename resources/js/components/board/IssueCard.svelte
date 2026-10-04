<script lang="ts">
    import { Link } from '@inertiajs/svelte';
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
>
    <span class={cn('min-w-0 break-words', issue.closed_at && 'line-through')}>{issue.name}</span>
    {#if issue.assignee}
        <UserAvatar user={issue.assignee} class="shrink-0" />
    {/if}
</Link>
