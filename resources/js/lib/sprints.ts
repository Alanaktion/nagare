import type { Sprint } from '@/types';

const formatDate = (date: string) =>
    new Date(`${date}T00:00:00`).toLocaleDateString(undefined, {
        month: 'short',
        day: 'numeric',
    });

/**
 * A short human label for a sprint, e.g. "2026W41 (Oct 5 – Oct 11)".
 */
export function sprintLabel(sprint: Sprint): string {
    return `${sprint.slug} (${formatDate(sprint.start_date)} – ${formatDate(sprint.end_date)})${sprint.closed_at ? ' · closed' : ''}`;
}
