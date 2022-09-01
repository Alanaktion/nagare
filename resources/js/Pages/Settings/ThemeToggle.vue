<template>
    <JetActionSection>
        <template #title>
            Theme
        </template>

        <template #description>
            Adjust the visual style of the app
        </template>

        <template #content>
            <div class="space-y-4">
                <div class="flex items-center">
                    <input id="auto" value="auto" v-model="theme" type="radio" class="focus:ring-indigo-500 dark:focus:ring-indigo-400 h-4 w-4 text-indigo-600 border-gray-300">
                    <label for="auto" class="ml-3 block text-sm font-medium text-gray-700 dark:text-neutral-300">
                        Automatic
                    </label>
                </div>
                <div class="flex items-center">
                    <input id="light" value="light" v-model="theme" type="radio" class="focus:ring-indigo-500 dark:focus:ring-indigo-400 h-4 w-4 text-indigo-600 border-gray-300">
                    <label for="light" class="ml-3 block text-sm font-medium text-gray-700 dark:text-neutral-300">
                        Light
                    </label>
                </div>
                <div class="flex items-center">
                    <input id="dark" value="dark" v-model="theme" type="radio" class="focus:ring-indigo-500 dark:focus:ring-indigo-400 h-4 w-4 text-indigo-600 border-gray-300">
                    <label for="dark" class="ml-3 block text-sm font-medium text-gray-700 dark:text-neutral-300">
                        Dark
                    </label>
                </div>
            </div>
        </template>
    </JetActionSection>
</template>

<script>
    import JetActionSection from '@/Jetstream/ActionSection.vue';

    export default {
        components: {
            JetActionSection,
        },

        data() {
            return {
                theme: 'auto',
            }
        },

        watch: {
            theme(val) {
                const { classList } = document.querySelector('html');
                if (val == 'auto') {
                    localStorage.removeItem('theme');
                    if (window.matchMedia('(prefers-color-scheme: dark)').matches) {
                        classList.add('dark');
                    } else {
                        classList.remove('dark');
                    }
                } else {
                    localStorage.theme = val;
                    if (val == 'light') {
                        classList.remove('dark');
                    } else {
                        classList.add('dark');
                    }
                }
            },
        },

        mounted() {
            if ('theme' in localStorage) {
                this.theme = localStorage.theme;
            }
        },
    }
</script>
