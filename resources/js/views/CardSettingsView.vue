<script setup lang="ts">
import PageHeader from "../components/layout/PageHeader.vue";
</script>

<template>
    <PageHeader
        eyebrow="CARD SETTINGS"
        title="カード設定"
        description="締め日・支払日・引き落とし口座を管理します。"
        action-label="＋ カードを追加"
    />
    <section class="settings">
        <article class="card card-setting">
            <div class="setting-top">
                <span class="large-card">EPOS</span
                ><em class="success">● 利用中</em><i>•••</i>
            </div>
            <h2>EPOSカード</h2>
            <p class="muted">CSV形式: epos</p>
            <dl>
                <div>
                    <dt>締め日</dt>
                    <dd>毎月末日</dd>
                </div>
                <div>
                    <dt>支払日</dt>
                    <dd>翌月27日</dd>
                </div>
                <div>
                    <dt>引き落とし口座</dt>
                    <dd>三菱UFJ銀行</dd>
                </div>
            </dl>
            <button class="outline">設定を編集</button>
        </article>
        <button class="add-card">
            <b>＋</b><strong>カードを追加</strong
            ><small>新しいカード情報を登録</small>
        </button>
    </section>
    <div class="info">
        <b>i</b>
        <p>
            <strong>現在対応しているCSV形式</strong
            ><br />初期リリースではEPOSカードのショッピング利用・1回払いに対応しています。
        </p>
    </div>
</template>
