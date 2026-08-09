<script setup lang="ts">
import type { HTMLAttributes } from 'vue';

defineOptions({
    inheritAttrs: false,
});

type Props = {
    className?: HTMLAttributes['class'];
};

defineProps<Props>();
</script>

<template>
    <svg
        xmlns="http://www.w3.org/2000/svg"
        viewBox="0 0 24 24"
        fill="none"
        :class="className"
        v-bind="$attrs"
    >
        <!-- motion trail: a dotted launch trajectory in the inherited color -->
        <path
            d="M3 20.6C5.1 18.2 7.6 15.6 10.4 13.2"
            stroke="currentColor"
            stroke-width="1.7"
            stroke-linecap="round"
            stroke-dasharray="0.1 3.2"
            opacity="0.85"
        />
        <circle cx="2.6" cy="21" r="1.15" fill="currentColor" />

        <!-- paper plane, brand-primary with a two-tone fold -->
        <g stroke="var(--primary)" stroke-width="1.3" stroke-linejoin="round">
            <!-- upper wing: lighter facet -->
            <path
                d="M21.5 2.5 2.8 9.2 11 12.8Z"
                fill="var(--primary)"
                fill-opacity="0.5"
            />
            <!-- body -->
            <path d="M21.5 2.5 11 12.8 14.6 21Z" fill="var(--primary)" />
        </g>
    </svg>
</template>
