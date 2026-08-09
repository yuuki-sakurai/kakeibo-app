<script setup lang="ts">
import type { Transaction } from "../../types";

withDefaults(
    defineProps<{ transactions: Transaction[]; detailed?: boolean }>(),
    { detailed: false },
);
const yen = (amount: number) => `¥${amount.toLocaleString("ja-JP")}`;
</script>

<template>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>利用日</th>
                    <th>利用場所</th>
                    <th v-if="detailed">カード</th>
                    <th>カテゴリ</th>
                    <th>支払予定月</th>
                    <th>金額</th>
                </tr>
            </thead>
            <tbody>
                <tr
                    v-for="transaction in transactions"
                    :key="`${transaction.date}-${transaction.merchant}`"
                >
                    <td>
                        {{
                            detailed
                                ? transaction.date
                                : transaction.date.slice(5)
                        }}
                    </td>
                    <td>
                        <strong>{{ transaction.merchant }}</strong
                        ><small v-if="detailed" class="note">1回払い</small>
                    </td>
                    <td v-if="detailed"><span class="mini-card">EPOS</span></td>
                    <td>
                        <span class="tag">{{ transaction.category }}</span>
                    </td>
                    <td>{{ transaction.paymentMonth }}</td>
                    <td class="amount">{{ yen(transaction.amount) }}</td>
                </tr>
                <tr v-if="!transactions.length">
                    <td :colspan="detailed ? 6 : 5" class="empty">
                        条件に一致する明細はありません
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>
