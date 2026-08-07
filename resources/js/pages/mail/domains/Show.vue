<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
import { ArrowLeft, Check, Copy, RefreshCw, Trash2 } from '@lucide/vue';
import { computed, ref } from 'vue';
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

function refresh() {
    router.post(
        mail.domains.refresh.url([slug.value, props.identity.id]),
        {},
        { preserveScroll: true },
    );
}

function remove() {
    if (!confirm(`Remove ${props.identity.identity}?`)) {
        return;
    }

    router.delete(mail.domains.destroy.url([slug.value, props.identity.id]));
}
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
            <div class="flex items-center gap-2">
                <Button
                    variant="outline"
                    size="sm"
                    data-test="recheck"
                    @click="refresh"
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
                <CardTitle>DNS records</CardTitle>
                <CardDescription>
                    Add these records at your DNS provider. Verification can
                    take up to 72 hours to propagate.
                </CardDescription>
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
                        </TableRow>
                        <TableEmpty
                            v-if="identity.dnsRecords.length === 0"
                            :colspan="5"
                        >
                            No DNS records — re-check to fetch them.
                        </TableEmpty>
                    </TableBody>
                </Table>
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
