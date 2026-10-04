<script lang="ts">
    import { labelColors } from '@/lib/labels';
    import { cn } from '@/lib/utils';
    import type { Label } from '@/types';

    let {
        label,
        class: className = '',
    }: {
        label: Pick<Label, 'name' | 'color'>;
        class?: string;
    } = $props();
</script>

<span
    class={cn(
        'inline-flex max-w-full items-center rounded-full px-2 py-0.5 text-xs font-medium',
        labelColors[label.color].badge,
        className,
    )}
>
    <span class="truncate">{label.name}</span>
</span>
