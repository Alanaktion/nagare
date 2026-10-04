import type { TimelineActivity } from '@/types';

/**
 * What an entry in an issue's timeline says happened, without the person's
 * name: "moved this from To Do to In Progress".
 */
export function describeActivity(activity: TimelineActivity): string {
    const { data } = activity;

    switch (activity.type) {
        case 'created':
            return 'created this';
        case 'renamed':
            return `renamed this from "${data.from}" to "${data.to}"`;
        case 'moved':
            return `moved this from ${data.from ?? 'a deleted status'} to ${data.to ?? 'a deleted status'}`;
        case 'closed':
            return `closed this by moving it to ${data.to ?? 'a deleted status'}`;
        case 'reopened':
            return `reopened this by moving it to ${data.to ?? 'a deleted status'}`;
        case 'assigned':
            if (data.to && data.from) {
                return `reassigned this from ${data.from} to ${data.to}`;
            }

            return data.to
                ? `assigned this to ${data.to}`
                : `unassigned ${data.from ?? 'this'}`;
        case 'sprint_changed':
            if (data.to && data.from) {
                return `moved this from sprint ${data.from} to sprint ${data.to}`;
            }

            return data.to
                ? `added this to sprint ${data.to}`
                : `moved this from sprint ${data.from} to the backlog`;
        case 'attached': {
            const names = data.names ?? [];

            return names.length === 1
                ? `attached ${names[0]}`
                : `attached ${names.length} files: ${names.join(', ')}`;
        }
        case 'attachment_removed':
            return `removed the attachment ${data.name}`;
        case 'labels_changed': {
            const added = data.added ?? [];
            const removed = data.removed ?? [];
            const named = (verb: string, names: string[]) =>
                `${verb} the ${names.length === 1 ? 'label' : 'labels'} ${names.join(', ')}`;

            return [
                added.length ? named('added', added) : null,
                removed.length ? named('removed', removed) : null,
            ]
                .filter(Boolean)
                .join(' and ');
        }
    }
}
