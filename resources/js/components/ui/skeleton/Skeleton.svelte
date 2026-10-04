<script lang="ts">
    import { cn } from '@/lib/utils';

    let {
        class: className = '',
    }: {
        class?: string;
    } = $props();
</script>

<div data-slot="skeleton" class={cn('rounded-md bg-foreground/10 motion-safe:animate-pulse', className)}></div>
