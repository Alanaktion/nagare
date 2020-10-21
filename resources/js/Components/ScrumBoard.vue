<template>
    <div class="grid grid-scrum sm:gap-px bg-gray-300 sm:border-b border-gray-300 w-full overflow-auto">
        <!-- Headers -->
        <div class="hidden sm:block sticky top-0 left-0 z-10 bg-white shadow-sm px-3 py-2 font-semibold">
            Story
        </div>
        <div
            v-for="status in statuses"
            :key="`${status.id}-header`"
            class="hidden sm:block sticky top-0 bg-white shadow-sm px-3 py-2">
            {{ status.name }}
        </div>

        <!-- Loop over stories, then over statuses, then cards by story/status -->
        <template v-if="!loading">
            <template v-for="story in stories">
                <div :key="story.id" class="bg-gray-200 px-3 py-2 sm:p-2 sm:sticky sm:left-0">
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
                    class="px-3 sm:py-2 bg-gray-100 flex flex-col gap-3">
                    <board-card
                        v-for="card in getCardsByStoryAndStatusId(story.id, status.id)"
                        :key="card.id"
                        :card="card"
                    />
                </div>
            </template>
        </template>
    </div>
</template>

<script>
import BoardCard from './BoardCard.vue'

export default {
    components: {
        BoardCard,
    },
    props: ['boardId', 'sprint', 'statuses'],
    data: () => ({
        loading: false,
        stories: [],
        cards: [],
    }),
    methods: {
        async loadData() {
            this.loading = true;
            const storyResponse = await axios.get(`/api/boards/${this.$props.boardId}/stories/${this.$props.sprint}`);
            this.stories = storyResponse.data;
            const cardResponse = await axios.get(`/api/boards/${this.$props.boardId}/cards/${this.$props.sprint}`);
            this.cards = cardResponse.data;
            this.loading = false;
        },
        getCardsByStoryAndStatusId(storyId, statusId) {
            return _.filter(this.cards, c => c.story_id == storyId && c.status_id == statusId);
        },
    },
    mounted() {
        this.loadData();
    },
}
</script>
