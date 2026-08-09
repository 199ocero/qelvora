<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import {
    Activity,
    BadgeCheck,
    Ban,
    ChartColumn,
    CircleCheck,
    KeyRound,
    PlugZap,
    Send,
    TriangleAlert,
    Undo2,
} from '@lucide/vue';
import { computed } from 'vue';
import DocsLink from '@/components/DocsLink.vue';
import PendingInvitationsModal from '@/components/PendingInvitationsModal.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import mail from '@/routes/mail';
import type { DashboardInvitation, ProviderConnection } from '@/types';

type Stats = {
    sent: number;
    delivered: number;
    bounced: number;
    complained: number;
    deliveryRate: number;
    bounceRate: number;
    complaintRate: number;
};

type TrendPoint = {
    date: string;
    sent: number;
    delivered: number;
    failed: number;
};

type Props = {
    connection: ProviderConnection | null;
    stats: Stats;
    trend: TrendPoint[];
    counts: {
        verifiedIdentities: number;
        suppressions: number;
        apiKeys: number;
    };
    pendingInvitations?: DashboardInvitation[];
};

const props = defineProps<Props>();

const page = usePage();
const slug = computed(() => page.props.currentTeam?.slug ?? '');

defineOptions({
    layout: (layoutProps: { currentTeam?: { slug: string } | null }) => ({
        breadcrumbs: [
            {
                title: 'Email overview',
                href: layoutProps.currentTeam
                    ? mail.dashboard.url(layoutProps.currentTeam.slug)
                    : '/',
            },
        ],
    }),
});

const maxSent = computed(() =>
    Math.max(1, ...props.trend.map((point) => point.sent)),
);

const quotaUsage = computed(() => {
    if (
        !props.connection?.sendQuotaMax ||
        props.connection.sentLast24h === null
    ) {
        return null;
    }

    return Math.min(
        100,
        Math.round(
            (props.connection.sentLast24h / props.connection.sendQuotaMax) *
                100,
        ),
    );
});

function formatNumber(value: number | null): string {
    return value === null ? '—' : new Intl.NumberFormat().format(value);
}

function formatShortDate(date: string): string {
    const parsed = new Date(`${date}T00:00:00`);

    return parsed.toLocaleDateString(undefined, {
        month: 'short',
        day: 'numeric',
    });
}

const trendTotals = computed(() => ({
    sent: props.trend.reduce((total, point) => total + point.sent, 0),
    failed: props.trend.reduce((total, point) => total + point.failed, 0),
}));
</script>

