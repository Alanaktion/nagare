<script setup>
import { Link } from '@inertiajs/inertia-vue3';

defineProps({
    href: String,
    as: String,
});
</script>

<template>
    <div>
        <button v-if="as == 'button'" type="submit" class="block w-full px-4 py-2 text-sm leading-5 text-gray-700 dark:text-neutral-300 text-left hover:bg-gray-100 dark:hover:bg-neutral-700 focus:outline-none focus:bg-gray-100 dark:focus:bg-neutral-700 transition">
            <slot />
        </button>

        <a v-else-if="as =='a'" :href="href" class="block px-4 py-2 text-sm leading-5 text-gray-700 dark:text-neutral-300 hover:bg-gray-100 dark:hover:bg-neutral-700 focus:outline-none focus:bg-gray-100 dark:focus:bg-neutral-700 transition">
            <slot />
        </a>

        <Link v-else :href="href" class="block px-4 py-2 text-sm leading-5 text-gray-700 dark:text-neutral-300 hover:bg-gray-100 dark:hover:bg-neutral-700 focus:outline-none focus:bg-gray-100 dark:focus:bg-neutral-700 transition">
            <slot />
        </Link>
    </div>
</template>
