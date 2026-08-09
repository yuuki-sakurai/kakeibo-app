<script setup lang="ts">
import { navigationItems } from "../../data/demo";
import type { Page } from "../../types";

defineProps<{ activePage: Page }>();
const emit = defineEmits<{ navigate: [page: Page] }>();
</script>

<template>
    <aside>
        <button class="brand" @click="emit('navigate', 'home')">
            <b>¥</b><strong>クレカ管理</strong>
        </button>
        <nav>
            <button
                v-for="item in navigationItems"
                :key="item.key"
                :class="{ active: activePage === item.key }"
                @click="emit('navigate', item.key)"
            >
                <i>{{ item.icon }}</i
                >{{ item.label }}
            </button>
        </nav>
        <div class="profile">
            <span>G</span>
            <p>
                <strong>ゲストユーザー</strong
                ><small>データは一時保存です</small>
            </p>
        </div>
    </aside>
</template>
