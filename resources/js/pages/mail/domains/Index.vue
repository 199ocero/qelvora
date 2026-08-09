<script setup lang="ts">
import { Form, Head, Link, usePage } from '@inertiajs/vue3';
import { ChevronDown, Globe, Mail, Plus } from '@lucide/vue';
import { computed, ref } from 'vue';
import DocsLink from '@/components/DocsLink.vue';
import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
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
import mail from '@/routes/mail';
import type { IdentityStatus, MailIdentity } from '@/types';

type Props = {
    identities: MailIdentity[];
    hasActiveConnection: boolean;
};

defineProps<Props>();

const page = usePage();
const slug = computed(() => page.props.currentTeam?.slug ?? '');
const formKey = ref(0);

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
</script>

<template>
    <Head title="Domains & identities" />
    <h1 class="sr-only">Domains &amp; identities</h1>

    <div class="space-y-6 px-4 py-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <h2 class="text-xl font-semibold">Domains &amp; identities</h2>
                <p class="text-sm text-muted-foreground">
                    Verify the domains and email addresses you send from.
                    Publish the DNS records we generate, then re-check to
                    confirm verification.
                </p>
            </div>
            <DocsLink page="verify-a-domain" />
        </div>

        <Card v-if="hasActiveConnection">
            <CardHeader>
                <CardTitle>Add an identity</CardTitle>
                <CardDescription>
                    Add a whole domain (recommended) or a single email address.
                </CardDescription>
            </CardHeader>
            <CardContent>
                <Form
                    :key="formKey"
                    v-bind="mail.domains.store.form(slug)"
                    class="flex flex-col gap-4 sm:flex-row sm:items-end"
                    v-slot="{ errors, processing }"
                    @success="formKey++"
                >
                    <div class="grid gap-2 sm:w-40">
                        <Label for="type">Type</Label>
                        <div class="relative">
                            <select
                                id="type"
                                name="type"
                                class="h-9 w-full appearance-none rounded-md border border-input bg-transparent py-1 pr-9 pl-3 text-sm outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 dark:bg-input/30"
                            >
                                <option value="domain">Domain</option>
                                <option value="email">Email address</option>
                            </select>
                            <ChevronDown
                                class="pointer-events-none absolute top-1/2 right-3 size-4 -translate-y-1/2 text-muted-foreground"
                            />
                        </div>
                    </div>
                    <div class="grid flex-1 gap-2">
                        <Label for="identity">Domain or email</Label>
                        <Input
                            id="identity"
                            name="identity"
                            placeholder="example.com"
                            required
                            autocomplete="off"
                        />
                        <InputError :message="errors.identity" />
                    </div>
                    <Button
                        type="submit"
                        :disabled="processing"
                        data-test="add-identity"
                    >
                        <Plus class="size-4" />
                        Add
                    </Button>
                </Form>
            </CardContent>
        </Card>

        <p
            v-else
            class="rounded-lg border border-dashed p-6 text-center text-sm text-muted-foreground"
        >
            Connect Amazon SES before adding sending domains.
        </p>

        <div class="space-y-3">
            <Link
                v-for="identity in identities"
                :key="identity.id"
                :href="mail.domains.show.url([slug, identity.id])"
                data-test="identity-row"
                class="block rounded-xl"
            >
                <Card
                    class="flex flex-row items-center justify-between gap-4 px-4 py-4 transition-colors hover:border-primary"
                >
                    <div class="flex items-center gap-3">
                        <component
                            :is="identity.type === 'domain' ? Globe : Mail"
                            class="size-4 text-muted-foreground"
                        />
                        <span class="font-medium">{{ identity.identity }}</span>
                    </div>
                    <Badge :variant="statusVariant(identity.status)">
                        {{ identity.statusLabel }}
                    </Badge>
                </Card>
            </Link>

            <p
                v-if="identities.length === 0"
                class="py-8 text-center text-muted-foreground"
            >
                No identities yet.
            </p>
        </div>
    </div>
</template>
