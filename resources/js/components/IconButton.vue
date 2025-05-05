<script setup lang="ts">
import { Button, type ButtonVariants } from '@/components/ui/button';
import { Tooltip, TooltipContent, TooltipTrigger } from '@/components/ui/tooltip';
import type { Component } from 'vue';

defineOptions({
    inheritAttrs: false,
});

interface Props {
    variant?: ButtonVariants['variant'];
    size?: ButtonVariants['size'];
    label: string;
    icon?: Component;
}

withDefaults(defineProps<Props>(), {
    variant: 'ghost',
    size: 'icon',
});
</script>

<template>
    <Tooltip>
        <TooltipTrigger asChild>
            <Button v-bind="$attrs" :variant="variant" :size="size" class="group cursor-pointer">
                <span class="sr-only">{{ label }}</span>
                <slot>
                    <component :is="icon" class="opacity-80 group-hover:opacity-100" />
                </slot>
            </Button>
        </TooltipTrigger>
        <TooltipContent>
            <p>{{ label }}</p>
        </TooltipContent>
    </Tooltip>
</template>
