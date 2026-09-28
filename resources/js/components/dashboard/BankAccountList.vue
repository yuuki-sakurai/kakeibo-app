<script setup lang="ts">
import { computed } from "vue";
import type { BankAccount } from "../../types";
const props = defineProps<{ accounts: BankAccount[] }>();
const total = computed(() =>
    props.accounts.reduce((sum, account) => sum + account.balance, 0),
);
const yen = (amount: number) => `¥${amount.toLocaleString("ja-JP")}`;
</script>

<template>
    <section class="card panel account-panel">
        <div class="panel-head">
            <div>
                <h3>銀行口座</h3>
                <p>登録した口座の現在残高</p>
            </div>
            <div class="account-total">
                <small>口座残高合計</small><strong>{{ yen(total) }}</strong>
            </div>
        </div>
        <div class="account-list">
            <button
                v-for="account in accounts"
                :key="account.id"
                class="account-row"
            >
                <span
                    class="account-logo"
                    :style="{ backgroundColor: account.color }"
                    >{{ account.bankName.slice(0, 1) }}</span
                >
                <span class="account-name"
                    ><strong>{{ account.bankName }}</strong
                    ><small
                        >{{ account.branchName }}・{{
                            account.accountType
                        }}</small
                    ></span
                >
                <strong class="account-balance">{{
                    yen(account.balance)
                }}</strong
                ><i>›</i>
            </button>
        </div>
        <button class="account-add">＋ 銀行口座を登録</button>
    </section>
</template>
