<script setup lang="ts">
import { cn } from '@/lib/utils'
import { type ButtonVariants, buttonVariants } from '@/components/ui/button'
import { AlertDialogAction, type AlertDialogActionProps } from 'reka-ui'
import { computed, type HTMLAttributes } from 'vue'

interface Props extends AlertDialogActionProps {
  variant?: ButtonVariants['variant']
  class?: HTMLAttributes['class']
}

const props = defineProps<Props>()

const delegatedProps = computed(() => {
  const { class: _, ...delegated } = props

  return delegated
})
</script>

<template>
  <AlertDialogAction v-bind="delegatedProps" :class="cn(buttonVariants(({ variant })), props.class)">
    <slot />
  </AlertDialogAction>
</template>
