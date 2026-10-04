<script lang="ts">
    import { Link, page } from '@inertiajs/svelte';
    import Kanban from '@lucide/svelte/icons/kanban';
    import LayoutGrid from '@lucide/svelte/icons/layout-grid';
    import Users from '@lucide/svelte/icons/users';
    import type { Snippet } from 'svelte';
    import AppLogo from '@/components/AppLogo.svelte';
    import NavMain from '@/components/NavMain.svelte';
    import NavUser from '@/components/NavUser.svelte';
    import {
        Sidebar,
        SidebarContent,
        SidebarFooter,
        SidebarHeader,
        SidebarMenu,
        SidebarMenuButton,
        SidebarMenuItem,
    } from '@/components/ui/sidebar';
    import { toUrl } from '@/lib/utils';
    import { dashboard } from '@/routes';
    import { index as boardsIndex, show as showBoard } from '@/routes/boards';
    import { index as usersIndex } from '@/routes/users';
    import type { NavItem } from '@/types';

    let {
        children,
    }: {
        children?: Snippet;
    } = $props();

    const mainNavItems: NavItem[] = [
        {
            title: 'Dashboard',
            href: dashboard(),
            icon: LayoutGrid,
        },
        {
            title: 'Users',
            href: usersIndex(),
            icon: Users,
        },
    ];

    const boardNavItems = $derived<NavItem[]>([
        { title: 'All boards', href: boardsIndex(), icon: Kanban },
        ...page.props.sidebarBoards.map((board) => ({
            title: board.name,
            href: showBoard(board.id),
        })),
    ]);
</script>

<Sidebar collapsible="icon" variant="inset">
    <SidebarHeader>
        <SidebarMenu>
            <SidebarMenuItem>
                <SidebarMenuButton size="lg" asChild>
                    {#snippet children(props)}
                        <Link
                            {...props}
                            href={toUrl(dashboard())}
                            class={props.class}
                        >
                            <AppLogo />
                        </Link>
                    {/snippet}
                </SidebarMenuButton>
            </SidebarMenuItem>
        </SidebarMenu>
    </SidebarHeader>

    <SidebarContent>
        <NavMain items={mainNavItems} />
        <NavMain items={boardNavItems} label="Boards" />
    </SidebarContent>

    <SidebarFooter>
        <NavUser />
    </SidebarFooter>
</Sidebar>
{@render children?.()}
