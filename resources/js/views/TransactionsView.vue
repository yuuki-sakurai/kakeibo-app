<script setup lang="ts">
import { computed, ref } from "vue";
import PageHeader from "../components/layout/PageHeader.vue";
import TransactionTable from "../components/transactions/TransactionTable.vue";
import { transactions } from "../data/demo";

const emit = defineEmits<{ upload: [] }>();
const search = ref("");
const month = ref("2026-08");
const filtered = computed(() =>
    transactions.filter(
        (item) =>
            (!month.value ||
                item.date.startsWith(month.value.replace("-", "/"))) &&
            item.merchant.toLowerCase().includes(search.value.toLowerCase()),
    ),
);
const total = computed(
    () =>
        `¥${filtered.value.reduce((sum, item) => sum + item.amount, 0).toLocaleString("ja-JP")}`,
);
</script>

<template>
    <PageHeader
        eyebrow="TRANSACTIONS"
        title="利用明細"
        description="取り込んだカード利用を検索・確認できます。"
        action-label="＋ CSVを取り込む"
        @action="emit('upload')"
    />
    <section class="card filters">
        <label class="search"
            >⌕ <input v-model="search" placeholder="利用場所を検索" /></label
        ><label
            >利用月<select v-model="month">
                <option value="">すべて</option>
                <option value="2026-08">2026年8月</option>
                <option value="2026-07">2026年7月</option>
            </select></label
        ><label
            >カード<select>
                <option>すべてのカード</option>
                <option>EPOSカード</option>
            </select></label
        >
    </section>
    <section class="card panel">
        <div class="panel-head">
            <div>
                <h3>利用明細</h3>
                <p>{{ filtered.length }}件の明細</p>
            </div>
            <strong class="total">合計 {{ total }}</strong>
        </div>
        <TransactionTable :transactions="filtered" detailed />
    </section>
</template>
