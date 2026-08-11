<script setup lang="ts">
import MarketingFooter from '@/components/marketing/MarketingFooter.vue';
import MarketingNav from '@/components/marketing/MarketingNav.vue';
import { useScrollReveal } from '@/composables/useScrollReveal';

useScrollReveal();
</script>

<template>
    <div class="min-h-screen bg-background text-foreground antialiased">
        <MarketingNav />
        <main class="relative overflow-x-clip pt-16">
            <slot />
        </main>
        <MarketingFooter />
    </div>
</template>
