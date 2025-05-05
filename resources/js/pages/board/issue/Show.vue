<script setup lang="ts">
import IconButton from '@/components/IconButton.vue';
import Button from '@/components/ui/button/Button.vue';
import { Tooltip, TooltipContent, TooltipTrigger } from '@/components/ui/tooltip';
import UserInfoInline from '@/components/UserInfoInline.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { Issue } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { BetweenVerticalStart, LayoutList, Plus } from 'lucide-vue-next';
import { computed, ref, toRefs } from 'vue';
import IssueModal from '../partials/IssueModal.vue';

interface Props {
    issue: Issue;
}
const props = defineProps<Props>();
const { issue } = toRefs(props);
const issueModel = computed(() => issue.value);
const editing = ref(false);

const getUser = (id: number | undefined) => {
    if (typeof id === 'undefined') return undefined;
    return issue.value.board.users.find((u) => u.id === id);
};
const update = (data: Issue) => {
    for (const key of Object.keys(data) as Array<keyof Issue>) {
        if (key == 'board') {
            continue;
        }
        // @ts-expect-error assignment from iteration is safe here
        issue.value[key] = data[key];
    }
    issue.value.assigned = getUser(issue.value.assigned_id);
};
</script>

<template>
    <AppLayout>
        <Head :title="`#${issue.id} - ${issue.name}`" />

        <header class="border-sidebar-border/70 border-b bg-zinc-50 dark:bg-zinc-900">
            <div class="flex flex-col justify-between p-4 sm:flex-row lg:p-6">
                <div>
                    <h1 class="mb-0.5 text-2xl font-medium lg:text-3xl">{{ issue.name }}</h1>
                    <div class="text-muted-foreground flex items-center gap-4 text-sm md:gap-6 lg:text-base">
                        <div :title="new Date(issue.created_at).toLocaleString()">
                            Created
                            <time :datetime="issue.created_at">{{ new Date(issue.created_at).toLocaleDateString() }}</time>
                        </div>
                        <Tooltip>
                            <TooltipTrigger asChild>
                                <Link :href="route('boards.show', [issue.board.id])" class="flex items-center gap-1 hover:text-indigo-500 md:gap-2">
                                    <LayoutList class="inline-block size-4" />
                                    <span class="sr-only">Board:</span>
                                    {{ issue.board.name }}
                                </Link>
                            </TooltipTrigger>
                            <TooltipContent side="bottom">
                                <p>Board</p>
                            </TooltipContent>
                        </Tooltip>
                        <Tooltip>
                            <TooltipTrigger asChild>
                                <div class="flex items-center gap-1 md:gap-2" tabindex="0">
                                    <BetweenVerticalStart class="inline-block size-4" />
                                    <span class="sr-only">Status:</span>
                                    {{ issue.status?.name }}
                                </div>
                            </TooltipTrigger>
                            <TooltipContent side="bottom">
                                <p>Status</p>
                            </TooltipContent>
                        </Tooltip>
                    </div>
                </div>
                <div class="flex gap-2">
                    <Button variant="outline" @click="editing = true">Edit</Button>
                    <Button>Complete</Button>
                </div>
            </div>
        </header>

        <div class="px-4 py-6 lg:px-6">
            <div class="flex flex-col gap-4 sm:flex-row sm:justify-between">
                <div class="md:max-w-2xl">
                    <div class="whitespace-pre-wrap" v-text="issue.description" />
                </div>
                <div class="md:w-60">
                    <div class="border-border mb-2 border-b-2 pb-1">
                        <span class="font-medium">Assigned</span>
                    </div>
                    <UserInfoInline v-if="issue.assigned_id" :user="issue.assigned" />
                    <div v-else class="text-muted-foreground">Unassigned</div>

                    <br />

                    <div class="border-border mb-2 flex items-center justify-between border-b-2 pb-1">
                        <span class="font-medium">Collaborators</span>
                        <IconButton variant="outline" size="icon" class="size-6 shadow-none" :icon="Plus" label="Add collaborator" />
                    </div>
                    <div class="text-muted-foreground">No collaborators</div>

                    <br />

                    <div class="border-border mb-2 flex items-center justify-between border-b-2 pb-1">
                        <span class="font-medium">Attachments</span>
                        <IconButton variant="outline" size="icon" class="size-6 shadow-none" :icon="Plus" label="Add attachment" />
                    </div>
                    <div class="text-muted-foreground">No attachments</div>
                </div>
            </div>
        </div>

        <IssueModal
            :users="issue.board.users"
            :board-id="issue.board.id"
            v-model="issueModel"
            v-model:show="editing"
            @close="editing = false"
            @saved="update"
        />
    </AppLayout>
</template>
