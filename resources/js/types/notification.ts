export type ChangeKind =
    | 'moved'
    | 'closed'
    | 'reopened'
    | 'assigned'
    | 'description';

export type NotificationChange = {
    type: ChangeKind;
    from?: string | null;
    to?: string | null;
    to_id?: number | null;
    /** Whether this change assigned the issue to the person reading it. */
    to_you?: boolean;
    excerpt?: string | null;
};

type NotificationBase = {
    issue_id: number;
    issue_name: string;
    board_id: number;
    board_name: string;
    actor: { id: number; name: string };
};

export type NotificationData =
    | (NotificationBase & { kind: 'changed'; changes: NotificationChange[] })
    | (NotificationBase & {
          kind: 'commented';
          comment_id: number;
          excerpt: string | null;
      });

export type AppNotification = {
    id: string;
    data: NotificationData;
    read_at: string | null;
    created_at: string;
};
