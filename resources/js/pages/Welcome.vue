<script setup lang="ts">
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { Head, Link } from '@inertiajs/vue3';
import { CalendarRange, ChartLine, Users, WalletCards } from 'lucide-vue-next';
</script>

<template>
    <Head title="Welcome" />

    <div class="flex min-h-screen flex-col items-center bg-zinc-100 p-6 lg:justify-center lg:p-8 dark:bg-zinc-950">
        <header class="mx-auto mb-6 w-full text-sm not-has-[nav]:hidden lg:max-w-5xl">
            <nav class="flex items-center justify-end gap-4">
                <Link
                    v-if="$page.props.auth.user"
                    :href="route('dashboard')"
                    class="inline-block rounded-sm border border-[#19140035] px-5 py-1.5 text-sm leading-normal text-[#1b1b18] hover:border-[#1915014a] dark:border-[#3E3E3A] dark:text-[#EDEDEC] dark:hover:border-[#62605b]"
                >
                    Dashboard
                </Link>
                <template v-else>
                    <Link
                        :href="route('login')"
                        class="inline-block rounded-sm border border-transparent px-5 py-1.5 text-sm leading-normal text-[#1b1b18] hover:border-[#19140035] dark:text-[#EDEDEC] dark:hover:border-[#3E3E3A]"
                    >
                        Log in
                    </Link>
                    <Link
                        :href="route('register')"
                        class="inline-block rounded-sm border border-[#19140035] px-5 py-1.5 text-sm leading-normal text-[#1b1b18] hover:border-[#1915014a] dark:border-[#3E3E3A] dark:text-[#EDEDEC] dark:hover:border-[#62605b]"
                    >
                        Register
                    </Link>
                </template>
            </nav>
        </header>

        <main class="mx-auto overflow-hidden bg-white shadow-xl sm:rounded-lg lg:max-w-5xl dark:bg-zinc-800">
            <div
                class="border-b border-zinc-200 bg-white p-6 lg:p-8 dark:border-zinc-700 dark:bg-zinc-800 dark:bg-gradient-to-bl dark:from-zinc-700/50 dark:via-transparent"
            >
                <div class="flex items-center gap-4">
                    <AppLogoIcon class="block size-12 lg:size-16" />
                    <div class="font-serif text-4xl text-zinc-600 dark:text-zinc-400">ながれ</div>
                </div>

                <p class="mt-6 leading-relaxed text-zinc-500 dark:text-zinc-400">
                    Nagare is an all-in-one project management and task board web app designed to help teams of all sizes streamline their workflow
                    and increase productivity. With its intuitive interface, flexible customization options, and robust set of features, Nagare
                    empowers teams to work more efficiently, collaborate effectively, and deliver results-driven projects. Whether you're a Scrum team
                    looking for a traditional scrum board or a Kanban team seeking a visual workflow management tool, Nagare has you covered.
                </p>
            </div>

            <div class="grid grid-cols-1 gap-6 bg-zinc-200 p-6 md:grid-cols-2 lg:gap-8 lg:p-8 dark:bg-zinc-800/25">
                <div>
                    <div class="flex items-center">
                        <WalletCards class="size-6 stroke-zinc-400" />
                        <h2 class="ms-3 text-xl font-semibold text-zinc-900 dark:text-white">Card-based workflow</h2>
                    </div>

                    <p class="mt-4 text-sm leading-relaxed text-zinc-500 dark:text-zinc-400">
                        Nagare's card-based interface allows teams to break down complex projects into actionable, movable cards that can be organized
                        in a Kanban-style board. This enables seamless visualization of tasks and workflows, making it easy for team members to
                        prioritize, assign, and track progress.
                    </p>
                </div>

                <div>
                    <div class="flex items-center">
                        <CalendarRange class="size-6 stroke-zinc-400" />
                        <h2 class="ms-3 text-xl font-semibold text-zinc-900 dark:text-white">Sprint planning</h2>
                    </div>

                    <p class="mt-4 text-sm leading-relaxed text-zinc-500 dark:text-zinc-400">
                        Nagare supports traditional Scrum-style management with features like sprint planning, daily stand-ups, and retrospectives.
                        Teams can easily create sprints, set deadlines, and track velocity to optimize their workflow and meet project goals.
                    </p>
                </div>

                <div>
                    <div class="flex items-center">
                        <Users class="size-6 stroke-zinc-400" />
                        <h2 class="ms-3 text-xl font-semibold text-zinc-900 dark:text-white">Team-centric</h2>
                    </div>

                    <p class="mt-4 text-sm leading-relaxed text-zinc-500 dark:text-zinc-400">
                        Each team member or group has complete ownership over their board, allowing them to customize columns, cards, and workflows to
                        suit their unique needs. This flexibility ensures that teams stay organized and focused on their priorities.
                    </p>
                </div>

                <div>
                    <div class="flex items-center">
                        <ChartLine class="size-6 stroke-zinc-400" />
                        <h2 class="ms-3 text-xl font-semibold text-zinc-900 dark:text-white">Reporting and insights</h2>
                    </div>

                    <p class="mt-4 text-sm leading-relaxed text-zinc-500 dark:text-zinc-400">
                        Nagare provides detailed analytics and insights into team performance, helping teams identify bottlenecks, optimize workflows,
                        and make data-driven decisions. With automated reporting features, teams can easily generate custom reports on progress,
                        velocity, and team productivity.
                    </p>
                </div>
            </div>
        </main>

        <div class="hidden h-14 lg:block"></div>
    </div>
</template>
