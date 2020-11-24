<template>
    <div>
        <button type="submit" class="block w-full px-4 py-2 text-sm leading-5 text-gray-700 dark:text-gray-300 text-left hover:bg-gray-100 dark:hover:bg-trueGray-700 focus:outline-none focus:bg-gray-100 dark:focus:bg-trueGray-700 transition duration-150 ease-in-out" v-if="as == 'button'">
            <slot></slot>
        </button>

        <inertia-link :href="href" class="block px-4 py-2 text-sm leading-5 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-trueGray-700 focus:outline-none focus:bg-gray-100 dark:focus:bg-trueGray-700 transition duration-150 ease-in-out" v-else>
            <slot></slot>
        </inertia-link>
    </div>
</template>

<script>
    export default {
        props: ['href', 'as']
    }
</script>
