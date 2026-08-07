<script setup lang="ts">
import { Form, Head, usePage } from '@inertiajs/vue3';
import { Send } from '@lucide/vue';
import { computed } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import mail from '@/routes/mail';

type Props = {
    senders: string[];
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
            { title: 'Compose', href: '#' },
        ],
    }),
});
</script>

<template>
    <Head title="Compose email" />
    <h1 class="sr-only">Compose email</h1>

    <div class="mx-auto w-full max-w-2xl space-y-6 px-4 py-6">
        <div>
            <h2 class="text-xl font-semibold">Compose</h2>
            <p class="text-sm text-muted-foreground">
                Send a message through your active provider.
            </p>
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

        <Form
            v-else
            v-bind="mail.emails.store.form(slug)"
            class="space-y-4"
            v-slot="{ errors, processing }"
        >
            <div class="grid gap-2">
                <Label for="from">From</Label>
                <select
                    id="from"
                    name="from"
                    class="h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 dark:bg-input/30"
                    required
                >
                    <option
                        v-for="sender in senders"
                        :key="sender"
                        :value="
                            sender.includes('@') ? sender : `hello@${sender}`
                        "
                    >
                        {{ sender.includes('@') ? sender : `hello@${sender}` }}
                    </option>
                </select>
                <p class="text-xs text-muted-foreground">
                    Any address at a verified domain works; edit it after
                    selecting if needed.
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
                <Input id="subject" name="subject" required />
                <InputError :message="errors.subject" />
            </div>

            <div class="grid gap-2">
                <Label for="html">HTML body</Label>
                <Textarea
                    id="html"
                    name="html"
                    class="min-h-48 font-mono text-sm"
                />
                <InputError :message="errors.html" />
            </div>

            <Button type="submit" :disabled="processing" data-test="send-email">
                <Send class="size-4" />
                Send
            </Button>
        </Form>
    </div>
</template>
