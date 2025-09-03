<script setup lang="ts">
import HeadingSmall from '@/components/HeadingSmall.vue';
import InputError from '@/components/InputError.vue';
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/components/ui/alert-dialog';
import Button from '@/components/ui/button/Button.vue';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import Input from '@/components/ui/input/Input.vue';
import Label from '@/components/ui/label/Label.vue';
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group';
import type { Board, BoardType, Status } from '@/types';
import type { FormDataConvertible } from '@inertiajs/core';
import { Link, useForm } from '@inertiajs/vue3';
import { type Ref, ref } from 'vue';
import BoardStatusList from './BoardStatusList.vue';

defineProps<{
    boards: Board[];
    boardTypes: BoardType[];
}>();

interface FormData extends Record<string, FormDataConvertible> {
    name?: string;
    type?: string;
    statuses?: Status[]; // TODO: how do we correctly handle statuses in FormData?
}
const updateBoardForm = useForm<FormData>({
    name: '',
    type: '',
    statuses: [],
});
const deleteBoardForm = useForm({});

const currentlyManagingBoard = ref(false);
const managingBoardFor: Ref<Board | null> = ref(null);
const currentDeletingBoard = ref(false);
const boardBeingDeleted = ref<Board | null>(null);

const manageBoard = (board: Board) => {
    managingBoardFor.value = board;
    currentlyManagingBoard.value = true;
    updateBoardForm.name = board.name;
    updateBoardForm.type = board.type;
    updateBoardForm.statuses = board.statuses;
};

const updateBoard = () => {
    updateBoardForm.put(route('boards.update', [managingBoardFor.value]), {
        preserveScroll: true,
        onSuccess: () => (currentlyManagingBoard.value = false),
    });
};

const deleteBoard = () => {
    deleteBoardForm.delete(route('boards.destroy', [boardBeingDeleted.value]));
};
</script>

<template>
    <div class="max-w-4xl px-4 py-6">
        <HeadingSmall title="Current Boards" description="View and edit the current project boards you have access to." />

        <div class="space-y-6">
            <div v-for="board in boards" :key="board.id" class="flex flex-col items-center sm:flex-row">
                <Link :href="route('boards.show', [board.id])" class="me-2 text-indigo-600 dark:text-indigo-400">
                    {{ board.name }}
                </Link>

                <div class="ms-auto flex items-center gap-2">
                    <Button variant="outline" @click="manageBoard(board)">Edit</Button>
                    <Button
                        variant="destructive"
                        @click="
                            currentDeletingBoard = true;
                            boardBeingDeleted = board;
                        "
                    >
                        Delete
                    </Button>
                </div>
            </div>
        </div>

        <form @submit.prevent="updateBoard">
            <Dialog v-model:open="currentlyManagingBoard" @close="currentlyManagingBoard = false">
                <DialogContent class="sm:max-w-xl">
                    <DialogHeader class="space-y-3">
                        <DialogTitle>Edit Board</DialogTitle>
                    </DialogHeader>
                    <div class="grid grid-cols-6 gap-6" v-if="managingBoardFor">
                        <div class="col-span-6 md:col-span-4">
                            <Label for="name">Board Name</Label>
                            <Input id="name" v-model="updateBoardForm.name" type="text" class="mt-1 block w-full" />
                            <InputError :message="updateBoardForm.errors.name" class="mt-2" />
                        </div>

                        <div class="col-span-6 md:col-span-5">
                            <Label for="type">Board Type</Label>
                            <RadioGroup id="type" v-model="updateBoardForm.type" class="relative mt-3 space-y-2">
                                <label
                                    class="group grid grid-cols-[1em_minmax(300px,_1fr)] grid-rows-2 items-start gap-x-2"
                                    v-for="boardType in boardTypes"
                                    :key="boardType.key"
                                >
                                    <RadioGroupItem class="row-span-full" :value="boardType.key" />
                                    <div class="leading-none peer-data-[state=checked]:text-indigo-600 peer-data-[state=checked]:dark:text-white">
                                        {{ boardType.name }}
                                    </div>
                                    <p class="text-muted-foreground text-sm">
                                        {{ boardType.description }}
                                    </p>
                                </label>
                            </RadioGroup>

                            <InputError :message="updateBoardForm.errors.type" class="mt-2" />
                        </div>

                        <div class="col-span-6 md:col-span-5">
                            <BoardStatusList v-model="updateBoardForm.statuses" />
                        </div>
                    </div>

                    <DialogFooter class="gap-2">
                        <Button variant="outline" @click="currentlyManagingBoard = false">Cancel</Button>

                        <Button :disabled="updateBoardForm.processing" @click="updateBoard">Save</Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </form>

        <AlertDialog v-model:open="currentDeletingBoard" @close="boardBeingDeleted = null">
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>Delete Board</AlertDialogTitle>
                </AlertDialogHeader>

                <div v-if="boardBeingDeleted" class="mb-2 text-base text-zinc-900 dark:text-zinc-100">
                    {{ boardBeingDeleted.name }}
                </div>
                <p>Are you sure you want to delete this board?</p>

                <AlertDialogFooter>
                    <AlertDialogCancel>Cancel</AlertDialogCancel>

                    <AlertDialogAction variant="destructive" :disabled="deleteBoardForm.processing" @click="deleteBoard">Delete</AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    </div>
</template>
