<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { Menu } from '@lucide/vue';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import GithubStarButton from '@/components/marketing/GithubStarButton.vue';
import { Button } from '@/components/ui/button';
import {
    Sheet,
    SheetContent,
    SheetTitle,
    SheetTrigger,
} from '@/components/ui/sheet';
import { GITHUB_REPO_URL } from '@/lib/marketing';
import { dashboard, login, register } from '@/routes';

const page = usePage();

const appName = computed(() => page.props.name);
const user = computed(() => page.props.auth?.user ?? null);
const currentTeam = computed(() => page.props.currentTeam ?? null);

const primaryHref = computed(() =>
    user.value && currentTeam.value
        ? dashboard(currentTeam.value.slug).url
        : register().url,
);

const primaryLabel = computed(() =>
    user.value && currentTeam.value ? 'Dashboard' : 'Get started',
);

const links = [
    { label: 'Features', href: '/features' },
    { label: 'Why Xelqun', href: '/#pillars' },
    { label: 'Developers', href: '/#developers' },
    { label: 'Docs', href: '/docs' },
];

const scrolled = ref(false);
const mobileOpen = ref(false);

function onScroll(): void {
    scrolled.value = window.scrollY > 8;
}

onMounted(() => {
    onScroll();
    window.addEventListener('scroll', onScroll, { passive: true });
});

onBeforeUnmount(() => window.removeEventListener('scroll', onScroll));
</script>

<template>
    <header
        class="fixed inset-x-0 top-0 z-50 transition-colors duration-300"
        :class="
            scrolled
                ? 'border-b border-border/70 bg-background/80 backdrop-blur-xl'
                : 'border-b border-transparent'
        "
    >
        <div
            class="mx-auto flex h-16 max-w-6xl items-center justify-between gap-4 px-5 sm:px-8"
        >
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

            <nav
                class="absolute left-1/2 hidden -translate-x-1/2 items-center gap-1 md:flex"
            >
                <Link
                    v-for="link in links"
                    :key="link.href"
                    :href="link.href"
                    class="rounded-md px-3 py-1.5 text-sm text-muted-foreground transition-colors hover:text-foreground"
                >
                    {{ link.label }}
                </Link>
            </nav>

            <div class="flex items-center gap-2">
                <GithubStarButton class="hidden sm:inline-flex" />

                <Link
                    v-if="!user"
                    :href="login().url"
                    class="hidden rounded-md px-3 py-1.5 text-sm text-muted-foreground transition-colors hover:text-foreground md:inline-block"
                >
                    Sign in
                </Link>

                <Button
                    as-child
                    size="sm"
                    class="hidden shadow-[0_0_0_1px_color-mix(in_oklab,var(--primary)_60%,transparent)] md:inline-flex"
                >
                    <Link :href="primaryHref">{{ primaryLabel }}</Link>
                </Button>

                <Sheet v-model:open="mobileOpen">
                    <SheetTrigger as-child>
                        <Button
                            variant="outline"
                            size="icon-sm"
                            class="md:hidden"
                            aria-label="Open menu"
                        >
                            <Menu class="size-4" />
                        </Button>
                    </SheetTrigger>
                    <SheetContent side="right" class="w-72 p-0">
                        <SheetTitle class="sr-only">Menu</SheetTitle>
                        <div class="flex flex-col gap-1 p-6 pt-14">
                            <Link
                                v-for="link in links"
                                :key="link.href"
                                :href="link.href"
                                class="rounded-md px-3 py-2 text-sm text-muted-foreground transition-colors hover:bg-accent hover:text-foreground"
                                @click="mobileOpen = false"
                            >
                                {{ link.label }}
                            </Link>
                            <a
                                :href="GITHUB_REPO_URL"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="rounded-md px-3 py-2 text-sm text-muted-foreground transition-colors hover:bg-accent hover:text-foreground"
                            >
                                GitHub
                            </a>
                            <div class="my-3 h-px bg-border" />
                            <Link
                                v-if="!user"
                                :href="login().url"
                                class="rounded-md px-3 py-2 text-sm text-muted-foreground transition-colors hover:bg-accent hover:text-foreground"
                            >
                                Sign in
                            </Link>
                            <Button as-child class="mt-1 w-full">
                                <Link :href="primaryHref">{{
                                    primaryLabel
                                }}</Link>
                            </Button>
                        </div>
                    </SheetContent>
                </Sheet>
            </div>
        </div>
    </header>
</template>
