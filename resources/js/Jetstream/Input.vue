<template>
    <input
        class="focus:ring-indigo-500 focus:border-indigo-500 dark:focus:border-indigo-500 dark:bg-trueGray-800 block w-full sm:text-sm border-gray-300 dark:border-trueGray-700 rounded-md"
        :value="value"
        @input="$emit('input', $event.target.value)"
        ref="input">
</template>

<script>
    export default {
        props: ['value'],

        methods: {
            focus() {
                this.$refs.input.focus()
            }
        }
    }
</script>
