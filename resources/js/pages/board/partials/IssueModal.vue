<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import UserSelect from '@/components/UserSelect.vue';
import Button from '@/components/ui/button/Button.vue';
import { Dialog, DialogClose, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import Input from '@/components/ui/input/Input.vue';
import Label from '@/components/ui/label/Label.vue';
import Textarea from '@/components/ui/textarea/Textarea.vue';
import type { Issue, User } from '@/types';
import { type FormDataConvertible } from '@inertiajs/core';
import { useForm } from '@inertiajs/vue3';
import axios from 'axios';
import { LoaderCircle } from 'lucide-vue-next';
import { computed, watch } from 'vue';

interface Props {
    show: boolean;
    boardId: number;
    users: User[];
}
const props = defineProps<Props>();

const emit = defineEmits(['close', 'saved', 'update:show']);
const issue = defineModel<Issue>();

const shown = computed({
    get: () => props.show,
    set: (val) => emit('update:show', val),
});

interface FormData extends Record<string, FormDataConvertible> {
    name?: string;
    description?: string;
    assigned_id?: number;
}
const form = useForm<FormData>({
    name: '',
    description: '',
    assigned_id: undefined,
});
watch(
    issue,
    () => {
        form.name = issue.value?.name;
        form.description = issue.value?.description;
        form.assigned_id = issue.value?.assigned_id;
    },
    { immediate: true },
);

const update = () => {
    let url, method, data;
    if (issue.value) {
        url = route('issues.update', [issue.value.id]);
        method = 'put';
        data = form.data();
    } else {
        url = route('issues.store');
        method = 'post';
        data = {
            board_id: props.boardId,
            ...form.data(),
        };
    }
    form.processing = true;
    axios
        .request({ method, url, data })
        .then(({ data }) => {
            form.processing = false;
            emit('update:show', false);
            emit('saved', data);
            onClose();
        })
        .catch(({ response }) => {
            form.processing = false;
            if (response.data?.errors) {
                Object.keys(response.data.errors).forEach((k) => {
                    form.setError(k, response.data.errors[k].join('\n'));
                });
            }
        });
};

const onClose = () => {
    form.clearErrors();
    form.reset();
};
</script>

<template>
    <Dialog v-model:open="shown" modal>
        <DialogContent class="max-w-2xl">
            <DialogHeader class="space-y-3">
                <template v-if="issue">
                    <DialogTitle>
                        Edit Issue <span class="text-zinc-500">#{{ issue.id }}</span>
                    </DialogTitle>
                    <DialogDescription class="sr-only">Edit the details of the selected issue.</DialogDescription>
                </template>
                <template v-else>
                    <DialogTitle> New Issue </DialogTitle>
                    <DialogDescription class="sr-only">Create a new issue on the board.</DialogDescription>
                </template>
            </DialogHeader>
            <form class="grid grid-cols-5 gap-6" @submit.prevent="update">
                <div class="col-span-5 md:col-span-3">
                    <Label for="name">Name</Label>
                    <Input id="name" v-model="form.name" type="text" class="mt-1 block w-full" autofocus />
                    <InputError :message="form.errors.name" class="mt-2" />
                </div>

                <div class="col-span-5 md:col-span-2">
                    <Label for="assigned_id">Assigned</Label>
                    <UserSelect
                        v-model="form.assigned_id"
                        trigger-id="assigned_id"
                        trigger-class="mt-1"
                        label="Assigned"
                        :users="users"
                        empty-text="Unassigned"
                    />
                    <InputError :message="form.errors.assigned_id" class="mt-2" />
                </div>

                <div class="col-span-5">
                    <Label for="description">Description</Label>
                    <Textarea class="mt-1" id="description" v-model="form.description" />
                    <InputError :message="form.errors.description" class="mt-2" />
                </div>
            </form>

            <DialogFooter class="gap-2">
                <DialogClose as-child>
                    <Button variant="outline" @click="onClose">Cancel</Button>
                </DialogClose>

                <Button type="submit" :disabled="form.processing" @click="update">
                    <LoaderCircle v-if="form.processing" class="size-4 animate-spin" />
                    Save
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
