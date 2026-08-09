<script setup lang="ts">
import PageHeader from "../components/layout/PageHeader.vue";
import { importHistories } from "../data/demo";
const emit = defineEmits<{ upload: [] }>();
</script>

<template>
    <PageHeader
        eyebrow="IMPORT HISTORY"
        title="取込履歴"
        description="CSVの取込結果とエラーを確認できます。"
        action-label="＋ CSVを取り込む"
        @action="emit('upload')"
    />
    <section class="card panel history">
        <div
            v-for="history in importHistories"
            :key="history.fileName"
            class="history-row"
        >
            <b :class="history.hasWarning ? 'warn-icon' : 'ok-icon'">{{
                history.hasWarning ? "!" : "✓"
            }}</b>
            <p>
                <strong>{{ history.fileName }}</strong
                ><small>{{ history.importedAt }} ・ EPOSカード</small>
            </p>
            <div>
                <b>{{ history.importedCount }}件</b><small>取込</small>
            </div>
            <div>
                <b>{{ history.excludedCount }}件</b><small>除外</small>
            </div>
            <em :class="history.hasWarning ? 'warning' : 'success'">{{
                history.hasWarning ? "一部警告" : "成功"
            }}</em>
        </div>
    </section>
</template>
