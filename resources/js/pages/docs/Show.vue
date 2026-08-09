<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { ArrowLeft, ArrowRight, Menu } from '@lucide/vue';
import {
    computed,
    nextTick,
    onBeforeUnmount,
    onMounted,
    ref,
    watch,
} from 'vue';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { Button } from '@/components/ui/button';
import {
    Sheet,
    SheetContent,
    SheetTitle,
    SheetTrigger,
} from '@/components/ui/sheet';
import { dashboard, login } from '@/routes';

interface NavItem {
    title: string;
    slug: string;
    description: string;
    href: string;
}

interface NavSection {
    title: string;
    items: NavItem[];
}

interface Heading {
    id: string;
    text: string;
    level: number;
}

interface DocPage {
    slug: string;
    title: string;
    description: string;
    section: string;
    html: string;
    headings: Heading[];
}

interface PagerLink {
    title: string;
    href: string;
}

const props = defineProps<{
    navigation: NavSection[];
    page: DocPage;
    prev: PagerLink | null;
    next: PagerLink | null;
}>();

const shared = usePage();

const appName = computed(() => shared.props.name);

const backToApp = computed(() => {
    const user = shared.props.auth?.user;
    const team = shared.props.currentTeam;

    if (user && team) {
        return { label: 'Dashboard', href: dashboard(team.slug).url };
    }

    return { label: 'Sign in', href: login().url };
});

const contentRef = ref<HTMLElement | null>(null);
const activeHeading = ref<string | null>(null);
const mobileNavOpen = ref(false);

let observer: IntersectionObserver | null = null;

function isActive(slug: string): boolean {
    return slug === props.page.slug;
}

/**
 * Highlight the heading nearest the top of the viewport as the reader scrolls.
 */
function setupScrollSpy(): void {
    observer?.disconnect();

    if (typeof window === 'undefined' || props.page.headings.length === 0) {
        activeHeading.value = props.page.headings[0]?.id ?? null;

        return;
    }

    const visible = new Set<string>();

    observer = new IntersectionObserver(
        (entries) => {
            for (const entry of entries) {
                if (entry.isIntersecting) {
                    visible.add(entry.target.id);
                } else {
                    visible.delete(entry.target.id);
                }
            }

            const firstVisible = props.page.headings.find((heading) =>
                visible.has(heading.id),
            );

            if (firstVisible) {
                activeHeading.value = firstVisible.id;
            }
        },
        { rootMargin: '0px 0px -70% 0px', threshold: 0 },
    );

    for (const heading of props.page.headings) {
        const element = document.getElementById(heading.id);

        if (element) {
            observer.observe(element);
        }
    }

    activeHeading.value = props.page.headings[0]?.id ?? null;
}

/**
 * Add a copy-to-clipboard button to every fenced code block.
 */
function enhanceCodeBlocks(): void {
    if (!contentRef.value || typeof navigator === 'undefined') {
        return;
    }

    const blocks = contentRef.value.querySelectorAll<HTMLPreElement>('pre');

    blocks.forEach((block) => {
        if (block.dataset.enhanced === 'true') {
            return;
        }

        block.dataset.enhanced = 'true';

        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'docs-copy';
        button.textContent = 'Copy';

        button.addEventListener('click', () => {
            const code = block.querySelector('code')?.textContent ?? '';

            navigator.clipboard?.writeText(code).then(() => {
                button.textContent = 'Copied';
                window.setTimeout(() => {
                    button.textContent = 'Copy';
                }, 1500);
            });
        });

        block.appendChild(button);
    });
}

function refresh(): void {
    nextTick(() => {
        setupScrollSpy();
        enhanceCodeBlocks();
    });
}

onMounted(refresh);

onBeforeUnmount(() => {
    observer?.disconnect();
});

// Inertia swaps props without remounting — re-run enhancements on navigation.
watch(
    () => props.page.slug,
    () => {
        mobileNavOpen.value = false;

        if (typeof window !== 'undefined') {
            window.scrollTo({ top: 0 });
        }

        refresh();
    },
);
</script>

