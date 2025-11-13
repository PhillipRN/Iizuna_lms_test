# Next Steps (2025-11-13)

1. **AWS クローンで master upload を完走させる**
   - PHP/Nginx の上限設定を反映させた状態で、データチェック → CSV 変換 → import → setup_book_range までを最後まで実行。
   - `app/Logs/book_upload.log` で `exitCode=0` を確認し、手順を Runbook に追記。

2. **本番への answer_id 不具合修正反映**
   - `docs/sftp_deployment_runbook_answer_id_fix.md` に沿って SFTP で app/public/converter を更新。
   - RDS で `scripts/add_answer_choice_ids.php --apply` を実行し、全クイズの JSON / 結果テーブルをバックフィル。
   - 先生/生徒画面で再確認し、リリースノートをまとめる。

3. **書籍アップロード機能の安定化**
   - BookUploadService のタイムアウト・メモリ設定を Runbook に反映し、失敗ジョブを自動クリーンアップする仕組みを検討。
   - UI の Notice 対策（`result_payload` の default / 失敗時のリトライ案）を整理する。

4. **Google Forms 出力機能の設計開始**
   - 既存 LMS JSON 出力との共存方法を整理し、必要なデータ変換ロジックを洗い出す。
   - 先生がテスト生成 → Google Forms にインポートするまでの操作フローを設計し、バックログにタスク化。

5. **Onigiri テスト生成フローの確認**
   - ローカル・オンライン双方で `public/teacher/onigiri_quiz_*` 系のエンドポイントを通し、JSON / delivery テーブルの差異を調査。
   - `app/Onigiri/` 配下のロジックと BookUpload の連携を確認し、マスターデータ（TC××××系）が十分に揃っているか検証。
   - 必要に応じて Onigiri 用の BookLoader/テーブルセットを RDS に投入し、開発手順を Runbook に追加する。

6. **監視・運用**
   - `summary_and_correct_answer_rate` cron の成功/失敗を通知する仕組み（Slack 等）を検討。
   - DNS / 証明書の更新スケジュールを整備し、クローン環境でも https アクセスが可能になるよう準備。
