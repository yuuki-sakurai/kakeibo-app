<script setup lang="ts">
import { computed, ref } from "vue";
import type { Transaction } from "../../types";

const props = defineProps<{ transactions: Transaction[]; initialDate: Date }>();
const cursor = ref(
    new Date(props.initialDate.getFullYear(), props.initialDate.getMonth(), 1),
);
const selectedDate = ref("");
const weekdays = ["日", "月", "火", "水", "木", "金", "土"];
const title = computed(
    () => `${cursor.value.getFullYear()}年${cursor.value.getMonth() + 1}月`,
);
const totalsByDate = computed(() =>
    props.transactions.reduce<Record<string, number>>((totals, item) => {
        totals[item.date] = (totals[item.date] ?? 0) + item.amount;
        return totals;
    }, {}),
);
const calendarDays = computed(() => {
    const year = cursor.value.getFullYear();
    const month = cursor.value.getMonth();
    const leading = new Date(year, month, 1).getDay();
    const count = new Date(year, month + 1, 0).getDate();
    return [
        ...Array(leading).fill(null),
        ...Array.from({ length: count }, (_, index) => {
            const day = index + 1;
            const key = `${year}/${String(month + 1).padStart(2, "0")}/${String(day).padStart(2, "0")}`;
            return {
                day,
                key,
                amount: totalsByDate.value[key] ?? 0,
                isToday: key === formatDate(props.initialDate),
            };
        }),
    ];
});
const selectedTransactions = computed(() =>
    props.transactions.filter((item) => item.date === selectedDate.value),
);
const selectedTotal = computed(() =>
    selectedTransactions.value.reduce((sum, item) => sum + item.amount, 0),
);
function formatDisplayDate(value: string) {
    const [year, month, day] = value.split("/");
    return `${year}年${Number(month)}月${Number(day)}日`;
}

function formatDate(date: Date) {
    return `${date.getFullYear()}/${String(date.getMonth() + 1).padStart(2, "0")}/${String(date.getDate()).padStart(2, "0")}`;
}
function moveMonth(amount: number) {
    cursor.value = new Date(
        cursor.value.getFullYear(),
        cursor.value.getMonth() + amount,
        1,
    );
    selectedDate.value = "";
}
const yen = (amount: number) => `¥${amount.toLocaleString("ja-JP")}`;
</script>

<template>
    <section class="card panel calendar-panel">
        <div class="panel-head calendar-head">
            <div>
                <h3>支出カレンダー</h3>
                <p>日別に使った金額を確認できます</p>
            </div>
            <div class="calendar-nav">
                <button aria-label="前月" @click="moveMonth(-1)">‹</button
                ><strong>{{ title }}</strong
                ><button aria-label="翌月" @click="moveMonth(1)">›</button>
            </div>
        </div>
        <div class="calendar-grid calendar-weekdays">
            <span v-for="weekday in weekdays" :key="weekday">{{
                weekday
            }}</span>
        </div>
        <div class="calendar-grid calendar-days">
            <span
                v-for="(date, index) in calendarDays"
                :key="date?.key ?? `empty-${index}`"
                :class="[
                    'calendar-day',
                    {
                        empty: !date,
                        today: date?.isToday,
                        selected: date?.key === selectedDate,
                    },
                ]"
                @click="date && (selectedDate = date.key)"
            >
                <template v-if="date"
                    ><b>{{ date.day }}</b
                    ><small v-if="date.amount">{{ yen(date.amount) }}</small
                    ><i v-if="date.amount"></i
                ></template>
            </span>
        </div>
        <div v-if="selectedDate" class="calendar-detail">
            <div>
                <span>{{ formatDisplayDate(selectedDate) }}</span
                ><strong>{{ yen(selectedTotal) }}</strong>
            </div>
            <p v-for="item in selectedTransactions" :key="item.merchant">
                <span>{{ item.merchant }}</span
                ><b>{{ yen(item.amount) }}</b>
            </p>
            <p v-if="!selectedTransactions.length" class="no-spending">
                この日の支出はありません
            </p>
        </div>
        <p v-else class="calendar-hint">
            日付を選択すると、その日の明細を表示します
        </p>
    </section>
</template>
