import type { Attachment } from './attachment';
import type { Member } from './issue';

export type ActivityType =
    | 'created'
    | 'renamed'
    | 'moved'
    | 'closed'
    | 'reopened'
    | 'assigned'
    | 'sprint_changed'
    | 'labels_changed'
    | 'attached'
    | 'attachment_removed';

export type TimelineActivity = {
    kind: 'activity';
    id: number;
    type: ActivityType;
    /** What changed, with the names as they were at the time. */
    data: {
        name?: string;
        names?: string[];
        from?: string | null;
        to?: string | null;
        added?: string[];
        removed?: string[];
    };
    created_at: string;
    user: Member | null;
};

export type TimelineComment = {
    kind: 'comment';
    id: number;
    issue_id: number;
    body: string;
    /** The body's Markdown rendered as sanitized HTML by the server. */
    body_html: string;
    created_at: string;
    edited_at: string | null;
    user: Member | null;
    attachments: Attachment[];
    can_update: boolean;
    can_delete: boolean;
};

export type TimelineEntry = TimelineActivity | TimelineComment;
