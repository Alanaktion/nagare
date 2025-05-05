<script setup lang="ts">
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { useInitials } from '@/composables/useInitials';
import type { User } from '@/types';
import { computed } from 'vue';

interface Props {
    user?: User;
}

const props = defineProps<Props>();

const { getInitials } = useInitials();

// Compute whether we should show the avatar image
const showAvatar = computed(() => props?.user?.avatar && props.user.avatar !== '');
</script>

<template>
    <div class="flex items-center gap-2">
        <Avatar class="size-6 overflow-hidden rounded-sm">
            <AvatarImage v-if="showAvatar && user?.avatar" :src="user.avatar" :alt="user?.name" />
            <AvatarFallback class="rounded-full text-black dark:text-white">
                {{ getInitials(user?.name) }}
            </AvatarFallback>
        </Avatar>

        <div class="flex-1 text-sm leading-tight">
            <span class="truncate font-medium">{{ user?.name }}</span>
        </div>
    </div>
</template>
