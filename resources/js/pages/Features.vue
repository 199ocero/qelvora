<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import type { Send } from '@lucide/vue';
import {
    ArrowRight,
    Ban,
    Check,
    FileText,
    Gauge,
    Globe,
    KeyRound,
    Plug,
    ShieldCheck,
    Timer,
    Users,
    Webhook,
} from '@lucide/vue';
import { computed } from 'vue';
import GithubStarButton from '@/components/marketing/GithubStarButton.vue';
import { Button } from '@/components/ui/button';
import MarketingLayout from '@/layouts/MarketingLayout.vue';
import { dashboard, register } from '@/routes';

const page = usePage();

const primaryHref = computed(() =>
    page.props.auth?.user && page.props.currentTeam
        ? dashboard(page.props.currentTeam.slug).url
        : register().url,
);

// Every SES event type Xelqun records, shown as a chip wall in the tracking
// section. Matches the list in /docs/message-events.
const eventTypes = [
    'Send',
    'Delivery',
    'Open',
    'Click',
    'Bounce',
    'Complaint',
    'Reject',
    'Rendering failure',
    'Delivery delay',
];

interface Feature {
    icon: typeof Send;
    eyebrow: string;
    title: string;
    lead: string;
    points: string[];
    doc: { label: string; href: string };
}

const featureSections: Feature[] = [
    {
        icon: Plug,
        eyebrow: 'Connection',
        title: 'Connect SES once, wire up the rest for you',
        lead: 'Add your access key, secret, and region. Xelqun checks the keys, then sets up the SES configuration set, the SNS topic, and the subscription that stream events back, all safe to run again.',
        points: [
            'Keys are validated the moment you connect',
            'Event tracking is turned on automatically',
            'Re-running setup never creates duplicates',
        ],
        doc: { label: 'Connect Amazon SES', href: '/docs/connect-a-provider' },
    },
    {
        icon: Globe,
        eyebrow: 'Domains',
        title: 'Verify a sending domain with copy-paste DNS',
        lead: 'Add the domain or address you send from and Xelqun hands you the exact DNS records to add. Refresh until the status turns verified. Pending identities keep checking in the background.',
        points: [
            'DKIM and verification records laid out for you',
            'Status polls on its own until it is verified',
            'A clear message once every record is seen',
        ],
        doc: { label: 'Verify a domain', href: '/docs/verify-a-domain' },
    },
    {
        icon: Gauge,
        eyebrow: 'Message events',
        title: 'A live history for every message you send',
        lead: 'Each message keeps an ordered timeline. As SES reports back through the webhook, the status moves to the furthest point reached and open and click counts add up on the message.',
        points: [
            'Status climbs from sent to delivered to opened',
            'Bounces and complaints are caught and shown',
            'Search and filter the whole send log',
        ],
        doc: { label: 'Message events', href: '/docs/message-events' },
    },
    {
        icon: Ban,
        eyebrow: 'Suppressions',
        title: 'Bad addresses block themselves',
        lead: 'When a message hard bounces or gets a spam complaint, the recipient is blocked right away with the reason saved. Add addresses by hand, or sync the list SES already keeps.',
        points: [
            'Complaints are honored the moment they arrive',
            'Import the blocked list from an existing SES account',
            'Remove an address to let mail through again',
        ],
        doc: { label: 'Suppressions', href: '/docs/suppressions' },
    },
    {
        icon: FileText,
        eyebrow: 'Templates',
        title: 'Write an email once, fill in the blanks later',
        lead: 'Save a subject and body with fill-in-the-blank fields. Change wording without touching your app, and send from the dashboard or the API by passing a template id and values.',
        points: [
            'Fields swap in at send time, missing ones stay visible',
            'HTML body, plain text body, or both',
            'Members can send with a template, admins manage them',
        ],
        doc: { label: 'Email templates', href: '/docs/templates' },
    },
    {
        icon: Timer,
        eyebrow: 'Schedule & resend',
        title: 'Queue for later, retry what fails',
        lead: 'Send now or schedule for a later time. Jobs run on Redis with Laravel Horizon, so sends and event handling stay off the request path. Any message that fails can be resent with one click.',
        points: [
            'Pick a send time when you compose',
            'Resend a failed message without rebuilding it',
            'Backed by Horizon, with its own dashboard',
        ],
        doc: { label: 'Resend failed', href: '/docs/resend-failed' },
    },
    {
        icon: KeyRound,
        eyebrow: 'API & keys',
        title: 'Let your app send through one endpoint',
        lead: 'Create an API key and POST to a single endpoint. Send raw HTML or a saved template with variables. Lock a key to one sender so a leaked key cannot send as anyone else.',
        points: [
            'One POST /api/v1/emails, one bearer token',
            'Scope a key to a single verified sender',
            'API sends land in the same live log',
        ],
        doc: { label: 'Send API reference', href: '/docs/send-api' },
    },
    {
        icon: Webhook,
        eyebrow: 'Webhooks',
        title: 'Signed events, set up for you',
        lead: 'Events reach Xelqun through a per-connection webhook with its own secret token. Every event is checked against Amazon’s signature before it is trusted, and the SNS handshake is handled for you.',
        points: [
            'A unique, secret webhook address per connection',
            'AWS signatures verified on every event',
            'Local tunnel support for development',
        ],
        doc: { label: 'Webhooks', href: '/docs/webhooks' },
    },
    {
        icon: Users,
        eyebrow: 'Teams & roles',
        title: 'Scope everything to a team',
        lead: 'Connections, domains, emails, suppressions, and keys all belong to a team. Owner, admin, and member roles decide who can send, who can manage, and who is in control. Invite teammates by email.',
        points: [
            'One team per product or client, each with its own SES',
            'Owner, admin, and member permissions',
            'Email invitations with expiry',
        ],
        doc: {
            label: 'Roles and permissions',
            href: '/docs/roles-and-permissions',
        },
    },
];
</script>

