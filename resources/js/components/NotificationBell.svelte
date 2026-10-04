<script lang="ts">
    import { Link, page, router } from '@inertiajs/svelte';
    import Bell from '@lucide/svelte/icons/bell';
    import { onMount } from 'svelte';
    import { toast } from 'svelte-sonner';
    import { getEcho } from '@/lib/echo';
    import { describeNotification } from '@/lib/notifications';
    import { index } from '@/routes/notifications';
    import type { NotificationData } from '@/types';

    const unread = $derived(page.props.unreadNotifications ?? 0);
    const userId = $derived(page.props.auth.user?.id);

    // New notifications arrive over the user's private channel, so the count
    // and the notifications page update without a reload.
    onMount(() => {
        const echo = getEcho();

        if (!echo || userId === undefined) {
            return;
        }

        const channelName = `App.Models.User.${userId}`;

        echo.private(channelName).notification((notification: NotificationData) => {
            toast(`${describeNotification(notification)} on "${notification.issue_name}"`);
            router.reload({ only: ['unreadNotifications', 'notifications'] });
        });

        return () => echo.leave(channelName);
    });
</script>

<Link
    href={index()}
    class="relative ml-auto inline-flex size-9 items-center justify-center rounded-md hover:bg-accent focus-visible:ring-2 focus-visible:ring-ring"
    aria-label={unread > 0 ? `Notifications, ${unread} unread` : 'Notifications'}
>
    <Bell class="size-5" />
    {#if unread > 0}
        <span
            class="absolute top-0.5 right-0.5 flex min-w-4 items-center justify-center rounded-full bg-primary px-1 text-[10px] leading-4 font-medium text-primary-foreground"
            aria-hidden="true"
        >
            {unread > 99 ? '99+' : unread}
        </span>
    {/if}
</Link>
