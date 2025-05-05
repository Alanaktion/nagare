<script setup lang="ts">
import Button from '@/components/ui/button/Button.vue';
import Checkbox from '@/components/ui/checkbox/Checkbox.vue';
import Input from '@/components/ui/input/Input.vue';
import Label from '@/components/ui/label/Label.vue';
import { Status } from '@/types';
import { GripHorizontal, X } from 'lucide-vue-next';
import Sortable from 'sortablejs';
import { ModelRef, onMounted, ref, type Ref } from 'vue';

const statuses: ModelRef<Status[]> = defineModel({
    required: true,
});

const addStatus = () => {
    let maxId = statuses.value[0].id || 0;
    statuses.value.forEach((status) => {
        if (status.id > maxId) {
            maxId = status.id;
        }
    });

    statuses.value.push({
        id: ++maxId,
        name: '',
        is_closed: false,
    });
};
const removeStatus = (status: Status) => {
    const index = statuses.value.findIndex((s) => s.id === status.id);
    statuses.value.splice(index, 1);
};

const sortDiv: Ref<HTMLElement | null> = ref(null);
onMounted(() => {
    Sortable.create(sortDiv.value as HTMLElement, {
        draggable: '[data-draggable]',
        handle: '[data-drag-handle]',
        dragClass: 'cursor-grabbing',
        onEnd: (e) => {
            moveItem(e.oldDraggableIndex!, e.newDraggableIndex!, statuses.value);
        },
    });
});
function moveItem(oldIndex: number, newIndex: number, list: Status[]) {
    if (oldIndex < 0 || newIndex < 0 || oldIndex >= list.length || newIndex >= list.length) {
        return;
    }
    const item = list[oldIndex];
    list.splice(oldIndex, 1);
    list.splice(newIndex, 0, item);
}
</script>

<template>
    <div>
        <Label>Statuses</Label>
        <div ref="sortDiv" class="mt-2 mb-3 space-y-2">
            <div data-draggable class="bg-background relative flex items-center gap-2 rounded-md" v-for="status in statuses" :key="status.id">
                <GripHorizontal data-drag-handle class="size-8 cursor-grab text-zinc-500" />
                <Input v-model="status.name" />
                <label class="flex items-center gap-2 sm:ms-2 dark:text-zinc-200">
                    <Checkbox v-model="status.is_closed" />
                    <span class="text-sm whitespace-nowrap">Close issue</span>
                </label>
                <Button variant="ghost" class="ms-auto size-8" @click="removeStatus(status)">
                    <span class="sr-only">Remove</span>
                    <X class="size-5" />
                </Button>
            </div>
        </div>
        <Button variant="outline" @click="addStatus">Add</Button>
    </div>
</template>
