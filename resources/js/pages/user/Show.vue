<script setup lang="ts">
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { useInitials } from '@/composables/useInitials';
import AppLayout from '@/layouts/AppLayout.vue';
import type { User } from '@/types';
import { Head } from '@inertiajs/vue3';

interface Props {
    user: User;
}
defineProps<Props>();

const { getInitials } = useInitials();
</script>

<template>
    <AppLayout>
        <Head :title="user.name" />

        <header class="border-sidebar-border/70 border-b bg-zinc-50 dark:bg-zinc-800">
            <div class="flex items-center gap-4 p-4 lg:gap-8 lg:p-6">
                <Avatar class="size-12 overflow-hidden rounded-full sm:size-20">
                    <AvatarImage v-if="user.avatar" :src="user.avatar" :alt="user.name" />
                    <AvatarFallback class="rounded-full text-black dark:text-white">
                        {{ getInitials(user.name) }}
                    </AvatarFallback>
                </Avatar>

                <div class="grid flex-1 text-left leading-tight">
                    <span class="mb-0.5 text-2xl font-medium lg:text-3xl">{{ user.name }}</span>
                    <span class="text-muted-foreground text-sm">{{ user.email }}</span>
                </div>
            </div>
        </header>

        <div class="px-4 py-6 lg:px-6">user details here</div>
    </AppLayout>
</template>
