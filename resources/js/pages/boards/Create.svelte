<script module lang="ts">
    import { create, index } from '@/routes/boards';

    export const layout = {
        breadcrumbs: [
            { title: 'Boards', href: index() },
            { title: 'New board', href: create() },
        ],
    };
</script>

<script lang="ts">
    import { store } from '@/actions/App/Http/Controllers/BoardController';
    import AppHead from '@/components/AppHead.svelte';
    import BoardForm from '@/components/board/BoardForm.svelte';
    import Heading from '@/components/Heading.svelte';
</script>

<AppHead title="New board" />

<div class="max-w-2xl space-y-6 p-4">
    <Heading title="New board" description="Choose how your team works and what columns to track." />
    <BoardForm action={store.form()} submitLabel="Create board" />
</div>
