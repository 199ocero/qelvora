<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import {
    Ban,
    BookOpen,
    FolderGit2,
    Gauge,
    Globe,
    KeyRound,
    LayoutGrid,
    Mail,
    Plug,
} from '@lucide/vue';
import { computed } from 'vue';
import AppLogo from '@/components/AppLogo.vue';
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import TeamSwitcher from '@/components/TeamSwitcher.vue';
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
import mail from '@/routes/mail';
import type { NavItem } from '@/types';

const page = usePage();

const dashboardUrl = computed(() =>
    page.props.currentTeam ? dashboard(page.props.currentTeam.slug).url : '/',
);

const mainNavItems = computed<NavItem[]>(() => [
    {
        title: 'Dashboard',
        href: dashboardUrl.value,
        icon: LayoutGrid,
    },
]);

const mailNavItems = computed<NavItem[]>(() => {
    const slug = page.props.currentTeam?.slug;
    const permissions = page.props.mailPermissions;

    if (!slug) {
        return [];
    }

    const items: NavItem[] = [];

    if (permissions?.canViewEmails) {
        items.push({
            title: 'Overview',
            href: mail.dashboard.url(slug),
            icon: Gauge,
        });
        items.push({
            title: 'Emails',
            href: mail.emails.index.url(slug),
            icon: Mail,
        });
    }

    if (permissions?.canManageDomains) {
        items.push({
            title: 'Domains',
            href: mail.domains.index.url(slug),
            icon: Globe,
        });
    }

    if (permissions?.canManageSuppressions) {
        items.push({
            title: 'Suppressions',
            href: mail.suppressions.index.url(slug),
            icon: Ban,
        });
    }

    if (permissions?.canManageApiKeys) {
        items.push({
            title: 'API keys',
            href: mail.apiKeys.index.url(slug),
            icon: KeyRound,
        });
    }

    if (permissions?.canManageProviders) {
        items.push({
            title: 'Providers',
            href: mail.connection.index.url(slug),
            icon: Plug,
        });
    }

    return items;
});

const footerNavItems: NavItem[] = [
    {
        title: 'Repository',
        href: 'https://github.com/laravel/vue-starter-kit',
        icon: FolderGit2,
    },
    {
        title: 'Documentation',
        href: 'https://laravel.com/docs/starter-kits#vue',
        icon: BookOpen,
    },
];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="dashboardUrl">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
            <SidebarMenu>
                <SidebarMenuItem>
                    <TeamSwitcher />
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
            <NavMain
                v-if="mailNavItems.length"
                :items="mailNavItems"
                label="Email"
            />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
