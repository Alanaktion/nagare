<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';

// Samples
import Button from '@/components/ui/button/Button.vue';
import Calendar from '@/components/ui/calendar/Calendar.vue';
import { Label } from '@/components/ui/label';
import { NumberField, NumberFieldContent, NumberFieldDecrement, NumberFieldIncrement, NumberFieldInput } from '@/components/ui/number-field';
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group';
import PlaceholderPattern from '../components/PlaceholderPattern.vue';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
];
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <div class="grid auto-rows-min gap-4 md:grid-cols-3">
                <div class="border-sidebar-border/70 dark:border-sidebar-border relative aspect-video overflow-hidden rounded-xl border">
                    <PlaceholderPattern />
                </div>
                <div class="border-sidebar-border/70 dark:border-sidebar-border relative aspect-video overflow-hidden rounded-xl border">
                    <PlaceholderPattern />
                </div>
                <div class="border-sidebar-border/70 dark:border-sidebar-border relative aspect-video overflow-hidden rounded-xl border">
                    <PlaceholderPattern />
                </div>
            </div>
            <div
                class="border-sidebar-border/70 dark:border-sidebar-border relative min-h-screen flex-1 space-y-4 rounded-xl border p-4 md:min-h-min"
            >
                <div>Buttons!</div>
                <div class="flex gap-2">
                    <Button>Primary</Button>
                    <Button variant="secondary">Secondary</Button>
                    <Button variant="destructive">Destructive</Button>
                    <Button variant="ghost">Ghost</Button>
                    <Button variant="outline">Outline</Button>
                    <Button variant="link">Link</Button>
                </div>
                <div>Components!</div>
                <div class="flex flex-wrap items-start gap-4">
                    <Calendar weekday-format="short" class="border-border rounded-md border" />

                    <RadioGroup default-value="weekly">
                        <div class="flex items-center space-x-2">
                            <RadioGroupItem id="r1" value="weekly" />
                            <Label for="r1">Weekly</Label>
                        </div>
                        <div class="flex items-center space-x-2">
                            <RadioGroupItem id="r2" value="monthly" />
                            <Label for="r2">Monthly</Label>
                        </div>
                        <div class="flex items-center space-x-2">
                            <RadioGroupItem id="r3" value="quarterly" />
                            <Label for="r3">Quarterly</Label>
                        </div>
                    </RadioGroup>

                    <NumberField id="hours" :default-value="4" :min="0">
                        <Label for="hours">Hours</Label>
                        <NumberFieldContent>
                            <NumberFieldDecrement />
                            <NumberFieldInput />
                            <NumberFieldIncrement />
                        </NumberFieldContent>
                    </NumberField>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
