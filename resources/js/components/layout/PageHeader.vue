<script setup lang="ts">
withDefaults(
    defineProps<{
        eyebrow?: string;
        title: string;
        description: string;
        actionLabel?: string;
    }>(),
    {
        eyebrow: "",
        actionLabel: "",
    },
);
const emit = defineEmits<{ action: [] }>();
</script>

<template>
    <section class="heading">
        <div>
            <small v-if="eyebrow" class="eyebrow">{{ eyebrow }}</small>
            <h1>{{ title }}</h1>
            <p>{{ description }}</p>
        </div>
        <button v-if="actionLabel" class="primary" @click="emit('action')">
            {{ actionLabel }}
        </button>
    </section>
</template>
