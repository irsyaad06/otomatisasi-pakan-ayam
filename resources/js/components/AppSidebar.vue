<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { LayoutGrid, Database, Calendar, History, Cpu, BookOpen, FolderGit2, Play, SlidersHorizontal, Layers } from 'lucide-vue-next';
import AppLogo from '@/components/AppLogo.vue';
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import type { NavItem } from '@/types';

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
        icon: LayoutGrid,
    },
    {
        title: 'Periode Pemeliharaan',
        href: '/periode-pemeliharaan',
        icon: Layers,
    },
    {
        title: 'Parameter Pakan',
        href: '/parameter-pakan',
        icon: SlidersHorizontal,
    },
    {
        title: 'Stok Pakan',
        href: '/dashboard/stok',
        icon: Database,
    },
    {
        title: 'Jadwal Pakan',
        href: '/dashboard/jadwal',
        icon: Calendar,
    },
    {
        title: 'Riwayat Pakan',
        href: '/dashboard/riwayat',
        icon: History,
    },
    {
        title: 'Status Alat',
        href: '/dashboard/status',
        icon: Cpu,
    },
    {
        title: 'Simulasi Sistem',
        href: '/simulasi-sistem',
        icon: Play,
    },
];

// const footerNavItems: NavItem[] = [
//     {
//         title: 'Repository',
//         href: 'https://github.com/laravel/vue-starter-kit',
//         icon: FolderGit2,
//     },
//     {
//         title: 'Documentation',
//         href: 'https://laravel.com/docs/starter-kits#vue',
//         icon: BookOpen,
//     },
// ];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="dashboard()">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
