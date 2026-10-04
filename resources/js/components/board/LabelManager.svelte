<script lang="ts">
    import { Form } from '@inertiajs/svelte';
    import { destroy, store, update } from '@/actions/App/Http/Controllers/LabelController';
    import Heading from '@/components/Heading.svelte';
    import InputError from '@/components/InputError.svelte';
    import LabelBadge from '@/components/LabelBadge.svelte';
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
    import { labelColorNames, labelColors } from '@/lib/labels';
    import type { Board, Label } from '@/types';

    let {
        board,
        labels,
    }: {
        board: Board;
        labels: Label[];
    } = $props();

    const selectClass =
        'h-9 rounded-md border border-input bg-background px-2 text-sm shadow-sm focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none';

    let labelToDelete = $state<Label>();
    let deleteOpen = $state(false);

    const confirmDelete = (label: Label) => {
        labelToDelete = label;
        deleteOpen = true;
    };
</script>

{#snippet colorSelect(id: string, value: string)}
    <select {id} name="color" class={selectClass} aria-label="Colour" {value}>
        {#each labelColorNames as color (color)}
            <option value={color} selected={color === value}>{color[0].toUpperCase() + color.slice(1)}</option>
        {/each}
    </select>
{/snippet}

<section class="space-y-4">
    <Heading
        variant="small"
        title="Labels"
        description="Tag issues with labels to find and filter them. Any member can edit labels."
    />

    {#if labels.length > 0}
        <ul class="divide-y rounded-lg border">
            {#each labels as label (label.id)}
                <li class="p-3">
                    <Form {...update.form(label.id)} class="flex flex-wrap items-start gap-2" options={{ preserveScroll: true }}>
                        {#snippet children({ errors, processing, recentlySuccessful })}
                            <span class="mt-2 size-3 shrink-0 rounded-full {labelColors[label.color].swatch}" aria-hidden="true"></span>
                            <div class="min-w-40 flex-1 space-y-1">
                                <Input name="name" value={label.name} aria-label="Name of {label.name}" required maxlength={50} />
                                <InputError message={errors.name} />
                            </div>
                            {@render colorSelect(`label-color-${label.id}`, label.color)}
                            <Button type="submit" size="sm" variant="outline" class="h-9" disabled={processing}>
                                {recentlySuccessful ? 'Saved' : 'Save'}
                            </Button>
                            <Button type="button" size="sm" variant="ghost" class="h-9" onclick={() => confirmDelete(label)}>
                                Delete
                            </Button>
                            <span class="w-full pl-5 text-xs text-muted-foreground">
                                {label.issues_count ?? 0}
                                {label.issues_count === 1 ? 'issue' : 'issues'}
                            </span>
                        {/snippet}
                    </Form>
                </li>
            {/each}
        </ul>
    {:else}
        <p class="rounded-lg border border-dashed p-4 text-sm text-muted-foreground">No labels yet.</p>
    {/if}

    <Form
        {...store.form(board.id)}
        class="flex flex-wrap items-start gap-2 rounded-lg border p-3"
        options={{ preserveScroll: true }}
        resetOnSuccess
    >
        {#snippet children({ errors, processing })}
            <div class="min-w-40 flex-1 space-y-1">
                <Input name="name" placeholder="New label" aria-label="New label name" required maxlength={50} />
                <InputError message={errors.name} />
            </div>
            {@render colorSelect('new-label-color', 'blue')}
            <Button type="submit" size="sm" class="h-9" disabled={processing}>Add label</Button>
            <InputError message={errors.color} />
        {/snippet}
    </Form>
</section>

<Dialog bind:open={deleteOpen}>
    <DialogContent>
        {#if labelToDelete}
            <Form {...destroy.form(labelToDelete.id)} class="space-y-6" onSuccess={() => (deleteOpen = false)}>
                {#snippet children({ processing })}
                    <div class="space-y-3">
                        <DialogTitle>Delete this label?</DialogTitle>
                        <DialogDescription>
                            <LabelBadge label={labelToDelete!} />
                            will be removed from
                            {labelToDelete!.issues_count ?? 0}
                            {labelToDelete!.issues_count === 1 ? 'issue' : 'issues'}. The issues themselves are kept.
                        </DialogDescription>
                    </div>
                    <DialogFooter class="gap-2">
                        <DialogClose>
                            <Button type="button" variant="secondary">Cancel</Button>
                        </DialogClose>
                        <Button type="submit" variant="destructive" disabled={processing}>Delete label</Button>
                    </DialogFooter>
                {/snippet}
            </Form>
        {/if}
    </DialogContent>
</Dialog>
