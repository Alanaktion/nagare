<script setup lang="ts">
import { HoverCard, HoverCardContent, HoverCardTrigger } from '@/components/ui/hover-card';
import type { Issue } from '@/types';
import { Calendar } from 'lucide-vue-next';
import BoardIssue from './BoardIssue.vue';

defineProps<{
    issue: Issue;
}>();
// TODO: do Vue events bubble? Or should I explicitly handle passing this up?
defineEmits(['edit']);
</script>

<template>
    <HoverCard>
        <HoverCardTrigger as-child>
            <BoardIssue :issue="issue" />
        </HoverCardTrigger>
        <HoverCardContent>
            <div class="space-y-1">
                <div class="text-muted-foreground flex items-center gap-1 text-xs">
                    <Calendar class="size-4" />
                    <time :datetime="issue.created_at">{{ new Date(issue.created_at).toLocaleDateString() }}</time>
                </div>
                <p class="text-sm">{{ issue.description }}</p>
            </div>
        </HoverCardContent>
    </HoverCard>
</template>
