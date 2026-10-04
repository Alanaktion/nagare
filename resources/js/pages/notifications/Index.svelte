<script module lang="ts">
    import { index } from '@/routes/notifications';

    export const layout = {
        breadcrumbs: [{ title: 'Notifications', href: index() }],
    };
</script>

<script lang="ts">
    import { Link, router } from '@inertiajs/svelte';
    import AppHead from '@/components/AppHead.svelte';
    import EmptyState from '@/components/EmptyState.svelte';
    import Heading from '@/components/Heading.svelte';
    import { Button } from '@/components/ui/button';
    import { describeNotification } from '@/lib/notifications';
    import { fullDate, timeAgo } from '@/lib/time';
    import { cn } from '@/lib/utils';
    import { show } from '@/routes/issues';
    import { update } from '@/routes/notifications';
    import type { AppNotification, Paginated } from '@/types';

    let { notifications }: { notifications: Paginated<AppNotification> } = $props();

    const hasUnread = $derived(notifications.data.some((notification) => notification.read_at === null));
</script>

<AppHead title="Notifications" />

<div class="max-w-3xl space-y-6 p-4">
    <div class="flex items-start justify-between gap-4">
        <Heading
            title="Notifications"
            description="Changes to the issues you're watching: status changes, comments, descriptions and assignments."
        />
        {#if hasUnread}
            <Button variant="outline" size="sm" onclick={() => router.put(update(), {}, { preserveScroll: true })}>
                Mark all as read
            </Button>
        {/if}
    </div>

    {#if notifications.data.length === 0}
        <EmptyState message="Nothing yet. You'll be notified when an issue you watch changes." />
    {:else}
        <ul class="divide-y rounded-lg border">
            {#each notifications.data as notification (notification.id)}
                <li>
                    <Link
                        href={show(notification.data.issue_id)}
                        class={cn('flex items-start gap-3 p-3 hover:bg-accent', notification.read_at === null && 'bg-accent/40')}
                    >
                        <span
                            class={cn('mt-1.5 size-2 shrink-0 rounded-full', notification.read_at === null ? 'bg-primary' : 'bg-transparent')}
                            aria-hidden="true"
                        ></span>
                        <div class="min-w-0 flex-1 space-y-0.5">
                            <p class="text-sm">
                                {#if notification.read_at === null}<span class="sr-only">Unread: </span>{/if}
                                <span class="font-medium">{describeNotification(notification.data)}</span>
                                on
                                <span class="font-medium">{notification.data.issue_name}</span>
                            </p>
                            {#if notification.data.kind === 'commented' && notification.data.excerpt}
                                <p class="line-clamp-2 text-sm text-muted-foreground">{notification.data.excerpt}</p>
                            {:else if notification.data.kind === 'changed'}
                                {#each notification.data.changes.filter((change) => change.type === 'description' && change.excerpt) as change}
                                    <p class="line-clamp-2 text-sm text-muted-foreground">{change.excerpt}</p>
                                {/each}
                            {/if}
                            <p class="text-xs text-muted-foreground">
                                {notification.data.board_name} ·
                                <time datetime={notification.created_at} title={fullDate(notification.created_at)}>
                                    {timeAgo(notification.created_at)}
                                </time>
                            </p>
                        </div>
                    </Link>
                </li>
            {/each}
        </ul>

        {#if notifications.meta.last_page > 1}
            <nav class="flex items-center justify-between text-sm text-muted-foreground" aria-label="Pagination">
                <span>Showing {notifications.meta.from}–{notifications.meta.to} of {notifications.meta.total}</span>
                <div class="flex gap-2">
                    <Button variant="outline" size="sm" disabled={!notifications.links.prev} asChild={!!notifications.links.prev}>
                        {#snippet children(props)}
                            {#if notifications.links.prev}
                                <Link {...props} href={notifications.links.prev}>Previous</Link>
                            {:else}
                                Previous
                            {/if}
                        {/snippet}
                    </Button>
                    <Button variant="outline" size="sm" disabled={!notifications.links.next} asChild={!!notifications.links.next}>
                        {#snippet children(props)}
                            {#if notifications.links.next}
                                <Link {...props} href={notifications.links.next}>Next</Link>
                            {:else}
                                Next
                            {/if}
                        {/snippet}
                    </Button>
                </div>
            </nav>
        {/if}
    {/if}
</div>
