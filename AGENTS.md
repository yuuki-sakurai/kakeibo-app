# AGENTS.md

## このリポジトリの目的

このリポジトリは、家計簿とクレカ管理SPAが共用するLaravel APIとDBマイグレーションを管理します。

- 家計簿画面は `kanntan-kakeibo`、クレカ画面は `credit-card-front` の独立リポジトリで管理する。
- このリポジトリへVue画面、Vite、Node.jsのビルド処理を戻さない。
- `app/Http/Controllers/Api` がHTTP層、`app/Http/Requests` が入力検証、`app/Services` が業務処理を担当する。
- 共通のユーザー、認証、カテゴリ、DBを使い、全データを所有ユーザーで絞り込む。
- Controllerは薄く保ち、CSV解析・取込処理はServiceで独立してテストする。

## ドメインの分離

以下の2つは別概念として扱ってください。

```text
CreditCardTransaction
```

```text
Expense
```

`CreditCardTransaction` は、カード会社等から取得・入力された元データです。

`Expense` は、家計簿として管理する支出データです。

将来的には以下のような関係を想定しています。

```text
CreditCardTransaction
        ↓ 確認・分類・取込
Expense
```

明確なプロダクト要件がない限り、これらを1つの概念へ統合しないでください。

## 移行しやすい実装のルール

### Backend を変更する場合

- ビジネスロジックを Controller や Route から分離する。
- Domain Logic に Frontend 固有の都合を持ち込まない。
- Frontend / Backend 間のURLをハードコードしない。
- API 契約を明示する。
- CSV解析、明細解析、取込処理などを独立してテスト可能な形にする。
- 共通Backendで扱いにくい特殊なDB依存を増やさない。

### Frontend を変更する場合

- Backend の内部実装詳細に依存しない。
- API通信をラップし、将来 Backend のBase URLやAPI先を差し替えやすくする。
- API型を明示する。
- エンドポイント文字列を複数Componentへ直接書かない。

## テスト方針

- 既存の家計簿APIの挙動を壊さない。
- Backendの挙動を変更した場合はテストを追加・更新する。
- Frontendは利用可能な typecheck / lint / test / build を実行する。
- コマンド実行前にリポジトリに存在する scripts を確認する。

代表例：

```bash
# frontend
npm run typecheck
npm run lint
npm run test
npm run build

# backend
php artisan test
```

実際に存在しないコマンド・scriptを推測で実行しないでください。

## リファクタリング方針

画面のリポジトリ分離は完了しています。無関係な作業のついでに追加の構成変更を行わないでください。

明示的な指示がない限り、以下は行わないでください。

- Backend全体を別リポジトリへ移動する。
- 大規模なディレクトリ移動・名称変更を行う。
- 将来設計に合わせるという理由だけで、現在のAPIパスを破壊的に変更する。

代わりに以下を意識してください。

- 新しいコードを後で切り出しやすくする。
- 関連箇所を触る場合は Frontend / Backend 間の不要な結合を減らす。
- 将来移行へ影響する設計判断を記録する。

## Git のルール

- 明示的な指示がない限り `git push` しない。
- 明示的な指示がない限り force push しない。
- コミットは意味のある単位にまとめる。
- ユーザーの無関係な変更を上書きしない。
- コミット前に `git diff` を確認する。
- Frontend と Backend の両方を変更した場合は、コミットメッセージ等で内容が分かるようにする。

## 禁止事項・注意事項

- `.env`、APIキー、パスワードなどの秘密情報をコミットしない。
- 明示的な指示なしに本番データやmigration履歴を破壊しない。
- 共通 Finance Backend が管理する概念について、理由なく別実装を増やさない。
- 明示的な指示なしにリポジトリ分離作業そのものを開始しない。

## Codex の作業手順

各タスクでは以下の流れを基本としてください。

1. まず共通APIと関連SPAの構成を確認する。
2. タスクがSPA、共通API、DBのどこに関係するか判断する。
3. 変更するファイル名を明示する。
4. 将来移行しやすい責務分離を意識する。
5. 必要最小限で一貫した変更を行う。
6. 可能であればテスト、typecheck、lint、build を実行する。
7. 最後に、変更ファイル、実行した確認内容、将来の共通Backend移行への影響を簡潔に報告する。
