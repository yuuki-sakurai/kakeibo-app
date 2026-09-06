# クレカ管理ツール

クレジットカード会社からダウンロードした利用明細CSVを取り込み、カードごとの利用額、支払予定額、支払日、引き落とし口座を確認するWebアプリケーションです。

現在は初期UIを実装しており、EPOSカードの利用を想定したデモデータを表示します。

## 使用技術

- PHP 8.3以上
- Laravel 13
- Vue 3
- TypeScript
- Vite 8
- SQLite

## 必要なソフトウェア

事前に以下をインストールしてください。

- PHP 8.3以上（SQLite拡張を含む）
- Composer 2
- Node.js 20.19以上、または22.12以上
- npm

インストール状況は次のコマンドで確認できます。

```bash
php --version
composer --version
node --version
npm --version
```

## セットアップ

### 1. リポジトリへ移動

```bash
cd /path/to/credit-card-manager
```

`/path/to/credit-card-manager` は、このリポジトリを配置したディレクトリに置き換えてください。

### 2. SQLiteデータベースを作成

```bash
touch database/database.sqlite
```

Windows PowerShellの場合は、次のコマンドを使用します。

```powershell
New-Item database/database.sqlite -ItemType File -Force
```

### 3. 初期セットアップを実行

```bash
composer setup
```

このコマンドは以下をまとめて実行します。

1. PHP依存パッケージのインストール
2. `.env` の作成
3. アプリケーションキーの生成
4. データベースマイグレーション
5. Node.js依存パッケージのインストール
6. フロントエンドの本番ビルド

## アプリケーションの起動

```bash
composer dev
```

起動後、ブラウザで次のURLを開きます。

```text
http://localhost:8000
```

`composer dev` は、Laravel開発サーバー、キューワーカー、ログ表示、Vite開発サーバーをまとめて起動します。

終了する場合は、起動したターミナルで `Ctrl+C` を押してください。

## 個別にセットアップする場合

`composer setup` を使用せず、各処理を個別に実行する場合は次の手順になります。

```bash
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate
npm install
npm run build
```

Windows PowerShellでは `cp` の代わりに次を使用してください。

```powershell
Copy-Item .env.example .env
```

開発サーバーを個別に起動する場合は、2つのターミナルを使用します。

ターミナル1:

```bash
php artisan serve
```

ターミナル2:

```bash
npm run dev
```

## テスト

Laravelのテストを実行します。

```bash
composer test
```

VueとTypeScriptの型チェックを実行します。

```bash
npx vue-tsc --noEmit
```

## フロントエンドのビルド

本番用アセットを生成します。

```bash
npm run build
```

生成されたファイルは `public/build` に出力されます。

## 主なディレクトリ

```text
app/                       Laravelのアプリケーションコード
database/migrations/       データベースマイグレーション
resources/css/             共通スタイル
resources/js/components/   再利用可能なVueコンポーネント
resources/js/data/         現在のデモ表示用データ
resources/js/types/        TypeScriptの共通型
resources/js/views/        各画面のVueコンポーネント
routes/                     Laravelのルート定義
tests/                      自動テスト
```

## トラブルシューティング

### `database/database.sqlite` が存在しない

次のコマンドを実行してから、マイグレーションを再実行します。

```bash
touch database/database.sqlite
php artisan migrate
```

### アプリケーションキーに関するエラーが表示される

```bash
php artisan key:generate
```

### フロントエンドの変更が反映されない

開発中は `npm run dev` が起動していることを確認してください。本番用アセットを確認する場合は、再度ビルドします。

```bash
npm run build
```

### キャッシュされた設定を消去したい

```bash
php artisan optimize:clear
```

## 家計簿API

共通MySQL向けの家計簿テーブルとAPI v1を追加しています。[DB設計・API仕様](docs/household-api.md)を参照してください。Docker環境では環境リポジトリのREADMEに従って起動します。
