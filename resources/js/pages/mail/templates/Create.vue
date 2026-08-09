<script setup lang="ts">
import { Form, Head, usePage } from '@inertiajs/vue3';
import { Save } from '@lucide/vue';
import { computed } from 'vue';
import DocsLink from '@/components/DocsLink.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import mail from '@/routes/mail';

const page = usePage();
const slug = computed(() => page.props.currentTeam?.slug ?? '');

const nameHint = '{{ name }}';

defineOptions({
    layout: (layoutProps: { currentTeam?: { slug: string } | null }) => ({
        breadcrumbs: [
            {
                title: 'Templates',
                href: layoutProps.currentTeam
                    ? mail.templates.index.url(layoutProps.currentTeam.slug)
                    : '/',
            },
            { title: 'New', href: '#' },
        ],
    }),
});
</script>

<template>
    <Head title="New template" />
    <h1 class="sr-only">New template</h1>

    <div class="mx-auto w-full max-w-2xl space-y-6 px-4 py-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <h2 class="text-xl font-semibold">New template</h2>
                <p class="text-sm text-muted-foreground">
                    Use
                    <code class="rounded bg-muted px-1 py-0.5 text-xs">{{
                        nameHint
                    }}</code>
                    style placeholders anywhere in the subject or body.
                </p>
            </div>
            <DocsLink page="templates" />
        </div>

        <Card>
            <CardContent>
                <Form
                    v-bind="mail.templates.store.form(slug)"
                    class="space-y-4"
                    v-slot="{ errors, processing }"
                >
                    <div class="grid gap-2">
                        <Label for="name">Name</Label>
                        <Input
                            id="name"
                            name="name"
                            placeholder="Welcome email"
                            required
                        />
                        <InputError :message="errors.name" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="subject">Subject</Label>
                        <Input
                            id="subject"
                            name="subject"
                            placeholder="Welcome, {{ name }}"
                        />
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

                    <div class="grid gap-2">
                        <Label for="text">Plain-text body</Label>
                        <Textarea
                            id="text"
                            name="text"
                            class="min-h-32 font-mono text-sm"
                        />
                        <InputError :message="errors.text" />
                    </div>

                    <Button
                        type="submit"
                        :disabled="processing"
                        data-test="save-template"
                    >
                        <Save class="size-4" />
                        Save template
                    </Button>
                </Form>
            </CardContent>
        </Card>
    </div>
</template>