<template>
    <div class="min-h-screen bg-background text-foreground">
        <Head :title="`${props.page.title} — ${appName} docs`">
            <meta name="description" :content="props.page.description" />
        </Head>

        <!-- Top bar -->
        <header
            class="sticky top-0 z-40 border-b border-border bg-background/80 backdrop-blur"
        >
            <div
                class="mx-auto flex h-16 w-full max-w-7xl items-center gap-3 px-4 sm:px-6 lg:px-8"
            >
                <Sheet v-model:open="mobileNavOpen">
                    <SheetTrigger as-child>
                        <Button
                            variant="ghost"
                            size="icon"
                            class="lg:hidden"
                            aria-label="Open navigation"
                        >
                            <Menu class="size-5" />
                        </Button>
                    </SheetTrigger>
                    <SheetContent side="left" class="w-80 p-0">
                        <SheetTitle class="sr-only">Documentation</SheetTitle>
                        <div class="flex h-full flex-col overflow-y-auto p-6">
                            <div class="mb-6 flex items-center gap-2">
                                <span
                                    class="flex size-8 items-center justify-center rounded-full bg-zinc-900 ring-1 ring-white/10"
                                >
                                    <AppLogoIcon class="size-5" />
                                </span>
                                <span class="font-semibold">{{ appName }}</span>
                            </div>
                            <nav class="space-y-7">
                                <div
                                    v-for="section in props.navigation"
                                    :key="section.title"
                                >
                                    <p
                                        class="mb-2 text-xs font-semibold tracking-wide text-muted-foreground uppercase"
                                    >
                                        {{ section.title }}
                                    </p>
                                    <ul class="space-y-0.5">
                                        <li
                                            v-for="item in section.items"
                                            :key="item.slug"
                                        >
                                            <Link
                                                :href="item.href"
                                                class="block rounded-md px-3 py-1.5 text-sm transition-colors"
                                                :class="
                                                    isActive(item.slug)
                                                        ? 'bg-accent font-medium text-foreground'
                                                        : 'text-muted-foreground hover:text-foreground'
                                                "
                                            >
                                                {{ item.title }}
                                            </Link>
                                        </li>
                                    </ul>
                                </div>
                            </nav>
                        </div>
                    </SheetContent>
                </Sheet>

                <Link :href="'/'" class="flex items-center gap-2 font-semibold">
                    <span
                        class="flex size-8 items-center justify-center rounded-full bg-zinc-900 ring-1 ring-white/10"
                    >
                        <AppLogoIcon class="size-5" />
                    </span>
                    <span>{{ appName }}</span>
                    <span
                        class="ml-1 rounded-full border border-border px-2 py-0.5 text-xs font-medium text-muted-foreground"
                    >
                        Docs
                    </span>
                </Link>

                <div class="ml-auto">
                    <Button as-child size="sm">
                        <Link :href="backToApp.href">{{
                            backToApp.label
                        }}</Link>
                    </Button>
                </div>
            </div>
        </header>

        <div class="mx-auto flex w-full max-w-7xl gap-8 px-4 sm:px-6 lg:px-8">
            <!-- Left navigation -->
            <aside class="hidden w-60 shrink-0 lg:block">
                <nav
                    class="sticky top-16 max-h-[calc(100vh-4rem)] space-y-7 overflow-y-auto py-10 pr-2"
                >
                    <div
                        v-for="section in props.navigation"
                        :key="section.title"
                    >
                        <p
                            class="mb-2 text-xs font-semibold tracking-wide text-muted-foreground uppercase"
                        >
                            {{ section.title }}
                        </p>
                        <ul class="space-y-0.5 border-l border-border">
                            <li v-for="item in section.items" :key="item.slug">
                                <Link
                                    :href="item.href"
                                    class="-ml-px block border-l px-3 py-1.5 text-sm transition-colors"
                                    :class="
                                        isActive(item.slug)
                                            ? 'border-primary font-medium text-foreground'
                                            : 'border-transparent text-muted-foreground hover:border-border hover:text-foreground'
                                    "
                                >
                                    {{ item.title }}
                                </Link>
                            </li>
                        </ul>
                    </div>
                </nav>
            </aside>

            <!-- Content + on-this-page -->
            <div class="flex min-w-0 flex-1 justify-center gap-10">
                <main class="w-full max-w-3xl min-w-0 py-10">
                    <p class="mb-2 text-sm font-medium text-primary">
                        {{ props.page.section }}
                    </p>
                    <h1
                        class="text-3xl font-semibold tracking-tight text-foreground"
                    >
                        {{ props.page.title }}
                    </h1>
                    <p
                        v-if="props.page.description"
                        class="mt-3 text-lg text-muted-foreground"
                    >
                        {{ props.page.description }}
                    </p>

                    <div
                        ref="contentRef"
                        class="docs-prose mt-8"
                        v-html="props.page.html"
                    />

                    <!-- Prev / next -->
                    <nav
                        v-if="props.prev || props.next"
                        class="mt-14 grid gap-4 border-t border-border pt-8 sm:grid-cols-2"
                    >
                        <Link
                            v-if="props.prev"
                            :href="props.prev.href"
                            class="group flex flex-col rounded-xl border border-border p-4 transition-colors hover:border-primary/60 hover:bg-accent"
                        >
                            <span
                                class="flex items-center gap-1 text-xs text-muted-foreground"
                            >
                                <ArrowLeft class="size-3.5" /> Previous
                            </span>
                            <span
                                class="mt-1 font-medium text-foreground group-hover:text-primary"
                            >
                                {{ props.prev.title }}
                            </span>
                        </Link>
                        <Link
                            v-if="props.next"
                            :href="props.next.href"
                            class="group flex flex-col rounded-xl border border-border p-4 text-right transition-colors hover:border-primary/60 hover:bg-accent sm:col-start-2"
                        >
                            <span
                                class="flex items-center justify-end gap-1 text-xs text-muted-foreground"
                            >
                                Next <ArrowRight class="size-3.5" />
                            </span>
                            <span
                                class="mt-1 font-medium text-foreground group-hover:text-primary"
                            >
                                {{ props.next.title }}
                            </span>
                        </Link>
                    </nav>
                </main>

                <!-- On this page -->
                <aside
                    v-if="props.page.headings.length"
                    class="hidden w-56 shrink-0 xl:block"
                >
                    <div
                        class="sticky top-16 max-h-[calc(100vh-4rem)] overflow-y-auto py-10"
                    >
                        <p
                            class="mb-3 text-xs font-semibold tracking-wide text-muted-foreground uppercase"
                        >
                            On this page
                        </p>
                        <ul class="space-y-1 border-l border-border">
                            <li
                                v-for="heading in props.page.headings"
                                :key="heading.id"
                            >
                                <a
                                    :href="`#${heading.id}`"
                                    class="-ml-px block border-l py-1 text-sm transition-colors"
                                    :class="[
                                        heading.level === 3 ? 'pl-6' : 'pl-3',
                                        activeHeading === heading.id
                                            ? 'border-primary text-foreground'
                                            : 'border-transparent text-muted-foreground hover:text-foreground',
                                    ]"
                                >
                                    {{ heading.text }}
                                </a>
                            </li>
                        </ul>
                    </div>
                </aside>
            </div>
        </div>
    </div>
