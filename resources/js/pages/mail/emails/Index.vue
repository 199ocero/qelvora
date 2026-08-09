<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { PenLine, Search, X } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
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
import type {
    EmailLogFilters,
    EmailMessage,
    EmailMessageStatus,
    Paginated,
} from '@/types';

type Props = {
    messages: Paginated<EmailMessage>;
    filters: EmailLogFilters;
    statuses: { value: string; label: string }[];
};

const props = defineProps<Props>();

const page = usePage();
const slug = computed(() => page.props.currentTeam?.slug ?? '');

const ALL = 'all';

const search = ref(props.filters.search ?? '');
const status = ref(props.filters.status ?? ALL);
const via = ref(props.filters.via ?? ALL);

defineOptions({
    layout: (layoutProps: { currentTeam?: { slug: string } | null }) => ({
        breadcrumbs: [
            {
                title: 'Emails',
                href: layoutProps.currentTeam
                    ? mail.emails.index.url(layoutProps.currentTeam.slug)
                    : '/',
            },
        ],
    }),
});

const hasFilters = computed(
    () => !!search.value || status.value !== ALL || via.value !== ALL,
);

function applyFilters() {
    router.get(
        mail.emails.index.url(slug.value),
        {
            search: search.value || undefined,
            status: status.value === ALL ? undefined : status.value,
            via: via.value === ALL ? undefined : via.value,
        },
        { preserveState: true, preserveScroll: true, replace: true },
    );
}

let searchTimeout: ReturnType<typeof setTimeout> | undefined;
watch(search, () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(applyFilters, 350);
});
watch([status, via], applyFilters);

function clearFilters() {
    search.value = '';
    status.value = ALL;
    via.value = ALL;
}

function statusVariant(value: EmailMessageStatus) {
    if (value === 'delivered' || value === 'sent') {
        return 'default';
    }

    if (
        value === 'bounced' ||
        value === 'complained' ||
        value === 'failed' ||
        value === 'rejected'
    ) {
        return 'destructive';
    }

    return 'secondary';
}

function formatDate(value: string | null): string {
    return value ? new Date(value).toLocaleString() : '—';
}
</script>

<template>
    <Head title="Email log" />
    <h1 class="sr-only">Email log</h1>

    <div class="space-y-6 px-4 py-6">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h2 class="text-xl font-semibold">Email log</h2>
                <p class="text-sm text-muted-foreground">
                    Every message sent through Xelqun and its delivery status.
                </p>
            </div>
            <Button as-child>
                <Link :href="mail.emails.create.url(slug)">
                    <PenLine class="size-4" />
                    Compose
                </Link>
            </Button>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <div class="relative min-w-56 flex-1">
                <Search
                    class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
                />
                <Input
                    v-model="search"
                    type="search"
                    placeholder="Search subject, sender, or recipient"
                    class="pl-9"
                    data-test="log-search"
                />
            </div>
            <Select v-model="status">
                <SelectTrigger class="w-40" data-test="log-status">
                    <SelectValue placeholder="Status" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem :value="ALL">All statuses</SelectItem>
                    <SelectItem
                        v-for="option in statuses"
                        :key="option.value"
                        :value="option.value"
                    >
                        {{ option.label }}
                    </SelectItem>
                </SelectContent>
            </Select>
            <Select v-model="via">
                <SelectTrigger class="w-32" data-test="log-via">
                    <SelectValue placeholder="Via" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem :value="ALL">Any source</SelectItem>
                    <SelectItem value="ui">UI</SelectItem>
                    <SelectItem value="api">API</SelectItem>
                </SelectContent>
            </Select>
            <Button
                v-if="hasFilters"
                variant="ghost"
                size="sm"
                @click="clearFilters"
            >
                <X class="size-4" />
                Clear
            </Button>
        </div>

        <Card class="overflow-hidden py-0 [&_tbody_tr:last-child]:border-0">
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>To</TableHead>
                        <TableHead>Subject</TableHead>
                        <TableHead>Status</TableHead>
                        <TableHead>Via</TableHead>
                        <TableHead>Sent</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableRow
                        v-for="message in messages.data"
                        :key="message.id"
                        data-test="message-row"
                        class="cursor-pointer"
                        @click="
                            $inertia.visit(
                                mail.emails.show.url([slug, message.id]),
                            )
                        "
                    >
                        <TableCell class="font-medium">{{
                            message.to.join(', ')
                        }}</TableCell>
                        <TableCell class="max-w-[280px] truncate">{{
                            message.subject
                        }}</TableCell>
                        <TableCell>
                            <Badge :variant="statusVariant(message.status)">{{
                                message.statusLabel
                            }}</Badge>
                        </TableCell>
                        <TableCell
                            class="text-xs text-muted-foreground uppercase"
                            >{{ message.sentVia }}</TableCell
                        >
                        <TableCell class="text-xs text-muted-foreground">{{
                            message.status === 'scheduled'
                                ? formatDate(message.scheduledAt)
                                : formatDate(message.createdAt)
                        }}</TableCell>
                    </TableRow>
                    <TableEmpty v-if="messages.data.length === 0" :colspan="5">
                        {{
                            hasFilters
                                ? 'No emails match these filters.'
                                : 'No emails sent yet.'
                        }}
                    </TableEmpty>
                </TableBody>
            </Table>
        </Card>

        <div
            v-if="messages.links.length > 3"
            class="flex flex-wrap justify-center gap-1"
        >
            <Button
                v-for="link in messages.links"
                :key="link.label"
                as-child
                variant="ghost"
                size="sm"
                :class="{
                    'bg-muted': link.active,
                    'pointer-events-none opacity-50': !link.url,
                }"
            >
                <Link :href="link.url ?? '#'" preserve-scroll>
                    <span v-html="link.label" />
                </Link>
            </Button>
        </div>
    </div>
</template>
