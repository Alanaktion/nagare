import type { DndEvent } from 'svelte-dnd-action';

declare module 'svelte/elements' {
    interface DOMAttributes<T> {
        onconsider?: (event: CustomEvent<DndEvent<any>>) => void;
        onfinalize?: (event: CustomEvent<DndEvent<any>>) => void;
    }
}
