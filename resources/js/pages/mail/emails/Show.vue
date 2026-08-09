<script setup lang="ts">
import { Form, Head, usePage } from '@inertiajs/vue3';
import { ArrowLeft, RefreshCw, X } from '@lucide/vue';
import { computed } from 'vue';
import DocsLink from '@/components/DocsLink.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import mail from '@/routes/mail';
import type { EmailEvent, EmailMessage, EmailMessageStatus } from '@/types';

type Props = {
    message: EmailMessage;
    events: EmailEvent[];
};

const props = defineProps<Props>();

const page = usePage();
const slug = computed(() => page.props.currentTeam?.slug ?? '');

const canSend = computed(() => page.props.mailPermissions?.canSendEmail);
const canResend = computed(
    () =>
        canSend.value &&
        (props.message.status === 'failed' ||
            props.message.status === 'rejected'),
);
const canCancel = computed(
    () => canSend.value && props.message.status === 'scheduled',
);

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
    <Head :title="message.subject ?? 'Email'" />
    <h1 class="sr-only">{{ message.subject }}</h1>

    <div class="space-y-6 px-4 py-6">
        <div class="flex items-center gap-3">
            <Button variant="ghost" size="icon-sm" as-child>
                <a :href="mail.emails.index.url(slug)"
                    ><ArrowLeft class="size-4"
                /></a>
            </Button>
            <div class="flex-1">
                <h2 class="text-xl font-semibold">{{ message.subject }}</h2>
                <p class="text-sm text-muted-foreground">
                    {{ message.fromAddress }} → {{ message.to.join(', ') }}
                </p>
            </div>

            <DocsLink page="message-events" />

            <Form
                v-if="canResend"
                v-bind="mail.emails.resend.form([slug, message.id])"
                v-slot="{ processing }"
            >
                <Button
                    type="submit"
                    variant="outline"
                    size="sm"
                    :disabled="processing"
                    data-test="resend-email"
                >
                    <RefreshCw class="size-4" />
                    Resend
                </Button>
            </Form>

            <Form
                v-if="canCancel"
                v-bind="mail.emails.cancel.form([slug, message.id])"
                v-slot="{ processing }"
            >
                <Button
                    type="submit"
                    variant="outline"
                    size="sm"
                    :disabled="processing"
                    data-test="cancel-email"
                >
                    <X class="size-4" />
                    Cancel
                </Button>
            </Form>

            <Badge :variant="statusVariant(message.status)">{{
                message.statusLabel
            }}</Badge>
        </div>

        <p
            v-if="message.status === 'scheduled' && message.scheduledAt"
            class="rounded-md border border-dashed px-4 py-2 text-sm text-muted-foreground"
        >
            Scheduled to send on {{ formatDate(message.scheduledAt) }}.
        </p>

        <div class="grid gap-6 lg:grid-cols-3">
            <Card class="lg:col-span-2">
                <CardHeader>
                    <CardTitle>Message</CardTitle>
                </CardHeader>
                <CardContent>
                    <div
                        v-if="message.html"
                        class="prose prose-sm dark:prose-invert max-w-none"
                        v-html="message.html"
                    />
                    <pre
                        v-else-if="message.text"
                        class="text-sm whitespace-pre-wrap"
                        >{{ message.text }}</pre>
                    <p v-else class="text-sm text-muted-foreground">
                        No body stored.
                    </p>

                    <p
                        v-if="message.error"
                        class="mt-4 text-sm text-destructive"
                    >
                        {{ message.error }}
                    </p>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Timeline</CardTitle>
                </CardHeader>
                <CardContent>
                    <ol class="space-y-4">
                        <li
                            v-for="event in events"
                            :key="event.id"
                            data-test="timeline-event"
                            class="flex items-start gap-3"
                        >
                            <span
                                class="mt-1.5 size-2 shrink-0 rounded-full bg-primary"
                            />
                            <div>
                                <p class="text-sm font-medium">
                                    {{ event.typeLabel }}
                                </p>
                                <p class="text-xs text-muted-foreground">
                                    {{
                                        formatDate(
                                            event.occurredAt ?? event.createdAt,
                                        )
                                    }}
                                    <template v-if="event.bounceType">
                                        · {{ event.bounceType }}</template
                                    >
                                </p>
                            </div>
                        </li>
                        <li
                            v-if="events.length === 0"
                            class="text-sm text-muted-foreground"
                        >
                            No events yet.
                        </li>
                    </ol>
                </CardContent>
            </Card>
        </div>
    </div>
</template>
