<script setup lang="ts">
import { Form, Head, router, usePage } from '@inertiajs/vue3';
import { ChevronDown, Plug, RefreshCw, Trash2, Webhook } from '@lucide/vue';
import { computed, ref } from 'vue';
import DocsLink from '@/components/DocsLink.vue';
import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardFooter,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import mail from '@/routes/mail';
import type { ProviderConnection, ProviderOption } from '@/types';

type Props = {
    provider: ProviderOption;
    connections: ProviderConnection[];
    webhookTunnel: {
        editable: boolean;
        url: string | null;
    };
};

const props = defineProps<Props>();

const tunnelUrl = ref(props.webhookTunnel.url ?? '');

const page = usePage();
const slug = computed(() => page.props.currentTeam?.slug ?? '');

defineOptions({
    layout: (layoutProps: { currentTeam?: { slug: string } | null }) => ({
        breadcrumbs: [
            {
                title: 'Connection',
                href: layoutProps.currentTeam
                    ? mail.connection.index.url(layoutProps.currentTeam.slug)
                    : '/',
            },
        ],
    }),
});

const isConnected = computed(() =>
    props.connections.some(
        (connection) => connection.provider === props.provider.value,
    ),
);

const activeConnection = computed(
    () => props.connections.find((connection) => connection.isActive) ?? null,
);

function statusVariant(status: string) {
    return status === 'connected'
        ? 'default'
        : status === 'failed'
          ? 'destructive'
          : 'secondary';
}

function switchTo(connection: ProviderConnection) {
    router.post(
        mail.connection.switch.url(slug.value),
        { provider: connection.provider },
        { preserveScroll: true },
    );
}

function sync(connection: ProviderConnection) {
    router.post(
        mail.connection.sync.url(slug.value),
        { provider: connection.provider },
        { preserveScroll: true },
    );
}

function provisionWebhook(connection: ProviderConnection) {
    router.post(
        mail.connection.webhook.url(slug.value),
        { provider: connection.provider },
        { preserveScroll: true },
    );
}

function saveTunnel() {
    router.post(
        mail.connection.tunnel.url(slug.value),
        { url: tunnelUrl.value },
        { preserveScroll: true },
    );
}

function clearTunnel() {
    tunnelUrl.value = '';
    router.post(
        mail.connection.tunnel.url(slug.value),
        { url: '' },
        { preserveScroll: true },
    );
}

function disconnect(connection: ProviderConnection) {
    if (
        !confirm(
            `Disconnect ${connection.providerLabel}? Stored credentials and its domains will be removed.`,
        )
    ) {
        return;
    }

    router.delete(mail.connection.destroy.url(slug.value), {
        data: { provider: connection.provider },
        preserveScroll: true,
    });
}

function formatNumber(value: number | null): string {
    return value === null ? '—' : new Intl.NumberFormat().format(value);
}
</script>

