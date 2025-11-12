# Current Development Status (2025-11-06 05:06 JST)

## 1. Project Overview
- プロジェクト名：いいずなLMS 書籍アップロード改善
- 目的・概要：編集部でも安全に書籍Excelを取り込み、ドライラン→DB反映を自走できるよう管理画面とバッチ処理を刷新中。
- 現在の開発フェーズ：機能拡張フェーズ（UI/UX整備とバリデーション強化）。
- 主な担当範囲：フロント（Smarty + JS）/ バックエンド（PHPサービス、converter/batch連携）/ ドキュメント整備。

## 2. Core Components
| コンポーネント | 状況 | 補足 |
| --- | --- | --- |
| フロントエンド | 進行中 | Ajax送信・モーダル進捗・履歴モーダル実装済。ステップ分岐など残課題あり。 |
| バックエンド | 進行中 | queueUpload/processJob 再構成済。setup_book_rangeエラー処理が要調整。 |
| スマートコントラクト | 未着手 | 対象外。 |
| アセット | 進行中 | ボタンスタイルや警告文言のチューニング中。 |
| ドキュメント | 進行中 | `docs/status_history/` で状況記録中。設計系ドキュメントは継続更新予定。 |

## 3. Major Achievements
- データチェック（dry-run）→DB反映の二段階UIを構築し、Ajaxモーダル・履歴モーダルを実装。
- バリデーションメッセージの整理（08_見出し語など）とDryRunメッセージを「DataCheck」表記に統一。
- import/exportフォルダの自動クリーンアップ（成功済み: import削除 / 60日経過export削除 / dry-runは最新1件保持）。

## 4. Remaining Tasks
- [High] setup_book_range.php 失敗時のステータス更新とモーダル反映。
- [High] データチェック時は2ステップ、DB反映時は4ステップに分岐するモーダル行程表示。
- [Medium] dry-run完了判定後のボタン状態切替の微調整（エラー→再実行時など）。
- [Low] 履歴モーダルのページングUI改善（必要なら）。

## 5. Known Issues & Technical Notes
- setup_book_range失敗時にステータスがsuccess扱いになるケースあり。コマンド戻り値の伝播が必要。
- フォルダクリーンアップはページロード時に走るため、長時間ブラウザを開いたままのケースでは古いフォルダが残る。
- Dockerローカル環境はRDSダンプを取り込んでいるので、影響範囲に注意。

## 6. Next Steps
- `BookUploadService::processJob()` で setup_book_range の戻り値を監視し、失敗時に `status=failed` + UI表示を同期。
- フロントJSの `stepOrder` を action（check/apply）毎に切り替える。
- 新仕様で生成された `book_upload_jobs` の動作確認をDocker/AWSで実施。

## 7. References
- `public/il_admin/_book_upload.html`
- `public/il_admin/book_upload.php`
- `app/Services/BookUpload/BookUploadService.php`
- `setup_database/iizuna_lms/converter.php`
- `docs/書籍アップロードフォーム設計.md`
