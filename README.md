# kakeibo-app — 共通Laravel API

家計簿SPA (`kanntan-kakeibo`) とクレカ管理SPA (`credit-card-front`) が共用するAPIです。画面は各フロントエンドでビルド・配信し、このリポジトリは認証、業務処理、DBマイグレーションを管理します。Vue/Vite/Node.jsの依存はありません。

## 開発・起動

推奨は `household-env` のDocker Composeです。PHP 8.4、Composer 2、共通MySQL 8.4を使います。詳細は環境リポジトリのREADMEを参照してください。

```bash
# household-env ディレクトリで実行
docker compose up -d --build
docker compose exec kakeibo-app php artisan migrate --force
```

ホットリロード構成では `docker compose -f compose.dev.yml exec kakeibo-app composer install` で依存を導入し、`php artisan migrate` を実行してください。Laravelのソースはbind mountで反映します。

単体開発は `composer install`、`.env.example` を `.env` にコピー、DB接続と新規環境のAPP_KEYを設定、`php artisan migrate`、`composer dev` の順です。既存APP_KEYは再生成しないでください。

- `/` : APIサービス情報
- `/up` : ヘルスチェック
- `/api/v1/*` : JSON API

## APIと認証

- [家計簿API](docs/household-api.md)
- [クレカAPI・CSV形式](docs/credit-card-api.md)

両SPAは同じusersテーブルとメール・パスワード認証を使います。フロントの同一オリジン `/api/` をLaravelへプロキシし、セッションCookieとCSRFトークンで認証します。DBは共用し、ユーザーごとにアクセスを制限します。別ドメイン間での自動SSOは実装していません。

## テスト

```bash
composer test
vendor/bin/pint --test
```

PHPUnitはSQLiteインメモリDBを使います（pdo_sqliteが必要）。実DBで `migrate:fresh` は実行しないでください。

## Railway

このリポジトリのDockerfileでAPIをデプロイし、既存APP_KEYとDB環境変数を維持します。新しいマイグレーションはデプロイ環境から `php artisan migrate --force` を実行してください。クレカ用テーブルの追加であり、既存の家計簿データは移動・削除しません。画面のDockerfileは各SPAリポジトリにあります。
