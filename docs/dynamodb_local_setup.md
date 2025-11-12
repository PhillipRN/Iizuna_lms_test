# DynamoDB Local セットアップ手順

teacher 画面は MySQL のみで動作しますが、student 画面は DynamoDB を利用したトークン管理が必須です。  
このドキュメントでは、ローカル開発環境（`docs/開発ワークフローガイド.md` 準拠の Docker 構成）で DynamoDB Local を初期化する具体的な手順を説明します。

---

## 1. 前提条件

- macOS / Linux（Windows の場合は適宜読み替えてください）
- Docker / docker-compose が起動済み（`make up` でコンテナ立ち上げ済み）
- プロジェクトルート: `/Users/phillipr.n./Documents/KUTO/いいずな/iizuna_apps_dev/iizuna-lms-main`

## 2. AWS CLI のインストール

1. [AWS CLI v2 公式手順](https://aws.amazon.com/cli/)に従いインストールします。
   - macOS (Intel / Apple Silicon):
     ```bash
     curl "https://awscli.amazonaws.com/AWSCLIV2.pkg" -o "AWSCLIV2.pkg"
     sudo installer -pkg AWSCLIV2.pkg -target /
     aws --version   # バージョン確認
     ```
   - Linux:
     ```bash
     curl "https://awscli.amazonaws.com/awscli-exe-linux-x86_64.zip" -o "awscliv2.zip"
     unzip awscliv2.zip
     sudo ./aws/install
     aws --version
     ```
2. 既にインストール済みの場合は、`aws --version` で確認して次に進みます。

> **補足**  
> ローカル開発では AWS アカウントの認証情報は不要です。`scripts/setup-dynamodb-local.sh` では `--endpoint-url http://localhost:8000` を指定しているため、DynamoDB Local だけに対して操作が行われます。

## 3. DynamoDB Local テーブルの作成

1. コンテナが起動していることを確認します（`make up` または `docker compose up -d`）。
2. プロジェクトルートで実行権限を付与し、スクリプトを実行します。
   ```bash
   cd /Users/phillipr.n./Documents/KUTO/いいずな/iizuna_apps_dev/iizuna-lms-main
   chmod +x scripts/setup-dynamodb-local.sh
   ./scripts/setup-dynamodb-local.sh
   ```
3. 以下の 3 つのテーブルが作成されます。
   - `dev-access-token`
   - `dev-login-token`
   - `dev-auto-login-token`

### 3-1. Docker で AWS CLI を使う場合（任意）

ホストに AWS CLI を入れたくない場合は、公式 CLI コンテナを利用しても構いません。

```bash
docker run --rm \
  --network iizuna-lms-main_iizuna-network \
  -v $(pwd)/scripts:/work \
  -w /work \
  amazon/aws-cli \
  sh -c "AWS_ENDPOINT=http://dynamodb-local:8000 AWS_REGION=ap-northeast-1 ./setup-dynamodb-local.sh"
```

ネットワーク名は `docker compose ps` の `NETWORKS` 列を確認し、異なる場合は読み替えてください。

## 4. テーブル作成の確認

```bash
docker compose exec dynamodb-local aws dynamodb list-tables \
  --endpoint-url http://localhost:8000
```

`dev-access-token`, `dev-login-token`, `dev-auto-login-token` が表示されれば完了です。

## 5. 学生ログインの動作確認

1. `http://localhost:8080/student/login.php` を開き、既存の学生アカウントでログインします。
2. エラーが出ずにダッシュボードへ遷移できれば DynamoDB が正しく機能しています。
3. ログイン後に `docker compose logs app | grep DynamoDB` などでエラーが無いか確認しておくと安心です。

---

## トラブルシューティング

| 症状 | 対応 |
| --- | --- |
| `ResourceNotFoundException`（Cannot do operations on a non-existent table） | テーブルが未作成です。再度 `scripts/setup-dynamodb-local.sh` を実行してください。 |
| `aws: command not found` | AWS CLI がインストールされていません。手順「2」を参照してください。 |
| `Unable to locate credentials` | `--endpoint-url` 付きでも CLI が認証情報を要求する場合があります。`aws configure set aws_access_key_id test` などダミー値を設定するか、`AWS_ACCESS_KEY_ID/SECRET_ACCESS_KEY` に適当な値を渡してください。 |

以上で DynamoDB Local の準備は完了です。student ログインや自動ログイン機能を含む画面が正常に動作するようになります。
