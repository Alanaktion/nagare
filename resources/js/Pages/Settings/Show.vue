<template>
    <app-layout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-neutral-300 leading-tight">
                Settings
            </h2>
        </template>

        <div>
            <div class="max-w-7xl mx-auto py-10 sm:px-6 lg:px-8">
                <theme-toggle />
            </div>
        </div>
    </app-layout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import ThemeToggle from './ThemeToggle.vue';
</script>
