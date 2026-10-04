import type { Member } from './issue';

export type Attachment = {
    id: number;
    issue_id: number;
    /** Set when the file was posted with a comment. */
    comment_id: number | null;
    name: string;
    size: number;
    mime_type: string;
    /** Whether it's a picture that can be shown in the page. */
    is_image: boolean;
    url: string;
    thumbnail_url: string | null;
    created_at: string;
    user: Member | null;
    can_delete: boolean;
};
