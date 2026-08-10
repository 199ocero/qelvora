<script setup lang="ts">
import { Form, Head, router, usePage } from '@inertiajs/vue3';
import { Check, ChevronDown, Copy, KeyRound, Lock, Plus } from '@lucide/vue';
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
import mail from '@/routes/mail';
import type { ApiKey, MailIdentity } from '@/types';

type Props = {
    apiKeys: ApiKey[];
    identities: MailIdentity[];
    newApiKey: string | null;
};

defineProps<Props>();

const page = usePage();
const slug = computed(() => page.props.currentTeam?.slug ?? '');
const formKey = ref(0);
const copied = ref(false);

defineOptions({
    layout: (layoutProps: { currentTeam?: { slug: string } | null }) => ({
        breadcrumbs: [
            {
                title: 'API keys',
                href: layoutProps.currentTeam
                    ? mail.apiKeys.index.url(layoutProps.currentTeam.slug)
                    : '/',
            },
        ],
    }),
});

function copyKey(value: string) {
    navigator.clipboard?.writeText(value);
    copied.value = true;
    setTimeout(() => (copied.value = false), 1500);
}

function revoke(apiKey: ApiKey) {
    if (
        !confirm(
            `Revoke "${apiKey.name}"? Applications using it will stop working.`,
        )
    ) {
        return;
    }

    router.delete(mail.apiKeys.destroy.url([slug.value, apiKey.id]), {
        preserveScroll: true,
    });
}
</script>

<template>
    <Head title="API keys" />
    <h1 class="sr-only">API keys</h1>

    <div class="space-y-6 px-4 py-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <h2 class="text-xl font-semibold">API keys</h2>
                <p class="text-sm text-muted-foreground">
                    Authenticate the sending API with a bearer token:
                    <code class="rounded bg-muted px-1 py-0.5 text-xs"
                        >POST /api/v1/emails</code
                    >
                </p>
            </div>
            <DocsLink page="api-keys" />
        </div>

        <!-- One-time key reveal -->
        <Card v-if="newApiKey" class="border-primary">
            <CardHeader>
                <CardTitle>Copy your new API key</CardTitle>
                <CardDescription>
                    This is the only time the full key is shown. Store it
                    somewhere safe.
                </CardDescription>
            </CardHeader>
            <CardContent>
                <Card
                    class="flex flex-row items-center gap-2 rounded-md bg-muted/50 p-3"
                >
                    <code class="flex-1 overflow-x-auto font-mono text-sm">{{
                        newApiKey
                    }}</code>
                    <Button
                        variant="outline"
                        size="sm"
                        @click="copyKey(newApiKey)"
                    >
                        <component :is="copied ? Check : Copy" class="size-4" />
                        {{ copied ? 'Copied' : 'Copy' }}
                    </Button>
                </Card>
            </CardContent>
        </Card>

        <Card>
            <CardHeader>
                <CardTitle>Create a key</CardTitle>
            </CardHeader>
            <CardContent>
                <Form
                    :key="formKey"
                    v-bind="mail.apiKeys.store.form(slug)"
                    class="flex flex-col gap-4 sm:flex-row sm:items-end"
                    v-slot="{ errors, processing }"
                    @success="formKey++"
                >
                    <div class="grid flex-1 gap-2">
                        <Label for="name">Name</Label>
                        <Input
                            id="name"
                            name="name"
                            placeholder="Production server"
                            required
                        />
                        <InputError :message="errors.name" />
                    </div>
                    <div class="grid flex-1 gap-2">
                        <Label for="mail_identity_id">Sender</Label>
                        <div class="relative">
                            <select
                                id="mail_identity_id"
                                name="mail_identity_id"
                                class="h-9 w-full appearance-none rounded-md border border-input bg-transparent py-1 pr-9 pl-3 text-sm outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 dark:bg-input/30"
                            >
                                <option value="">
                                    Any verified domain or address
                                </option>
                                <option
                                    v-for="identity in identities"
                                    :key="identity.id"
                                    :value="identity.id"
                                >
                                    {{ identity.identity }}
                                </option>
                            </select>
                            <ChevronDown
                                class="pointer-events-none absolute top-1/2 right-3 size-4 -translate-y-1/2 text-muted-foreground"
                            />
                        </div>
                        <InputError :message="errors.mail_identity_id" />
                    </div>
                    <Button
                        type="submit"
                        :disabled="processing"
                        data-test="create-api-key"
                    >
                        <Plus class="size-4" />
                        Create key
                    </Button>
                </Form>
            </CardContent>
        </Card>

        <div class="space-y-3">
            <Card
                v-for="apiKey in apiKeys"
                :key="apiKey.id"
                data-test="api-key-row"
                class="flex flex-row flex-wrap items-center justify-between gap-4 px-4 py-4"
            >
                <div class="flex items-center gap-3">
                    <KeyRound class="size-4 text-muted-foreground" />
                    <div>
                        <p class="font-medium">{{ apiKey.name }}</p>
                        <p class="font-mono text-xs text-muted-foreground">
                            {{ apiKey.keyPrefix }}…{{ apiKey.lastFour }}
                        </p>
                        <p
                            v-if="apiKey.restrictedTo"
                            class="mt-1 flex items-center gap-1 text-xs text-muted-foreground"
                        >
                            <Lock class="size-3" />
                            Sends only from {{ apiKey.restrictedTo }}
                        </p>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <span
                        v-if="apiKey.revokedAt"
                        class="text-xs text-destructive"
                        >Revoked</span
                    >
                    <Button
                        v-else
                        variant="ghost"
                        size="sm"
                        class="text-destructive"
                        @click="revoke(apiKey)"
                    >
                        Revoke
                    </Button>
                </div>
            </Card>

            <p
                v-if="apiKeys.length === 0"
                class="py-8 text-center text-muted-foreground"
            >
                No API keys yet.
            </p>
        </div>
    </div>
</template>
