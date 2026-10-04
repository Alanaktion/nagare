<script lang="ts">
    import { Form } from '@inertiajs/svelte';
    import { untrack } from 'svelte';
    import { store, update } from '@/actions/App/Http/Controllers/IssueController';
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
    import LabelBadge from '@/components/LabelBadge.svelte';
    import { sprintLabel } from '@/lib/sprints';
    import { cn } from '@/lib/utils';
    import type { Board, Issue, IssueRole, Label as BoardLabel, Member, Sprint } from '@/types';

    let {
        open = $bindable(false),
        board,
        members,
        stories,
        labels = [],
        sprints = [],
        sprintId,
        issue,
        role = 'task',
        statusId,
        parentId,
    }: {
        open?: boolean;
        board: Board;
        members: Member[];
        stories: Issue[];
        labels?: BoardLabel[];
        sprints?: Sprint[];
        sprintId?: number;
        issue?: Issue;
        role?: IssueRole;
        statusId?: number;
        parentId?: number;
    } = $props();

    // The checked labels are tracked here, since an empty selection must still be sent to clear them.
    // svelte-ignore state_referenced_locally
    let selectedLabelIds: number[] = $state(issue?.labels?.map((label) => label.id) ?? []);

    $effect(() => {
        if (open) {
            selectedLabelIds = untrack(() => issue?.labels?.map((label) => label.id) ?? []);
        }
    });

    const toggleLabel = (id: number) => {
        selectedLabelIds = selectedLabelIds.includes(id)
            ? selectedLabelIds.filter((selected) => selected !== id)
            : [...selectedLabelIds, id];
    };

    const isEditing = $derived(issue !== undefined);
    const issueRole = $derived(issue?.role ?? role);
    const canHaveParent = $derived(board.has_stories && issueRole === 'task');
    const selectClass =
        'h-9 w-full rounded-md border border-input bg-background px-3 text-sm shadow-sm focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none';
</script>

<Dialog bind:open>
    <DialogContent>
        <Form
            {...issue ? update.form(issue.id) : store.form(board.id)}
            class="space-y-4"
            options={{ preserveScroll: true }}
            transform={(data) => ({ ...data, label_ids: selectedLabelIds })}
            onSuccess={() => (open = false)}
        >
            {#snippet children({ errors, processing })}
                <div class="space-y-1.5">
                    <DialogTitle>{isEditing ? `Edit ${issueRole}` : `New ${issueRole}`}</DialogTitle>
                    <DialogDescription class="sr-only">
                        {isEditing ? 'Update the details of this issue.' : 'Add an issue to the board.'}
                    </DialogDescription>
                </div>

                {#if !isEditing}
                    <input type="hidden" name="role" value={issueRole} />
                {/if}

                <div class="grid gap-2">
                    <Label for="issue-name">Name</Label>
                    <Input id="issue-name" name="name" value={issue?.name ?? ''} required autofocus />
                    <InputError message={errors.name} />
                </div>

                <div class="grid gap-2">
                    <Label for="issue-description">Description</Label>
                    <textarea
                        id="issue-description"
                        name="description"
                        rows="4"
                        class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-sm focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                        >{issue?.description ?? ''}</textarea
                    >
                    <InputError message={errors.description} />
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="grid gap-2">
                        <Label for="issue-status">Status</Label>
                        <select id="issue-status" name="status_id" class={selectClass}>
                            {#each board.statuses ?? [] as status (status.id)}
                                <option
                                    value={status.id}
                                    selected={status.id === (issue?.status_id ?? statusId ?? board.statuses?.[0]?.id)}
                                >
                                    {status.name}
                                </option>
                            {/each}
                        </select>
                        <InputError message={errors.status_id} />
                    </div>

                    <div class="grid gap-2">
                        <Label for="issue-assignee">Assignee</Label>
                        <select id="issue-assignee" name="assigned_id" class={selectClass}>
                            <option value="" selected={!issue?.assigned_id}>Unassigned</option>
                            {#each members as member (member.id)}
                                <option value={member.id} selected={member.id === issue?.assigned_id}>
                                    {member.name}
                                </option>
                            {/each}
                        </select>
                        <InputError message={errors.assigned_id} />
                    </div>
                </div>

                {#if labels.length > 0}
                    <fieldset class="grid gap-2">
                        <legend class="text-sm leading-none font-medium">Labels</legend>
                        <div class="flex flex-wrap gap-1.5">
                            {#each labels as boardLabel (boardLabel.id)}
                                {@const selected = selectedLabelIds.includes(boardLabel.id)}
                                <button
                                    type="button"
                                    aria-pressed={selected}
                                    class={cn(
                                        'rounded-full ring-offset-background focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none',
                                        selected ? 'ring-2 ring-foreground/60 ring-offset-1' : 'opacity-60 hover:opacity-100',
                                    )}
                                    onclick={() => toggleLabel(boardLabel.id)}
                                >
                                    <LabelBadge label={boardLabel} />
                                </button>
                            {/each}
                        </div>
                        <InputError message={errors['label_ids.0'] ?? errors.label_ids} />
                    </fieldset>
                {/if}

                {#if board.has_sprints}
                    <div class="grid gap-2">
                        <Label for="issue-sprint">Sprint</Label>
                        <select id="issue-sprint" name="sprint_id" class={selectClass}>
                            <option value="" selected={!(issue ? issue.sprint_id : sprintId)}>Backlog</option>
                            {#each sprints as sprint (sprint.id)}
                                <option value={sprint.id} selected={sprint.id === (issue ? issue.sprint_id : sprintId)}>
                                    {sprintLabel(sprint)}
                                </option>
                            {/each}
                        </select>
                        {#if issueRole === 'story'}
                            <p class="text-xs text-muted-foreground">
                                A story also appears in any sprint that has one of its tasks.
                            </p>
                        {/if}
                        <InputError message={errors.sprint_id} />
                    </div>
                {/if}

                {#if canHaveParent}
                    <div class="grid gap-2">
                        <Label for="issue-parent">Story</Label>
                        <select id="issue-parent" name="parent_id" class={selectClass}>
                            <option value="" selected={!(issue?.parent_id ?? parentId)}>No story</option>
                            {#each stories as story (story.id)}
                                <option value={story.id} selected={story.id === (issue?.parent_id ?? parentId)}>
                                    {story.name}
                                </option>
                            {/each}
                        </select>
                        <InputError message={errors.parent_id} />
                    </div>
                {/if}

                <DialogFooter class="gap-2">
                    <DialogClose>
                        <Button type="button" variant="secondary">Cancel</Button>
                    </DialogClose>
                    <Button type="submit" disabled={processing}>{isEditing ? 'Save' : 'Create'}</Button>
                </DialogFooter>
            {/snippet}
        </Form>
    </DialogContent>
</Dialog>
