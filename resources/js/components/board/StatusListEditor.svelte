<script lang="ts">
    import ArrowDown from '@lucide/svelte/icons/arrow-down';
    import ArrowUp from '@lucide/svelte/icons/arrow-up';
    import GripVertical from '@lucide/svelte/icons/grip-vertical';
    import Plus from '@lucide/svelte/icons/plus';
    import Trash2 from '@lucide/svelte/icons/trash-2';
    import InputError from '@/components/InputError.svelte';
    import { dndzone, type DndEvent } from 'svelte-dnd-action';
    import { Button } from '@/components/ui/button';
    import { Input } from '@/components/ui/input';

    /**
     * `id` is a client-side key (required by the drag-and-drop library);
     * `statusId` is the saved status's database id, if it has one.
     */
    export type StatusDraft = {
        id: number;
        statusId?: number;
        name: string;
        is_closed: boolean;
        issues_count?: number;
    };

    export type RemovedStatus = {
        id: number;
        name: string;
        is_closed: boolean;
        issues_count: number;
        move_to: number | undefined;
    };

    let {
        statuses = $bindable(),
        removed = $bindable(),
        errors = {},
    }: {
        statuses: StatusDraft[];
        removed: RemovedStatus[];
        errors?: Record<string, string>;
    } = $props();

    const keptExisting = $derived(statuses.filter((status) => status.statusId !== undefined));

    let nextKey = Math.max(0, ...statuses.map((status) => status.id)) + 1;

    const add = () => {
        statuses.push({ id: nextKey++, name: '', is_closed: false });
    };

    const remove = (index: number) => {
        const [status] = statuses.splice(index, 1);
        if (status.statusId !== undefined) {
            removed.push({
                id: status.statusId,
                name: status.name,
                is_closed: status.is_closed,
                issues_count: status.issues_count ?? 0,
                move_to: undefined,
            });
        }
    };

    const restore = (index: number) => {
        const [status] = removed.splice(index, 1);
        statuses.push({
            id: nextKey++,
            statusId: status.id,
            name: status.name,
            is_closed: status.is_closed,
            issues_count: status.issues_count,
        });
    };

    // Rows can only be dragged by their handle, so the name input stays selectable.
    let dragDisabled = $state(true);

    const handleConsider = (event: CustomEvent<DndEvent<StatusDraft>>) => {
        statuses = event.detail.items;
    };

    const handleFinalize = (event: CustomEvent<DndEvent<StatusDraft>>) => {
        statuses = event.detail.items;
        dragDisabled = true;
    };

    const move = (index: number, offset: -1 | 1) => {
        const target = index + offset;
        if (target < 0 || target >= statuses.length) {
            return;
        }
        [statuses[index], statuses[target]] = [statuses[target], statuses[index]];
    };
</script>

<div class="space-y-3">
    <ol
        class="space-y-2"
        use:dndzone={{ items: statuses, flipDurationMs: 0, dragDisabled, dropTargetStyle: {} }}
        onconsider={handleConsider}
        onfinalize={handleFinalize}
    >
        {#each statuses as status, index (status.id)}
            <li class="space-y-1 rounded-md bg-background">
                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        class="cursor-grab touch-none text-muted-foreground"
                        aria-label="Drag to reorder {status.name || 'status'}"
                        onmousedown={() => (dragDisabled = false)}
                        ontouchstart={() => (dragDisabled = false)}
                        onmouseup={() => (dragDisabled = true)}
                        ontouchend={() => (dragDisabled = true)}
                    >
                        <GripVertical class="size-4" />
                    </button>
                    {#if status.statusId}
                        <input type="hidden" name="statuses[{index}][id]" value={status.statusId} />
                    {/if}
                    <Input
                        name="statuses[{index}][name]"
                        bind:value={status.name}
                        placeholder="Status name"
                        aria-label="Status {index + 1} name"
                        required
                    />
                    <input type="hidden" name="statuses[{index}][is_closed]" value={status.is_closed ? 1 : 0} />
                    <label class="flex shrink-0 items-center gap-1.5 text-sm text-muted-foreground">
                        <input type="checkbox" bind:checked={status.is_closed} class="size-4 accent-primary" />
                        Closes issues
                    </label>
                    <Button
                        type="button"
                        variant="ghost"
                        size="icon"
                        aria-label="Move status up"
                        disabled={index === 0}
                        onclick={() => move(index, -1)}
                    >
                        <ArrowUp class="size-4" />
                    </Button>
                    <Button
                        type="button"
                        variant="ghost"
                        size="icon"
                        aria-label="Move status down"
                        disabled={index === statuses.length - 1}
                        onclick={() => move(index, 1)}
                    >
                        <ArrowDown class="size-4" />
                    </Button>
                    <Button
                        type="button"
                        variant="ghost"
                        size="icon"
                        aria-label="Remove status"
                        disabled={statuses.length === 1}
                        onclick={() => remove(index)}
                    >
                        <Trash2 class="size-4" />
                    </Button>
                </div>
                <InputError message={errors[`statuses.${index}.name`] ?? errors[`statuses.${index}.id`]} />
            </li>
        {/each}
    </ol>
    <InputError message={errors.statuses} />
    <Button type="button" variant="outline" size="sm" onclick={add}>
        <Plus class="size-4" /> Add status
    </Button>

    {#if removed.length > 0}
        <div class="space-y-2 rounded-md border border-dashed p-3">
            <p class="text-sm font-medium">Removed statuses</p>
            <ul class="space-y-3">
                {#each removed as status, index (status.id)}
                    <li class="space-y-1 text-sm">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="font-medium">{status.name}</span>
                            {#if status.issues_count > 0}
                                <span class="text-muted-foreground">
                                    — move {status.issues_count}
                                    {status.issues_count === 1 ? 'issue' : 'issues'} to
                                </span>
                                <select
                                    name="status_moves[{status.id}]"
                                    bind:value={status.move_to}
                                    aria-label="Move issues from {status.name} to"
                                    required
                                    class="h-8 rounded-md border border-input bg-background px-2 text-sm shadow-sm focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                                >
                                    <option value={undefined} disabled>Choose a status</option>
                                    {#each keptExisting as target (target.statusId)}
                                        <option value={target.statusId}>{target.name || 'Untitled status'}</option>
                                    {/each}
                                </select>
                            {:else}
                                <span class="text-muted-foreground">— will be deleted</span>
                            {/if}
                            <Button type="button" variant="ghost" size="sm" onclick={() => restore(index)}>
                                Undo
                            </Button>
                        </div>
                        <InputError message={errors[`status_moves.${status.id}`]} />
                    </li>
                {/each}
            </ul>
        </div>
    {/if}
</div>
