<template>
    <!-- TODO: set border color to assigned user color -->
    <div class="border-l-3 bg-white dark:bg-neutral-800 shadow rounded-sm px-3 py-2">
        <div class="leading-tight mb-1">
            {{ card.name }}
        </div>
        <div class="text-sm leading-tight text-teal-700 dark:text-teal-400">
            {{ card.assigned.name }}
        </div>
    </div>
</template>

<script setup>
defineProps({
    card: Object,
});
</script>
