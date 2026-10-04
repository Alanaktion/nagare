<script lang="ts">
    import { Form } from '@inertiajs/svelte';
    import type { RouteFormDefinition } from '@/wayfinder';
    import StatusListEditor, { type RemovedStatus, type StatusDraft } from '@/components/board/StatusListEditor.svelte';
    import InputError from '@/components/InputError.svelte';
    import { Button } from '@/components/ui/button';
    import { Input } from '@/components/ui/input';
    import { Label } from '@/components/ui/label';
    import type { Board, SprintCycle, Status } from '@/types';

    let {
        action,
        board,
        submitLabel,
    }: {
        action: RouteFormDefinition<'post'>;
        board?: Board;
        submitLabel: string;
    } = $props();

    type StatusSeed = Pick<Status, 'name' | 'is_closed'> & Partial<Pick<Status, 'id' | 'issues_count'>>;

    const defaultStatuses: StatusSeed[] = [
        { name: 'To Do', is_closed: false },
        { name: 'In Progress', is_closed: false },
        { name: 'Done', is_closed: true },
    ];

    // The form only seeds its editable state from the board once.
    /* svelte-ignore state_referenced_locally */
    let hasStories = $state(board?.has_stories ?? false);
    /* svelte-ignore state_referenced_locally */
    let hasSprints = $state(board?.has_sprints ?? false);
    /* svelte-ignore state_referenced_locally */
    let statuses: StatusDraft[] = $state(
        ((board?.statuses ?? defaultStatuses) as StatusSeed[]).map((status, index) => ({
            id: index,
            statusId: status.id,
            name: status.name,
            is_closed: status.is_closed,
            issues_count: status.issues_count,
        })),
    );

    let removedStatuses: RemovedStatus[] = $state([]);

    const sprintCycles: { value: SprintCycle; label: string }[] = [
        { value: 'weekly', label: 'Weekly' },
        { value: 'monthly', label: 'Monthly' },
        { value: 'quarterly', label: 'Quarterly' },
        { value: 'custom', label: 'Custom' },
    ];
</script>

<Form {...action} class="space-y-8">
    {#snippet children({ errors, processing })}
        <div class="grid gap-2">
            <Label for="name">Name</Label>
            <Input id="name" name="name" value={board?.name ?? ''} required placeholder="Board name" />
            <InputError message={errors.name} />
        </div>

        <fieldset class="space-y-4">
            <legend class="text-sm font-medium">Workflow</legend>

            <input type="hidden" name="has_stories" value={hasStories ? 1 : 0} />
            <label class="flex items-start gap-3">
                <input type="checkbox" bind:checked={hasStories} class="mt-1 size-4 accent-primary" />
                <span>
                    <span class="block text-sm font-medium">Stories</span>
                    <span class="block text-sm text-muted-foreground">
                        Organize tasks within stories.
                    </span>
                </span>
            </label>

            <input type="hidden" name="has_sprints" value={hasSprints ? 1 : 0} />
            <label class="flex items-start gap-3">
                <input type="checkbox" bind:checked={hasSprints} class="mt-1 size-4 accent-primary" />
                <span>
                    <span class="block text-sm font-medium">Sprints</span>
                    <span class="block text-sm text-muted-foreground">
                        Plan work in time-boxed sprints.
                    </span>
                </span>
            </label>

            {#if hasSprints}
                <div class="grid gap-2 pl-7">
                    <Label for="sprint_cycle">Sprint cycle</Label>
                    <select
                        id="sprint_cycle"
                        name="sprint_cycle"
                        required
                        class="h-9 w-full max-w-xs rounded-md border border-input bg-background px-3 text-sm shadow-sm focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                    >
                        {#each sprintCycles as cycle (cycle.value)}
                            <option value={cycle.value} selected={(board?.sprint_cycle ?? 'weekly') === cycle.value}>
                                {cycle.label}
                            </option>
                        {/each}
                    </select>
                    <InputError message={errors.sprint_cycle} />
                </div>
            {/if}
        </fieldset>

        <fieldset class="space-y-3">
            <legend class="text-sm font-medium">Statuses</legend>
            <p class="text-sm text-muted-foreground">
                Each status is a column on the board. Issues moved into a status that closes issues are marked closed.
            </p>
            <StatusListEditor bind:statuses bind:removed={removedStatuses} {errors} />
        </fieldset>

        <Button type="submit" disabled={processing}>{submitLabel}</Button>
    {/snippet}
</Form>
