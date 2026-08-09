<script setup lang="ts">
import { computed, ref } from "vue";
type Page = "home" | "transactions" | "imports" | "cards";
const page = ref<Page>("home");
const uploadOpen = ref(false);
const file = ref<File>();
const search = ref("");
const month = ref("2026-08");
const toast = ref("");
const nav: { key: Page; label: string; icon: string }[] = [
    { key: "home", label: "ホーム", icon: "⌂" },
    { key: "transactions", label: "利用明細", icon: "≡" },
    { key: "imports", label: "取込履歴", icon: "↥" },
    { key: "cards", label: "カード設定", icon: "▣" },
];
const rows = [
    ["2026/08/08", "Amazon.co.jp", "ショッピング", 4820, "2026年9月"],
    ["2026/08/06", "スターバックス コーヒー", "飲食", 680, "2026年9月"],
    ["2026/08/03", "JR東日本 モバイルSuica", "交通", 3000, "2026年9月"],
    ["2026/08/01", "東京電力 電気料金", "光熱費", 8740, "2026年9月"],
    ["2026/07/29", "無印良品", "ショッピング", 6290, "2026年8月"],
    ["2026/07/24", "Netflix.com", "サブスク", 1490, "2026年8月"],
] as const;
const filtered = computed(() =>
    rows.filter(
        (r) =>
            (!month.value || r[0].startsWith(month.value.replace("-", "/"))) &&
            r[1].toLowerCase().includes(search.value.toLowerCase()),
    ),
);
const yen = (n: number) => `¥${n.toLocaleString("ja-JP")}`;
function pick(e: Event) {
    file.value = (e.target as HTMLInputElement).files?.[0];
}
function importCsv() {
    if (!file.value) return;
    toast.value = `${file.value.name} を取り込みました`;
    uploadOpen.value = false;
    file.value = undefined;
    setTimeout(() => (toast.value = ""), 3000);
}
</script>
<template>
    <div class="shell">
        <aside>
            <button class="brand" @click="page = 'home'">
                <b>¥</b><strong>クレカ管理</strong>
            </button>
            <nav>
                <button
                    v-for="n in nav"
                    :key="n.key"
                    :class="{ active: page === n.key }"
                    @click="page = n.key"
                >
                    <i>{{ n.icon }}</i
                    >{{ n.label }}
                </button>
            </nav>
            <div class="profile">
                <span>G</span>
                <p>
                    <strong>ゲストユーザー</strong
                    ><small>データは一時保存です</small>
                </p>
            </div>
        </aside>
        <main>
            <header>
                <span class="mobile-brand">¥　クレカ管理</span
                ><button class="primary top" @click="uploadOpen = true">
                    ＋ CSVを取り込む
                </button>
            </header>
            <div class="content">
                <template v-if="page === 'home'"
                    ><section class="heading">
                        <div>
                            <small>2026年8月9日 日曜日</small>
                            <h1>おはようございます</h1>
                            <p>今月のカード利用状況を確認しましょう。</p>
                        </div>
                        <button class="primary" @click="uploadOpen = true">
                            ＋ CSVを取り込む
                        </button>
                    </section>
                    <section class="summaries">
                        <article class="card summary">
                            <div class="title">
                                <i>¥</i><strong>今月の支払予定</strong
                                ><em>あと18日</em>
                            </div>
                            <h2>¥42,300</h2>
                            <p class="muted">8月27日（木）　·　EPOSカード</p>
                            <div class="bank">
                                <i>▥</i>
                                <p>
                                    <small>引き落とし口座</small
                                    ><strong>三菱UFJ銀行</strong>
                                </p>
                            </div>
                            <div class="reminder">
                                <b>✓</b
                                ><span
                                    ><strong>8月26日までに</strong
                                    ><br />三菱UFJ銀行へ
                                    <em>¥42,300</em> 用意してください</span
                                >
                            </div>
                        </article>
                        <article class="card summary">
                            <div class="title">
                                <i class="blue">↗</i
                                ><strong>今月の利用額</strong>
                            </div>
                            <h2>¥17,240</h2>
                            <p class="change">
                                <b>↘ 12.4%</b> 先月の同じ期間と比較
                            </p>
                            <div class="limit">
                                <span>設定した目安</span
                                ><b>¥17,240 / ¥50,000</b>
                            </div>
                            <div class="bar"><i></i></div>
                            <p class="muted">
                                あと <b>¥32,760</b> 利用できます
                            </p>
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
                            v-for="(m, i) in ['8月', '9月']"
                            :key="m"
                            class="payment"
                        >
                            <b
                                >27<small>{{ m }}</small></b
                            ><span class="epos">EPOS</span>
                            <p>
                                <strong>EPOSカード</strong
                                ><small>三菱UFJ銀行</small>
                            </p>
                            <div>
                                <strong>{{ i ? "¥17,240" : "¥42,300" }}</strong
                                ><small :class="i ? 'forecast' : 'ok'"
                                    >● {{ i ? "見込み" : "確定" }}</small
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
                            <button @click="page = 'transactions'">
                                明細をすべて見る →
                            </button>
                        </div>
                        <div class="table-wrap">
                            <table>
                                <thead>
                                    <tr>
                                        <th>利用日</th>
                                        <th>利用場所</th>
                                        <th>カテゴリ</th>
                                        <th>金額</th>
                                        <th>支払予定月</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr
                                        v-for="r in rows.slice(0, 4)"
                                        :key="r[1]"
                                    >
                                        <td>{{ r[0].slice(5) }}</td>
                                        <td>
                                            <strong>{{ r[1] }}</strong>
                                        </td>
                                        <td>
                                            <span class="tag">{{ r[2] }}</span>
                                        </td>
                                        <td class="amount">{{ yen(r[3]) }}</td>
                                        <td>{{ r[4] }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </section></template
                >
                <template v-else-if="page === 'transactions'"
                    ><section class="heading">
                        <div>
                            <small class="eyebrow">TRANSACTIONS</small>
                            <h1>利用明細</h1>
                            <p>取り込んだカード利用を検索・確認できます。</p>
                        </div>
                        <button class="primary" @click="uploadOpen = true">
                            ＋ CSVを取り込む
                        </button>
                    </section>
                    <section class="card filters">
                        <label class="search"
                            >⌕
                            <input
                                v-model="search"
                                placeholder="利用場所を検索" /></label
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
                            <strong class="total"
                                >合計
                                {{
                                    yen(filtered.reduce((s, r) => s + r[3], 0))
                                }}</strong
                            >
                        </div>
                        <div class="table-wrap">
                            <table>
                                <thead>
                                    <tr>
                                        <th>利用日</th>
                                        <th>利用場所</th>
                                        <th>カード</th>
                                        <th>カテゴリ</th>
                                        <th>支払予定月</th>
                                        <th>金額</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="r in filtered" :key="r[1]">
                                        <td>{{ r[0] }}</td>
                                        <td>
                                            <strong>{{ r[1] }}</strong
                                            ><small class="note">1回払い</small>
                                        </td>
                                        <td>
                                            <span class="mini-card">EPOS</span>
                                        </td>
                                        <td>
                                            <span class="tag">{{ r[2] }}</span>
                                        </td>
                                        <td>{{ r[4] }}</td>
                                        <td class="amount">{{ yen(r[3]) }}</td>
                                    </tr>
                                    <tr v-if="!filtered.length">
                                        <td colspan="6" class="empty">
                                            条件に一致する明細はありません
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </section></template
                >
                <template v-else-if="page === 'imports'"
                    ><section class="heading">
                        <div>
                            <small class="eyebrow">IMPORT HISTORY</small>
                            <h1>取込履歴</h1>
                            <p>CSVの取込結果とエラーを確認できます。</p>
                        </div>
                        <button class="primary" @click="uploadOpen = true">
                            ＋ CSVを取り込む
                        </button>
                    </section>
                    <section class="card panel history">
                        <div
                            v-for="(x, i) in [
                                [
                                    'epos_202608.csv',
                                    '2026/08/09 08:42',
                                    '12',
                                    '0',
                                ],
                                [
                                    'epos_202607.csv',
                                    '2026/07/31 21:18',
                                    '24',
                                    '2',
                                ],
                                [
                                    'epos_202606.csv',
                                    '2026/06/30 19:06',
                                    '18',
                                    '0',
                                ],
                            ]"
                            :key="x[0]"
                            class="history-row"
                        >
                            <b :class="i === 1 ? 'warn-icon' : 'ok-icon'">{{
                                i === 1 ? "!" : "✓"
                            }}</b>
                            <p>
                                <strong>{{ x[0] }}</strong
                                ><small>{{ x[1] }} ・ EPOSカード</small>
                            </p>
                            <div>
                                <b>{{ x[2] }}件</b><small>取込</small>
                            </div>
                            <div>
                                <b>{{ x[3] }}件</b><small>除外</small>
                            </div>
                            <em :class="i === 1 ? 'warning' : 'success'">{{
                                i === 1 ? "一部警告" : "成功"
                            }}</em>
                        </div>
                    </section></template
                >
                <template v-else
                    ><section class="heading">
                        <div>
                            <small class="eyebrow">CARD SETTINGS</small>
                            <h1>カード設定</h1>
                            <p>締め日・支払日・引き落とし口座を管理します。</p>
                        </div>
                        <button class="primary">＋ カードを追加</button>
                    </section>
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
                    </div></template
                >
            </div>
        </main>
        <div
            v-if="uploadOpen"
            class="backdrop"
            @click.self="uploadOpen = false"
        >
            <section class="modal">
                <div class="modal-head">
                    <div>
                        <small>CSV IMPORT</small>
                        <h2>利用明細を取り込む</h2>
                    </div>
                    <button @click="uploadOpen = false">×</button>
                </div>
                <label class="field"
                    >カードを選択<select>
                        <option>EPOSカード</option>
                    </select></label
                ><label class="drop"
                    ><input type="file" accept=".csv" @change="pick" /><b>↥</b
                    ><template v-if="file"
                        ><strong>{{ file.name }}</strong
                        ><small
                            >{{ (file.size / 1024).toFixed(1) }} KB</small
                        ></template
                    ><template v-else
                        ><strong>CSVファイルを選択</strong
                        ><span
                            >EPOSカードからダウンロードしたCSV</span
                        ></template
                    ></label
                >
                <div class="modal-note">
                    ⓘ
                    ショッピング利用・1回払いのみ取り込みます。分割・リボ・キャッシングは除外されます。
                </div>
                <div class="modal-actions">
                    <button class="outline" @click="uploadOpen = false">
                        キャンセル</button
                    ><button
                        class="primary"
                        :disabled="!file"
                        @click="importCsv"
                    >
                        取り込む
                    </button>
                </div>
            </section>
        </div>
        <div v-if="toast" class="toast">✓　{{ toast }}</div>
    </div>
</template>
