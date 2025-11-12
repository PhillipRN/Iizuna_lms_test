# Repository Guidelines

このLMSリポジトリを常にDockerフレンドリーに保ち、誰でも素早く作業を引き継げるよう下記ガイドラインを参照してください。開発中は `docs/開発ワークフローガイド.md` を必ず主参考にし、`ReadMe.md` は本番公開用として不用意に参照・改変しないでください。

## プロジェクト構造とモジュール構成
- `app/` に `IizunaLMS\\` 空間のPHPロジック（Controllers, Models, Requestsなど）がまとまっており、`app/config*.ini` と `token.ini*` に設定テンプレートが置かれます。
- `public/` と `public_registration/` がWebエントリーポイントで、Smartyテンプレートのアセット参照はこれらを起点に解決されます。
- `tests/` は `app/` と同じ名前空間でPHPUnitを配置。`docs/` はデプロイメモ、`docker/` と `scripts/` はCompose上書きとDynamoDB初期化ツールを管理します。

## ビルド・テスト・開発コマンド
- `make setup` — 設定テンプレートをコピーし、Dockerスタックを起動、ローカルDynamoDBを初期セットアップします。
- `make up` / `make down` — PHP-FPM/Nginx/MySQL/MailHogのコンテナ群を開始・停止します。
- `make test` — `iizuna-lms-app` コンテナ内でPHPUnitを実行。個別テストは `./phpunit.sh --filter FooTest` をシェル内で利用します。
- `make composer-install` / `make composer-dump` — ホスト側から依存解決とオートロード再生成を行います。

## コーディングスタイルと命名
- 基本はPSR-12（4スペースインデント、改行後の波括弧、可能な限り型宣言）に従います。
- クラスはPSR-4で `app/` に配置し、フォルダ階層と名前空間を一致させます（例: `app/Controllers/Admin/...`).
- クラス名はCamelCase、JSON設定のキーはsnake_case、Smarty変数は小文字+アンダースコアを推奨します。

## テスト方針
- サポートされるランナーはPHPUnit 8（`vendor/phpunit/phpunit/phpunit`）。
- テストは `tests/<Domain>/<Subject>Test.php` に配置し、AWS SDKやGoogle APIなど外部依存はモック化して決定性を保ちます。
- PR前にCRUDコントローラのスモークテストを確保し、fail/skipを残したままのマージは禁止です。

## コミットとPRガイド
- 既存履歴は簡潔で説明的な件名（例: `初期コミット: ...`）を使用。72文字以内を目安に、詳細は本文へ。
- 必要に応じてJira/GitHubの課題IDを件名先頭に付与します。
- PRではユーザー影響、実行したテスト（`make test`、UI変更はスクショ）、設定ファイルへの変更点 (`app/*.ini`) を明示してください。

## 設定とセキュリティの注意
- `app/config.ini` や `app/token.ini` の実値はコミット禁止。`.example` を基にし、新しいキーはREADMEか本ガイドで説明します。
- AWS/Google/Mailのシークレットは環境変数またはDocker Secretsに保存し、リポジトリにはダミー値のみを残します。
