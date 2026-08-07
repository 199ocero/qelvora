<script setup lang="ts">
import { Form, Head, router, usePage } from '@inertiajs/vue3';
import { Check, Copy, KeyRound, Plus } from '@lucide/vue';
import { computed, ref } from 'vue';
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
import type { ApiKey } from '@/types';

type Props = {
    apiKeys: ApiKey[];
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
        <div>
            <h2 class="text-xl font-semibold">API keys</h2>
            <p class="text-sm text-muted-foreground">
                Authenticate the sending API with a bearer token:
                <code class="rounded bg-muted px-1 py-0.5 text-xs"
                    >POST /api/v1/emails</code
                >
            </p>
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
                <div
                    class="flex items-center gap-2 rounded-md border bg-muted/50 p-3"
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
                </div>
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
            <div
                v-for="apiKey in apiKeys"
                :key="apiKey.id"
                data-test="api-key-row"
                class="flex flex-wrap items-center justify-between gap-4 rounded-lg border p-4"
            >
                <div class="flex items-center gap-3">
                    <KeyRound class="size-4 text-muted-foreground" />
                    <div>
                        <p class="font-medium">{{ apiKey.name }}</p>
                        <p class="font-mono text-xs text-muted-foreground">
                            {{ apiKey.keyPrefix }}…{{ apiKey.lastFour }}
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
            </div>

            <p
                v-if="apiKeys.length === 0"
                class="py-8 text-center text-muted-foreground"
            >
                No API keys yet.
            </p>
        </div>
    </div>
</template>