</template>

<style scoped>
.docs-prose {
    font-size: 0.9375rem;
    line-height: 1.75;
    color: var(--foreground);
}

.docs-prose :deep(> *:first-child) {
    margin-top: 0;
}

.docs-prose :deep(h2) {
    scroll-margin-top: 5rem;
    margin-top: 2.75rem;
    margin-bottom: 1rem;
    padding-bottom: 0.4rem;
    border-bottom: 1px solid var(--border);
    font-size: 1.4rem;
    font-weight: 600;
    letter-spacing: -0.01em;
    color: var(--foreground);
}

.docs-prose :deep(h3) {
    scroll-margin-top: 5rem;
    margin-top: 2rem;
    margin-bottom: 0.75rem;
    font-size: 1.1rem;
    font-weight: 600;
    color: var(--foreground);
}

.docs-prose :deep(p) {
    margin: 1rem 0;
}

.docs-prose :deep(a) {
    color: var(--primary);
    text-decoration: underline;
    text-underline-offset: 3px;
    text-decoration-thickness: 1px;
}

.docs-prose :deep(a:hover) {
    text-decoration-thickness: 2px;
}

.docs-prose :deep(strong) {
    font-weight: 600;
    color: var(--foreground);
}

.docs-prose :deep(ul),
.docs-prose :deep(ol) {
    margin: 1rem 0;
    padding-left: 1.4rem;
}

