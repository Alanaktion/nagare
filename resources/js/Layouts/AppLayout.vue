<template>
    <div class="min-h-screen bg-gray-100 dark:bg-neutral-900">
        <Head :title="title" />

        <JetBanner />

        <nav class="bg-white dark:bg-neutral-800 border-b border-gray-100 dark:border-neutral-700 sticky shadow top-0">
            <!-- Primary Navigation Menu -->
            <div class="px-4 sm:px-6">
                <div class="flex justify-between h-16">
                    <div class="flex">
                        <!-- Logo -->
                        <div class="flex-shrink-0 flex items-center">
                            <Link href="/dashboard">
                                <JetApplicationMark class="block h-9 w-auto" />
                            </Link>
                        </div>

                        <!-- Navigation Links -->
                        <div class="hidden space-x-8 sm:-my-px sm:ml-10 sm:flex">
                            <JetNavLink
                                v-for="board in $page.props.auth.boards"
                                :key="board.id"
                                :href="`/boards/${board.slug}`"
                                :active="$inertia.page.url == `/boards/${board.slug}`">
                                {{ board.name }}
                            </JetNavLink>
                        </div>
                    </div>

                    <!-- Settings Dropdown -->
                    <div class="hidden sm:flex sm:items-center sm:ml-6">
                        <div class="ml-3 relative">
                            <JetDropdown align="right" width="48">
                                <template #trigger>
                                    <button v-if="$page.props.jetstream.managesProfilePhotos" class="flex text-sm border-2 border-transparent rounded-full focus:outline-none focus:border-gray-300 transition duration-150 ease-in-out">
                                        <img class="h-8 w-8 rounded-full object-cover" :src="$page.props.user.profile_photo_url" :alt="$page.props.user.name" />
                                    </button>

                                    <button v-else class="flex items-center text-sm font-medium text-gray-500 hover:text-gray-700 hover:border-gray-300 focus:outline-none focus:text-gray-700 focus:border-gray-300 transition duration-150 ease-in-out">
                                        <div>{{ $page.props.user.name }}</div>

                                        <div class="ml-1">
                                            <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                            </svg>
                                        </div>
                                    </button>
                                </template>

                                <template #content>
                                    <!-- Account Management -->
                                    <div class="block px-4 py-2 text-xs text-gray-400 dark:text-neutral-500">
                                        Manage Account
                                    </div>

                                    <JetDropdownLink :href="route('profile.show')">
                                        Profile
                                    </JetDropdownLink>

                                    <JetDropdownLink href="/user/settings">
                                        Settings
                                    </JetDropdownLink>

                                    <JetDropdownLink v-if="$page.props.jetstream.hasApiFeatures" :href="route('api-tokens.index')">
                                        API Tokens
                                    </JetDropdownLink>

                                    <div class="border-t border-gray-100 dark:border-neutral-700"></div>

                                    <!-- Team Management -->
                                    <template v-if="$page.props.jetstream.hasTeamFeatures">
                                        <div class="block px-4 py-2 text-xs text-gray-400 dark:text-neutral-500">
                                            Manage Team
                                        </div>

                                        <!-- Team Settings -->
                                        <JetDropdownLink :href="route('teams.show', $page.props.user.current_team)">
                                            Team Settings
                                        </JetDropdownLink>

                                        <JetDropdownLink v-if="$page.props.jetstream.canCreateTeams" :href="route('teams.create')">
                                            Create New Team
                                        </JetDropdownLink>

                                        <div class="border-t border-gray-100 dark:border-neutral-700"></div>

                                        <!-- Team Switcher -->
                                        <div class="block px-4 py-2 text-xs text-gray-400 dark:text-neutral-500">
                                            Switch Teams
                                        </div>

                                        <template v-for="team in $page.props.user.all_teams" :key="team.id">
                                            <form @submit.prevent="switchToTeam(team)">
                                                <JetDropdownLink as="button">
                                                    <div class="flex items-center">
                                                        <svg v-if="team.id == $page.props.user.current_team_id" class="mr-2 h-5 w-5 text-green-400" fill="none" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" stroke="currentColor" viewBox="0 0 24 24"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                                        <div>{{ team.name }}</div>
                                                    </div>
                                                </JetDropdownLink>
                                            </form>
                                        </template>

                                        <div class="border-t border-gray-100 dark:border-neutral-700"></div>
                                    </template>

                                    <!-- Authentication -->
                                    <form @submit.prevent="logout">
                                        <JetDropdownLink as="button">
                                            Logout
                                        </JetDropdownLink>
                                    </form>
                                </template>
                            </JetDropdown>
                        </div>
                    </div>

                    <!-- Hamburger -->
                    <div class="-mr-2 flex items-center sm:hidden">
                        <button @click="showingNavigationDropdown = ! showingNavigationDropdown" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 focus:text-gray-500 transition duration-150 ease-in-out">
                            <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                                <path :class="{'hidden': showingNavigationDropdown, 'inline-flex': ! showingNavigationDropdown }" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                                <path :class="{'hidden': ! showingNavigationDropdown, 'inline-flex': showingNavigationDropdown }" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Responsive Navigation Menu -->
            <div :class="{'block': showingNavigationDropdown, 'hidden': ! showingNavigationDropdown}" class="sm:hidden">
                <div class="pt-2 pb-3 space-y-1">
                    <JetResponsiveNavLink
                        v-for="board in $page.props.auth.boards"
                        :key="board.id"
                        :href="`/boards/${board.slug}`"
                        :active="$inertia.page.url == `/boards/${board.slug}`">
                        {{ board.name }}
                    </JetResponsiveNavLink>
                </div>

                <!-- Responsive Settings Options -->
                <div class="pt-4 pb-1 border-t border-gray-200 dark:border-gray-700">
                    <div class="flex items-center px-4">
                        <div class="flex-shrink-0">
                            <img class="h-10 w-10 rounded-full" :src="$page.props.user.profile_photo_url" :alt="$page.props.user.name" />
                        </div>

                        <div class="ml-3">
                            <div class="font-medium text-base text-gray-800">{{ $page.props.user.name }}</div>
                            <div class="font-medium text-sm text-gray-500">{{ $page.props.user.email }}</div>
                        </div>
                    </div>

                    <div class="mt-3 space-y-1">
                        <JetResponsiveNavLink href="/user/profile" :active="$page.currentRouteName == 'profile.show'">
                            Profile
                        </JetResponsiveNavLink>

                        <JetResponsiveNavLink href="/user/api-tokens" :active="$page.currentRouteName == 'api-tokens.index'" v-if="$page.props.jetstream.hasApiFeatures">
                            API Tokens
                        </JetResponsiveNavLink>

                        <!-- Authentication -->
                        <form method="POST" @submit.prevent="logout">
                            <JetResponsiveNavLink as="button">
                                Logout
                            </JetResponsiveNavLink>
                        </form>

                        <!-- Team Management -->
                        <template v-if="$page.props.jetstream.hasTeamFeatures">
                            <div class="border-t border-gray-200 dark:border-gray-700"></div>

                            <div class="block px-4 py-2 text-xs text-gray-400 dark:text-neutral-500">
                                Manage Team
                            </div>

                            <!-- Team Settings -->
                            <JetResponsiveNavLink :href="'/teams/' + $page.props.user.current_team.id" :active="$page.currentRouteName == 'teams.show'">
                                Team Settings
                            </JetResponsiveNavLink>

                            <JetResponsiveNavLink href="/teams/create" :active="$page.currentRouteName == 'teams.create'">
                                Create New Team
                            </JetResponsiveNavLink>

                            <div class="border-t border-gray-200 dark:border-gray-700"></div>

                            <!-- Team Switcher -->
                            <div class="block px-4 py-2 text-xs text-gray-400 dark:text-neutral-500">
                                Switch Teams
                            </div>

                            <template v-for="team in $page.props.user.all_teams" :key="team.id">
                                <form @submit.prevent="switchToTeam(team)">
                                    <JetResponsiveNavLink as="button">
                                        <div class="flex items-center">
                                            <svg v-if="team.id == $page.props.user.current_team_id" class="mr-2 h-5 w-5 text-green-400" fill="none" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" stroke="currentColor" viewBox="0 0 24 24"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                            <div>{{ team.name }}</div>
                                        </div>
                                    </JetResponsiveNavLink>
                                </form>
                            </template>
                        </template>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Page Heading -->
        <header v-show="!!$slots.header">
            <div class="max-w-7xl mx-auto pt-6 px-4 sm:px-6 lg:px-8">
                <slot name="header"></slot>
                <div class="pb-6 border-b dark:border-neutral-700"></div>
            </div>
        </header>

        <!-- Page Content -->
        <main>
            <slot />
        </main>
    </div>
</template>

<script setup>
import { computed, ref } from 'vue';
import { Inertia } from '@inertiajs/inertia';
import { Head, Link } from '@inertiajs/inertia-vue3';
import JetApplicationMark from '@/Jetstream/ApplicationMark.vue';
import JetBanner from '@/Jetstream/Banner.vue';
import JetDropdown from '@/Jetstream/Dropdown.vue';
import JetDropdownLink from '@/Jetstream/DropdownLink.vue';
import JetNavLink from '@/Jetstream/NavLink.vue';
import JetResponsiveNavLink from '@/Jetstream/ResponsiveNavLink.vue';

defineProps({
    title: String,
});

const showingNavigationDropdown = ref(false);

const switchToTeam = (team) => {
    Inertia.put(route('current-team.update'), {
        team_id: team.id,
    }, {
        preserveState: false,
    });
};

const logout = () => {
    Inertia.post(route('logout'));
};

const path = computed(() => {
    return window.location.pathname;
});
</script>
