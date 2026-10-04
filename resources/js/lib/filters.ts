import type { Issue } from '@/types';

export type IssueFilters = {
    text: string;
    /** A member id, or `none` for unassigned issues. */
    assignee: string;
    label: string;
    mine: boolean;
};

export const noFilters: IssueFilters = {
    text: '',
    assignee: '',
    label: '',
    mine: false,
};

export const hasFilters = (filters: IssueFilters) =>
    filters.text.trim() !== '' ||
    filters.assignee !== '' ||
    filters.label !== '' ||
    filters.mine;

/**
 * Read the filters from a URL's query string.
 */
export function filtersFromSearch(search: string): IssueFilters {
    const params = new URLSearchParams(search);

    return {
        text: params.get('q') ?? '',
        assignee: params.get('assignee') ?? '',
        label: params.get('label') ?? '',
        mine: params.get('mine') === '1',
    };
}

/**
 * The filters as query parameters, leaving out the ones that aren't set.
 */
export function filtersToQuery(filters: IssueFilters): Record<string, string> {
    const query: Record<string, string> = {};

    if (filters.text.trim() !== '') {
        query.q = filters.text.trim();
    }
    if (filters.assignee !== '') {
        query.assignee = filters.assignee;
    }
    if (filters.label !== '') {
        query.label = filters.label;
    }
    if (filters.mine) {
        query.mine = '1';
    }

    return query;
}

/**
 * Whether an issue passes every filter that is set.
 */
export function matchesFilters(
    issue: Issue,
    filters: IssueFilters,
    currentUserId: number,
): boolean {
    const text = filters.text.trim().toLowerCase();

    if (
        text !== '' &&
        !issue.name.toLowerCase().includes(text) &&
        !(issue.description ?? '').toLowerCase().includes(text) &&
        !(issue.labels ?? []).some((label) =>
            label.name.toLowerCase().includes(text),
        )
    ) {
        return false;
    }

    if (filters.mine && issue.assigned_id !== currentUserId) {
        return false;
    }

    if (filters.assignee === 'none' && issue.assigned_id !== null) {
        return false;
    }

    if (
        filters.assignee !== '' &&
        filters.assignee !== 'none' &&
        issue.assigned_id !== Number(filters.assignee)
    ) {
        return false;
    }

    if (
        filters.label !== '' &&
        !(issue.labels ?? []).some(
            (label) => label.id === Number(filters.label),
        )
    ) {
        return false;
    }

    return true;
}

/**
 * The issues to show with the filters applied. A story stays visible when it
 * matches itself or any of its tasks do, so the tasks that matched keep their lane.
 */
export function applyFilters(
    issues: Issue[],
    filters: IssueFilters,
    currentUserId: number,
): Issue[] {
    if (!hasFilters(filters)) {
        return issues;
    }

    const matching = issues.filter(
        (issue) =>
            issue.role !== 'story' &&
            matchesFilters(issue, filters, currentUserId),
    );
    const matchingIds = new Set(matching.map((task) => task.id));
    const storiesWithMatches = new Set(matching.map((task) => task.parent_id));

    return issues.filter((issue) =>
        issue.role === 'story'
            ? storiesWithMatches.has(issue.id) ||
              matchesFilters(issue, filters, currentUserId)
            : matchingIds.has(issue.id),
    );
}
