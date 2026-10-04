<script lang="ts">
    import { Form } from '@inertiajs/svelte';
    import { store } from '@/actions/App/Http/Controllers/SprintController';
    import InputError from '@/components/InputError.svelte';
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
    import { Label } from '@/components/ui/label';
    import type { Board } from '@/types';

    let {
        open = $bindable(false),
        board,
    }: {
        open?: boolean;
        board: Board;
    } = $props();

    const isCustom = $derived(board.sprint_cycle === 'custom');
</script>

<Dialog bind:open>
    <DialogContent>
        <Form {...store.form(board.id)} class="space-y-4" onSuccess={() => (open = false)}>
            {#snippet children({ errors, processing })}
                <div class="space-y-1.5">
                    <DialogTitle>New sprint</DialogTitle>
                    <DialogDescription>
                        {#if isCustom}
                            Choose the first and last day of the sprint.
                        {:else}
                            Pick any day. The sprint will cover the whole {board.sprint_cycle?.replace('ly', '')}
                            that contains it.
                        {/if}
                    </DialogDescription>
                </div>

                <div class="grid gap-2">
                    <Label for="sprint-start">{isCustom ? 'Start date' : 'Date in the sprint'}</Label>
                    <Input id="sprint-start" type="date" name="start_date" required />
                    <InputError message={errors.start_date} />
                </div>

                {#if isCustom}
                    <div class="grid gap-2">
                        <Label for="sprint-end">End date</Label>
                        <Input id="sprint-end" type="date" name="end_date" required />
                        <InputError message={errors.end_date} />
                    </div>
                {/if}

                <DialogFooter class="gap-2">
                    <DialogClose>
                        <Button type="button" variant="secondary">Cancel</Button>
                    </DialogClose>
                    <Button type="submit" disabled={processing}>Create sprint</Button>
                </DialogFooter>
            {/snippet}
        </Form>
    </DialogContent>
</Dialog>
