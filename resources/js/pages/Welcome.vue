<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { ArrowRight, Check, Copy } from '@lucide/vue';
import { computed, ref } from 'vue';
import DashboardPreview from '@/components/marketing/DashboardPreview.vue';
import FeatureGlyph from '@/components/marketing/FeatureGlyph.vue';
import GithubStarButton from '@/components/marketing/GithubStarButton.vue';
import IsoArt from '@/components/marketing/IsoArt.vue';
import SpotlightCard from '@/components/marketing/SpotlightCard.vue';
import { Button } from '@/components/ui/button';
import MarketingLayout from '@/layouts/MarketingLayout.vue';
import { highlight } from '@/lib/highlight';
import type { HighlightLanguage } from '@/lib/highlight';
import { GITHUB_REPO_URL } from '@/lib/marketing';
import { dashboard, register } from '@/routes';

const page = usePage();

const primaryHref = computed(() =>
    page.props.auth?.user && page.props.currentTeam
        ? dashboard(page.props.currentTeam.slug).url
        : register().url,
);

const primaryLabel = computed(() =>
    page.props.auth?.user && page.props.currentTeam
        ? 'Open dashboard'
        : 'Start for free',
);

const pillars = [
    {
        label: '01 / SOURCE',
        variant: 'server' as const,
        phase: 'a' as const,
        title: 'Runs on your own account',
        body: 'Every message leaves from your own Amazon SES. Xelqun is the control panel on top, never a middleman.',
    },
    {
        label: '02 / SIGNAL',
        variant: 'timeline' as const,
        phase: 'b' as const,
        title: 'Track every message',
        body: 'A live event trail for each send: delivered, opened, clicked, bounced, complained. Nothing hidden.',
    },
    {
        label: '03 / SHIELD',
        variant: 'shield' as const,
        phase: 'a' as const,
        title: 'Protect your reputation',
        body: 'Bounces and spam complaints suppress themselves, so you never send to a bad address twice.',
    },
];

const features = [
    {
        variant: 'tracking' as const,
        title: 'Live delivery tracking',
        body: 'Every message keeps a timeline that updates itself as SES reports back.',
    },
    {
        variant: 'templates' as const,
        title: 'Reusable templates',
        body: 'Save an email once with fill-in-the-blank fields, and send in one call.',
    },
    {
        variant: 'suppression' as const,
        title: 'Automatic suppression',
        body: 'Hard bounces and spam complaints get blocked from future sends.',
    },
    {
        variant: 'domains' as const,
        title: 'Domain verification',
        body: 'Copy-paste the DNS records and refresh until it turns verified.',
    },
    {
        variant: 'schedule' as const,
        title: 'Schedule and resend',
        body: 'Queue email for later, and resend anything that fails in one click.',
    },
    {
        variant: 'webhooks' as const,
        title: 'Verified webhooks',
        body: 'Signed, per-connection event delivery, set up for you.',
    },
];

const tabs = ['cURL', 'JavaScript', 'PHP'] as const;
type Tab = (typeof tabs)[number];
const activeTab = ref<Tab>('JavaScript');

const languages: Record<Tab, HighlightLanguage> = {
    cURL: 'bash',
    JavaScript: 'javascript',
    PHP: 'php',
};

const snippets: Record<Tab, string> = {
    cURL: `curl https://your-app.com/api/v1/emails \\
  -H "Authorization: Bearer qlv_your_key_here" \\
  -H "Content-Type: application/json" \\
  -d '{
    "from": "hello@example.com",
    "to": "user@example.com",
    "subject": "Hello from Xelqun",
    "html": "<p>It works!</p>"
  }'`,
    JavaScript: `await fetch("https://your-app.com/api/v1/emails", {
  method: "POST",
  headers: {
    Authorization: "Bearer qlv_your_key_here",
    "Content-Type": "application/json",
  },
  body: JSON.stringify({
    from: "hello@example.com",
    to: "user@example.com",
    subject: "Hello from Xelqun",
  }),
});`,
    PHP: `Http::withToken('qlv_your_key_here')
    ->post('https://your-app.com/api/v1/emails', [
        'from' => 'hello@example.com',
        'to' => 'user@example.com',
        'subject' => 'Hello from Xelqun',
    ]);`,
};

const highlighted = computed(() =>
    highlight(snippets[activeTab.value], languages[activeTab.value]),
);

const copied = ref(false);

