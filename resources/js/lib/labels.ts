import type { LabelColor } from '@/types';

/**
 * Classes for each label colour. Tailwind only generates classes it can see
 * written out in full, so these are spelled out rather than built up.
 */
export const labelColors: Record<
    LabelColor,
    { badge: string; swatch: string }
> = {
    gray: {
        badge: 'bg-zinc-100 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-200',
        swatch: 'bg-zinc-500',
    },
    red: {
        badge: 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-200',
        swatch: 'bg-red-500',
    },
    orange: {
        badge: 'bg-orange-100 text-orange-800 dark:bg-orange-900/40 dark:text-orange-200',
        swatch: 'bg-orange-500',
    },
    amber: {
        badge: 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-200',
        swatch: 'bg-amber-500',
    },
    green: {
        badge: 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-200',
        swatch: 'bg-green-500',
    },
    teal: {
        badge: 'bg-teal-100 text-teal-800 dark:bg-teal-900/40 dark:text-teal-200',
        swatch: 'bg-teal-500',
    },
    blue: {
        badge: 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-200',
        swatch: 'bg-blue-500',
    },
    indigo: {
        badge: 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/40 dark:text-indigo-200',
        swatch: 'bg-indigo-500',
    },
    purple: {
        badge: 'bg-purple-100 text-purple-800 dark:bg-purple-900/40 dark:text-purple-200',
        swatch: 'bg-purple-500',
    },
    pink: {
        badge: 'bg-pink-100 text-pink-800 dark:bg-pink-900/40 dark:text-pink-200',
        swatch: 'bg-pink-500',
    },
};

export const labelColorNames = Object.keys(labelColors) as LabelColor[];
