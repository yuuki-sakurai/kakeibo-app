# クレカAPI v1

共通プレフィックス `/api/v1`。全エンドポイントで既存のセッション認証が必要です。書き込みには `auth/session` から取得した `csrfToken` を `X-CSRF-TOKEN` ヘッダーで送ります。未ログイン401、他ユーザーの更新対象404、不正入力422、CSRF切れ419。応答はprivate/no-storeです。

## データモデル

| テーブル | 用途 |
| --- | --- |
| bank_accounts | ユーザー所有の口座名・支店・種別・手動残高・表示色 |
| credit_cards | カード名、締め日、支払日、支払月、所有口座への参照 |
| statement_imports | ユーザー・カード・ファイルSHA-256、ファイル名、登録件数 |
| credit_card_transactions | 利用日、利用場所、共通カテゴリ、整数円、支払予定月、元取込への参照 |

全テーブルにusersへの外部キーがあります。カテゴリは既存expense_categoriesの共通初期カテゴリ／自分のカテゴリのみ参照できます。CreditCardTransactionとExpenseは別の概念です。家計簿への自動転記、残高の自動引落し、銀行同期は行いません。カード番号・口座番号は保存しません。

## エンドポイント

| HTTP | パス | 内容 |
| --- | --- | --- |
| GET / POST | bank-accounts | 口座一覧／作成 |
| PUT | bank-accounts/{id} | 口座更新 |
| GET / POST | credit-cards | カード一覧／作成 |
| PUT | credit-cards/{id} | カード更新 |
| GET | credit-card-transactions | 利用明細。month=YYYY-MM必須。date=YYYY-MM-DD、credit_card_id、search（利用場所の部分一致）、pageで絞り込み |
| GET | credit-card-overview | month=YYYY-MM必須。月合計・件数・日別／カテゴリ別集計・当月から3か月分の支払予定 |
| GET | statement-imports | 取込履歴。page指定可能 |
| POST | statement-imports | multipart: credit_card_id と file |

口座入力: `bank_name`（100文字）、`branch_name`（任意・100文字）、`account_type`（普通/当座/貯蓄）、`balance`（整数円±999999999999）、`color`（#RRGGBB）。

カード入力: `name`（100文字）、`closing_day`と`payment_day`（1〜31、31=末日）、`payment_month_offset`（0=当月/1=翌月/2=翌々月）、`bank_account_id`（自分の口座IDまたはnull）。締め日・支払月設定は管理情報で、CSVの支払予定月は上書きしません。支払日は存在しない日を月末に丸めて画面表示し、銀行休業日の補正はしません。

一覧応答は口座・カードが配列、明細・履歴が `{data, total, page, per_page: 50}` です。集計は一覧ページ分割による上限の影響を受けません。金額は整数円（DB集計が文字列として返る場合もあるため画面では数値化します）。時刻はUTCです。

## CSV

現時点は共通テンプレートのみ。カード会社固有のCSVフォーマット（EPOS等）の自動解析は未対応です。

```csv
利用日,利用場所,カテゴリ,金額,支払予定月
2026-10-01,サンプル店舗,,1200,2026-11
2026-10-02,返金,,-200,2026-11
```

負数のマイナスは半角 `-` を使います。テンプレートはSPAの `/templates/credit-card.csv` から取得できます。

- UTF-8（BOM可）／Shift_JIS、2MB・5,000明細まで。
- ヘッダーと列順は上記に一致させます。利用日YYYY-MM-DD、支払予定月YYYY-MM（1900〜9998年）。
- 利用場所は1〜200文字、金額は9桁以内の整数円。カンマ区切り・通貨記号・小数は不可。返金は負数。
- カテゴリは登録済みの名前、または空欄（未分類）。未登録カテゴリは行番号を返して取込を中止します。
- 全行検証後、履歴と明細を1トランザクションで保存します。エラーなら1件も保存しません。
- 同一ユーザー・カード・ファイル内容の再送は200と `alreadyImported: true`、新規は201と `alreadyImported: false`。同時再送もDBのunique制約で保護します。
- 重複判定は元ファイル全体のSHA-256です。別形式・改変ファイル・期間が重なる別CSV内の重複は検出しません。事前に重複行を取り除いてください。
- CSV原本は保存せず、ファイル名・ハッシュ・解析済み明細のみ保持します。

削除、手入力のカード明細、明細編集、カード会社固有形式、家計簿への転記はこの分離の対象外です。
