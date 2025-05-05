<script setup lang="ts">
import UserInfoInline from '@/components/UserInfoInline.vue';
import type { Issue } from '@/types';
import { Link } from '@inertiajs/vue3';

defineProps<{
    issue: Issue;
}>();
defineEmits(['edit']);
</script>

<template>
    <div
        class="flex min-h-20 flex-col justify-between rounded-sm border bg-white px-3 py-2 shadow-sm dark:bg-zinc-900"
        :data-issue="issue.id"
        @click="$emit('edit')"
    >
        <div class="flex justify-between">
            <div
                class="mb-1 leading-tight"
                :class="{
                    'line-through': issue.closed_at,
                }"
            >
                {{ issue.name }}
            </div>
            <Link :href="route('issues.show', [issue.id])" class="text-sm text-zinc-500 hover:text-blue-500 hover:underline" @click.stop>
                #{{ issue.id }}
            </Link>
        </div>
        <div v-if="issue.assigned" class="-mx-1 flex items-center gap-2 text-sm leading-tight text-zinc-500 dark:text-zinc-400">
            <UserInfoInline :user="issue.assigned" />
        </div>
        <div v-else class="text-sm leading-tight text-zinc-500 dark:text-zinc-400">Unassigned</div>
    </div>
</template>
