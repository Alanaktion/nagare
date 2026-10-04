<script lang="ts">
    import { Form, Link, page, router } from '@inertiajs/svelte';
    import { destroy, store, update } from '@/actions/App/Http/Controllers/BoardMemberController';
    import Heading from '@/components/Heading.svelte';
    import InputError from '@/components/InputError.svelte';
    import { Badge } from '@/components/ui/badge';
    import { Button } from '@/components/ui/button';
    import {
        Dialog,
        DialogClose,
        DialogContent,
        DialogDescription,
        DialogFooter,
        DialogTitle,
    } from '@/components/ui/dialog';
    import { Input } from '@/components/ui/input';
    import UserAvatar from '@/components/UserAvatar.svelte';
    import { show } from '@/routes/users';
    import type { Board, BoardRole, Member } from '@/types';

    let {
        board,
        members,
        candidates,
    }: {
        board: Board;
        members: Member[];
        candidates?: Member[];
    } = $props();

    const currentUserId = $derived(page.props.auth.user.id);
    const canManage = $derived(board.role === 'admin');
    const selectClass =
        'h-8 rounded-md border border-input bg-background px-2 text-sm shadow-sm focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none';

    let roleError = $state<string>();
    let memberToRemove = $state<Member>();
    let removeOpen = $state(false);

    const changeRole = (member: Member, select: HTMLSelectElement) => {
        roleError = undefined;
        router.put(
            update.url([board.id, member.id]),
            { role: select.value as BoardRole },
            {
                preserveScroll: true,
                onError: (errors) => {
                    roleError = errors.role;
                    // The role didn't change, so show the saved one again.
                    select.value = member.role ?? 'member';
                },
            },
        );
    };

    const confirmRemove = (member: Member) => {
        memberToRemove = member;
        removeOpen = true;
    };

    let search = $state('');
    let newRole: BoardRole = $state('member');
    let searchTimer: ReturnType<typeof setTimeout> | undefined;

    const searchCandidates = () => {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(() => {
            router.reload({
                only: ['candidates'],
                data: { search },
                replace: true,
            });
        }, 250);
    };

    const addMember = (candidate: Member) => {
        router.post(
            store.url(board.id),
            { user_id: candidate.id, role: newRole },
            {
                preserveScroll: true,
                onSuccess: () => (search = ''),
            },
        );
    };
</script>

<section class="space-y-4">
    <Heading
        variant="small"
        title="Members"
        description={canManage
            ? 'Admins can add people, change roles and remove members.'
            : 'People who can view and edit this board.'}
    />

    <ul class="divide-y rounded-lg border">
        {#each members as member (member.id)}
            <li class="flex flex-wrap items-center gap-3 p-3">
                <UserAvatar user={member} class="size-8" />
                <div class="min-w-0 flex-1">
                    <Link href={show(member.id)} class="block truncate text-sm font-medium hover:underline">
                        {member.name}
                        {#if member.id === currentUserId}<span class="text-muted-foreground">(you)</span>{/if}
                    </Link>
                    <p class="truncate text-xs text-muted-foreground">{member.email}</p>
                </div>

                {#if canManage}
                    <select
                        class={selectClass}
                        aria-label="Role of {member.name}"
                        value={member.role}
                        onchange={(event) => changeRole(member, event.currentTarget)}
                    >
                        <option value="member">Member</option>
                        <option value="admin">Admin</option>
                    </select>
                {:else}
                    <Badge variant="secondary" class="capitalize">{member.role}</Badge>
                {/if}

                {#if canManage || member.id === currentUserId}
                    <Button variant="ghost" size="sm" onclick={() => confirmRemove(member)}>
                        {member.id === currentUserId ? 'Leave' : 'Remove'}
                    </Button>
                {/if}
            </li>
        {/each}
    </ul>
    <InputError message={roleError} />

    {#if canManage}
        <div class="space-y-3 rounded-lg border p-3">
            <p class="text-sm font-medium">Add a member</p>
            <div class="flex gap-2">
                <Input
                    type="search"
                    placeholder="Search by name or email"
                    aria-label="Search users to add"
                    bind:value={search}
                    oninput={searchCandidates}
                />
                <select class={selectClass} aria-label="Role for new member" bind:value={newRole}>
                    <option value="member">Member</option>
                    <option value="admin">Admin</option>
                </select>
            </div>

            {#if search.trim() !== '' && candidates}
                {#if candidates.length === 0}
                    <p class="text-sm text-muted-foreground">No matching users outside this board.</p>
                {:else}
                    <ul class="divide-y rounded-md border">
                        {#each candidates as candidate (candidate.id)}
                            <li class="flex items-center gap-3 p-2">
                                <UserAvatar user={candidate} />
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm">{candidate.name}</p>
                                    <p class="truncate text-xs text-muted-foreground">{candidate.email}</p>
                                </div>
                                <Button size="sm" variant="outline" onclick={() => addMember(candidate)}>Add</Button>
                            </li>
                        {/each}
                    </ul>
                {/if}
            {/if}
        </div>
    {/if}
</section>

<Dialog bind:open={removeOpen}>
    <DialogContent>
        {#if memberToRemove}
            <Form {...destroy.form([board.id, memberToRemove.id])} class="space-y-6" onSuccess={() => (removeOpen = false)}>
                {#snippet children({ errors, processing })}
                    <div class="space-y-3">
                        <DialogTitle>
                            {memberToRemove?.id === currentUserId
                                ? `Leave ${board.name}?`
                                : `Remove ${memberToRemove?.name}?`}
                        </DialogTitle>
                        <DialogDescription>
                            {memberToRemove?.id === currentUserId
                                ? 'You will lose access to this board until an admin adds you again.'
                                : 'They will lose access to this board, and issues assigned to them will become unassigned.'}
                        </DialogDescription>
                        <InputError message={errors.user} />
                    </div>
                    <DialogFooter class="gap-2">
                        <DialogClose>
                            <Button type="button" variant="secondary">Cancel</Button>
                        </DialogClose>
                        <Button type="submit" variant="destructive" disabled={processing}>
                            {memberToRemove?.id === currentUserId ? 'Leave board' : 'Remove'}
                        </Button>
                    </DialogFooter>
                {/snippet}
            </Form>
        {/if}
    </DialogContent>
</Dialog>
