# Railway 本番用バックエンド

Laravel 13 / PHP 8.4 FPM / nginx / MySQL を想定しています。
composer.lock に PHP >=8.4.1 の依存関係があるため PHP 8.4 を使用します。
Node.js・Vite は実行せず、nginx は `/api/*` と `/up` のみ Laravel に渡します。
`/` は 404 が正常です。既存のフロントコードやローカル開発設定は変更しません。

## Railway の設定

1. この変更を GitHub にプッシュ後、Railway のアプリサービスにリポジトリを接続します。
2. Root Directory はリポジトリルート、Dockerfile は `Dockerfile` を使用します。
3. Start Command は空欄にします（Docker ENTRYPOINT が起動します）。
4. Variables に下記を設定します。
5. Pre-deploy Command に `php artisan migrate --force` を設定します。起動スクリプト内では migration や seeder を実行しません。
6. Healthcheck Path を `/up` に設定します。
7. Public Networking でドメインを発行し、その HTTPS URL を APP_URL に設定します。

nginx は Railway の PORT に追従します。未指定時は 8080 です。
Healthcheck は Laravel/PHP の起動確認であり、DB接続やテーブルの検証は行いません。

## Variables の例

`MySQL` は実際の Railway DB サービス名に合わせてください。
FRONTEND_ORIGINS は末尾スラッシュなしの正確なオリジンを指定します。

```dotenv
APP_NAME=Kakeibo
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:生成した固定キー
APP_URL=https://api.example.com
LOG_CHANNEL=stderr
LOG_LEVEL=info
DB_CONNECTION=mysql
DB_HOST=${{MySQL.MYSQLHOST}}
DB_PORT=${{MySQL.MYSQLPORT}}
DB_DATABASE=${{MySQL.MYSQLDATABASE}}
DB_USERNAME=${{MySQL.MYSQLUSER}}
DB_PASSWORD=${{MySQL.MYSQLPASSWORD}}
SESSION_DRIVER=database
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=lax
CACHE_STORE=database
QUEUE_CONNECTION=sync
FRONTEND_ORIGINS=https://app.example.com
```

APP_KEY はローカルの Laravel 環境で `php artisan key:generate --show` を実行し、表示された値を一度だけ登録します。再デプロイごとに生成し直さないでください。
SESSION_DOMAIN は通常未設定（APIホスト限定）で構いません。
現在のAPIはセッションCookie認証です。同じ独自ドメイン配下の `app.example.com` と `api.example.com` を推奨します。
Pages のドメインと Railway のドメインなど別サイト間では `SESSION_SAME_SITE=none` が必要ですが、ブラウザの第三者Cookie制限で動かない場合があります。
フロントのAPI通信には credentials の送信設定と既存APIのCSRFトークン対応が必要です。

QUEUE_CONNECTION=sync はジョブをリクエスト内で実行します。非同期処理が必要になったら別 worker サービスを用意してください。
コンテナ内のアップロードファイルは再デプロイで失われるため、永続ファイル保存が必要になったら S3 等を設定してください。
この構成は公開ストレージのURL配信を行いません。

## ローカルで本番イメージを検証する

```bash
docker build -t kakeibo-api:railway .
docker run --rm --env-file .env.production kakeibo-api:railway php artisan migrate --force
docker run --rm --env-file .env.production -p 8080:8080 kakeibo-api:railway
curl --fail http://localhost:8080/up
```

.env.production は手元で用意し、コミットしません。ローカルから接続可能なDBを設定してください。
APIの `GET /api/v1/auth/session` でもDB・セッションを含む疎通を確認してください。

## 運用

- nginx/PHP のログは標準出力・標準エラーから Railway で確認できます。
- 起動時に設定・ルートキャッシュを作成します。ビルド時の APP_KEY や DB 接続は不要です。
- nginx または PHP-FPM が終了するとコンテナ全体を終了させます。Railway の再起動ポリシーも設定してください。
- PHP-FPM はリクエスト時に worker を起動し、最大5プロセスです。負荷・メモリ使用量に応じて調整してください。
- 共通Backendへの移行に影響するアプリケーション/APIの変更はありません。

公式ドキュメント: https://docs.railway.com/builds/dockerfiles / https://docs.railway.com/deployments/healthchecks
