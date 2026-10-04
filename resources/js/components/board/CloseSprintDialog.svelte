<script lang="ts">
    import { Form } from '@inertiajs/svelte';
    import { store } from '@/actions/App/Http/Controllers/ClosedSprintController';
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
    import { Label } from '@/components/ui/label';
    import { sprintLabel } from '@/lib/sprints';
    import type { Sprint } from '@/types';

    let {
        open = $bindable(false),
        sprint,
        otherOpenSprints,
    }: {
        open?: boolean;
        sprint: Sprint;
        otherOpenSprints: Sprint[];
    } = $props();
</script>

<Dialog bind:open>
    <DialogContent>
        <Form {...store.form([sprint.board_id, sprint.slug])} class="space-y-4" onSuccess={() => (open = false)}>
            {#snippet children({ errors, processing })}
                <div class="space-y-1.5">
                    <DialogTitle>Close sprint {sprint.slug}?</DialogTitle>
                    <DialogDescription>
                        Finished issues stay in the sprint. Choose where unfinished issues should go.
                    </DialogDescription>
                </div>

                <div class="grid gap-2">
                    <Label for="move-to">Move unfinished issues to</Label>
                    <select
                        id="move-to"
                        name="move_to"
                        class="h-9 w-full rounded-md border border-input bg-background px-3 text-sm shadow-sm focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                    >
                        <option value="">Backlog</option>
                        {#each otherOpenSprints as other (other.id)}
                            <option value={other.id}>{sprintLabel(other)}</option>
                        {/each}
                    </select>
                    <InputError message={errors.move_to} />
                </div>

                <DialogFooter class="gap-2">
                    <DialogClose>
                        <Button type="button" variant="secondary">Cancel</Button>
                    </DialogClose>
                    <Button type="submit" disabled={processing}>Close sprint</Button>
                </DialogFooter>
            {/snippet}
        </Form>
    </DialogContent>
</Dialog>
