<script setup lang="ts">
import IconButton from '@/components/IconButton.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { Board, BreadcrumbItem, Issue, Sprint } from '@/types';
import { Head } from '@inertiajs/vue3';
import axios from 'axios';
import { Plus } from 'lucide-vue-next';
import Sortable from 'sortablejs';
import { computed, onBeforeUnmount, onMounted, ref, type Ref } from 'vue';
import BoardIssue from './partials/BoardIssue.vue';
import BoardStory from './partials/BoardStory.vue';
import IssueModal from './partials/IssueModal.vue';

interface Props {
    board: Board;
    sprint?: Sprint;
}
const props = defineProps<Props>();

const statuses = computed(() => props.board.statuses);
const columnCount = computed(() => {
    const statusCount = props.board.statuses.length;
    if (props.board.type === 'scrum') {
        return statusCount + 1;
    }
    return statusCount;
});

const issues: Ref<Issue[]> = ref(props.board.issues);
const stories: Ref<Issue[]> = ref(props.board.stories);
const editing = ref(false);
const editingIssue: Ref<Issue | undefined> = ref(undefined);

const getIssuesByStatusId = (statusId: number) => {
    return issues.value.filter((c) => c.status_id == statusId).sort((a, b) => a.sort - b.sort);
};
const getIssuesByStoryAndStatusId = (storyId: number, statusId: number) => {
    return issues.value.filter((c) => c.parent_id == storyId && c.status_id == statusId).sort((a, b) => a.sort - b.sort);
};

const getUser = (id: number | undefined) => {
    if (typeof id === 'undefined') return undefined;
    return props.board.users.find((u) => u.id === id);
};

const submitIssueUpdate = (data: Partial<Issue>) => {
    axios.patch(route('issues.update', [data.id]), data).then(({ data }) => updateIssueState(data));
};
const updateIssueState = (data: Issue) => {
    const issue = issues.value.find((c) => c.id === data.id);
    if (typeof issue === 'undefined') {
        issues.value.push(data);
        return;
    }
    for (const key of Object.keys(data) as Array<keyof Issue>) {
        // @ts-expect-error assignment from iteration is safe here
        issue[key] = data[key];
    }
    issue.assigned = getUser(issue.assigned_id);
};

// Get the model data for a issue element
const elementModel = (el: Element | null) => {
    if (!el) {
        return null;
    }
    const id = parseInt(el.getAttribute('data-issue')!, 10);
    return issues.value.find((c) => c.id === id);
};
const updateSortable = () => {
    if (!boardGrid.value) return;
    boardGrid.value.querySelectorAll<HTMLElement>('[data-status]').forEach((el) => {
        Sortable.create(el, {
            group: el.getAttribute('data-story') || 'status',
            draggable: '[data-issue]',
            delay: 300,
            delayOnTouchOnly: true,
            onEnd: (e) => {
                // TODO: prevent weird duplication issue when using issues wrapped in HoverIssue handlers.
                if (e.from === e.to && e.oldIndex === e.newIndex) {
                    return;
                }

                // Calculate new sort index
                let sort = 0;
                const prev = elementModel(e.item.previousElementSibling);
                const next = elementModel(e.item.nextElementSibling);
                if (next && prev) {
                    sort = (next.sort + prev.sort) / 2;
                } else if (next) {
                    sort = next.sort - 1;
                } else if (prev) {
                    sort = prev.sort + 1;
                }

                submitIssueUpdate({
                    id: parseInt(e.item.getAttribute('data-issue')!, 10),
                    status_id: parseInt(e.to.getAttribute('data-status')!, 10),
                    sort,
                });
            },
        });
    });
};

interface IssueEvent {
    model: Issue;
}
const boardGrid: Ref<HTMLElement | null> = ref(null);
onMounted(() => {
    updateSortable();

    // Watch board changes
    window.Echo.private(`boards.${props.board.id}`)
        .listen('.IssueUpdated', ({ model }: IssueEvent) => updateIssueState(model))
        .listen('.IssueCreated', ({ model }: IssueEvent) => updateIssueState(model));
});
onBeforeUnmount(() => {
    window.Echo.leave(`boards.${props.board.id}`);
});

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: props.board.name,
        href: route('boards.show', [props.board.id], false),
    },
];
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs" full-width>
        <Head :title="board.name" />

        <Teleport defer to="#nav-right">
            <!-- TODO: this should add a story on a Scrum board, and add a Issue on a Kanban board. -->
            <IconButton
                @click="
                    editing = true;
                    editingIssue = undefined;
                "
                variant="default"
                label="New Issue"
                :icon="Plus"
            />
        </Teleport>

        <div
            ref="boardGrid"
            class="grid-board sm:grid-board-desktop grid min-h-[calc(100vh-4rem-1px)] w-full snap-x snap-proximity gap-px overflow-x-auto border-zinc-300 bg-zinc-300 sm:snap-none dark:border-zinc-700 dark:bg-zinc-700 dark:text-zinc-200"
            :style="`--grid-cols: ${columnCount}`"
        >
            <!-- Headers -->
            <div
                v-if="board.type === 'scrum'"
                class="sticky top-0 snap-center bg-white px-3 py-2 text-sm leading-5 font-medium text-zinc-900 shadow-xs dark:bg-zinc-900 dark:text-zinc-100"
            >
                Story
            </div>
            <div
                v-for="status in statuses"
                :key="`${status.id}-header`"
                class="sticky top-0 snap-center bg-white px-3 py-2 text-sm leading-5 font-medium text-zinc-900 shadow-xs dark:bg-zinc-900 dark:text-zinc-100"
            >
                {{ status.name }}
            </div>

            <!-- Cells -->
            <template v-if="board.type === 'scrum'">
                <template v-for="story in stories" :key="story.id">
                    <div class="bg-zinc-200 p-2 px-3 dark:bg-zinc-900">
                        <BoardStory :story="story" />
                    </div>
                    <div
                        v-for="status in statuses"
                        :key="`${story.id}-${status.id}`"
                        :data-story="story.id"
                        :data-status="status.id"
                        class="flex flex-col gap-3 bg-zinc-100 px-3 py-2 dark:bg-zinc-950"
                    >
                        <BoardIssue
                            v-for="issue in getIssuesByStoryAndStatusId(story.id, status.id)"
                            :key="issue.id"
                            :issue="issue"
                            @edit="
                                editing = true;
                                editingIssue = issue;
                            "
                        />
                    </div>
                </template>
            </template>
            <div
                v-else
                v-for="status in statuses"
                :key="status.id"
                :data-status="status.id"
                class="flex flex-col gap-3 bg-zinc-100 px-3 py-2 dark:bg-zinc-950"
            >
                <BoardIssue
                    v-for="issue in getIssuesByStatusId(status.id)"
                    :key="issue.id"
                    :issue="issue"
                    @edit="
                        editing = true;
                        editingIssue = issue;
                    "
                />
            </div>
        </div>

        <IssueModal
            :users="board.users"
            :board-id="board.id"
            v-model="editingIssue"
            v-model:show="editing"
            @close="editing = false"
            @saved="(data) => updateIssueState(data)"
        />
    </AppLayout>
</template>
