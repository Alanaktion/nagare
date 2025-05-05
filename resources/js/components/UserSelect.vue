<script setup lang="ts">
import { Select, SelectContent, SelectGroup, SelectItem, SelectLabel, SelectTrigger, SelectValue } from '@/components/ui/select';
import { cn } from '@/lib/utils';
import type { User } from '@/types';
import UserInfoInline from './UserInfoInline.vue';

interface Props {
    users: User[];
    triggerId?: string;
    triggerClass?: string;
    emptyText?: string;
}
const props = defineProps<Props>();

const userById = (id: number | null) => props.users.find((i) => i.id === id);
</script>

<template>
    <Select>
        <SelectTrigger :id="triggerId" :class="cn('pl-2', triggerClass)">
            <SelectValue class="pl-1" v-slot="{ value }">
                <UserInfoInline class="-ml-1" :user="userById(value as number)" v-if="value" />
            </SelectValue>
        </SelectTrigger>
        <SelectContent position="item-aligned">
            <SelectGroup v-if="emptyText">
                <SelectLabel class="sr-only">No user</SelectLabel>
                <SelectItem :value="null">
                    {{ emptyText }}
                </SelectItem>
            </SelectGroup>
            <SelectGroup>
                <SelectLabel class="sr-only">Available users</SelectLabel>
                <SelectItem v-for="user in users" :key="user.id" :value="user.id">
                    <UserInfoInline :user="user" />
                </SelectItem>
            </SelectGroup>
        </SelectContent>
    </Select>
</template>
