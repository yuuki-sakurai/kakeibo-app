<script setup lang="ts">
import { ref } from "vue";
import CsvImportModal from "./components/imports/CsvImportModal.vue";
import AppHeader from "./components/layout/AppHeader.vue";
import AppSidebar from "./components/layout/AppSidebar.vue";
import CardSettingsView from "./views/CardSettingsView.vue";
import DashboardView from "./views/DashboardView.vue";
import ImportHistoryView from "./views/ImportHistoryView.vue";
import TransactionsView from "./views/TransactionsView.vue";
import type { Page } from "./types";

const activePage = ref<Page>("home");
const uploadOpen = ref(false);
const toast = ref("");

function handleImported(fileName: string) {
    toast.value = `${fileName} を取り込みました`;
    uploadOpen.value = false;
    window.setTimeout(() => (toast.value = ""), 3000);
}
</script>

<template>
    <div class="shell">
        <AppSidebar :active-page="activePage" @navigate="activePage = $event" />
        <main>
            <AppHeader @upload="uploadOpen = true" />
            <div class="content">
                <DashboardView
                    v-if="activePage === 'home'"
                    @upload="uploadOpen = true"
                    @navigate="activePage = $event"
                />
                <TransactionsView
                    v-else-if="activePage === 'transactions'"
                    @upload="uploadOpen = true"
                />
                <ImportHistoryView
                    v-else-if="activePage === 'imports'"
                    @upload="uploadOpen = true"
                />
                <CardSettingsView v-else />
            </div>
        </main>
        <CsvImportModal
            v-if="uploadOpen"
            @close="uploadOpen = false"
            @imported="handleImported"
        />
        <div v-if="toast" class="toast">✓　{{ toast }}</div>
    </div>
</template>
