import type { NotificationChange, NotificationData } from '@/types';

const describeChange = (change: NotificationChange): string => {
    const status = (name?: string | null) => name ?? 'a deleted status';

    switch (change.type) {
        case 'moved':
            return `moved it from ${status(change.from)} to ${status(change.to)}`;
        case 'closed':
            return `closed it by moving it to ${status(change.to)}`;
        case 'reopened':
            return `reopened it by moving it to ${status(change.to)}`;
        case 'assigned':
            if (change.to_you) {
                return 'assigned it to you';
            }

            return change.to
                ? `assigned it to ${change.to}`
                : `unassigned ${change.from ?? 'it'}`;
        case 'description':
            return change.excerpt
                ? 'updated the description'
                : 'removed the description';
    }
};

/**
 * What a notification says, starting with who did it: "Ada moved it from
 * To Do to Done". Several changes made together are joined into one sentence.
 */
export function describeNotification(data: NotificationData): string {
    if (data.kind === 'commented') {
        return `${data.actor.name} commented`;
    }

    const parts = data.changes.map(describeChange);
    const last = parts.pop();
    const joined = parts.length > 0 ? `${parts.join(', ')} and ${last}` : last;

    return `${data.actor.name} ${joined}`;
}
