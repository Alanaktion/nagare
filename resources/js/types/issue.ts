export type IssueRole = 'epic' | 'story' | 'task';

export type Member = {
    id: number;
    name: string;
    email: string;
    avatar_url: string | null;
};

export type Issue = {
    id: number;
    board_id: number;
    status_id: number;
    parent_id: number | null;
    role: IssueRole;
    name: string;
    description: string | null;
    sort: number;
    author_id: number | null;
    assigned_id: number | null;
    assignee?: Member | null;
    closed_at: string | null;
};
