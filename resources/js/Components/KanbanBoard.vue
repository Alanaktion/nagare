<template>
    <div class="grid grid-kanban sm:gap-px bg-gray-300 dark:bg-neutral-700 sm:border-b border-gray-300 dark:border-neutral-700 w-full overflow-auto">
        <!-- Headers -->
        <div
            v-for="status in statuses"
            :key="`${status.id}-header`"
            class="hidden sm:block sticky top-0 bg-white dark:bg-neutral-800 shadow-sm px-3 py-2">
            {{ status.name }}
        </div>

        <!-- Cells -->
        <div
            v-for="status in statuses"
            :key="status.id"
            class="px-3 sm:py-2 bg-gray-100 dark:bg-neutral-900 flex flex-col gap-3">
            <BoardCard
                v-for="card in getCardsByStatusId(status.id)"
                :key="card.id"
                :card="card"
            />
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue';
import BoardCard from './BoardCard.vue';

const props = defineProps(['boardId', 'statuses']);
const loading = ref(false);
const cards = ref([]);

const loadCards = () => {
    loading.value = true;
    axios.get(`/api/boards/${props.boardId}/cards`).then(({ data }) => {
        cards.value = data;
        loading.value = false;
    });
};
const getCardsByStatusId = (statusId) => {
    return _.filter(cards.value, c => c.status_id == statusId);
};

loadCards();
</script>
