/**
 * Compute a sort value that places an item between two neighbours.
 *
 * Items are ordered by a fractional sort value so a drop only needs to
 * update the dropped item. With no neighbours, `fallback` is used.
 */
export function sortBetween(
    previous: number | undefined,
    next: number | undefined,
    fallback = 1,
): number {
    if (previous !== undefined && next !== undefined) {
        return (previous + next) / 2;
    }

    if (next !== undefined) {
        return next - 1;
    }

    if (previous !== undefined) {
        return previous + 1;
    }

    return fallback;
}
