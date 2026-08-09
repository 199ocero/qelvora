<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { PenLine } from '@lucide/vue';
import { computed } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
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
import type { EmailMessage, EmailMessageStatus, Paginated } from '@/types';

type Props = {
    messages: Paginated<EmailMessage>;
};

defineProps<Props>();

const page = usePage();
const slug = computed(() => page.props.currentTeam?.slug ?? '');

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

function statusVariant(status: EmailMessageStatus) {
    if (status === 'delivered' || status === 'sent') {
        return 'default';
    }

    if (
        status === 'bounced' ||
        status === 'complained' ||
        status === 'failed' ||
        status === 'rejected'
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
                            formatDate(message.createdAt)
                        }}</TableCell>
                    </TableRow>
                    <TableEmpty v-if="messages.data.length === 0" :colspan="5">
                        No emails sent yet.
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
