# Handover Documents

このリポジトリは、学校向けいいずなLMS本体（本番のEC2/RDSクローン + Docker ローカル検証環境）です。`app/` 以下がメインの PHP アプリケーション（Controllers/Models/Commands 等）、`public/` 以下が管理画面・生徒画面など Web UI のエントリポイントなので、仕様確認やデバッグ時はこの2ディレクトリを中心に確認すると全体像を掴みやすくなります。

最新状況を把握するには、以下のドキュメントを順番に参照してください。

1. `docs/status_history/current_development_status_2511131230.md`
   - 最新の開発状況、残タスク、次のアクションが整理されています。
2. `docs/status_history/session_activity_log_2511131230.md`
   - 今セッションのコマンド履歴や注意点、次回への引き継ぎ事項が記録されています。
3. `docs/status_history/fix_log_20251112.md`
   - これまでの修正内容と、未着手のTODOが一覧化されています。
4. `docs/status_history/next_steps_20251113.md`
   - 次セッションで着手すべきタスクの優先度やメモをまとめています。
5. `docs/status_history/current_development_status.md`（テンプレ）
6. `docs/status_history/session_activity_log.md`（テンプレ）
7. `AGENTS.md`
   - リポジトリ全体のガイドラインと注意事項がまとめられています。
8. `docs/開発ワークフローガイド.md`
   - 環境構築や AWS 複製環境との連携など、開発フロー全体の詳細。
9. `docs/書籍アップロードフォーム設計.md`
   - 書籍アップロードフォームの基本設計。
10. `docs/dynamodb_local_setup.md` / `docs/summary_and_correct_answer_rate.md`
    - student ログイン用 DynamoDB 初期化手順と、統計集計コマンドの実行ガイドです。
11. `docs/online_deployment_runbook_20251112.md`
    - AWS クローン環境へ反映する際の詳細な手順書です。
12. `docs/sftp_deployment_runbook_answer_id_fix.md`
    - answer_id 不具合修正を SFTP で本番に適用するための手順書です。

上記を順に読み進めることで、プロジェクトの状況と開発手順を迅速に把握できます。
また、反応・応答・報告などこのセッションのチャットは全て日本語でお願いします。
