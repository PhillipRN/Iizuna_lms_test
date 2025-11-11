# Current Development Status (2025-11-07 13:35 JST)

## 1. Project Overview
- プロジェクト名：いいずなLMS 書籍アップロード改善
- 目的・概要：編集部がExcelを安全にアップロードし、dry-run→DB反映→範囲生成までを自走できるよう管理画面とバッチ処理を刷新。
- 現在の開発フェーズ：機能拡張フェーズ（UI/UX整備とバリデーション強化）。
- 主な担当範囲：フロント（Smarty + JS）／バックエンド（PHPサービス、converter/batch 連携）／ドキュメント整備。

## 2. Core Components
| コンポーネント | 状況 | 補足 |
| --- | --- | --- |
| フロントエンド | 進行中 | モーダル行程表示の分岐、失敗時の詳細表示、履歴自動更新を改善。|
| バックエンド | 進行中 | BookUploadServiceのワークフロー制御、load_data.sh・setup_book_range のエラーハンドリングを強化。|
| スマートコントラクト | 対象外 |  |
| アセット | 進行中 | 追加のUI調整待ち。 |
| ドキュメント | 進行中 | status_history とルール集 (`masterxls_rule_list.md`) を更新。 |

## 3. Major Achievements
- `setup_database/batch/load_data.sh` に `mysql` 検出 / exit code 伝播 / env オーバーライドを追加し、TLS・local_infile 設定を両環境で整備。
- `BookUploadService` に `BookUploadPipelineException` を導入し、失敗ステップ/ログパスをUIへ伝達。モーダルは2ステップ/4ステップを切り替えつつ、失敗ステップを赤表示。
- `setup_book_range.php` のトランザクション処理を見直し、`TRUNCATE` 後でも rollback/commit が破綻しないように修正。ステップ04まで完走することを確認。
- Dockerfile へ `default-mysql-client` を追加し、ローカル環境でも CLI 依存のバッチが動作。AWS 側は RDS CA 設定＋ `mysql-dbaccess.cnf` で TLS 接続を確認。
- `BookUploadValidator` に「問題形式1=4択」の詳細ルール（H列=1、T列スラッシュ上限、重複チェック、V列必須など）を実装し、`masterxls_rule_list.md` に仕様を記載。

## 4. Remaining Tasks
- [High] 追加の問題形式（整序、入力、チャレンジ等）のバリデーションルールを `masterxls_rule_list.md` と Validator に反映。
- [High] `setup_database/batch/mysql-dbaccess.cnf` のテンプレート分離（ローカル用／AWS用）とドキュメント化。
- [Medium] モーダル履歴更新を部分更新へ最適化（現状はリロードで代替）。
- [Low] BookUpload UI の警告メッセージ／ボタン文言の最終調整。

## 5. Known Issues & Technical Notes
- `load_data.sh` は書籍ごとの CSV 数に依存するため、未生成フォルダがあると DROP の WARN が発生するが挙動には影響なし。
- `BookUploadValidator` の H/T/V ルールは4択のみに適用。その他形式は未実装のため、Excel不備を見逃す可能性あり。
- Docker Compose v2 では `version` フィールドが deprecated 警告を出すが、動作には影響しない。将来的に削除予定。

## 6. Next Steps
- `masterxls_rule_list.md` を起点に、問題形式ごとの仕様を網羅しValidatorへ実装。
- 追加ルール実装後、dry-run→DB反映→範囲生成を再テストし、AWS複製環境でも同手順を検証。
- ルール確定後、編集部向け手順書の更新案を作成。

## 7. References
- `public/il_admin/_book_upload.html`
- `public/il_admin/book_upload.php`
- `app/Services/BookUpload/BookUploadService.php`
- `app/Services/BookUpload/BookUploadValidator.php`
- `app/Commands/setup_book_range.php`
- `setup_database/batch/load_data.sh`
- `masterxls_rule_list.md`
