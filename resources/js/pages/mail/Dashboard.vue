<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import mail from '@/routes/mail';
import type { ProviderConnection } from '@/types';

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
</script>

<template>
    <Head title="Email overview" />
    <h1 class="sr-only">Email overview</h1>

    <div class="space-y-6 px-4 py-6">
        <h2 class="text-xl font-semibold">Email overview</h2>

        <!-- Not connected state -->
        <Card v-if="!connection">
            <CardHeader>
                <CardTitle>Connect a provider to get started</CardTitle>
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
                <Card>
                    <CardHeader class="pb-2">
                        <CardDescription>Sent (30d)</CardDescription>
                        <CardTitle class="text-2xl">{{
                            formatNumber(stats.sent)
                        }}</CardTitle>
                    </CardHeader>
                </Card>
                <Card>
                    <CardHeader class="pb-2">
                        <CardDescription>Delivery rate</CardDescription>
                        <CardTitle class="text-2xl"
                            >{{ stats.deliveryRate }}%</CardTitle
                        >
                    </CardHeader>
                </Card>
                <Card>
                    <CardHeader class="pb-2">
                        <CardDescription>Bounce rate</CardDescription>
                        <CardTitle
                            class="text-2xl"
                            :class="
                                stats.bounceRate >= 5 ? 'text-destructive' : ''
                            "
                        >
                            {{ stats.bounceRate }}%
                        </CardTitle>
                    </CardHeader>
                </Card>
                <Card>
                    <CardHeader class="pb-2">
                        <CardDescription>Complaint rate</CardDescription>
                        <CardTitle
                            class="text-2xl"
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
                    <CardHeader>
                        <CardTitle>Last 14 days</CardTitle>
                        <CardDescription
                            >Daily sends, with failures
                            highlighted.</CardDescription
                        >
                    </CardHeader>
                    <CardContent>
                        <div class="flex h-40 items-end gap-1">
                            <div
                                v-for="point in trend"
                                :key="point.date"
                                class="group flex flex-1 flex-col items-center justify-end"
                                :title="`${point.date}: ${point.sent} sent, ${point.failed} failed`"
                            >
                                <div
                                    class="w-full rounded-sm bg-primary/80 transition-colors group-hover:bg-primary"
                                    :style="{
                                        height: `${(point.sent / maxSent) * 100}%`,
                                    }"
                                />
                                <div
                                    v-if="point.failed > 0"
                                    class="w-full rounded-sm bg-destructive"
                                    :style="{
                                        height: `${(point.failed / maxSent) * 100}%`,
                                    }"
                                />
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Account health -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
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
                <div class="rounded-lg border p-4">
                    <p class="text-2xl font-semibold">
                        {{ counts.verifiedIdentities }}
                    </p>
                    <p class="text-sm text-muted-foreground">
                        Verified identities
                    </p>
                </div>
                <div class="rounded-lg border p-4">
                    <p class="text-2xl font-semibold">
                        {{ counts.suppressions }}
                    </p>
                    <p class="text-sm text-muted-foreground">
                        Suppressed addresses
                    </p>
                </div>
                <div class="rounded-lg border p-4">
                    <p class="text-2xl font-semibold">{{ counts.apiKeys }}</p>
                    <p class="text-sm text-muted-foreground">Active API keys</p>
                </div>
            </div>
        </template>
    </div>
</template>
