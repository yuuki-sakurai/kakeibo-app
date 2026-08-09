<script setup lang="ts">
import { ref } from "vue";

const emit = defineEmits<{ close: []; imported: [fileName: string] }>();
const selectedFile = ref<File>();
function selectFile(event: Event) {
    selectedFile.value = (event.target as HTMLInputElement).files?.[0];
}
function importCsv() {
    if (selectedFile.value) emit("imported", selectedFile.value.name);
}
</script>

<template>
    <div class="backdrop" @click.self="emit('close')">
        <section
            class="modal"
            role="dialog"
            aria-modal="true"
            aria-labelledby="import-title"
        >
            <div class="modal-head">
                <div>
                    <small>CSV IMPORT</small>
                    <h2 id="import-title">利用明細を取り込む</h2>
                </div>
                <button aria-label="閉じる" @click="emit('close')">×</button>
            </div>
            <label class="field"
                >カードを選択<select>
                    <option>EPOSカード</option>
                </select></label
            >
            <label class="drop"
                ><input
                    type="file"
                    accept=".csv,text/csv"
                    @change="selectFile"
                /><b>↥</b
                ><template v-if="selectedFile"
                    ><strong>{{ selectedFile.name }}</strong
                    ><small
                        >{{ (selectedFile.size / 1024).toFixed(1) }} KB</small
                    ></template
                ><template v-else
                    ><strong>CSVファイルを選択</strong
                    ><span>EPOSカードからダウンロードしたCSV</span></template
                ></label
            >
            <div class="modal-note">
                ⓘ
                ショッピング利用・1回払いのみ取り込みます。分割・リボ・キャッシングは除外されます。
            </div>
            <div class="modal-actions">
                <button class="outline" @click="emit('close')">
                    キャンセル</button
                ><button
                    class="primary"
                    :disabled="!selectedFile"
                    @click="importCsv"
                >
                    取り込む
                </button>
            </div>
        </section>
    </div>
</template>