<template>
    <Head title="Features">
        <meta
            name="description"
            content="A full tour of Xelqun: connection, domains, live message events, automatic suppression, templates, scheduling, the send API, verified webhooks, and team roles."
        />
    </Head>

    <MarketingLayout>
        <!-- ======================= PAGE HEADER ======================= -->
        <section class="relative isolate border-b border-border/70">
            <div
                class="marketing-grid pointer-events-none absolute inset-0 -z-10 h-[520px]"
            />
            <div
                class="marketing-glow pointer-events-none absolute inset-x-0 top-0 -z-10 h-[520px]"
            />
            <div
                class="mx-auto max-w-3xl px-5 py-24 text-center sm:px-8 sm:py-28"
                data-reveal
            >
                <p
                    class="font-mono text-xs tracking-widest text-primary uppercase"
                >
                    Features
                </p>
                <h1
                    class="marketing-display mt-4 text-4xl font-semibold tracking-tight text-balance sm:text-5xl"
                >
                    Everything around Amazon SES, in one place.
                </h1>
                <p class="mx-auto mt-5 max-w-xl text-lg text-muted-foreground">
                    SES stays in your AWS account and sends the mail. Xelqun
                    handles the rest: connecting, verifying, sending, tracking,
                    blocking, and letting your app in through an API.
                </p>
                <div
                    class="mt-8 flex flex-col items-center justify-center gap-3 sm:flex-row"
                >
                    <Button
                        as-child
                        size="lg"
                        class="w-full shadow-[0_0_0_1px_color-mix(in_oklab,var(--primary)_55%,transparent)] sm:w-auto"
                    >
                        <Link :href="primaryHref"
                            >Start for free <ArrowRight class="size-4"
                        /></Link>
                    </Button>
                    <GithubStarButton
                        variant="solid"
                        class="h-10 w-full justify-center px-6 sm:w-auto"
                    />
                </div>
            </div>
        </section>

        <!-- ==================== FEATURE SECTIONS ==================== -->
        <div class="mx-auto max-w-5xl px-5 sm:px-8">
            <section
                v-for="(feature, index) in featureSections"
                :key="feature.title"
                class="grid gap-8 border-b border-border/70 py-16 lg:grid-cols-[0.9fr_1.1fr] lg:items-center lg:gap-16"
                :class="index % 2 === 1 ? 'lg:[&>*:first-child]:order-2' : ''"
            >
                <!-- Copy -->
                <div data-reveal>
                    <div class="flex items-center gap-3">
                        <div
                            class="flex size-10 items-center justify-center rounded-lg border border-border bg-secondary/50 text-primary"
                        >
                            <component :is="feature.icon" class="size-5" />
                        </div>
                        <span
                            class="font-mono text-xs tracking-widest text-muted-foreground/70 uppercase"
                            >{{ feature.eyebrow }}</span
                        >
                    </div>
                    <h2
                        class="mt-5 text-2xl font-semibold tracking-tight text-balance"
                    >
                        {{ feature.title }}
                    </h2>
                    <p class="mt-3 leading-relaxed text-muted-foreground">
                        {{ feature.lead }}
                    </p>
                    <ul class="mt-5 space-y-2.5">
                        <li
                            v-for="point in feature.points"
                            :key="point"
                            class="flex items-start gap-3 text-sm text-foreground/90"
                        >
                            <Check
                                class="mt-0.5 size-4 shrink-0 text-primary"
                            />
                            {{ point }}
                        </li>
                    </ul>
                    <Link
                        :href="feature.doc.href"
                        class="mt-6 inline-flex items-center gap-1.5 text-sm text-primary transition-colors hover:text-primary/80"
                    >
                        {{ feature.doc.label }}
                        <ArrowRight class="size-4" />
                    </Link>
                </div>

                <!-- Spec panel -->
                <div
                    class="rounded-xl border border-border bg-card p-6"
                    data-reveal
                    style="--reveal-delay: 90ms"
                >
                    <!-- Event-type chip wall for the tracking section -->
                    <template v-if="feature.eyebrow === 'Message events'">
                        <p
                            class="font-mono text-[10px] tracking-widest text-muted-foreground/70 uppercase"
                        >
                            Event types tracked
                        </p>
                        <div class="mt-4 flex flex-wrap gap-2">
                            <span
                                v-for="event in eventTypes"
                                :key="event"
                                class="rounded-md border border-border bg-secondary/50 px-2.5 py-1 text-xs text-foreground/90"
                            >
                                {{ event }}
                            </span>
                        </div>
                    </template>

                    <!-- Default: restate the points as a checked spec list -->
                    <template v-else>
                        <p
                            class="font-mono text-[10px] tracking-widest text-muted-foreground/70 uppercase"
                        >
                            What you get
                        </p>
                        <ul class="mt-4 divide-y divide-border/70">
                            <li
                                v-for="point in feature.points"
                                :key="point"
                                class="flex items-center gap-3 py-3 text-sm text-foreground/90"
                            >
                                <span
                                    class="flex size-5 shrink-0 items-center justify-center rounded-full bg-primary/15 text-primary"
                                >
                                    <Check class="size-3" />
                                </span>
                                {{ point }}
                            </li>
                        </ul>
                    </template>
                </div>
            </section>
        </div>

        <!-- ========================= CTA ========================= -->
        <section class="border-b border-border/70">
            <div
                class="mx-auto max-w-3xl px-5 py-24 text-center sm:px-8"
                data-reveal
            >
                <div
                    class="mx-auto flex size-12 items-center justify-center rounded-xl border border-border bg-card"
                >
                    <ShieldCheck class="size-5 text-primary" />
                </div>
                <h2
                    class="mt-6 text-3xl font-semibold tracking-tight text-balance"
                >
                    Ready when your SES account is.
                </h2>
                <p class="mx-auto mt-4 max-w-lg text-muted-foreground">
                    Connect your keys and follow five short steps to your first
                    tracked send. Everything here is free and open source.
                </p>
                <div
                    class="mt-8 flex flex-col items-center justify-center gap-3 sm:flex-row"
                >
                    <Button
                        as-child
                        size="lg"
                        class="w-full shadow-[0_0_0_1px_color-mix(in_oklab,var(--primary)_55%,transparent)] sm:w-auto"
                    >
                        <Link :href="primaryHref"
                            >Get started <ArrowRight class="size-4"
                        /></Link>
                    </Button>
                    <Button as-child variant="outline" size="lg">
                        <Link href="/docs/quickstart">Read the quickstart</Link>
                    </Button>
                </div>
            </div>
        </section>
    </MarketingLayout>
</template>
