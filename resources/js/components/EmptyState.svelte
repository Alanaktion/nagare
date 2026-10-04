<script lang="ts">
    import type { Snippet } from 'svelte';
    import { cn } from '@/lib/utils';

    let {
        message,
        class: className = '',
        children,
    }: {
        message: string;
        class?: string;
        children?: Snippet;
    } = $props();
</script>

<div class={cn('flex flex-col items-center gap-3 rounded-lg border border-dashed p-6 text-center', className)}>
    <p class="text-sm text-muted-foreground">{message}</p>
    {@render children?.()}
</div>
