export type SprintCycle = 'weekly' | 'monthly' | 'quarterly' | 'custom';

export type BoardRole = 'admin' | 'member';

export type Status = {
    id: number;
    name: string;
    sort: number;
    is_closed: boolean;
};

export type Board = {
    id: number;
    name: string;
    has_stories: boolean;
    has_sprints: boolean;
    sprint_cycle: SprintCycle | null;
    role?: BoardRole;
    statuses?: Status[];
};

export type SidebarBoard = Pick<Board, 'id' | 'name'>;
