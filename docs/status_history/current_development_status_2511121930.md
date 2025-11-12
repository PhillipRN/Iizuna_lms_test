# Current Development Status (2025-11-12 19:30 JST)

## 1. Project Overview
- プロジェクト名：いいずなLMS 書籍アップロード改善
- 目的・概要：編集部がExcelを安全にアップロードし、dry-run→DB反映→範囲生成までを自走できるよう管理画面とバッチ処理を刷新。
- 現在の開発フェーズ：機能拡張フェーズ（UI/UX整備とバリデーション強化 + 採点精度改善）。
- 主な担当範囲：フロント（Smarty + JS）／バックエンド（PHPサービス、converter/batch 連携、統計集計）／ドキュメント整備。

## 2. Core Components
| コンポーネント | 状況 | 補足 |
| --- | --- | --- |
| フロントエンド | 進行中 | 学生向けクイズ送信・結果表示を answer_id ベースへ刷新。|
| バックエンド | 進行中 | JsonQuizController の採点／結果保存を ID 化、BookLoader・DynamoDB 手順を整備。|
| バッチ／コンバータ | 要対応 | converter.php はまだ文字列比較仕様。ID付与ロジックを取り込み予定。|
| 統計処理 | 進行中 | CorrectAnswerRate が自動 INSERT/詳細ログ対応済み。cron 化と UI 連携の仕上げが残り。|
| ドキュメント | 進行中 | handover／集計コマンド／DynamoDB ガイドを更新。|

## 3. Major Achievements
- DynamoDB Local 初期化手順を `docs/dynamodb_local_setup.md` にまとめ、生徒ログイン周りのエラーを解消。
- BookLoader の厳密比較バグを修正し、teacher の所持書籍リストが正しく表示されるようにした。
- クイズ回答フローを `answer_id` ベースに刷新：`JsonQuizController`、student送信／結果表示、teacherプレビュー、PHP CLI スクリプトを整備。
- 既存クイズに対して `scripts/add_answer_choice_ids.php --apply` を実行し、`json_quiz`／`json_quiz_result` に ID を付与。
- 統計更新手順を `docs/summary_and_correct_answer_rate.md` に記載し、`quiz_id=1312` のサマリー／統計データを反映。
- `app/Commands/CorrectAnswerRate.php` を改修し、`json_quiz_result_summary`/`statistics` へ初回 INSERT と進捗ログを自動で出力できるようにした。

## 4. Remaining Tasks
- [High] `setup_database/iizuna_lms/converter.php` で選択肢IDを生成し、BookUpload パイプラインから出力される JSON を ID 化する。
- [High] CorrectAnswerRate 実行フローの自動化（cron での定期実行、Onigiri 側との同等対応、失敗監視）。
- [Medium] `json_quiz_result_statistics` の answer_rates_json を ID ベースでも扱えるようにし、UI 側も連携させる。
- [Medium] 追加の問題形式（整序、入力等）の Validator/`masterxls_rule_list.md` 反映。
- [Low] BookUpload UI の警告文言／履歴更新の最適化。
- [Low] `docker-compose.yml` の `version` キーと `mbstring.internal_encoding` の非推奨設定を整理し、警告を解消する。

## 5. Known Issues & Technical Notes
- converter.php が answer_id を出力しないため、BookUpload で生成したクイズはスクリプトのバックフィルが必要。
- CorrectAnswerRate は `summary_and_correct_answer_rate.php` を通さないと実行されない。`calc_correct_answer_rate` フラグと `is_first_result` を要確認。
- `docker compose` 実行時に `version` キーが非推奨警告を出す。compose V2 に合わせて削除する。
- PHP 8.2 で `mbstring.internal_encoding` が非推奨になり、CLI 実行時に警告が出る。ini / bootstrap の設定見直しが必要。

## 6. Next Steps
1. converter.php と BookUploadService の JSON 生成部に `buildAnswerItem` 相当のロジックを導入し、エクスポート時点で answer_id を付与。
2. summary_and_correct_answer_rate.php を定期実行する運用（cron or queue）を整備し、警告ログと Onigiri 側の集計フローも揃える。
3. answer_id 化に伴う統計 UI の体裁確認と、`json_quiz_result_statistics` のデータ形式整備。
4. Validator 追加対応（問題形式2以降）と `masterxls_rule_list.md` の拡充。

## 7. References
- `public/student/_quiz.html`, `_quiz_result.html`, `_quiz_result_preview.html`
- `app/Controllers/JsonQuizController.php`, `app/Controllers/TestController.php`
- `scripts/add_answer_choice_ids.php`
- `docs/dynamodb_local_setup.md`, `docs/summary_and_correct_answer_rate.md`
- `app/Commands/summary_and_correct_answer_rate.php`
