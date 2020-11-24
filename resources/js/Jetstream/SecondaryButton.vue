<template>
    <button :type="type" class="inline-flex items-center px-4 py-2 bg-white dark:bg-trueGray-700 border border-gray-300 dark:border-trueGray-700 rounded-md font-semibold text-xs text-gray-700 dark:text-gray-300 uppercase tracking-widest shadow-sm hover:text-gray-500 dark:hover:bg-trueGray-600 dark:hover:border-trueGray-600 focus:outline-none focus:border-blue-300 focus:shadow-outline-blue active:text-gray-800 dark:active:text-gray-300 active:bg-gray-50 dark:active:bg-trueGray-600 transition ease-in-out duration-150">
        <slot></slot>
    </button>
</template>

<script>
    export default {
        props: {
            type: {
                type: String,
                default: 'button',
            },
        }
    }
</script>
