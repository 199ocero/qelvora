<script setup lang="ts">
import { Form, Head, router, usePage } from '@inertiajs/vue3';
import { Ban, Plus, RefreshCw } from '@lucide/vue';
import { computed, ref } from 'vue';
import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
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
import type { Paginated, Suppression, SuppressionReason } from '@/types';

type Props = {
    suppressions: Paginated<Suppression>;
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
                title: 'Suppressions',
                href: layoutProps.currentTeam
                    ? mail.suppressions.index.url(layoutProps.currentTeam.slug)
                    : '/',
            },
        ],
    }),
});

function reasonVariant(reason: SuppressionReason) {
    return reason === 'complaint'
        ? 'destructive'
        : reason === 'bounce'
          ? 'secondary'
          : 'outline';
}

function formatDate(value: string | null): string {
    return value ? new Date(value).toLocaleDateString() : '—';
}

function sync() {
    router.post(
        mail.suppressions.sync.url(slug.value),
        {},
        { preserveScroll: true },
    );
}

function remove(suppression: Suppression) {
    if (!confirm(`Remove ${suppression.email} from the suppression list?`)) {
        return;
    }

    router.delete(mail.suppressions.destroy.url([slug.value, suppression.id]), {
        preserveScroll: true,
    });
}
</script>

<template>
    <Head title="Suppressions" />
    <h1 class="sr-only">Suppressions</h1>

    <div class="space-y-6 px-4 py-6">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h2 class="text-xl font-semibold">Suppression list</h2>
                <p class="text-sm text-muted-foreground">
                    Addresses that will never receive mail. Bounces and
                    complaints are added automatically.
                </p>
            </div>
            <Button
                v-if="hasActiveConnection"
                variant="outline"
                size="sm"
                @click="sync"
            >
                <RefreshCw class="size-4" />
                Sync from provider
            </Button>
        </div>

        <Card>
            <CardHeader>
                <CardTitle>Suppress an address</CardTitle>
            </CardHeader>
            <CardContent>
                <Form
                    :key="formKey"
                    v-bind="mail.suppressions.store.form(slug)"
                    class="flex flex-col gap-4 sm:flex-row sm:items-end"
                    v-slot="{ errors, processing }"
                    @success="formKey++"
                >
                    <div class="grid flex-1 gap-2">
                        <Label for="email">Email address</Label>
                        <Input
                            id="email"
                            name="email"
                            type="email"
                            placeholder="user@example.com"
                            required
                        />
                        <InputError :message="errors.email" />
                    </div>
                    <Button
                        type="submit"
                        :disabled="processing"
                        data-test="add-suppression"
                    >
                        <Plus class="size-4" />
                        Suppress
                    </Button>
                </Form>
            </CardContent>
        </Card>

        <div class="rounded-lg border">
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>Email</TableHead>
                        <TableHead>Reason</TableHead>
                        <TableHead>Source</TableHead>
                        <TableHead>Since</TableHead>
                        <TableHead class="text-right">Actions</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableRow
                        v-for="suppression in suppressions.data"
                        :key="suppression.id"
                        data-test="suppression-row"
                    >
                        <TableCell class="font-medium">{{
                            suppression.email
                        }}</TableCell>
                        <TableCell>
                            <Badge :variant="reasonVariant(suppression.reason)">
                                {{ suppression.reasonLabel }}
                            </Badge>
                        </TableCell>
                        <TableCell
                            class="text-xs text-muted-foreground capitalize"
                            >{{ suppression.source }}</TableCell
                        >
                        <TableCell class="text-xs text-muted-foreground">{{
                            formatDate(suppression.suppressedAt)
                        }}</TableCell>
                        <TableCell class="text-right">
                            <Button
                                variant="ghost"
                                size="sm"
                                @click="remove(suppression)"
                            >
                                <Ban class="size-4" />
                                Remove
                            </Button>
                        </TableCell>
                    </TableRow>
                    <TableEmpty
                        v-if="suppressions.data.length === 0"
                        :colspan="5"
                    >
                        No suppressed addresses.
                    </TableEmpty>
                </TableBody>
            </Table>
        </div>
    </div>
</template>
