<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { FileText, Pencil, Plus } from '@lucide/vue';
import { computed } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import mail from '@/routes/mail';
import type { EmailTemplate } from '@/types';

type Props = {
    templates: EmailTemplate[];
    canManage: boolean;
};

const props = defineProps<Props>();

const page = usePage();
const slug = computed(() => page.props.currentTeam?.slug ?? '');

const variablesHint = '{{ variables }}';

defineOptions({
    layout: (layoutProps: { currentTeam?: { slug: string } | null }) => ({
        breadcrumbs: [
            {
                title: 'Templates',
                href: layoutProps.currentTeam
                    ? mail.templates.index.url(layoutProps.currentTeam.slug)
                    : '/',
            },
        ],
    }),
});

function destroy(template: EmailTemplate) {
    if (!confirm(`Delete "${template.name}"? This cannot be undone.`)) {
        return;
    }

    router.delete(mail.templates.destroy.url([slug.value, template.id]), {
        preserveScroll: true,
    });
}
</script>

<template>
    <Head title="Email templates" />
    <h1 class="sr-only">Email templates</h1>

    <div class="space-y-6 px-4 py-6">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h2 class="text-xl font-semibold">Templates</h2>
                <p class="text-sm text-muted-foreground">
                    Reusable email content with
                    <code class="rounded bg-muted px-1 py-0.5 text-xs">{{
                        variablesHint
                    }}</code>
                    you fill in when sending.
                </p>
            </div>
            <Button v-if="props.canManage" as-child>
                <Link :href="mail.templates.create.url(slug)">
                    <Plus class="size-4" />
                    New template
                </Link>
            </Button>
        </div>

        <div class="space-y-3">
            <Card
                v-for="template in templates"
                :key="template.id"
                data-test="template-row"
                class="flex flex-row flex-wrap items-center justify-between gap-4 px-4 py-4"
            >
                <div class="flex items-start gap-3">
                    <FileText class="mt-0.5 size-4 text-muted-foreground" />
                    <div class="space-y-1">
                        <p class="font-medium">{{ template.name }}</p>
                        <p class="text-sm text-muted-foreground">
                            {{ template.subject ?? 'No subject' }}
                        </p>
                        <div
                            v-if="template.variables.length"
                            class="flex flex-wrap gap-1 pt-1"
                        >
                            <Badge
                                v-for="variable in template.variables"
                                :key="variable"
                                variant="secondary"
                                class="font-mono text-xs"
                            >
                                {{ variable }}
                            </Badge>
                        </div>
                    </div>
                </div>
                <div v-if="props.canManage" class="flex items-center gap-1">
                    <Button variant="ghost" size="sm" as-child>
                        <Link
                            :href="mail.templates.edit.url([slug, template.id])"
                        >
                            <Pencil class="size-4" />
                            Edit
                        </Link>
                    </Button>
                    <Button
                        variant="ghost"
                        size="sm"
                        class="text-destructive"
                        @click="destroy(template)"
                    >
                        Delete
                    </Button>
                </div>
            </Card>

            <p
                v-if="templates.length === 0"
                class="py-8 text-center text-muted-foreground"
            >
                No templates yet.
            </p>
        </div>
    </div>
</template>
