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
    };

    let {
        statuses = $bindable(),
        errors = {},
    }: {
        statuses: StatusDraft[];
        errors?: Record<string, string>;
    } = $props();

    let nextKey = Math.max(0, ...statuses.map((status) => status.key)) + 1;

    const add = () => {
        statuses.push({ key: nextKey++, name: '', is_closed: false });
    };

    const remove = (index: number) => {
        statuses.splice(index, 1);
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
</div>
