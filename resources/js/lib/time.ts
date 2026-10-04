const units: [Intl.RelativeTimeFormatUnit, number][] = [
    ['year', 365 * 24 * 60 * 60],
    ['month', 30 * 24 * 60 * 60],
    ['week', 7 * 24 * 60 * 60],
    ['day', 24 * 60 * 60],
    ['hour', 60 * 60],
    ['minute', 60],
];

const relative = new Intl.RelativeTimeFormat(undefined, { numeric: 'auto' });

/**
 * A short relative time such as "3 hours ago" or "yesterday", or "just now"
 * for the last minute.
 */
export function timeAgo(iso: string, now: Date = new Date()): string {
    const seconds = Math.round(
        (new Date(iso).getTime() - now.getTime()) / 1000,
    );

    for (const [unit, size] of units) {
        if (Math.abs(seconds) >= size) {
            return relative.format(Math.round(seconds / size), unit);
        }
    }

    return 'just now';
}

/**
 * The full date and time, for tooltips.
 */
export function fullDate(iso: string): string {
    return new Date(iso).toLocaleString(undefined, {
        dateStyle: 'medium',
        timeStyle: 'short',
    });
}
