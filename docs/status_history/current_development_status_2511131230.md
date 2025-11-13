# Current Development Status (2025-11-13 12:30 JST)

## 1. Project Overview
- プロジェクト名：いいずなLMS 書籍アップロード改善 / answer_id 不具合修正
- 目的：学生クイズの選択肢ハイライト不具合を解消し、AWS クローン環境で master upload ～ 統計更新までを再現できるようにする。
- 現在フェーズ：不具合修正の安定化 + クローン環境整備（本番リリース前最終確認）。

## 2. Core Components
| コンポーネント | 状況 | メモ |
| --- | --- | --- |
| フロントエンド (student) | 完了 | `_quiz.html` / `_quiz_result*.html` が answer_id 表示に対応済み。 |
| バックエンド (app/Controllers) | 完了 | `JsonQuizController` / `TestController` が answer_id を用いて採点・結果保存。 |
| コンバータ | 進行中 | `setup_database/iizuna_lms/converter.php` に answer_id 付与ロジックを組み込み済み。AWS での長時間実行対策継続中。 |
| 書籍アップロード | 進行中 | BookUploadService に長時間設定・権限調整を適用。ログ取得まで確認。 |
| 統計コマンド | 完了 | `CorrectAnswerRate` の自動 INSERT / ログ強化と cron 運用を整備。 |

## 3. Achievements (this session)
- `CorrectAnswerRate` のログ出力と自動 INSERT を実装し、Docker/AWS で `[CorrectAnswerRate]` ログを確認。
- `run_summary_and_correct_answer_rate.sh` を追加し、cron 手順とログ監視方法を文書化。
- AWS クローンで PHP/Nginx のアップロード上限・タイムアウト・権限を調整し、書籍アップロードが実行できるところまで復旧。
- DNS に `spapp-dev-clone.iizuna-lms.com` を追加し、外部端末からのアクセスを確認。
- オンライン反映手順書と SFTP 用反映手順書を作成（`docs/online_deployment_runbook_20251112.md`, `docs/sftp_deployment_runbook_answer_id_fix.md`）。

## 4. Remaining Tasks
- [High] AWS クローンで master upload（データチェック→import→setup_book_range）を完走させ、手順を確定。
- [High] 本番 RDS で `scripts/add_answer_choice_ids.php --apply` を実行し、app/public/converter の差分を SFTP で反映（不具合修正版の本番適用）。
- [Medium] 書籍アップロードのタイムアウト/メモリ調整を詰め、エラーハンドリング改善。
- [Medium] 次フェーズ：Google Forms 用テスト出力機能の要件定義（既存 LMS JSON との共存方法を整理）。
- [Low] cron の失敗検知（summary_and_correct_answer_rate のアラート化）とログ整理。

## 5. Known Issues & Notes
- converter 実行時に `Maximum execution time` / `Allowed memory size` が発生する場合があるため、PHP/Nginx の上限を十分に確保すること。
- BookUpload の失敗ジョブが残ると `result_payload` が空のままになり、UI で Notice が出る。失敗ジョブは削除するか `result_payload={}` を設定しておく。
- DNS 追加後、旧 hosts 設定が端末に残っていると `ERR_NAME_NOT_RESOLVED` になるので注意。

## 6. Next Focus
1. AWS クローン上で master upload を最後まで成功させ、反映手順を Runbook に反映。
2. 本番に answer_id 修正版を SFTP で適用し、RDS バックフィルを実施。
3. Google Forms 出力機能の仕様を整理し、既存テスト生成フローとの整合を取る。

## 7. References
- `docs/online_deployment_runbook_20251112.md`
- `docs/sftp_deployment_runbook_answer_id_fix.md`
- `scripts/run_summary_and_correct_answer_rate.sh`
- `app/Controllers/JsonQuizController.php`, `public/student/_quiz.html`
- 書籍アップロード関連ログ (`app/Logs/book_upload.log`)
