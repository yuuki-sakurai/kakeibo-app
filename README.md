# kakeibo-app — 共通Laravel API

家計簿SPA (`kanntan-kakeibo`) とクレカ管理SPA (`credit-card-front`) が共用するAPIです。画面は各フロントエンドでビルド・配信し、このリポジトリは認証、業務処理、DBマイグレーションを管理します。Vue/Vite/Node.jsの依存はありません。

## 開発・起動

推奨は `household-env` のDocker Composeです。Laravel 13（composer.jsonは `^13.8`）、PHP 8.4、Composer 2、共通MySQL 8.4を使います。詳細は [環境リポジトリのREADME](https://github.com/yuuki-sakurai/household-env/blob/develop/README.md) を参照してください。

```bash
# household-env ディレクトリで実行
docker compose up -d --build
docker compose exec kakeibo-app php artisan migrate --force
```

標準構成はソースをbind mountしないため、変更後はAPIイメージの再ビルドが必要です。開発時は次の構成へ切り替えます。Laravelのソースはbind mountで反映され、配信用に `kakeibo-web` が加わります。

```bash
# household-env ディレクトリで実行
docker compose -f compose.dev.yml up -d --build --remove-orphans
docker compose -f compose.dev.yml exec kakeibo-app composer install
docker compose -f compose.dev.yml exec kakeibo-app php artisan migrate

# 標準構成へ戻す
docker compose up -d --build --remove-orphans
```

両構成は同じDBボリュームを使用します。既存DBを初期化せず、マイグレーションは必要な変更がある場合に適用してください。

単体開発は `composer install`、`.env.example` を `.env` にコピー、DB接続と新規環境のAPP_KEYを設定、`php artisan migrate`、`composer dev` の順です。既存APP_KEYは再生成しないでください。

- `/` : 開発構成・`composer dev` ではAPIサービス情報。標準Dockerイメージのnginx経由では404
- `/up` : ヘルスチェック
- `/api/v1/*` : JSON API

## APIと認証

- [家計簿API](docs/household-api.md)
- [クレカAPI・CSV形式](docs/credit-card-api.md)

両SPAは同じusersテーブルとメール・パスワード認証を使います。フロントの同一オリジン `/api/` をLaravelへプロキシし、セッションCookieとCSRFトークンで認証します。DBは共用し、ユーザーごとにアクセスを制限します。別ドメイン間での自動SSOは実装していません。

## テスト

開発用依存を導入した環境で実行します。標準Dockerイメージは `composer install --no-dev` のため、テストには開発構成を使います。

```bash
# household-env ディレクトリで、開発構成が起動中の場合
docker compose -f compose.dev.yml exec kakeibo-app composer test
docker compose -f compose.dev.yml exec kakeibo-app vendor/bin/pint --test
```

単体の開発環境では、このリポジトリで `composer test` と `vendor/bin/pint --test` を実行します。

PHPUnitはSQLiteインメモリDBを使います（pdo_sqliteが必要）。実DBで `migrate:fresh` は実行しないでください。

## Railway

このリポジトリのDockerfileでAPIをデプロイし、既存APP_KEYとDB環境変数を維持します。新しいマイグレーションはデプロイ環境から `php artisan migrate --force` を実行してください。クレカ用テーブルの追加であり、既存の家計簿データは移動・削除しません。画面のDockerfileは各SPAリポジトリにあります。
