# iizunaLMS セットアップ・運用ガイド

## 初期セットアップ

### 1. 設定ファイルの準備

```bash
# 設定ファイルをコピー
cp -p app/config.ini.example app/config.ini
```

`app/config.ini` のパラメータを環境に合わせて設定してください。

### 2. Firebase 認証設定

`firebase_auth.json` を設置してください。  
参考: [Firebase Cloud Messaging v1 への移行](https://firebase.google.com/docs/cloud-messaging/migrate-v1?hl=ja#provide-credentials-manually)

### 3. バッチ用トークン設定

```bash
# トークンファイルをコピー
cp -p app/token.ini.example app/token.ini
chmod 666 app/token.ini
```

https://xxxx.xx/app/register_admin_token.php にアクセスし、管理者権限でログインして AdminToken が正常にセットされることを確認してください。

### 4. Crontab 設定

```bash
# 以下をcrontabに追加（フルパスで設定）
* * * * * php /path/to/app/Commands/summary_and_correct_answer_rate.php
```

### 5. 依存関係のインストール

```bash
composer install
```

### 6. テスト実行

```bash
./vendor/phpunit/phpunit/phpunit
```

## データベース情報

| 環境 | RDS 接続ドメイン               | 接続ユーザー | データベース名 |
| ---- | ------------------------------ | ------------ | -------------- |
| 開発 | db-dev.spapp-db.localdomain    | iizunaLMS    | iizunaLMS      |
| 開発 | db-dev.onigiri-db.localdomain  | onigiri      | onigiri        |
| 本番 | db-prod.spapp-db.localdomain   | iizunaLMS    | iizunaLMS      |
| 本番 | db-prod.onigiri-db.localdomain | onigiri      | onigiri        |

## サーバー構成

### iizunaLMS 用サーバー

| インスタンス名               | 用途                     | 備考                    |
| ---------------------------- | ------------------------ | ----------------------- |
| spapp-dev-ec2                | 開発サーバー             |                         |
| spapp-prod-ec2-ami-base-2503 | AMI 作成用ベースサーバー | 現在は直接アクセス可能  |
| spapp-prod-ec2-asg           | 本番サーバー             | Auto Scaling で自動作成 |
| spapp-prod-ec2-bastion       | 踏み台・バッチサーバー   | 毎分計算処理も実行      |
| spapp-prod-ec2-registration  | 先生登録申請用サイト     |                         |

### e-ONIGIRI 英単語用サーバー

| インスタンス名            | 用途                     | 備考                         |
| ------------------------- | ------------------------ | ---------------------------- |
| onigiri-dev-ec2           | 開発サーバー             |                              |
| onigiri-prod-ec2-ami-base | AMI 作成用ベースサーバー | 踏み台サーバー経由でアクセス |
| onigiri-prod-ec2-asg      | 本番サーバー             | Auto Scaling で自動作成      |
| onigiri-prod-ec2-bastion  | 踏み台サーバー           |                              |

### SSH 接続設定例

以下を `~/.ssh/config` に追加してください：

```ini
Host spapp-dev
  Hostname 15.152.199.165
  User ec2-user
  IdentityFile ~/.ssh/spapp-dev-keypair.pem

Host spapp-prod-bastion
  Hostname 15.168.23.66
  User ec2-user
  IdentityFile ~/.ssh/spapp-prod-keypair.pem

Host spapp-prod-ami-base-2503
  Hostname 15.168.245.205
  User ec2-user
  IdentityFile ~/.ssh/spapp-prod-keypair.pem

Host onigiri-dev
  Hostname 15.152.67.248
  User ec2-user
  IdentityFile ~/.ssh/onigiri-dev-keypair.pem

Host onigiri-prod-ec2-ami-base
  Hostname 10.0.10.156
  User ec2-user
  IdentityFile ~/.ssh/onigiri-prod-keypair.pem
  ProxyCommand ssh onigiri-prod-bastion -W %h:%p
```

## デプロイ手順

### 本番環境デプロイプロセス

#### 事前準備

1. **リリースブランチのプッシュ**
   - ブランチ名: `release/yymmdd` 形式でリリースブランチを作成・プッシュ

#### AMI 作成・更新手順

1. **更新用インスタンスにログイン**

   - `spapp-prod-ec2-ami-base-2503` インスタンスに SSH でログイン

2. **アプリケーションの更新**

   ```bash
   cd /var/www/iizuna_lms/
   git fetch
   git checkout release/yymmdd  # 該当のリリースブランチを指定
   ```

3. **AMI の作成**

   - EC2 コンソールで `spapp-prod-ec2-ami-base-2503` を選択
   - 「イメージを作成」を実行
   - **AMI 名**: `spapp-prod-ami-yyyymmddhhmmss` （年月日時分秒）
   - **タグ設定**:
     - `Project`: `SpApp`
     - `Name`: AMI 名と同じ
     - `Environment`: `Prod`
   - AMI 作成完了まで待機

4. **起動テンプレートの更新**

   - 起動テンプレート `spapp-prod-launch-template-202303` の詳細を表示
   - バージョンの詳細のアクションから「テンプレートを変更（新しいバージョンを作成）」を選択
   - 「起動テンプレートのコンテンツ」で「自分の AMI」を選択し、手順 3 で作成した AMI を指定
   - 新しいテンプレートバージョンを作成

5. **デフォルトバージョンの設定**

   - 起動テンプレートのバージョン詳細のアクションから「デフォルトバージョンを設定」を選択
   - 手順 4 で作成したバージョンをデフォルトに設定

6. **Auto Scaling グループでのインスタンス更新**
   - Auto Scaling グループのコンソールを開く
   - `spapp-prod-asg` の詳細を表示
   - 「インスタンスの更新」タブを開く
   - 「インスタンスの更新を開始する」を実行

> **重要:** このシステムでは Auto Scaling グループが起動テンプレートの「デフォルトバージョン」を参照する設定になっています。そのため、手順 5 でデフォルトバージョンを変更すると、Auto Scaling グループは自動的に新しい AMI を使用するようになります。これにより、インスタンス更新時に新しいバージョンのアプリケーションが反映されます。

## 先生申請用サイト更新手順

1. Elastic IP を作成し、`spapp-prod-registration` に関連付け
   > Public IP が設定されていないと外部接続ができないため必須
2. `spapp-prod-registration` に ssh でログインして更新実行
   ```bash
   cd /var/www/iizuna_lms/
   git pull --rebase
   ```
3. 不要になった Elastic IP を開放

## 音声ファイルアップロード

### 開発環境

```bash
scp -i ~/.ssh/spapp-dev-keypair.pem ./20230210.zip spapp-dev:/var/www/iizuna_lms/app/Assets/Sounds/.
```

### 本番環境

```bash
scp -i ~/.ssh/spapp-prod-keypair.pem ./20230210.zip spapp-prod-develop:/var/www/iizuna_lms/app/Assets/Sounds/.
```
