<script setup lang="ts">
import { computed } from "vue";
import type { Transaction } from "../../types";

const props = defineProps<{ transactions: Transaction[]; currentDate: Date }>();
const budget = 120000;
const monthKey = computed(
    () =>
        `${props.currentDate.getFullYear()}/${String(props.currentDate.getMonth() + 1).padStart(2, "0")}`,
);
const monthlyTransactions = computed(() =>
    props.transactions.filter((item) => item.date.startsWith(monthKey.value)),
);
const total = computed(() =>
    monthlyTransactions.value.reduce((sum, item) => sum + item.amount, 0),
);
const remaining = computed(() => Math.max(budget - total.value, 0));
const progress = computed(() => Math.min((total.value / budget) * 100, 100));
const categoryTotals = computed(() => {
    const totals = new Map<string, number>();
    monthlyTransactions.value.forEach((item) =>
        totals.set(
            item.category,
            (totals.get(item.category) ?? 0) + item.amount,
        ),
    );
    return [...totals.entries()].sort((a, b) => b[1] - a[1]).slice(0, 3);
});
const yen = (amount: number) => `¥${amount.toLocaleString("ja-JP")}`;
</script>

<template>
    <article class="card spending-hero">
        <div class="spending-hero__main">
            <div class="section-label"><span>¥</span>今月使ったお金</div>
            <h2>{{ yen(total) }}</h2>
            <p>
                {{ currentDate.getMonth() + 1 }}月1日〜{{
                    currentDate.getDate()
                }}日の支出合計
            </p>
            <div class="budget-row">
                <span>今月の予算</span
                ><strong>{{ yen(total) }} / {{ yen(budget) }}</strong>
            </div>
            <div class="budget-bar">
                <i :style="{ width: `${progress}%` }"></i>
            </div>
            <small
                >予算まであと <b>{{ yen(remaining) }}</b></small
            >
        </div>
        <div class="spending-hero__categories">
            <p>支出の多いカテゴリ</p>
            <div
                v-for="([category, amount], index) in categoryTotals"
                :key="category"
                class="category-rank"
            >
                <span>{{ index + 1 }}</span
                ><strong>{{ category }}</strong
                ><b>{{ yen(amount) }}</b>
            </div>
        </div>
    </article>
</template>
