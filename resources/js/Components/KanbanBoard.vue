<template>
    <div class="grid grid-kanban sm:gap-px bg-gray-300 sm:border-b border-gray-300 w-full overflow-auto">
        <!-- Headers -->
        <div
            v-for="status in statuses"
            :key="`${status.id}-header`"
            class="hidden sm:block sticky top-0 bg-white shadow-sm px-3 py-2">
            {{ status.name }}
        </div>

        <!-- Cells -->
        <div
            v-for="status in statuses"
            :key="status.id"
            class="px-3 sm:py-2 bg-gray-100 flex flex-col gap-3">
            <board-card
                v-for="card in getCardsByStatusId(status.id)"
                :key="card.id"
                :card="card"
            />
        </div>
    </div>
</template>

<script>
import BoardCard from './BoardCard.vue'

export default {
    components: {
        BoardCard,
    },
    props: ['boardId', 'statuses'],
    data: () => ({
        loading: false,
        cards: [],
    }),
    methods: {
        loadCards() {
            this.loading = true;
            axios.get(`/api/boards/${this.$props.boardId}/cards`).then(({ data }) => {
                this.cards = data;
                this.loading = false;
            });
        },
        getCardsByStatusId(statusId) {
            return _.filter(this.cards, c => c.status_id == statusId);
        },
    },
    mounted() {
        this.loadCards();
    },
}
</script>
