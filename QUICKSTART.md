# いいずな LMS - クイックスタートガイド

## 🚀 最速で開発環境を起動する

### 前提条件

- Docker Desktop がインストール済み
- 最低 8GB のメモリ
- 10GB 以上の空きディスク容量

### 手順（5 分で完了）

```bash
# 1. プロジェクトディレクトリに移動
cd /Users/phillipr.n./Documents/KUTO/いいずな/iizuna_apps_dev/iizuna-lms-main

# 2. 設定ファイルをコピー（Docker用）
cp app/config.docker.ini app/config.ini

# 3. トークン設定ファイルをコピー
cp app/token.ini.example app/token.ini
chmod 666 app/token.ini

# 4. Docker環境を起動
docker-compose up -d

# 5. ログを確認（Ctrl+C で終了）
docker-compose logs -f

# 6. DynamoDBテーブルを作成
./scripts/setup-dynamodb-local.sh
```

### アクセス URL

起動完了後、以下の URL にアクセスできます：

| サービス             | URL                   | 用途                 |
| -------------------- | --------------------- | -------------------- |
| **アプリケーション** | http://localhost:8080 | LMS メイン画面       |
| **phpMyAdmin**       | http://localhost:8081 | データベース管理     |
| **MailHog**          | http://localhost:8025 | メール確認（開発用） |

### 管理者ログイン

- **ログイン ID**: `admin`
- **パスワード**: `admin123`

---

## 📦 主な管理コマンド

```bash
# 環境起動
docker-compose up -d

# 環境停止
docker-compose down

# ログ確認
docker-compose logs -f app

# データベース確認
docker-compose logs -f mysql-iizuna

# コンテナ内に入る
docker exec -it iizuna-lms-app bash

# MySQL接続
docker exec -it iizuna-lms-db mysql -u iizunaLMS -pGawbvgt2f983mru iizunaLMS
```

---

## 🔧 トラブルシューティング

### ポート 8080 が使用中

```bash
# docker-compose.yml を編集してポート変更
ports:
  - "8090:80"  # 8080 → 8090
```

### データベースが作成されない

```bash
# コンテナを完全削除して再作成
docker-compose down -v
docker-compose up -d

# 初期化ログ確認
docker-compose logs mysql-iizuna
```

### Composer 依存関係エラー

```bash
# コンテナ内で再インストール
docker exec -it iizuna-lms-app bash
composer install --no-cache
composer dump-autoload
```

### DynamoDB テーブルが作成されない

```bash
# DynamoDB Localの起動確認
curl http://localhost:8000

# AWS CLIが必要（ローカルマシンにインストール）
brew install awscli  # macOS
# または https://aws.amazon.com/cli/

# テーブル作成スクリプトを再実行
./scripts/setup-dynamodb-local.sh
```

---

## 📚 詳細ドキュメント

より詳しい情報は以下を参照：

- **[開発環境構築ガイド](docs/開発環境構築ガイド.md)** - 完全版セットアップ手順
- **[ReadMe.md](ReadMe.md)** - 本番環境のデプロイ手順
- **[API.md](API.md)** - API 仕様書

---

## 🎯 次のステップ

### 1. データベースの確認

phpMyAdmin (http://localhost:8081) にアクセス：

- サーバー: `mysql-iizuna`
- ユーザー: `root`
- パスワード: `rootpassword`

### 2. テストデータの投入

```bash
# コンテナ内でSQLを実行
docker exec -it iizuna-lms-db mysql -u iizunaLMS -pGawbvgt2f983mru iizunaLMS

# 例: テスト用の学校を作成
INSERT INTO school (name, zip, pref, address, phone, lms_code_id, create_date, update_date)
VALUES ('テスト学校', '100-0001', '東京都', '千代田区', '03-1234-5678', 1, NOW(), NOW());
```

### 3. PHPUnit テストの実行

```bash
docker exec -it iizuna-lms-app ./vendor/phpunit/phpunit/phpunit
```

### 4. コード編集

ローカルのファイルを編集すると、Docker 内にリアルタイムで反映されます：

```bash
# 例: コントローラーの編集
code app/Controllers/StudentController.php
```

保存後、ブラウザをリロードすれば変更が反映されます。

---

## 💡 開発のヒント

### ホットリロード

PHP はインタープリタ言語なので、ファイルを保存 → ブラウザリロードで即座に反映されます。

### デバッグ

```php
// app/config.ini で設定済み
DEBUG_MODE = 1
DISPLAY_ERROR_ALL = 1

// コード内でのデバッグ出力
error_log(print_r($variable, true));
var_dump($data);
```

### ログ確認

```bash
# Apacheエラーログ
docker exec -it iizuna-lms-app tail -f /var/log/apache2/error.log

# PHPエラーログ
docker exec -it iizuna-lms-app tail -f /var/log/php_errors.log
```

### データベーステストデータリセット

```bash
# データベースを完全リセット
docker-compose down -v
docker-compose up -d

# マイグレーション再実行（自動実行される）
```

---

## 🛑 環境の完全削除

開発環境を完全に削除したい場合：

```bash
# コンテナとボリュームをすべて削除
docker-compose down -v

# Dockerイメージも削除
docker rmi iizuna-lms-main-app

# 設定ファイルを削除（必要に応じて）
rm app/config.ini
```

---

## ❓ よくある質問

### Q. 既存の AWS 開発環境とどちらを使うべき？

**A.** 用途によります：

- **AWS 開発環境（spapp-dev-ec2）**: 本番と同じ環境でテストしたい、Firebase 等の外部サービスをそのまま使いたい
- **ローカル Docker 環境**: オフラインで開発したい、自由に環境を壊して試したい

### Q. 本番環境のデータベースをローカルに持ってこれる？

**A.** 可能です：

```bash
# 本番DBからダンプを取得（本番サーバーで実行）
mysqldump -u iizunaLMS -p iizunaLMS > backup.sql

# ローカルにコピー
scp spapp-prod-bastion:/path/to/backup.sql ./

# ローカルDocker環境にインポート
docker exec -i iizuna-lms-db mysql -u iizunaLMS -pGawbvgt2f983mru iizunaLMS < backup.sql
```

### Q. xserver で動かせる？

**A.** 部分的に可能ですが、推奨しません：

- ❌ DynamoDB 非対応
- ❌ Cron 制約（最短 5 分）
- ✅ PHP/MySQL は動作

完全な開発環境としては、ローカル Docker 環境を推奨します。

---

困った時は [docs/開発環境構築ガイド.md](docs/開発環境構築ガイド.md) を参照してください。