async function copySnippet(): Promise<void> {
    try {
        await navigator.clipboard.writeText(snippets[activeTab.value]);
        copied.value = true;
        window.setTimeout(() => (copied.value = false), 1600);
    } catch {
        // Clipboard blocked in an insecure context: the code stays selectable.
    }
}
</script>

<template>
    <Head title="Email platform for Amazon SES">
        <meta
            name="description"
            content="Xelqun is the open-source email platform for Amazon SES. Get a real dashboard for SES with live logs, delivery tracking, suppression lists, domain verification, templates, and a send API, all on your own AWS account."
        />
        <meta
            name="keywords"
            content="Amazon SES dashboard, SES email logs, transactional email, delivery tracking, email suppression list, open source email platform, SES API"
        />
        <meta property="og:type" content="website" />
        <meta property="og:site_name" content="Xelqun" />
        <meta
            property="og:title"
            content="Xelqun — the missing dashboard for Amazon SES"
        />
        <meta
            property="og:description"
            content="Send, track, and manage transactional email on your own AWS account. Live logs, delivery tracking, suppressions, and an API for Amazon SES."
        />
        <meta name="twitter:card" content="summary_large_image" />
        <meta
            name="twitter:title"
            content="Xelqun — the missing dashboard for Amazon SES"
        />
        <meta
            name="twitter:description"
            content="The open-source email platform for Amazon SES. Live logs, delivery tracking, suppressions, and an API on your own AWS account."
        />
    </Head>

    <MarketingLayout>
        <!-- ============================ HERO ============================ -->
        <section class="relative isolate">
            <div
                class="marketing-grid pointer-events-none absolute inset-0 -z-10 h-[680px]"
            />
            <div
                class="marketing-glow pointer-events-none absolute inset-x-0 top-0 -z-10 h-[520px]"
            />

            <div class="mx-auto max-w-5xl px-5 pt-24 pb-20 text-center sm:px-8">
                <div data-reveal>
                    <a
                        :href="GITHUB_REPO_URL"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="inline-flex items-center gap-2 rounded-full border border-border bg-card/60 py-1 pr-3 pl-1.5 text-xs backdrop-blur transition-colors hover:border-primary/40"
                    >
                        <span
                            class="rounded-full bg-primary/15 px-2 py-0.5 font-mono text-[10px] font-medium tracking-wide text-primary uppercase"
                            >Open source</span
                        >
                        <span class="text-muted-foreground"
                            >Built on Amazon SES</span
                        >
                    </a>

                    <h1
                        class="marketing-display mx-auto mt-6 max-w-3xl text-4xl font-semibold tracking-tight text-balance sm:text-6xl"
                    >
                        The missing dashboard for Amazon SES.
                    </h1>

                    <p
                        class="mx-auto mt-6 max-w-xl text-lg text-pretty text-muted-foreground"
                    >
                        Send, track, and manage transactional email on your own
                        AWS account, with the logs and controls SES never gave
                        you.
                    </p>

                    <div
                        class="mt-8 flex flex-col items-center justify-center gap-3 sm:flex-row"
                    >
                        <Button
                            as-child
                            size="lg"
                            class="w-full shadow-[0_0_0_1px_color-mix(in_oklab,var(--primary)_55%,transparent),0_18px_40px_-16px_color-mix(in_oklab,var(--primary)_70%,transparent)] sm:w-auto"
                        >
                            <Link :href="primaryHref"
                                >{{ primaryLabel }} <ArrowRight class="size-4"
                            /></Link>
                        </Button>
                        <GithubStarButton
                            variant="solid"
                            class="h-10 w-full justify-center px-6 sm:w-auto"
                        />
                    </div>
                </div>

                <div
                    class="mt-16 text-left"
                    data-reveal
                    style="--reveal-delay: 120ms"
                >
                    <DashboardPreview />
                </div>
            </div>
        </section>

        <!-- ========================== PILLARS ========================== -->
        <section
            id="pillars"
            class="relative isolate scroll-mt-24 overflow-hidden border-t border-border/70"
        >
            <!-- ambient drifting circles, very low opacity -->
            <div
                class="drift-blob pointer-events-none absolute top-10 -left-24 -z-10 size-[420px]"
            />
            <div
                class="drift-blob pointer-events-none absolute -right-32 bottom-0 -z-10 size-[360px]"
                style="animation-delay: -12s"
            />

            <div class="mx-auto max-w-6xl px-5 sm:px-8">
                <div
                    class="grid divide-y divide-border/70 border-x border-border/70 md:grid-cols-3 md:divide-x md:divide-y-0"
                >
                    <div
                        v-for="(pillar, index) in pillars"
                        :key="pillar.label"
                        class="px-8 py-14"
                        data-reveal
                        :style="{ '--reveal-delay': `${index * 110}ms` }"
                    >
                        <p
                            class="font-mono text-xs tracking-[0.2em] text-muted-foreground/50"
                        >
                            {{ pillar.label }}
                        </p>
                        <IsoArt
                            :variant="pillar.variant"
                            :phase="pillar.phase"
                            class="my-6"
                        />
                        <h2 class="text-lg font-semibold text-foreground">
                            {{ pillar.title }}
                        </h2>
                        <p
                            class="mt-2 max-w-xs text-sm leading-relaxed text-muted-foreground"
                        >
                            {{ pillar.body }}
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- ======================== DEVELOPERS ========================= -->
        <section id="developers" class="scroll-mt-24 border-t border-border/70">
            <div class="mx-auto max-w-6xl px-5 py-24 sm:px-8">
                <div
                    class="grid gap-8 lg:grid-cols-2 lg:items-start"
                    data-reveal
                >
                    <div>
                        <p
                            class="font-mono text-xs tracking-widest text-primary uppercase"
                        >
                            For developers
                        </p>
                        <h2
                            class="mt-4 text-4xl font-semibold tracking-tight text-balance sm:text-5xl"
                        >
                            Send email with one request.
                        </h2>
                    </div>
                    <div class="lg:pt-10">
                        <p class="max-w-md text-lg text-muted-foreground">
                            One endpoint, one token. Pass raw HTML or a template
                            with variables, and every send lands in the same
                            live log.
                        </p>
                        <div class="mt-6 flex flex-wrap gap-3">
                            <Button as-child variant="outline">
                                <Link href="/docs/send-api"
                                    >API reference <ArrowRight class="size-4"
                                /></Link>
                            </Button>
                            <Button as-child variant="ghost">
                                <Link href="/docs/quickstart"
                                    >Read the docs</Link
                                >
                            </Button>
                        </div>
                    </div>
                </div>

                <div
                    class="mt-14 overflow-hidden rounded-xl border border-sidebar-border bg-card shadow-2xl shadow-black/40"
                    data-reveal
                    style="--reveal-delay: 100ms"
                >
                    <div
                        class="flex items-center gap-1 border-b border-sidebar-border bg-secondary/40 px-2"
                    >
                        <button
                            v-for="tab in tabs"
                            :key="tab"
                            type="button"
                            class="relative px-3 py-2.5 text-sm transition-colors"
                            :class="
                                activeTab === tab
                                    ? 'text-foreground'
                                    : 'text-muted-foreground hover:text-foreground'
                            "
                            @click="activeTab = tab"
                        >
                            {{ tab }}
                            <span
                                v-if="activeTab === tab"
                                class="absolute inset-x-2 -bottom-px h-px bg-primary"
                            />
                        </button>
                        <button
                            type="button"
                            class="ml-auto inline-flex items-center gap-1.5 rounded-md px-2 py-1 text-xs text-muted-foreground transition-colors hover:text-foreground"
                            @click="copySnippet"
                        >
                            <component
                                :is="copied ? Check : Copy"
                                class="size-3.5"
                                :class="copied ? 'text-primary' : ''"
                            />
                            {{ copied ? 'Copied' : 'Copy' }}
                        </button>
                    </div>
                    <!-- Highlighted code: HTML is escaped by highlight.js. -->
                    <pre
                        class="overflow-x-auto px-5 py-5 font-mono text-[13px] leading-relaxed"
                    ><code class="hljs bg-transparent" v-html="highlighted" /></pre>
                    <div
                        class="flex items-center gap-2 border-t border-sidebar-border bg-secondary/20 px-5 py-3 font-mono text-xs text-muted-foreground"
                    >
                        <span
                            class="inline-flex items-center gap-1.5 rounded-full border border-primary/30 bg-primary/10 px-2 py-0.5 text-primary"
                        >
                            <span class="size-1.5 rounded-full bg-primary" />
                            202
                        </span>
                        Accepted · queued to Amazon SES
                    </div>
                </div>
            </div>
        </section>

        <!-- ========================= FEATURES ========================== -->
        <section class="border-t border-border/70">
            <div class="mx-auto max-w-6xl px-5 py-24 sm:px-8">
                <div class="grid gap-8 lg:grid-cols-2 lg:items-end" data-reveal>
                    <h2
                        class="text-4xl font-semibold tracking-tight text-balance sm:text-5xl"
                    >
                        Everything around your email, in one place.
                    </h2>
                    <div class="lg:pb-2">
                        <p class="max-w-md text-lg text-muted-foreground">
                            From the first DNS record to the millionth send,
                            every part of running email lives under one roof.
                        </p>
                        <Link
                            href="/features"
                            class="mt-4 inline-flex items-center gap-1.5 text-sm text-primary transition-colors hover:text-primary/80"
                        >
                            Explore all features <ArrowRight class="size-4" />
                        </Link>
                    </div>
                </div>

                <div
                    class="mt-14 grid grid-cols-1 border-t border-l border-border/70 sm:grid-cols-2 lg:grid-cols-3"
                >
                    <SpotlightCard
                        v-for="(feature, index) in features"
                        :key="feature.title"
                        class="border-r border-b border-border/70 p-8 transition-colors hover:bg-card/60"
                        data-reveal
                        :style="{ '--reveal-delay': `${(index % 3) * 80}ms` }"
                    >
                        <FeatureGlyph
                            :variant="feature.variant"
                            class="size-14"
                        />
                        <h3 class="mt-6 font-semibold text-foreground">
                            {{ feature.title }}
                        </h3>
                        <p class="mt-1.5 text-sm text-muted-foreground">
                            {{ feature.body }}
                        </p>
                    </SpotlightCard>
                </div>
            </div>
        </section>

        <!-- ========================== TEAMS =========================== -->
        <section class="border-t border-border/70">
            <div
                class="mx-auto flex max-w-6xl flex-col items-center gap-8 px-5 py-24 text-center sm:px-8"
            >
                <div class="max-w-2xl" data-reveal>
                    <p
                        class="font-mono text-xs tracking-widest text-primary uppercase"
                    >
                        Built for teams
                    </p>
                    <h2
                        class="mt-3 text-4xl font-semibold tracking-tight text-balance sm:text-5xl"
                    >
                        One workspace per product or client.
                    </h2>
                    <p
                        class="mx-auto mt-4 max-w-xl text-lg text-muted-foreground"
                    >
                        Every connection, domain, and key belongs to a team.
                        Invite your teammates and give each the right role.
                    </p>
                </div>

                <div
                    class="flex flex-wrap items-center justify-center gap-3"
                    data-reveal
                    style="--reveal-delay: 90ms"
                >
                    <span
                        v-for="role in ['Owner', 'Admin', 'Member']"
                        :key="role"
                        class="inline-flex items-center gap-2 rounded-full border border-border bg-card px-4 py-1.5 text-sm text-foreground"
                    >
                        <Check class="size-3.5 text-primary" />
                        {{ role }}
                    </span>
                </div>
            </div>
        </section>

        <!-- ========================= FINAL CTA ========================= -->
        <section class="relative isolate border-t border-border/70">
            <div
                class="marketing-glow pointer-events-none absolute inset-x-0 top-0 -z-10 h-[420px] opacity-25"
            />
            <div
                class="mx-auto max-w-2xl px-5 py-28 text-center sm:px-8"
                data-reveal
            >
                <h2
                    class="text-4xl font-semibold tracking-tight text-balance sm:text-5xl"
                >
                    Give Amazon SES the dashboard it deserves.
                </h2>
                <p class="mx-auto mt-4 max-w-md text-lg text-muted-foreground">
                    Free, open source, and running on your own AWS account in
                    minutes. A star helps other teams find it.
                </p>
                <div
                    class="mt-9 flex flex-col items-center justify-center gap-3 sm:flex-row"
                >
                    <Button
                        as-child
                        size="lg"
                        class="w-full shadow-[0_0_0_1px_color-mix(in_oklab,var(--primary)_55%,transparent)] sm:w-auto"
                    >
                        <Link :href="primaryHref"
                            >{{ primaryLabel }} <ArrowRight class="size-4"
                        /></Link>
                    </Button>
                    <GithubStarButton
                        variant="solid"
                        class="h-10 w-full justify-center px-6 sm:w-auto"
                    />
                </div>
            </div>
        </section>
    </MarketingLayout>
</template>
