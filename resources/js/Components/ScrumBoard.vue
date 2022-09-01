<template>
    <div class="grid grid-scrum sm:gap-px bg-gray-300 dark:bg-neutral-700 sm:border-b border-gray-300 dark:border-neutral-700 w-full overflow-auto">
        <!-- Headers -->
        <div class="hidden sm:block sticky top-0 left-0 z-10 bg-white dark:bg-neutral-800 shadow-sm px-3 py-2 font-semibold">
            Story
        </div>
        <div
            v-for="status in statuses"
            :key="`${status.id}-header`"
            class="hidden sm:block sticky top-0 bg-white dark:bg-neutral-800 shadow-sm px-3 py-2">
            {{ status.name }}
        </div>

        <!-- Loop over stories, then over statuses, then cards by story/status -->
        <template v-if="!loading">
            <template v-for="story in stories" :key="story.id">
                <div class="bg-gray-200 dark:bg-neutral-900 px-3 py-2 sm:p-2 sm:sticky sm:left-0">
                    <div class="bg-white shadow rounded-sm px-3 py-2" >
                        <div class="leading-tight mb-1">
                            {{ story.name }}
                        </div>
                        <div class="text-sm leading-tight text-teal-700">
                            {{ story.assigned.name }}
                        </div>
                    </div>
                </div>
                <div
                    v-for="status in statuses"
                    :key="`${story.id}-${status.id}`"
                    class="px-3 sm:py-2 bg-gray-100 dark:bg-neutral-900 flex flex-col gap-3">
                    <BoardCard
                        v-for="card in getCardsByStoryAndStatusId(story.id, status.id)"
                        :key="card.id"
                        :card="card"
                    />
                </div>
            </template>
        </template>
    </div>
</template>

<script setup>
import { ref } from 'vue';
import BoardCard from './BoardCard.vue';

const props = defineProps(['boardId', 'sprint', 'statuses']);

const loading = ref(false);
const stories = ref([]);
const cards = ref([]);

const loadData = async () => {
    loading.value = true;
    const storyResponse = await axios.get(`/api/boards/${props.boardId}/stories/${props.sprint}`);
    stories.value = storyResponse.data;
    const cardResponse = await axios.get(`/api/boards/${props.boardId}/cards/${props.sprint}`);
    cards.value = cardResponse.data;
    loading.value = false;
};
const getCardsByStoryAndStatusId = (storyId, statusId) => {
    return _.filter(cards.value, c => c.story_id == storyId && c.status_id == statusId);
};

loadData();
</script>
