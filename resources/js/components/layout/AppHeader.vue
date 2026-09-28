<script setup lang="ts">
const emit = defineEmits<{ upload: [] }>();
</script>

<template>
    <header>
        <span class="mobile-brand">¥　俺の家計簿</span>
        <button class="primary top" @click="emit('upload')">
            ＋ CSVを取り込む
        </button>
    </header>
</template>