<template>
    <Head title="Email overview" />
    <h1 class="sr-only">Email overview</h1>

    <PendingInvitationsModal
        v-if="pendingInvitations && pendingInvitations.length > 0"
        :invitations="pendingInvitations"
    />

    <div class="space-y-6 px-4 py-6">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <h2 class="text-xl font-semibold">Email overview</h2>
            <DocsLink page="quickstart" label="Quickstart" />
        </div>

        <!-- Not connected state -->
        <Card v-if="!connection">
            <CardHeader>
                <CardTitle class="flex items-center gap-3">
                    <div
                        class="flex size-9 shrink-0 items-center justify-center rounded-full border border-border bg-linear-to-br from-muted/70 to-muted/20"
                    >
                        <PlugZap class="size-4.5 text-muted-foreground" />
                    </div>
                    Connect a provider to get started
                </CardTitle>
                <CardDescription>
                    Connect Amazon SES (or another provider) to start sending
                    and see analytics here.
                </CardDescription>
            </CardHeader>
            <CardContent>
                <Button as-child>
                    <Link :href="mail.connection.index.url(slug)"
                        >Connect a provider</Link
                    >
                </Button>
            </CardContent>
        </Card>

        <template v-else>
            <!-- Stat tiles -->
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <Card class="transition-shadow hover:shadow-md">
                    <CardHeader>
                        <CardDescription class="flex items-center gap-3">
                            <div
                                class="flex size-9 shrink-0 items-center justify-center rounded-full border border-border bg-linear-to-br from-muted/70 to-muted/20"
                            >
                                <Send class="size-4.5 text-muted-foreground" />
                            </div>
                            Sent (30d)
                        </CardDescription>
                        <CardTitle
                            class="ml-12 text-2xl font-bold tabular-nums"
                        >
                            {{ formatNumber(stats.sent) }}
                        </CardTitle>
                    </CardHeader>
                </Card>
                <Card class="transition-shadow hover:shadow-md">
                    <CardHeader>
                        <CardDescription class="flex items-center gap-3">
                            <div
                                class="flex size-9 shrink-0 items-center justify-center rounded-full border border-border bg-linear-to-br from-muted/70 to-muted/20"
                            >
                                <CircleCheck
                                    class="size-4.5 text-muted-foreground"
                                />
                            </div>
                            Delivery rate
                        </CardDescription>
                        <CardTitle
                            class="ml-12 text-2xl font-bold tabular-nums"
                        >
                            {{ stats.deliveryRate }}%
                        </CardTitle>
                    </CardHeader>
                </Card>
                <Card class="transition-shadow hover:shadow-md">
                    <CardHeader>
                        <CardDescription class="flex items-center gap-3">
                            <div
                                class="flex size-9 shrink-0 items-center justify-center rounded-full border border-border bg-linear-to-br from-muted/70 to-muted/20"
                            >
                                <Undo2
                                    class="size-4.5"
                                    :class="
                                        stats.bounceRate >= 5
                                            ? 'text-destructive'
                                            : 'text-muted-foreground'
                                    "
                                />
                            </div>
                            Bounce rate
                        </CardDescription>
                        <CardTitle
                            class="ml-12 text-2xl font-bold tabular-nums"
                            :class="
                                stats.bounceRate >= 5 ? 'text-destructive' : ''
                            "
                        >
                            {{ stats.bounceRate }}%
                        </CardTitle>
                    </CardHeader>
                </Card>
                <Card class="transition-shadow hover:shadow-md">
                    <CardHeader>
                        <CardDescription class="flex items-center gap-3">
                            <div
                                class="flex size-9 shrink-0 items-center justify-center rounded-full border border-border bg-linear-to-br from-muted/70 to-muted/20"
                            >
                                <TriangleAlert
                                    class="size-4.5"
                                    :class="
                                        stats.complaintRate >= 0.1
                                            ? 'text-destructive'
                                            : 'text-muted-foreground'
                                    "
                                />
                            </div>
                            Complaint rate
                        </CardDescription>
                        <CardTitle
                            class="ml-12 text-2xl font-bold tabular-nums"
                            :class="
                                stats.complaintRate >= 0.1
                                    ? 'text-destructive'
                                    : ''
                            "
                        >
                            {{ stats.complaintRate }}%
                        </CardTitle>
                    </CardHeader>
                </Card>
            </div>

            <div class="grid gap-6 lg:grid-cols-3">
                <!-- Trend -->
                <Card class="lg:col-span-2">
                    <CardHeader
                        class="flex flex-row items-start justify-between gap-4 space-y-0"
                    >
                        <div class="space-y-1.5">
                            <CardTitle class="flex items-center gap-3">
                                <div
                                    class="flex size-9 shrink-0 items-center justify-center rounded-full border border-border bg-linear-to-br from-muted/70 to-muted/20"
                                >
                                    <ChartColumn
                                        class="size-4.5 text-muted-foreground"
                                    />
                                </div>
                                Last 14 days
                            </CardTitle>
                            <CardDescription
                                >Daily sends, with failures
                                highlighted.</CardDescription
                            >
                        </div>
                        <div
                            class="flex items-center gap-4 text-xs text-muted-foreground"
                        >
                            <span class="flex items-center gap-1.5">
                                <span class="size-2 rounded-full bg-primary" />
                                Sent
                            </span>
                            <span class="flex items-center gap-1.5">
                                <span
                                    class="size-2 rounded-full bg-destructive"
                                />
                                Failed
                            </span>
                        </div>
                    </CardHeader>
                    <CardContent>
                        <div class="flex gap-3">
                            <!-- Y axis -->
                            <div
                                class="flex h-44 flex-col justify-between py-0.5 text-right text-[10px] text-muted-foreground tabular-nums"
                            >
                                <span>{{ formatNumber(maxSent) }}</span>
                                <span>{{
                                    formatNumber(Math.round(maxSent / 2))
                                }}</span>
                                <span>0</span>
                            </div>

                            <div class="min-w-0 flex-1">
                                <!-- Plot area -->
                                <TooltipProvider :delay-duration="0">
                                    <div
                                        class="relative flex h-44 items-end gap-1.5"
                                    >
                                        <!-- Gridlines -->
                                        <div
                                            class="pointer-events-none absolute inset-0 flex flex-col justify-between"
                                        >
                                            <div
                                                class="border-t border-border/60"
                                            />
                                            <div
                                                class="border-t border-border/60"
                                            />
                                            <div
                                                class="border-t border-border/60"
                                            />
                                        </div>

                                        <Tooltip
                                            v-for="point in trend"
                                            :key="point.date"
                                        >
                                            <TooltipTrigger as-child>
                                                <div
                                                    class="group relative flex h-full flex-1 flex-col justify-end"
                                                >
                                                    <!-- Empty-day baseline track -->
                                                    <div
                                                        class="absolute inset-x-0 bottom-0 h-full rounded-sm bg-muted/50 transition-colors group-hover:bg-muted"
                                                    />
                                                    <!-- Sent bar with failed portion at its base -->
                                                    <div
                                                        class="relative w-full overflow-hidden rounded-t-sm bg-primary/85 transition-colors group-hover:bg-primary"
                                                        :style="{
                                                            height: `${Math.max((point.sent / maxSent) * 100, point.sent > 0 ? 2 : 0)}%`,
                                                        }"
                                                    >
                                                        <div
                                                            v-if="
                                                                point.failed > 0
                                                            "
                                                            class="absolute inset-x-0 bottom-0 bg-destructive"
                                                            :style="{
                                                                height: `${Math.min((point.failed / Math.max(point.sent, point.failed)) * 100, 100)}%`,
                                                            }"
                                                        />
                                                    </div>
                                                </div>
                                            </TooltipTrigger>
                                            <TooltipContent>
                                                <div class="space-y-0.5">
                                                    <div class="font-medium">
                                                        {{
                                                            formatShortDate(
                                                                point.date,
                                                            )
                                                        }}
                                                    </div>
                                                    <div>
                                                        {{ point.sent }} sent ·
                                                        {{ point.failed }}
                                                        failed
                                                    </div>
                                                </div>
                                            </TooltipContent>
                                        </Tooltip>
                                    </div>
                                </TooltipProvider>

                                <!-- X axis -->
                                <div class="mt-2 flex gap-1.5">
                                    <div
                                        v-for="(point, index) in trend"
                                        :key="point.date"
                                        class="flex-1 truncate text-center text-[10px] text-muted-foreground"
                                    >
                                        <span v-if="index % 3 === 0">{{
                                            formatShortDate(point.date)
                                        }}</span>
                                    </div>
                                </div>

                                <div
                                    class="mt-3 flex items-center gap-4 border-t border-border/60 pt-3 text-xs text-muted-foreground"
                                >
                                    <span
                                        class="inline-flex items-baseline gap-1"
                                    >
                                        <span
                                            class="font-medium text-foreground"
                                            >{{
                                                formatNumber(trendTotals.sent)
                                            }}</span
                                        >
                                        <span>sent</span>
                                    </span>
                                    <span
                                        class="inline-flex items-baseline gap-1"
                                    >
                                        <span
                                            class="font-medium text-foreground"
                                            >{{
                                                formatNumber(trendTotals.failed)
                                            }}</span
                                        >
                                        <span>failed</span>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Account health -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-3">
                            <div
                                class="flex size-9 shrink-0 items-center justify-center rounded-full border border-border bg-linear-to-br from-muted/70 to-muted/20"
                            >
                                <Activity
                                    class="size-4.5 text-muted-foreground"
                                />
                            </div>
                            Account health
                            <Badge variant="outline">{{
                                connection.providerLabel
                            }}</Badge>
                        </CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-4 text-sm">
                        <div v-if="quotaUsage !== null">
                            <div class="mb-1 flex justify-between">
                                <span class="text-muted-foreground"
                                    >24h quota</span
                                >
                                <span
                                    >{{ formatNumber(connection.sentLast24h) }}
                                    /
                                    {{
                                        formatNumber(connection.sendQuotaMax)
                                    }}</span
                                >
                            </div>
                            <div
                                class="h-2 w-full overflow-hidden rounded-full bg-muted"
                            >
                                <div
                                    class="h-full bg-primary"
                                    :style="{ width: `${quotaUsage}%` }"
                                />
                            </div>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-muted-foreground"
                                >Production access</span
                            >
                            <span>{{
                                connection.productionAccess
                                    ? 'Enabled'
                                    : 'Sandbox'
                            }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-muted-foreground"
                                >Max send rate</span
                            >
                            <span
                                >{{
                                    formatNumber(connection.maxSendRate)
                                }}/s</span
                            >
                        </div>
                        <div class="flex justify-between">
                            <span class="text-muted-foreground"
                                >Event webhook</span
                            >
                            <span>{{
                                connection.webhookProvisioned
                                    ? 'Provisioned'
                                    : 'Not set up'
                            }}</span>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Resource counts -->
            <div class="grid gap-4 sm:grid-cols-3">
                <Card class="transition-shadow hover:shadow-md">
                    <CardHeader>
                        <CardDescription class="flex items-center gap-3">
                            <div
                                class="flex size-9 shrink-0 items-center justify-center rounded-full border border-border bg-linear-to-br from-muted/70 to-muted/20"
                            >
                                <BadgeCheck
                                    class="size-4.5 text-muted-foreground"
                                />
                            </div>
                            Verified identities
                        </CardDescription>
                        <CardTitle
                            class="ml-12 text-2xl font-bold tabular-nums"
                        >
                            {{ counts.verifiedIdentities }}
                        </CardTitle>
                    </CardHeader>
                </Card>
                <Card class="transition-shadow hover:shadow-md">
                    <CardHeader>
                        <CardDescription class="flex items-center gap-3">
                            <div
                                class="flex size-9 shrink-0 items-center justify-center rounded-full border border-border bg-linear-to-br from-muted/70 to-muted/20"
                            >
                                <Ban class="size-4.5 text-muted-foreground" />
                            </div>
                            Suppressed addresses
                        </CardDescription>
                        <CardTitle
                            class="ml-12 text-2xl font-bold tabular-nums"
                        >
                            {{ counts.suppressions }}
                        </CardTitle>
                    </CardHeader>
                </Card>
                <Card class="transition-shadow hover:shadow-md">
                    <CardHeader>
                        <CardDescription class="flex items-center gap-3">
                            <div
                                class="flex size-9 shrink-0 items-center justify-center rounded-full border border-border bg-linear-to-br from-muted/70 to-muted/20"
                            >
                                <KeyRound
                                    class="size-4.5 text-muted-foreground"
                                />
                            </div>
                            Active API keys
                        </CardDescription>
                        <CardTitle
                            class="ml-12 text-2xl font-bold tabular-nums"
                        >
                            {{ counts.apiKeys }}
                        </CardTitle>
                    </CardHeader>
                </Card>
            </div>
        </template>
    </div>
</template>
