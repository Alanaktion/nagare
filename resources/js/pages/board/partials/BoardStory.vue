<script setup lang="ts">
import type { Issue } from '@/types';

defineProps<{
    story: Issue;
}>();
</script>

<template>
    <div class="flex min-h-20 flex-col justify-between px-3 py-2">
        <div class="flex items-center justify-between">
            <div class="mb-1 leading-tight">
                {{ story.name }}
            </div>
            <div class="text-sm text-zinc-500">S{{ story.id }}</div>
        </div>
        <div v-if="story.assigned" class="-mx-1 flex items-center gap-2 text-sm leading-tight text-zinc-500 dark:text-zinc-400">
            <UserInfoInline :user="story.assigned" />
        </div>
        <div v-else class="text-sm leading-tight text-zinc-500 dark:text-zinc-400">Unassigned</div>
    </div>
</template>
