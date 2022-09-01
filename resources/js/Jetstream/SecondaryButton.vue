<script setup>
defineProps({
    type: {
        type: String,
        default: 'button',
    },
});
</script>

<template>
    <button :type="type" class="inline-flex items-center px-4 py-2 bg-white dark:bg-neutral-700 border border-gray-300 dark:border-neutral-700 rounded-md font-semibold text-xs text-gray-700 dark:text-neutral-300 uppercase tracking-widest shadow-sm hover:text-gray-500 dark:hover:bg-neutral-600 dark:hover:border-neutral-600 focus:outline-none focus:border-blue-300 focus:ring focus:ring-blue-200 active:text-gray-800 dark:active:text-gray-300 active:bg-gray-50 dark:active:bg-neutral-600 disabled:opacity-25 transition">
        <slot />
    </button>
</template>
