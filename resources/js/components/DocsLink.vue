<script setup lang="ts">
import { BookOpen } from '@lucide/vue';
import docs from '@/routes/docs';

withDefaults(
    defineProps<{
        page: string;
        label?: string;
    }>(),
    {
        label: 'View docs',
    },
);
</script>

<template>
    <a
        :href="docs.show(page).url"
        target="_blank"
        rel="noopener noreferrer"
        class="inline-flex shrink-0 items-center gap-1.5 text-sm font-medium text-muted-foreground transition-colors hover:text-foreground"
    >
        <BookOpen class="size-4" />
        {{ label }}
    </a>
</template>