<template>
    <Head title="Amazon SES" />
    <h1 class="sr-only">Amazon SES</h1>

    <div class="space-y-6 px-4 py-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <h2 class="text-xl font-semibold">Amazon SES</h2>
                <p class="text-sm text-muted-foreground">
                    Connect your Amazon SES account with your own AWS
                    credentials. They are encrypted at rest and never leave your
                    team.
                </p>
            </div>
            <DocsLink page="connect-a-provider" />
        </div>

        <!-- Connect form -->
        <Card v-if="!isConnected" data-test="connect-card">
            <CardHeader>
                <CardTitle>Connect {{ provider.label }}</CardTitle>
                <CardDescription>
                    Enter the credentials for your {{ provider.label }} account.
                    They are encrypted at rest and never leave your team.
                </CardDescription>
            </CardHeader>
            <CardContent>
                <Form
                    v-bind="mail.connection.store.form(slug)"
                    class="space-y-4"
                    v-slot="{ errors, processing }"
                >
                    <input
                        type="hidden"
                        name="provider"
                        :value="provider.value"
                    />

                    <div
                        v-for="field in provider.credentialFields"
                        :key="field.name"
                        class="grid gap-2"
                    >
                        <Label :for="field.name">{{ field.label }}</Label>

                        <div v-if="field.type === 'select'" class="relative">
                            <select
                                :id="field.name"
                                :name="`credentials[${field.name}]`"
                                class="h-9 w-full appearance-none rounded-md border border-input bg-transparent py-1 pr-9 pl-3 text-sm outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 dark:bg-input/30"
                                :required="field.required"
                            >
                                <option
                                    v-for="option in field.options"
                                    :key="option.value"
                                    :value="option.value"
                                >
                                    {{ option.label }}
                                </option>
                            </select>
                            <ChevronDown
                                class="pointer-events-none absolute top-1/2 right-3 size-4 -translate-y-1/2 text-muted-foreground"
                            />
                        </div>

                        <Input
                            v-else
                            :id="field.name"
                            :name="`credentials[${field.name}]`"
                            :type="field.type"
                            :placeholder="field.placeholder"
                            :required="field.required"
                            autocomplete="off"
                        />

                        <InputError
                            :message="errors[`credentials.${field.name}`]"
                        />
                    </div>

                    <InputError :message="errors.provider" />

                    <Button
                        type="submit"
                        :disabled="processing"
                        data-test="connect-submit"
                    >
                        <Plug class="size-4" />
                        Connect {{ provider.label }}
                    </Button>
                </Form>
            </CardContent>
        </Card>

        <!-- Account health for the active connection -->
        <Card v-if="activeConnection">
            <CardHeader>
                <CardTitle class="flex items-center gap-2">
                    Account health
                    <Badge variant="outline">{{
                        activeConnection.providerLabel
                    }}</Badge>
                </CardTitle>
                <CardDescription>
                    Sending limits and reputation for your SES account.
                </CardDescription>
            </CardHeader>
            <CardContent class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div>
                    <p class="text-xs text-muted-foreground">
                        Sent (24h) / Quota
                    </p>
                    <p class="text-lg font-semibold">
                        {{ formatNumber(activeConnection.sentLast24h) }} /
                        {{ formatNumber(activeConnection.sendQuotaMax) }}
                    </p>
                </div>
                <div>
                    <p class="text-xs text-muted-foreground">Max send rate</p>
                    <p class="text-lg font-semibold">
                        {{ formatNumber(activeConnection.maxSendRate) }}/s
                    </p>
                </div>
                <div>
                    <p class="text-xs text-muted-foreground">
                        Production access
                    </p>
                    <p class="text-lg font-semibold">
                        {{
                            activeConnection.productionAccess
                                ? 'Enabled'
                                : 'Sandbox'
                        }}
                    </p>
                </div>
                <div>
                    <p class="text-xs text-muted-foreground">Event webhook</p>
                    <p class="text-lg font-semibold">
                        {{
                            activeConnection.webhookProvisioned
                                ? 'Provisioned'
                                : 'Not set up'
                        }}
                    </p>
                </div>
            </CardContent>

            <!-- Webhook setup / retry -->
            <CardFooter
                v-if="!activeConnection.webhookProvisioned"
                class="flex flex-col items-start gap-3 border-t"
            >
                <div class="text-sm text-muted-foreground">
                    <p>
                        Bounce, complaint, delivery and open/click events need a
                        publicly reachable HTTPS endpoint. In local development,
                        expose your app with a tunnel, paste its URL into the
                        <span class="font-medium">Local webhook tunnel</span>
                        field below, then retry.
                    </p>
                    <p
                        v-if="activeConnection.provisionError"
                        class="mt-2 text-destructive"
                    >
                        {{ activeConnection.provisionError }}
                    </p>
                </div>
                <Button
                    variant="outline"
                    size="sm"
                    data-test="provision-webhook"
                    @click="provisionWebhook(activeConnection)"
                >
                    <Webhook class="size-4" />
                    Set up event webhook
                </Button>
            </CardFooter>
        </Card>

        <!-- Local development webhook tunnel -->
        <Card v-if="webhookTunnel.editable" data-test="webhook-tunnel">
            <CardHeader>
                <CardTitle class="flex items-center gap-2">
                    <Webhook class="size-4" />
                    Local webhook tunnel
                    <Badge variant="secondary">Local only</Badge>
                </CardTitle>
                <CardDescription>
                    Start a tunnel (Expose, ngrok, <code>herd share</code>,
                    Cloudflare…) to your local app and paste its public HTTPS
                    URL here. SES delivers events to this base URL instead of
                    <code class="rounded bg-muted px-1 py-0.5 text-xs"
                        >APP_URL</code
                    >. Re-provision the webhook after changing it.
                </CardDescription>
            </CardHeader>
            <CardContent
                class="flex flex-col gap-3 sm:flex-row sm:items-center"
            >
                <div class="grid flex-1 gap-2">
                    <Label for="tunnel-url" class="sr-only"
                        >Tunnel base URL</Label
                    >
                    <Input
                        id="tunnel-url"
                        v-model="tunnelUrl"
                        type="url"
                        inputmode="url"
                        placeholder="https://your-subdomain.sharedwithexpose.com"
                        autocomplete="off"
                        @keyup.enter="saveTunnel"
                    />
                </div>
                <div class="flex items-center gap-2">
                    <Button data-test="save-tunnel" @click="saveTunnel">
                        Save
                    </Button>
                    <Button
                        v-if="webhookTunnel.url"
                        variant="ghost"
                        data-test="clear-tunnel"
                        @click="clearTunnel"
                    >
                        Clear
                    </Button>
                </div>
            </CardContent>
            <CardFooter
                v-if="activeConnection"
                class="flex flex-col items-start gap-2 border-t"
            >
                <p class="text-sm text-muted-foreground">
                    Re-provision after changing the URL so
                    {{ activeConnection.providerLabel }} delivers events to the
                    new endpoint. The previous subscription stops receiving
                    them.
                </p>
                <Button
                    variant="outline"
                    size="sm"
                    data-test="reprovision-webhook"
                    @click="provisionWebhook(activeConnection)"
                >
                    <Webhook class="size-4" />
                    Re-provision event webhook
                </Button>
            </CardFooter>
        </Card>

        <!-- Saved connections -->
        <div class="space-y-3">
            <h3 class="text-sm font-medium">Saved connections</h3>

            <Card
                v-for="connection in connections"
                :key="connection.id"
                data-test="connection-row"
                class="flex flex-row flex-wrap items-center justify-between gap-4 px-4 py-4"
            >
                <div class="flex items-center gap-3">
                    <span class="font-medium">{{
                        connection.providerLabel
                    }}</span>
                    <Badge :variant="statusVariant(connection.status)">
                        {{ connection.status }}
                    </Badge>
                    <Badge v-if="connection.isActive" variant="outline"
                        >Active</Badge
                    >
                </div>

                <div class="flex items-center gap-2">
                    <Button
                        v-if="!connection.isActive"
                        variant="outline"
                        size="sm"
                        data-test="switch-connection"
                        @click="switchTo(connection)"
                    >
                        Make active
                    </Button>
                    <Button variant="ghost" size="sm" @click="sync(connection)">
                        <RefreshCw class="size-4" />
                        Sync
                    </Button>
                    <Button
                        variant="ghost"
                        size="sm"
                        class="text-destructive"
                        data-test="disconnect-connection"
                        @click="disconnect(connection)"
                    >
                        <Trash2 class="size-4" />
                    </Button>
                </div>
            </Card>

            <p
                v-if="connections.length === 0"
                class="py-8 text-center text-muted-foreground"
            >
                No SES account connected yet. Add your credentials above to get
                started.
            </p>
        </div>
    </div>
</template>
