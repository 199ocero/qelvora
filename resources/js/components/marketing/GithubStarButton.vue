<script setup lang="ts">
import { Star } from '@lucide/vue';
import { onMounted, ref } from 'vue';
import { GITHUB_API_REPO, GITHUB_REPO_URL } from '@/lib/marketing';
import { cn } from '@/lib/utils';

const props = withDefaults(
    defineProps<{
        /** Visual weight: the hero uses `solid`, nav uses `ghost`. */
        variant?: 'solid' | 'ghost';
        class?: string;
    }>(),
    { variant: 'ghost' },
);

const stars = ref<number | null>(null);

/** Compact star count, e.g. 1234 -> "1.2k". Null until the fetch resolves. */
const formattedStars = ref<string>('');

onMounted(async () => {
    try {
        const response = await fetch(GITHUB_API_REPO, {
            headers: { Accept: 'application/vnd.github+json' },
        });

        if (!response.ok) {
            return;
        }

        const data = (await response.json()) as { stargazers_count?: number };
        const count = data.stargazers_count;

        if (typeof count !== 'number') {
            return;
        }

        stars.value = count;
        formattedStars.value =
            count >= 1000 ? `${(count / 1000).toFixed(1)}k` : String(count);
    } catch {
        // Rate-limited or offline: the button still links out, just without a
        // count. No error surfaced to the visitor.
    }
});
</script>

<template>
    <a
        :href="GITHUB_REPO_URL"
        target="_blank"
        rel="noopener noreferrer"
        :class="
            cn(
                'group inline-flex h-9 items-center gap-2 rounded-md border px-3 text-sm font-medium transition-colors',
                props.variant === 'solid'
                    ? 'border-border bg-secondary text-secondary-foreground hover:bg-accent'
                    : 'border-border/70 bg-transparent text-foreground hover:bg-accent',
                props.class,
            )
        "
    >
        <svg viewBox="0 0 24 24" aria-hidden="true" class="size-4 fill-current">
            <path
                d="M12 .5C5.37.5 0 5.87 0 12.5c0 5.3 3.44 9.8 8.21 11.39.6.11.82-.26.82-.58 0-.29-.01-1.05-.02-2.06-3.34.73-4.04-1.61-4.04-1.61-.55-1.39-1.33-1.76-1.33-1.76-1.09-.74.08-.73.08-.73 1.2.09 1.84 1.24 1.84 1.24 1.07 1.83 2.81 1.3 3.5.99.11-.78.42-1.3.76-1.6-2.67-.3-5.47-1.34-5.47-5.95 0-1.31.47-2.39 1.24-3.23-.13-.3-.54-1.53.11-3.18 0 0 1.01-.32 3.3 1.23a11.5 11.5 0 0 1 6.01 0c2.29-1.55 3.3-1.23 3.3-1.23.65 1.65.24 2.88.12 3.18.77.84 1.23 1.92 1.23 3.23 0 4.62-2.81 5.64-5.49 5.94.43.37.81 1.1.81 2.22 0 1.61-.01 2.9-.01 3.29 0 .32.21.7.82.58A12.01 12.01 0 0 0 24 12.5C24 5.87 18.63.5 12 .5Z"
            />
        </svg>
        <span>Star</span>
        <span
            v-if="stars !== null"
            class="flex items-center gap-1 border-l border-border/70 pl-2 font-mono text-xs text-muted-foreground tabular-nums"
        >
            <Star class="size-3 fill-primary text-primary" />
            {{ formattedStars }}
        </span>
    </a>
</template>
