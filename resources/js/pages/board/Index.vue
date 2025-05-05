<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import type { Board, BoardType, BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import BoardManager from './partials/BoardManager.vue';

defineProps<{
    boards: Board[];
    boardTypes: BoardType[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Boards',
        href: '/boards',
    },
];
</script>

<template>
    <Head title="Boards" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <BoardManager :boards="boards" :board-types="boardTypes" />
    </AppLayout>
</template>
