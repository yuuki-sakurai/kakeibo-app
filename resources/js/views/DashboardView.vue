<script setup lang="ts">
import BankAccountList from "../components/dashboard/BankAccountList.vue";
import MonthlySpendingSummary from "../components/dashboard/MonthlySpendingSummary.vue";
import SpendingCalendar from "../components/dashboard/SpendingCalendar.vue";
import TransactionTable from "../components/transactions/TransactionTable.vue";
import { bankAccounts, dashboardDate, transactions } from "../data/demo";
import type { Page } from "../types";

const emit = defineEmits<{ upload: []; navigate: [page: Page] }>();
const formattedDate = new Intl.DateTimeFormat("ja-JP", {
    year: "numeric",
    month: "long",
    day: "numeric",
    weekday: "long",
}).format(dashboardDate);
</script>

<template>
    <section class="heading dashboard-heading">
        <div>
            <small>{{ formattedDate }}</small>
            <h1>今月の家計</h1>
            <p>支出と口座残高をまとめて確認しましょう。</p>
        </div>
        <button class="primary" @click="emit('upload')">
            ＋ 明細を取り込む
        </button>
    </section>

    <MonthlySpendingSummary
        :transactions="transactions"
        :current-date="dashboardDate"
    />

    <section class="dashboard-grid">
        <SpendingCalendar
            :transactions="transactions"
            :initial-date="dashboardDate"
        />
        <BankAccountList :accounts="bankAccounts" />
    </section>

    <section class="card panel payment-panel">
        <div class="panel-head">
            <div>
                <h3>クレジットカードの支払予定</h3>
                <p>今後の引き落とし予定です</p>
            </div>
            <button>すべて見る →</button>
        </div>
        <div
            v-for="(payment, index) in [
                { month: '8月', amount: '¥42,300' },
                { month: '9月', amount: '¥54,930' },
            ]"
            :key="payment.month"
            class="payment"
        >
            <b
                >27<small>{{ payment.month }}</small></b
            ><span class="epos">EPOS</span>
            <p><strong>EPOSカード</strong><small>三菱UFJ銀行</small></p>
            <div>
                <strong>{{ payment.amount }}</strong
                ><small :class="index ? 'forecast' : 'ok'"
                    >● {{ index ? "見込み" : "確定" }}</small
                >
            </div>
            <i>›</i>
        </div>
    </section>

    <section class="card panel">
        <div class="panel-head">
            <div>
                <h3>最近の利用明細</h3>
                <p>直近の支出を表示しています</p>
            </div>
            <button @click="emit('navigate', 'transactions')">
                明細をすべて見る →
            </button>
        </div>
        <TransactionTable
            :transactions="
                [...transactions]
                    .sort((a, b) => b.date.localeCompare(a.date))
                    .slice(0, 5)
            "
        />
    </section>
</template>
