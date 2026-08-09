<script setup lang="ts">
import TransactionTable from "../components/transactions/TransactionTable.vue";
import { transactions } from "../data/demo";
import type { Page } from "../types";
const emit = defineEmits<{ upload: []; navigate: [page: Page] }>();
</script>

<template>
    <section class="heading">
        <div>
            <small>2026年8月9日 日曜日</small>
            <h1>おはようございます</h1>
            <p>今月のカード利用状況を確認しましょう。</p>
        </div>
        <button class="primary" @click="emit('upload')">
            ＋ CSVを取り込む
        </button>
    </section>
    <section class="summaries">
        <article class="card summary">
            <div class="title">
                <i>¥</i><strong>今月の支払予定</strong><em>あと18日</em>
            </div>
            <h2>¥42,300</h2>
            <p class="muted">8月27日（木）　·　EPOSカード</p>
            <div class="bank">
                <i>▥</i>
                <p><small>引き落とし口座</small><strong>三菱UFJ銀行</strong></p>
            </div>
            <div class="reminder">
                <b>✓</b
                ><span
                    ><strong>8月26日までに</strong><br />三菱UFJ銀行へ
                    <em>¥42,300</em> 用意してください</span
                >
            </div>
        </article>
        <article class="card summary">
            <div class="title">
                <i class="blue">↗</i><strong>今月の利用額</strong>
            </div>
            <h2>¥17,240</h2>
            <p class="change"><b>↘ 12.4%</b> 先月の同じ期間と比較</p>
            <div class="limit">
                <span>設定した目安</span><b>¥17,240 / ¥50,000</b>
            </div>
            <div class="bar"><i></i></div>
            <p class="muted">あと <b>¥32,760</b> 利用できます</p>
        </article>
    </section>
    <section class="card panel">
        <div class="panel-head">
            <div>
                <h3>支払予定</h3>
                <p>今後の引き落とし予定です</p>
            </div>
            <button>すべて見る →</button>
        </div>
        <div
            v-for="(payment, index) in [
                { month: '8月', amount: '¥42,300' },
                { month: '9月', amount: '¥17,240' },
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
                <p>直近のカード利用を表示しています</p>
            </div>
            <button @click="emit('navigate', 'transactions')">
                明細をすべて見る →
            </button>
        </div>
        <TransactionTable :transactions="transactions.slice(0, 4)" />
    </section>
</template>
