<script setup lang="ts">
import { Form, Head, usePage } from '@inertiajs/vue3';
import { ChevronDown, Send } from '@lucide/vue';
import { computed, ref } from 'vue';
import DocsLink from '@/components/DocsLink.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import mail from '@/routes/mail';
import type { EmailTemplate } from '@/types';

type Props = {
    senders: string[];
    templates: EmailTemplate[];
};

const props = defineProps<Props>();

const page = usePage();
const slug = computed(() => page.props.currentTeam?.slug ?? '');

const subject = ref('');
const html = ref('');
const scheduledAt = ref('');
const selectedTemplate = ref('');

function applyTemplate() {
    const template = props.templates.find(
        (candidate) => String(candidate.id) === selectedTemplate.value,
    );

    if (!template) {
        return;
    }

    subject.value = template.subject ?? '';
    html.value = template.html ?? template.text ?? '';
}

defineOptions({
    layout: (layoutProps: { currentTeam?: { slug: string } | null }) => ({
        breadcrumbs: [
            {
                title: 'Emails',
                href: layoutProps.currentTeam
                    ? mail.emails.index.url(layoutProps.currentTeam.slug)
                    : '/',
            },
            { title: 'Compose', href: '#' },
        ],
    }),
});
</script>

<template>
    <Head title="Compose email" />
    <h1 class="sr-only">Compose email</h1>

    <div class="mx-auto w-full max-w-2xl space-y-6 px-4 py-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <h2 class="text-xl font-semibold">Compose</h2>
                <p class="text-sm text-muted-foreground">
                    Send a message through your active provider.
                </p>
            </div>
            <DocsLink page="sending-email" />
        </div>

        <Card v-if="senders.length === 0">
            <CardHeader>
                <CardTitle>No verified senders</CardTitle>
                <CardDescription>
                    Verify a domain or email address before sending. Head to
                    Domains to add one.
                </CardDescription>
            </CardHeader>
        </Card>

        <Card v-else>
            <CardContent>
                <Form
                    v-bind="mail.emails.store.form(slug)"
                    class="space-y-4"
                    v-slot="{ errors, processing }"
                >
                    <div v-if="templates.length" class="grid gap-2">
                        <Label for="template">Template</Label>
                        <div class="relative">
                            <select
                                id="template"
                                v-model="selectedTemplate"
                                class="h-9 w-full appearance-none rounded-md border border-input bg-transparent py-1 pr-9 pl-3 text-sm outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 dark:bg-input/30"
                                data-test="template-picker"
                                @change="applyTemplate"
                            >
                                <option value="">Start from scratch</option>
                                <option
                                    v-for="template in templates"
                                    :key="template.id"
                                    :value="String(template.id)"
                                >
                                    {{ template.name }}
                                </option>
                            </select>
                            <ChevronDown
                                class="pointer-events-none absolute top-1/2 right-3 size-4 -translate-y-1/2 text-muted-foreground"
                            />
                        </div>
                        <p class="text-xs text-muted-foreground">
                            Prefill the subject and body from a saved template,
                            then edit before sending.
                        </p>
                    </div>

                    <div class="grid gap-2">
                        <Label for="from">From</Label>
                        <div class="relative">
                            <select
                                id="from"
                                name="from"
                                class="h-9 w-full appearance-none rounded-md border border-input bg-transparent py-1 pr-9 pl-3 text-sm outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 dark:bg-input/30"
                                required
                            >
                                <option
                                    v-for="sender in senders"
                                    :key="sender"
                                    :value="
                                        sender.includes('@')
                                            ? sender
                                            : `hello@${sender}`
                                    "
                                >
                                    {{
                                        sender.includes('@')
                                            ? sender
                                            : `hello@${sender}`
                                    }}
                                </option>
                            </select>
                            <ChevronDown
                                class="pointer-events-none absolute top-1/2 right-3 size-4 -translate-y-1/2 text-muted-foreground"
                            />
                        </div>
                        <p class="text-xs text-muted-foreground">
                            Any address at a verified domain works; edit it
                            after selecting if needed.
                        </p>
                        <InputError :message="errors.from" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="to">To</Label>
                        <Input
                            id="to"
                            name="to"
                            type="text"
                            placeholder="user@customer.com"
                            required
                        />
                        <p class="text-xs text-muted-foreground">
                            Separate multiple recipients with commas.
                        </p>
                        <InputError :message="errors.to" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="subject">Subject</Label>
                        <Input id="subject" v-model="subject" name="subject" />
                        <InputError :message="errors.subject" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="html">HTML body</Label>
                        <Textarea
                            id="html"
                            v-model="html"
                            name="html"
                            class="min-h-48 font-mono text-sm"
                        />
                        <InputError :message="errors.html" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="scheduled_at">Schedule for later</Label>
                        <Input
                            id="scheduled_at"
                            v-model="scheduledAt"
                            name="scheduled_at"
                            type="datetime-local"
                            data-test="schedule-at"
                        />
                        <p class="text-xs text-muted-foreground">
                            Leave empty to send now.
                        </p>
                        <InputError :message="errors.scheduled_at" />
                    </div>

                    <Button
                        type="submit"
                        :disabled="processing"
                        data-test="send-email"
                    >
                        <Send class="size-4" />
                        {{ scheduledAt ? 'Schedule' : 'Send' }}
                    </Button>
                </Form>
            </CardContent>
        </Card>
    </div>
</template>
