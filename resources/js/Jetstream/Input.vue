<script setup>
import { onMounted, ref } from 'vue';

defineProps({
    modelValue: String,
});

defineEmits(['update:modelValue']);

const input = ref(null);

onMounted(() => {
    if (input.value.hasAttribute('autofocus')) {
        input.value.focus();
    }
});

defineExpose({ focus: () => input.value.focus() });
</script>

<template>
    <input
        ref="input"
        class="border-gray-300 dark:border-neutral-700 focus:border-indigo-300 focus:ring focus:ring-indigo-200 dark:focus:border-indigo-500 dark:bg-neutral-800 focus:ring-opacity-50 rounded-md shadow-sm"
        :value="modelValue"
        @input="$emit('update:modelValue', $event.target.value)"
    >
</template>