.docs-prose :deep(ul) {
    list-style: disc;
}

.docs-prose :deep(ol) {
    list-style: decimal;
}

.docs-prose :deep(li) {
    margin: 0.4rem 0;
    padding-left: 0.3rem;
}

.docs-prose :deep(li::marker) {
    color: var(--muted-foreground);
}

.docs-prose :deep(code) {
    font-family: var(--font-mono);
    font-size: 0.85em;
    background: var(--muted);
    border: 1px solid var(--border);
    border-radius: 0.375rem;
    padding: 0.1rem 0.35rem;
}

.docs-prose :deep(pre) {
    position: relative;
    margin: 1.25rem 0;
    padding: 1rem 1.1rem;
    background: #0d0f10;
    border: 1px solid var(--border);
    border-radius: 0.75rem;
    overflow-x: auto;
}

.docs-prose :deep(pre code) {
    background: none;
    border: none;
    padding: 0;
    font-size: 0.85rem;
    color: #d7dbe0;
}

.docs-prose :deep(.docs-copy) {
    position: absolute;
    top: 0.55rem;
    right: 0.55rem;
    padding: 0.2rem 0.55rem;
    font-size: 0.7rem;
    font-weight: 500;
    color: var(--muted-foreground);
    background: var(--secondary);
    border: 1px solid var(--border);
    border-radius: 0.4rem;
    opacity: 0;
    transition: opacity 0.15s ease;
    cursor: pointer;
}

.docs-prose :deep(pre:hover .docs-copy) {
    opacity: 1;
}

.docs-prose :deep(.docs-copy:hover) {
    color: var(--foreground);
}

.docs-prose :deep(blockquote) {
    margin: 1.5rem 0;
    padding: 0.85rem 1.1rem;
    border-left: 3px solid var(--primary);
    border-radius: 0 0.5rem 0.5rem 0;
    background: color-mix(in srgb, var(--primary) 8%, transparent);
    color: var(--foreground);
}

.docs-prose :deep(blockquote p) {
    margin: 0.35rem 0;
}

.docs-prose :deep(table) {
    width: 100%;
    margin: 1.5rem 0;
    border-collapse: collapse;
    font-size: 0.875rem;
}

.docs-prose :deep(thead) {
    border-bottom: 1px solid var(--border);
}

.docs-prose :deep(th) {
    padding: 0.6rem 0.8rem;
    text-align: left;
    font-weight: 600;
    color: var(--foreground);
}

.docs-prose :deep(td) {
    padding: 0.6rem 0.8rem;
    border-top: 1px solid var(--border);
    color: var(--muted-foreground);
    vertical-align: top;
}

.docs-prose :deep(hr) {
    margin: 2.5rem 0;
    border: none;
    border-top: 1px solid var(--border);
}

.docs-prose :deep(img) {
    max-width: 100%;
    border-radius: 0.5rem;
}
</style>
