<script lang="ts">
    import ArrowDown from '@lucide/svelte/icons/arrow-down';
    import ArrowUp from '@lucide/svelte/icons/arrow-up';
    import Plus from '@lucide/svelte/icons/plus';
    import Trash2 from '@lucide/svelte/icons/trash-2';
    import InputError from '@/components/InputError.svelte';
    import { Button } from '@/components/ui/button';
    import { Input } from '@/components/ui/input';

    export type StatusDraft = {
        key: number;
        id?: number;
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

    const keptExisting = $derived(statuses.filter((status) => status.id !== undefined));

    let nextKey = Math.max(0, ...statuses.map((status) => status.key)) + 1;

    const add = () => {
        statuses.push({ key: nextKey++, name: '', is_closed: false });
    };

    const remove = (index: number) => {
        const [status] = statuses.splice(index, 1);
        if (status.id !== undefined) {
            removed.push({
                id: status.id,
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
            key: nextKey++,
            id: status.id,
            name: status.name,
            is_closed: status.is_closed,
            issues_count: status.issues_count,
        });
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
    <ol class="space-y-2">
        {#each statuses as status, index (status.key)}
            <li class="space-y-1">
                <div class="flex items-center gap-2">
                    {#if status.id}
                        <input type="hidden" name="statuses[{index}][id]" value={status.id} />
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
                                    {#each keptExisting as target (target.id)}
                                        <option value={target.id}>{target.name || 'Untitled status'}</option>
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
