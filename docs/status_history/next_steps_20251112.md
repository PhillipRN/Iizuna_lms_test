# Next Steps (2025-11-12)

1. **converter.php への answer_id 付与**
   - `setup_database/iizuna_lms/converter.php` で選択肢生成時に `q{question_id}_a{index}` を付与するロジックを追加。
   - BookUploadService のパイプラインから出力される JSON にも同じ ID が含まれることを確認。

2. **CorrectAnswerRate / Onigiri 集計の自動化**
   - 改修済みコマンドを cron / queue で定期実行し、失敗時は Slack / log で検知できるようにする。
   - Onigiri 側 (`OnigiriJsonQuizCorrectAnswerRate`) も同様のログ・自動 INSERT 仕様に揃える。
   - CLI 実行時に出る `docker compose` / `mbstring.internal_encoding` 警告を解消し、運用ログをクリーンに保つ。

3. **統計 UI とデータ形式の整備**
   - `json_quiz_result_statistics.answer_rates_json` の構造をドキュメント化し、必要なら `answer_id` も持たせる。
   - 教師画面（quiz_statistics.php）の表示が ID 化後も崩れないことを確認。

4. **Validator 追加タスク**
   - 問題形式2（整序）、3（入力）、チャレンジ問題などのルールを `BookUploadValidator` ＋ `masterxls_rule_list.md` に追記。
   - 仕様確定後に乾燥テスト（dry-run→DB反映）を実施。

5. **ドキュメント/Runbook**
   - `docs/summary_and_correct_answer_rate.md` に converter 対応後の確認手順を追記予定。
   - 必要に応じて `docs/dynamodb_local_setup.md` に AWS CLI v2 のアンインストール手順をまとめる。

> **備考**: `scripts/add_answer_choice_ids.php --apply` は全クイズで実行済み。新しいクイズを投入する際も ID が付与されるよう、converter 対応が完了するまで忘れずにスクリプトを走らせてください。
