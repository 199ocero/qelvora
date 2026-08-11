<script setup lang="ts">
import { ref } from 'vue';

/**
 * A card that reveals a soft radial "flashlight" following the cursor on hover.
 * The pointer position is tracked as local coordinates and fed into a radial
 * gradient overlay; the overlay fades out when the pointer leaves.
 */
const x = ref(0);
const y = ref(0);
const active = ref(false);

function onMove(event: MouseEvent): void {
    const target = event.currentTarget as HTMLElement;
    const rect = target.getBoundingClientRect();

    x.value = event.clientX - rect.left;
    y.value = event.clientY - rect.top;
}
</script>

<template>
    <div
        class="group relative overflow-hidden"
        @mousemove="onMove"
        @mouseenter="active = true"
        @mouseleave="active = false"
    >
        <!-- flashlight overlay -->
        <div
            class="pointer-events-none absolute inset-0 z-0 transition-opacity duration-300"
            :style="{
                opacity: active ? 1 : 0,
                background: `radial-gradient(220px circle at ${x}px ${y}px, color-mix(in oklab, var(--primary) 16%, transparent), transparent 72%)`,
            }"
        />
        <div class="relative z-10">
            <slot />
        </div>
    </div>
</template>
