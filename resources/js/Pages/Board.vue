<template>
    <app-layout>
        <component :is="'style'" type="text/css">
            @media (min-width: {{ columnCount * 12 }}rem) {
                .grid-kanban {
                    grid-template-columns: repeat({{ columnCount }}, minmax(12rem, 1fr));
                }
            }
            @media (min-width: {{ (columnCount + 1) * 12 }}rem) {
                .grid-scrum {
                    grid-template-columns: repeat({{ columnCount + 1 }}, minmax(12rem, 1fr));
                }
            }
        </component>
        <KanbanBoard
            v-if="$page.props.board.type === 'kanban'"
            :board-id="$page.props.board.id"
            :statuses="statuses"
        />
        <ScrumBoard
            v-else-if="$page.props.board.type === 'scrum'"
            :board-id="$page.props.board.id"
            :sprint="$page.sprint"
            :statuses="statuses"
        />
    </app-layout>
</template>

<script setup>
import { computed, ref } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import KanbanBoard from '@/Components/KanbanBoard.vue';
import ScrumBoard from '@/Components/ScrumBoard.vue';

const loading = ref(true);
const statuses = ref([]);
const columnCount = computed(() => statuses.value.length);

// TODO: figure out if this is how stuff works.
const props = defineProps({
    board: Object,
});

axios.get(`/api/boards/${props.board.id}/statuses`).then(({ data }) => {
    statuses.value = data;
    loading.value = false;
});
</script>
