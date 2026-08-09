<script setup lang="ts">
const emit = defineEmits<{ upload: [] }>();
</script>

<template>
    <header>
        <span class="mobile-brand">¥　クレカ管理</span>
        <button class="primary top" @click="emit('upload')">
            ＋ CSVを取り込む
        </button>
    </header>
</template>
