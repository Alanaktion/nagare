<script setup lang="ts">
import AppContent from '@/components/AppContent.vue';
import AppHeader from '@/components/AppHeader.vue';
import AppShell from '@/components/AppShell.vue';
import type { BreadcrumbItemType } from '@/types';

interface Props {
    breadcrumbs?: BreadcrumbItemType[];
    fullWidth: boolean;
}

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
    fullWidth: false,
});
</script>

<template>
    <AppShell :class="['flex-col', fullWidth && 'full-width']">
        <AppHeader :breadcrumbs="breadcrumbs"/>
        <AppContent :full-width="fullWidth">
            <slot />
        </AppContent>
    </AppShell>
</template>
