<script setup lang="ts">
import { Form, Head, usePage } from '@inertiajs/vue3';
import { Save } from '@lucide/vue';
import { computed } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import mail from '@/routes/mail';
import type { EmailTemplate } from '@/types';

type Props = {
    template: EmailTemplate;
};

const props = defineProps<Props>();

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
            { title: 'Edit', href: '#' },
        ],
    }),
});
</script>

<template>
    <Head :title="`Edit ${props.template.name}`" />
    <h1 class="sr-only">Edit template</h1>

    <div class="mx-auto w-full max-w-2xl space-y-6 px-4 py-6">
        <div>
            <h2 class="text-xl font-semibold">Edit template</h2>
            <p class="text-sm text-muted-foreground">
                Use
                <code class="rounded bg-muted px-1 py-0.5 text-xs">{{
                    nameHint
                }}</code>
                style placeholders anywhere in the subject or body.
            </p>
        </div>

        <Card>
            <CardContent>
                <Form
                    v-bind="
                        mail.templates.update.form([slug, props.template.id])
                    "
                    class="space-y-4"
                    v-slot="{ errors, processing }"
                >
                    <div class="grid gap-2">
                        <Label for="name">Name</Label>
                        <Input
                            id="name"
                            name="name"
                            :default-value="props.template.name"
                            required
                        />
                        <InputError :message="errors.name" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="subject">Subject</Label>
                        <Input
                            id="subject"
                            name="subject"
                            :default-value="props.template.subject ?? ''"
                        />
                        <InputError :message="errors.subject" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="html">HTML body</Label>
                        <Textarea
                            id="html"
                            name="html"
                            class="min-h-48 font-mono text-sm"
                            :default-value="props.template.html ?? ''"
                        />
                        <InputError :message="errors.html" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="text">Plain-text body</Label>
                        <Textarea
                            id="text"
                            name="text"
                            class="min-h-32 font-mono text-sm"
                            :default-value="props.template.text ?? ''"
                        />
                        <InputError :message="errors.text" />
                    </div>

                    <Button
                        type="submit"
                        :disabled="processing"
                        data-test="save-template"
                    >
                        <Save class="size-4" />
                        Save changes
                    </Button>
                </Form>
            </CardContent>
        </Card>
    </div>
</template>
