<script lang="ts">
    import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
    import { getInitials } from '@/lib/initials';
    import { cn } from '@/lib/utils';
    import type { Member } from '@/types';

    let {
        user,
        class: className = '',
    }: {
        user: Pick<Member, 'name' | 'avatar_url'>;
        class?: string;
    } = $props();
</script>

<span title={user.name} class="inline-flex">
    <Avatar class={cn('size-6 overflow-hidden rounded-full text-[10px]', className)}>
        {#if user.avatar_url}
            <AvatarImage src={user.avatar_url} alt={user.name} />
        {/if}
        <AvatarFallback>{getInitials(user.name)}</AvatarFallback>
    </Avatar>
</span>
