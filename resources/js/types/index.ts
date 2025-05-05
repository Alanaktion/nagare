import type { PageProps } from '@inertiajs/core';
import type { LucideIcon } from 'lucide-vue-next';

export interface Auth {
    user: User;
}

export interface BreadcrumbItem {
    title: string;
    href: string;
}

export interface NavItem {
    title: string;
    href: string;
    icon?: LucideIcon;
    isActive?: boolean;
}

export interface SharedData extends PageProps {
    name: string;
    accessible_boards: Board[];
    auth: Auth;
}

export interface User {
    id: number;
    name: string;
    email: string;
    avatar?: string;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
}

export interface BoardUser extends User {
    pivot: {
        role: 'user' | 'admin';
        board_id: number;
        user_id: number;
    };
}

export interface Board {
    id: number;
    type: string;
    sprint_cycle?: string;
    name: string;
    statuses: Status[];
    sprints: Sprint[];
    users: BoardUser[];
    issues: Issue[];
    stories: Issue[];
}

export interface BoardType {
    key: string;
    name: string;
    description: string;
}

export interface Sprint {
    id: number;
    board_id: number;
    board: Board;
    slug: string;
    start_date: string;
    end_date: string;
}

export interface Status {
    id: number;
    board_id?: number;
    board?: Board;
    name: string;
    sort?: number;
    is_closed: boolean;
}

export interface Issue {
    id: number;
    board_id: number;
    board: Board;
    status_id: number;
    status: Status;
    parent_id?: number;
    parent?: Issue;
    sprint_id?: number;
    sprint?: Sprint;
    sort: number;
    name: string;
    description?: string;
    user_id: number;
    user: User;
    assigned_id?: number;
    assigned?: User;
    created_at: string;
    updated_at: string;
    closed_at?: string;
}

export type BreadcrumbItemType = BreadcrumbItem;
