# 不具合修正（answer_id 対応）SFTP 反映手順

対象: フロント/UI + PHP 本体 + converter.php の answer_id 対応を本番サーバーへ安全に反映するための手順。

---

## 1. 事前確認
- ローカルで `git status` が clean であること。
- 差分ファイルをリストアップする（例：`git diff --stat main origin/main`）。
- SFTP 接続情報・アップロード先パス（例：`/var/www/iizuna_lms`）を確認。
- RDS のスナップショットを取得しておく（万一に備える）。

---

## 2. アップロード対象（例）
1. `app/` 配下
   - `app/Controllers/JsonQuizController.php`
   - `app/Controllers/TestController.php`
   - `app/Books/BookLoader.php`
   - その他 diff になっているファイル
2. `public/` 配下
   - `public/student/_quiz.html`
   - `public/student/_quiz_result.html` など差分のあるテンプレート一式
3. コンバータ
   - `setup_database/iizuna_lms/converter.php`
4. ドキュメント類（任意）
   - `docs/answer_choice_id_migration_plan.md` など必要に応じて。
5. ログ・cron 関連は不要（今回の不具合修正に限定するため）。

※ 実際にアップロードするファイルは `git diff` を基に洗い出してください。

---

## 3. アップロード手順
1. SFTP でサーバーに接続。
   ```bash
   sftp user@production-host
   ```
2. 作業用ディレクトリへ移動。
   ```
   cd /var/www/iizuna_lms
   ```
3. 必要ファイルをアップロード（例）。
   ```
   put app/Controllers/JsonQuizController.php app/Controllers/
   put app/Controllers/TestController.php app/Controllers/
   put public/student/_quiz.html public/student/
   put setup_database/iizuna_lms/converter.php setup_database/iizuna_lms/
   ```
4. 作業完了後、権限が変わっていないか軽くチェック。
   ```bash
   ls -l app/Controllers/JsonQuizController.php
   ```
   （もし www-data/nginx 以外の権限に変わってしまった場合は `sudo chown nginx:nginx ...` で戻す。）

---

## 4. answer_id バックフィル
1. SSH でサーバーに接続。
   ```bash
   ssh user@production-host
   cd /var/www/iizuna_lms
   ```
2. Dry-run でログを確認。
   ```bash
   php scripts/add_answer_choice_ids.php
   ```
3. 問題なければ適用。
   ```bash
   php scripts/add_answer_choice_ids.php --apply
   ```
4. 実行結果ログに "Quiz JSON updated" / "Result rows updated" が表示されたか確認。

---

## 5. 動作確認
1. 教員画面で既存テストの結果表示を確認（不正解表示が正常化しているか）。
2. 生徒画面でも同じテストを開き、選択肢の赤/緑表示が正しいか確認。
3. 教員画面で新しいテストを作成し、JSON に `answer_id` が付与されているか spot check。
4. 必要に応じて BookUpload で新規 Excel からテスト生成し、`converter.php` の更新内容が効いているか確認。

---

## 6. チェックリスト
- [ ] 差分ファイルを SFTP で本番に反映した
- [ ] `scripts/add_answer_choice_ids.php --apply` を本番 RDS に対して実行した
- [ ] 教員/生徒画面で UI 不具合が解消されたことを確認
- [ ] 新規テストでも `answer_id` が自動付与されることを確認

必要に応じて作業ログを残し、問題があれば速やかにリストア（ファイル差し戻し／RDS スナップショット）できるようにしておいてください。
