<script module lang="ts">
    import { index } from '@/routes/users';

    export const layout = {
        breadcrumbs: [{ title: 'Users', href: index() }],
    };
</script>

<script lang="ts">
    import { Link, router } from '@inertiajs/svelte';
    import AppHead from '@/components/AppHead.svelte';
    import EmptyState from '@/components/EmptyState.svelte';
    import Heading from '@/components/Heading.svelte';
    import { Button } from '@/components/ui/button';
    import { Card } from '@/components/ui/card';
    import { Input } from '@/components/ui/input';
    import UserAvatar from '@/components/UserAvatar.svelte';
    import { show } from '@/routes/users';
    import type { Member, Paginated } from '@/types';

    let {
        users,
        search: initialSearch,
    }: {
        users: Paginated<Member>;
        search: string;
    } = $props();

    // The input owns the search text after the first render.
    // svelte-ignore state_referenced_locally
    let search = $state(initialSearch);
    let searchTimer: ReturnType<typeof setTimeout> | undefined;

    const searchUsers = () => {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(() => {
            router.get(
                index.url(),
                search.trim() ? { search } : {},
                { preserveState: true, replace: true, only: ['users', 'search'] },
            );
        }, 250);
    };
</script>

<AppHead title="Users" />

<div class="space-y-6 p-4">
    <Heading title="Users" description="Everyone with an account." />

    <Input
        type="search"
        class="max-w-sm"
        placeholder="Search by name or email"
        aria-label="Search users"
        bind:value={search}
        oninput={searchUsers}
    />

    {#if users.data.length === 0}
        <EmptyState message={search.trim() ? `No users match "${search.trim()}".` : 'No users yet.'} />
    {:else}
        <ul class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
            {#each users.data as user (user.id)}
                <li>
                    <Link href={show(user.id)} class="block rounded-xl focus-visible:ring-2 focus-visible:ring-ring">
                        <Card class="flex-row items-center gap-3 p-4 transition-colors hover:bg-accent">
                            <UserAvatar {user} class="size-10 text-xs" />
                            <div class="min-w-0">
                                <p class="truncate font-medium">{user.name}</p>
                                <p class="truncate text-sm text-muted-foreground">{user.email}</p>
                            </div>
                        </Card>
                    </Link>
                </li>
            {/each}
        </ul>

        <nav class="flex items-center justify-between text-sm text-muted-foreground" aria-label="Pagination">
            <span>
                Showing {users.meta.from}–{users.meta.to} of {users.meta.total}
            </span>
            <div class="flex gap-2">
                <Button variant="outline" size="sm" disabled={!users.links.prev} asChild={!!users.links.prev}>
                    {#snippet children(props)}
                        {#if users.links.prev}
                            <Link {...props} href={users.links.prev} preserveState>Previous</Link>
                        {:else}
                            Previous
                        {/if}
                    {/snippet}
                </Button>
                <Button variant="outline" size="sm" disabled={!users.links.next} asChild={!!users.links.next}>
                    {#snippet children(props)}
                        {#if users.links.next}
                            <Link {...props} href={users.links.next} preserveState>Next</Link>
                        {:else}
                            Next
                        {/if}
                    {/snippet}
                </Button>
            </div>
        </nav>
    {/if}
</div>
