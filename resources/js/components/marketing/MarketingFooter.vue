<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import GithubStarButton from '@/components/marketing/GithubStarButton.vue';

const appName = computed(() => usePage().props.name);
const currentYear = new Date().getFullYear();

const columns = [
    {
        title: 'Product',
        links: [
            { label: 'Features', href: '/features' },
            { label: 'Why Xelqun', href: '/#pillars' },
            { label: 'Developers', href: '/#developers' },
            { label: 'Quickstart', href: '/docs/quickstart' },
        ],
    },
    {
        title: 'Documentation',
        links: [
            { label: 'Introduction', href: '/docs/introduction' },
            { label: 'Connect Amazon SES', href: '/docs/connect-a-provider' },
            { label: 'Verify a domain', href: '/docs/verify-a-domain' },
            { label: 'Send API', href: '/docs/send-api' },
        ],
    },
    {
        title: 'Reference',
        links: [
            { label: 'Message events', href: '/docs/message-events' },
            { label: 'Suppressions', href: '/docs/suppressions' },
            { label: 'Webhooks', href: '/docs/webhooks' },
            {
                label: 'Roles & permissions',
                href: '/docs/roles-and-permissions',
            },
        ],
    },
] as const;
</script>

<template>
    <footer class="relative isolate overflow-hidden border-t border-border/70">
        <div
            class="marketing-grid pointer-events-none absolute inset-x-0 top-0 -z-10 h-64 opacity-40"
        />

        <div class="mx-auto max-w-6xl px-5 pt-16 sm:px-8">
            <div class="grid gap-12 md:grid-cols-[1.4fr_1fr_1fr_1fr]">
                <div class="max-w-xs">
                    <Link
                        href="/"
                        class="flex items-center gap-2.5 text-sm font-semibold tracking-tight"
                    >
                        <span
                            class="flex size-8 items-center justify-center rounded-lg border border-border bg-card"
                        >
                            <AppLogoIcon class="size-5" />
                        </span>
                        {{ appName }}
                    </Link>
                    <p
                        class="mt-4 text-sm leading-relaxed text-muted-foreground"
                    >
                        The operational layer around Amazon SES. Free, open
                        source, and it runs on your own AWS account.
                    </p>
                    <GithubStarButton variant="solid" class="mt-5" />
                </div>

                <div
                    v-for="column in columns"
                    :key="column.title"
                    class="text-sm"
                >
                    <p
                        class="font-mono text-xs tracking-widest text-muted-foreground/70 uppercase"
                    >
                        {{ column.title }}
                    </p>
                    <ul class="mt-4 space-y-3">
                        <li v-for="link in column.links" :key="link.href">
                            <Link
                                :href="link.href"
                                class="text-muted-foreground transition-colors hover:text-foreground"
                            >
                                {{ link.label }}
                            </Link>
                        </li>
                    </ul>
                </div>
            </div>

            <div
                class="mt-14 border-t border-border/70 pt-6 text-sm text-muted-foreground"
            >
                <p>&copy; {{ currentYear }} {{ appName }}</p>
            </div>
        </div>

        <!-- Oversized brand wordmark, faint, bleeding off the bottom edge. -->
        <p
            aria-hidden="true"
            class="pointer-events-none mt-10 -mb-[0.16em] translate-y-[0.08em] text-center text-[clamp(4rem,19vw,15rem)] leading-none font-bold tracking-tighter text-foreground/[0.04] select-none"
        >
            {{ appName }}
        </p>
    </footer>
</template>
