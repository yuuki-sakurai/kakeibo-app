<script setup lang="ts">
import { computed, ref } from 'vue';

const appName = ref('俺の家計簿');
const count = ref(0);

const message = computed(() => {
    return `${appName.value}の開発環境が動いています`;
});

const increment = (): void => {
    count.value++;
};
</script>

<template>
    <main class="app">
        <section class="card">
            <p class="eyebrow">
                Development Environment
            </p>

            <h1>{{ appName }}</h1>

            <p>
                {{ message }}
            </p>

            <p>
                カウント: {{ count }}
            </p>

            <button
                type="button"
                @click="increment"
            >
                ＋1
            </button>
        </section>
    </main>
</template>

<style scoped>
.app {
    display: grid;
    min-height: 100vh;
    padding: 32px;
    place-items: center;
}

.card {
    width: min(100%, 640px);
    padding: 40px;
    border: 1px solid #dbe1ea;
    border-radius: 20px;
    background: #ffffff;
}

.eyebrow {
    color: #2563eb;
    font-size: 0.75rem;
    font-weight: 700;
}
</style>
