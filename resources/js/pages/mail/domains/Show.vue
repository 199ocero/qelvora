<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
import {
    ArrowLeft,
    Check,
    ChevronDown,
    Copy,
    Pause,
    Play,
    RefreshCw,
    Trash2,
} from '@lucide/vue';
import { computed, onMounted, onUnmounted, ref } from 'vue';
import DocsLink from '@/components/DocsLink.vue';
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
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible';
import {
    Table,
    TableBody,
    TableCell,
    TableEmpty,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import mail from '@/routes/mail';
import type { IdentityStatus, MailIdentity } from '@/types';

type Props = {
    identity: MailIdentity;
};

const props = defineProps<Props>();

const page = usePage();
const slug = computed(() => page.props.currentTeam?.slug ?? '');
const copied = ref<string | null>(null);
const copiedAll = ref(false);
const tipsOpen = ref(false);

// Auto re-check: while a domain is still pending, poll the refresh endpoint on
// an interval so the user does not have to keep clicking. It stops once the
// domain verifies or fails, or after a capped number of attempts.
const AUTO_INTERVAL_SECONDS = 20;
const MAX_AUTO_ATTEMPTS = 12;

const pendingStatuses: IdentityStatus[] = [
    'not_started',
    'pending',
    'temporary_failure',
];

const checking = ref(false);
const autoPaused = ref(false);
const autoAttempts = ref(0);
const countdown = ref(AUTO_INTERVAL_SECONDS);
let timer: ReturnType<typeof setInterval> | null = null;

const isPending = computed(() =>
    pendingStatuses.includes(props.identity.status),
);

const autoActive = computed(
    () =>
        props.identity.type === 'domain' &&
        isPending.value &&
        !autoPaused.value &&
        autoAttempts.value < MAX_AUTO_ATTEMPTS,
);

defineOptions({
    layout: (layoutProps: { currentTeam?: { slug: string } | null }) => ({
        breadcrumbs: [
            {
                title: 'Domains',
                href: layoutProps.currentTeam
                    ? mail.domains.index.url(layoutProps.currentTeam.slug)
                    : '/',
            },
        ],
    }),
});

function statusVariant(status: IdentityStatus) {
    return status === 'verified'
        ? 'default'
        : status === 'failed' || status === 'temporary_failure'
          ? 'destructive'
          : 'secondary';
}

function copy(value: string) {
    navigator.clipboard?.writeText(value);
    copied.value = value;
    setTimeout(() => (copied.value = null), 1500);
}

function copyAll() {
    const text = props.identity.dnsRecords
        .map((record) =>
            [record.type, record.host, record.value, record.priority ?? '']
                .filter((part) => part !== '')
                .join('\t'),
        )
        .join('\n');

    navigator.clipboard?.writeText(text);
    copiedAll.value = true;
    setTimeout(() => (copiedAll.value = false), 1500);
}

function refresh(auto = false) {
    router.post(
        mail.domains.refresh.url([slug.value, props.identity.id]),
        {},
        {
            preserveScroll: true,
            onStart: () => (checking.value = true),
            onFinish: () => {
                checking.value = false;
                countdown.value = AUTO_INTERVAL_SECONDS;

                if (auto) {
                    autoAttempts.value += 1;
                }
            },
        },
    );
}

function toggleAuto() {
    if (autoActive.value) {
        autoPaused.value = true;

        return;
    }

    autoPaused.value = false;
    autoAttempts.value = 0;
    countdown.value = AUTO_INTERVAL_SECONDS;
}

function tick() {
    if (!autoActive.value || checking.value) {
        return;
    }

    countdown.value -= 1;

    if (countdown.value <= 0) {
        refresh(true);
    }
}

function remove() {
    if (!confirm(`Remove ${props.identity.identity}?`)) {
        return;
    }

    router.delete(mail.domains.destroy.url([slug.value, props.identity.id]));
}

onMounted(() => {
    timer = setInterval(tick, 1000);
});

onUnmounted(() => {
    if (timer) {
        clearInterval(timer);
    }
});
</script>

<template>
    <Head :title="identity.identity" />
    <h1 class="sr-only">{{ identity.identity }}</h1>

    <div class="space-y-6 px-4 py-6">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <Button variant="ghost" size="icon-sm" as-child>
                    <a :href="mail.domains.index.url(slug)"
                        ><ArrowLeft class="size-4"
                    /></a>
                </Button>
                <div>
                    <h2 class="text-xl font-semibold">
                        {{ identity.identity }}
                    </h2>
                    <p class="text-sm text-muted-foreground capitalize">
                        {{ identity.type }}
                    </p>
                </div>
                <Badge :variant="statusVariant(identity.status)">{{
                    identity.statusLabel
                }}</Badge>
            </div>
            <div class="flex items-center gap-3">
                <DocsLink page="verify-a-domain" />
                <span
                    v-if="identity.type === 'domain' && isPending"
                    class="text-xs text-muted-foreground"
                    data-test="auto-check"
                >
                    <template v-if="checking">Checking…</template>
                    <template v-else-if="autoActive"
                        >Auto-checking, next in {{ countdown }}s</template
                    >
                    <template v-else>Auto-check off</template>
                </span>
                <Button
                    v-if="identity.type === 'domain' && isPending"
                    variant="ghost"
                    size="icon-sm"
                    :title="
                        autoActive ? 'Pause auto-check' : 'Resume auto-check'
                    "
                    @click="toggleAuto"
                >
                    <component :is="autoActive ? Pause : Play" class="size-4" />
                </Button>
                <Button
                    variant="outline"
                    size="sm"
                    data-test="recheck"
                    @click="refresh()"
                >
                    <RefreshCw class="size-4" />
                    Re-check
                </Button>
                <Button
                    variant="ghost"
                    size="sm"
                    class="text-destructive"
                    @click="remove"
                >
                    <Trash2 class="size-4" />
                    Remove
                </Button>
            </div>
        </div>

        <Card v-if="identity.type === 'domain'">
            <CardHeader>
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div class="space-y-1.5">
                        <CardTitle>DNS records</CardTitle>
                        <CardDescription>
                            Add these records at your DNS provider. Verification
                            can take up to 72 hours to propagate.
                        </CardDescription>
                    </div>
                    <Button
                        v-if="identity.dnsRecords.length > 0"
                        variant="outline"
                        size="sm"
                        data-test="copy-all"
                        @click="copyAll"
                    >
                        <component
                            :is="copiedAll ? Check : Copy"
                            class="size-4"
                        />
                        {{ copiedAll ? 'Copied' : 'Copy all' }}
                    </Button>
                </div>
            </CardHeader>
            <CardContent>
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Purpose</TableHead>
                            <TableHead>Type</TableHead>
                            <TableHead>Host</TableHead>
                            <TableHead>Value</TableHead>
                            <TableHead>Priority</TableHead>
                            <TableHead>In DNS</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow
                            v-for="(record, index) in identity.dnsRecords"
                            :key="index"
                            data-test="dns-record"
                        >
                            <TableCell>
                                <Badge variant="outline">{{
                                    record.purpose
                                }}</Badge>
                            </TableCell>
                            <TableCell class="font-mono text-xs">{{
                                record.type
                            }}</TableCell>
                            <TableCell class="font-mono text-xs">
                                <button
                                    type="button"
                                    class="inline-flex items-center gap-1 hover:text-primary"
                                    @click="copy(record.host)"
                                >
                                    <span class="max-w-[220px] truncate">{{
                                        record.host
                                    }}</span>
                                    <component
                                        :is="
                                            copied === record.host
                                                ? Check
                                                : Copy
                                        "
                                        class="size-3 shrink-0"
                                    />
                                </button>
                            </TableCell>
                            <TableCell class="font-mono text-xs">
                                <button
                                    type="button"
                                    class="inline-flex items-center gap-1 hover:text-primary"
                                    @click="copy(record.value)"
                                >
                                    <span class="max-w-[260px] truncate">{{
                                        record.value
                                    }}</span>
                                    <component
                                        :is="
                                            copied === record.value
                                                ? Check
                                                : Copy
                                        "
                                        class="size-3 shrink-0"
                                    />
                                </button>
                            </TableCell>
                            <TableCell class="text-xs">{{
                                record.priority ?? '—'
                            }}</TableCell>
                            <TableCell class="text-xs">
                                <span
                                    v-if="record.status === 'seen'"
                                    class="inline-flex items-center gap-1 text-primary"
                                    data-test="record-seen"
                                >
                                    <Check class="size-3" />
                                    Seen
                                </span>
                                <span
                                    v-else-if="record.status === 'missing'"
                                    class="text-muted-foreground"
                                >
                                    Not found yet
                                </span>
                                <span v-else class="text-muted-foreground"
                                    >—</span
                                >
                            </TableCell>
                        </TableRow>
                        <TableEmpty
                            v-if="identity.dnsRecords.length === 0"
                            :colspan="6"
                        >
                            No DNS records — re-check to fetch them.
                        </TableEmpty>
                    </TableBody>
                </Table>

                <Collapsible v-model:open="tipsOpen" class="mt-6">
                    <CollapsibleTrigger
                        class="flex w-full items-center justify-between rounded-md border px-3 py-2 text-sm font-medium"
                    >
                        How to add these records
                        <ChevronDown
                            class="size-4 transition-transform"
                            :class="{ 'rotate-180': tipsOpen }"
                        />
                    </CollapsibleTrigger>
                    <CollapsibleContent
                        class="space-y-4 px-3 py-4 text-sm text-muted-foreground"
                    >
                        <div>
                            <p class="font-medium text-foreground">
                                Cloudflare
                            </p>
                            <p>
                                Add each record under DNS. Set the DKIM CNAMEs
                                and the MX record to DNS only (grey cloud), not
                                proxied. Proxying breaks mail records.
                            </p>
                        </div>
                        <div>
                            <p class="font-medium text-foreground">Route 53</p>
                            <p>
                                Add the records to the hosted zone that runs
                                this domain's nameservers.
                            </p>
                        </div>
                        <div>
                            <p class="font-medium text-foreground">
                                Other providers
                            </p>
                            <p>
                                Some hosts want the name relative to your domain
                                (for example
                                <span class="font-mono">token._domainkey</span>)
                                instead of the full name. Enter whichever your
                                provider expects.
                            </p>
                        </div>
                    </CollapsibleContent>
                </Collapsible>
            </CardContent>
        </Card>

        <Card v-else>
            <CardHeader>
                <CardTitle class="flex items-center gap-2">
                    Email verification
                    <Check
                        v-if="identity.status === 'verified'"
                        class="size-4 text-primary"
                    />
                </CardTitle>
                <CardDescription v-if="identity.status === 'verified'">
                    {{ identity.identity }} is verified and ready to send from.
                </CardDescription>
                <CardDescription v-else>
                    Amazon SES emails a verification link to
                    {{ identity.identity }} (only while it is unverified — an
                    address that is already confirmed gets no new email). Click
                    the link, then re-check. If none arrives, the address is
                    likely already verified — just re-check.
                </CardDescription>
            </CardHeader>
        </Card>
    </div>
</template>
