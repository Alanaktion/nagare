<script setup lang="ts">
import TooltipProvider from '@/components/ui/tooltip/TooltipProvider.vue';
import AppLayout from '@/layouts/app/AppHeaderLayout.vue';
import type { BreadcrumbItemType } from '@/types';

interface Props {
    breadcrumbs?: BreadcrumbItemType[];
    fullWidth?: boolean;
}

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
    fullWidth: false,
});
</script>

<template>
    <TooltipProvider :delay-duration="300">
        <AppLayout :breadcrumbs="breadcrumbs" :full-width="fullWidth">
            <slot />
        </AppLayout>
    </TooltipProvider>
</template>
