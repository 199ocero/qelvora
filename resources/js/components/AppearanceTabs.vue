<script setup lang="ts">
import { Monitor, Moon, Sun } from '@lucide/vue';
import type { HTMLAttributes } from 'vue';
import { Tabs, TabsList, TabsTrigger } from '@/components/ui/tabs';
import type { Appearance } from '@/composables/useAppearance';
import { useAppearance } from '@/composables/useAppearance';

defineProps<{ class?: HTMLAttributes['class'] }>();

const { appearance, updateAppearance } = useAppearance();

const tabs: { value: Appearance; Icon: typeof Sun; label: string }[] = [
    { value: 'light', Icon: Sun, label: 'Light' },
    { value: 'dark', Icon: Moon, label: 'Dark' },
    { value: 'system', Icon: Monitor, label: 'System' },
];
</script>

<template>
    <Tabs
        :model-value="appearance"
        :class="$props.class"
        @update:model-value="(value) => updateAppearance(value as Appearance)"
    >
        <TabsList>
            <TabsTrigger
                v-for="{ value, Icon, label } in tabs"
                :key="value"
                :value="value"
            >
                <component :is="Icon" />
                {{ label }}
            </TabsTrigger>
        </TabsList>
    </Tabs>
</template>
