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
        <kanban-board
            v-if="$page.board.type === 'kanban'"
            :board-id="$page.board.id"
            :statuses="statuses" />
        <scrum-board
            v-else-if="$page.board.type === 'scrum'"
            :board-id="$page.board.id"
            :sprint="$page.sprint"
            :statuses="statuses" />
    </app-layout>
</template>

<script>
    import AppLayout from './../Layouts/AppLayout'
    import KanbanBoard from './../Components/KanbanBoard'
    import ScrumBoard from './../Components/ScrumBoard'

    export default {
        components: {
            AppLayout,
            KanbanBoard,
            ScrumBoard,
        },
        data: () => ({
            loading: false,
            statuses: [],
        }),
        computed: {
            columnCount() {
                return this.statuses.length;
            },
        },
        mounted() {
            this.loading = true;
            axios.get(`/api/boards/${this.$page.board.id}/statuses`).then(({ data }) => {
                this.statuses = data;
                this.loading = false;
            });
        },
    }
</script>
