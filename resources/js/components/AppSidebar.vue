<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { Ban, Gauge, Globe, KeyRound, Mail, Plug } from '@lucide/vue';
import { computed } from 'vue';
import AppLogo from '@/components/AppLogo.vue';
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
import mail from '@/routes/mail';
import type { NavItem } from '@/types';

const page = usePage();

const homeUrl = computed(() =>
    page.props.currentTeam
        ? mail.dashboard.url(page.props.currentTeam.slug)
        : '/',
);

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
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="homeUrl">
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
            <NavMain
                v-if="mailNavItems.length"
                :items="mailNavItems"
                label="Email"
            />
        </SidebarContent>

        <SidebarFooter>
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
