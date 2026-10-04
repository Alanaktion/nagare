<script lang="ts">
    import { Form, setLayoutProps } from '@inertiajs/svelte';
    import { destroy, update } from '@/actions/App/Http/Controllers/BoardController';
    import AppHead from '@/components/AppHead.svelte';
    import BoardForm from '@/components/board/BoardForm.svelte';
    import BoardMembers from '@/components/board/BoardMembers.svelte';
    import LabelManager from '@/components/board/LabelManager.svelte';
    import Heading from '@/components/Heading.svelte';
    import { Button } from '@/components/ui/button';
    import {
        Dialog,
        DialogClose,
        DialogContent,
        DialogDescription,
        DialogFooter,
        DialogTitle,
        DialogTrigger,
    } from '@/components/ui/dialog';
    import { edit, index, show } from '@/routes/boards';
    import type { Board, Label, Member } from '@/types';

    let {
        board,
        members,
        labels,
        candidates,
    }: {
        board: { data: Board };
        members: { data: Member[] };
        labels: { data: Label[] };
        candidates?: { data: Member[] };
    } = $props();

    const current = $derived(board.data);

    $effect(() => {
        setLayoutProps({
            breadcrumbs: [
                { title: 'Boards', href: index() },
                { title: current.name, href: show(current.id) },
                { title: 'Settings', href: edit(current.id) },
            ],
        });
    });
</script>

<AppHead title="{current.name} settings" />

<div class="max-w-2xl space-y-10 p-4">
    <div class="space-y-6">
        <Heading title="Board settings" description="Rename the board, change its workflow, or edit its statuses." />
        <BoardForm action={update.form(current.id)} board={current} submitLabel="Save changes" />
    </div>

    <LabelManager board={current} labels={labels.data} />

    <BoardMembers board={current} members={members.data} candidates={candidates?.data} />

    {#if current.role === 'admin'}
        <section class="space-y-4 rounded-lg border border-red-100 bg-red-50 p-4 dark:border-red-200/10 dark:bg-red-700/10">
            <div class="space-y-0.5 text-red-600 dark:text-red-100">
                <p class="font-medium">Delete board</p>
                <p class="text-sm">The board is hidden from all members. You can restore it later from your boards list.</p>
            </div>
            <Dialog>
                <DialogTrigger>
                    <Button variant="destructive">Delete board</Button>
                </DialogTrigger>
                <DialogContent>
                    <Form {...destroy.form(current.id)} class="space-y-6">
                        {#snippet children({ processing })}
                            <div class="space-y-3">
                                <DialogTitle>Delete {current.name}?</DialogTitle>
                                <DialogDescription>Members will lose access until an admin restores it.</DialogDescription>
                            </div>
                            <DialogFooter class="gap-2">
                                <DialogClose>
                                    <Button type="button" variant="secondary">Cancel</Button>
                                </DialogClose>
                                <Button type="submit" variant="destructive" disabled={processing}>Delete board</Button>
                            </DialogFooter>
                        {/snippet}
                    </Form>
                </DialogContent>
            </Dialog>
        </section>
    {/if}
</div>
