<script setup lang="ts">
import TooltipProvider from '@/components/ui/tooltip/TooltipProvider.vue';
import AppLayout from '@/layouts/app/AppHeaderLayout.vue';
import type { BreadcrumbItemType } from '@/types';

interface Props {
    breadcrumbs?: BreadcrumbItemType[];
}

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});
</script>

<template>
    <TooltipProvider :delay-duration="300">
        <AppLayout :breadcrumbs="breadcrumbs">
            <slot />
        </AppLayout>
    </TooltipProvider>
</template>
