# AWS クローン環境反映手順（2025-11-12）

このメモは、ローカル開発ブランチ `check_20251027` を AWS クローン環境（EC2 + RDS）へ反映し、書籍アップロード機能を動作させるまでに実施した作業のまとめです。全国向け本番適用前に、ここに記載した手順を必ず再確認してください。

---

## 0. 事前準備
- ローカルで `git status` が clean であることを確認。
- 変更を `check_20251027` にコミット → `origin/check_20251027` へ push。
- `main` ブランチへ `check_20251027` をマージし、`origin/main` へ push。
- AWS クローンの認証情報（SSH、RDS）を確認。

---

## 1. コード同期
```bash
ssh spapp-dev-clone
cd /var/www/iizuna_lms
git fetch --all
git checkout main
git pull origin main
```

---

## 2. 追加スクリプト・ログディレクトリ
- `scripts/add_answer_choice_ids.php` など新規ファイルが入っているか確認。
- `app/Logs` を作成し、書き込み可能にする：
```bash
sudo mkdir -p app/Logs
sudo chown -R nginx:nginx app/Logs
sudo chmod -R 775 app/Logs
```

---

## 3. PHP.ini 調整
以下を `/etc/php.ini` に設定（既存値を置き換え）。
```
upload_max_filesize = 50M
post_max_size = 50M
max_execution_time = 300
memory_limit = 512M
session.save_path = "/var/lib/php/session"
```
反映：
```bash
sudo systemctl restart php-fpm
```

---

## 4. PHP-FPM 設定
`/etc/php-fpm.d/www.conf` の `user` / `group` を確認（当環境は `nginx`）。
```
request_terminate_timeout = 600
```
を設定し、`sudo systemctl restart php-fpm`。

---

## 5. Nginx 設定
1. `/etc/nginx/nginx.conf` の `http { ... }` で `client_max_body_size 50m;` を設定。
2. 追加タイムアウト設定を `conf.d` へ分離。
```bash
sudo tee /etc/nginx/conf.d/book_upload_timeouts.conf <<'CONF'
fastcgi_read_timeout 600;
fastcgi_send_timeout 600;
proxy_read_timeout 600;
proxy_send_timeout 600;
CONF
```
3. テスト＆再読込。
```bash
sudo nginx -t
sudo systemctl reload nginx
```

---

## 6. ディレクトリ権限（書籍アップロード）
```bash
cd /var/www/iizuna_lms
sudo chown -R nginx:nginx setup_database/iizuna_lms/import setup_database/iizuna_lms/export setup_database/batch /tmp/book_upload /var/lib/php/session
sudo chmod -R 775 setup_database/iizuna_lms/import setup_database/iizuna_lms/export setup_database/batch /tmp/book_upload
sudo chmod 1733 /var/lib/php/session
sudo chmod 1777 /tmp
sudo systemctl restart php-fpm
```
`/tmp/book_upload` が無い場合は `sudo mkdir -p /tmp/book_upload` を先に実行。

---

## 7. セッションリセット
```bash
sudo rm -f /var/lib/php/session/sess_*
sudo chown -R nginx:nginx /var/lib/php/session
sudo chmod 1733 /var/lib/php/session
sudo systemctl restart php-fpm
```
ブラウザの Cookie を削除し再ログイン。

---

## 8. answer_id バックフィル
```bash
php scripts/add_answer_choice_ids.php          # dry-run
php scripts/add_answer_choice_ids.php --apply  # 本適用
```
RDS 接続情報は `app/config.ini` で確認。

---

## 9. summary_and_correct_answer_rate 自動化
```bash
chmod +x scripts/run_summary_and_correct_answer_rate.sh
crontab -e
*/10 * * * * /var/www/iizuna_lms/scripts/run_summary_and_correct_answer_rate.sh >> /var/log/iizuna/summary_cron.log 2>&1
```
ログ出力先：
- `/var/www/iizuna_lms/app/Logs/summary_and_correct_answer_rate.log`
- `/var/log/iizuna/summary_cron.log`（事前に `sudo mkdir -p /var/log/iizuna && sudo chown ec2-user:apache /var/log/iizuna && sudo chmod 775 /var/log/iizuna`）

---

## 10. 書籍アップロードの確認
1. 管理画面にログインし、Excel を選択して「データチェック」。
2. `app/Logs/book_upload.log` を tail し、`converter` コマンドの exitCode を確認。
3. 失敗した場合は `/setup_database/iizuna_lms` で手動実行。
```bash
cd /var/www/iizuna_lms/setup_database/iizuna_lms
IMPORT_FOLDER='<フォルダ>' php converter.php
```

---

## 11. 失敗ジョブのクリーンアップ
```bash
mysql -h <RDSホスト> -u<USER> -p'<PASS>' iizunaLMS <<'SQL'
DELETE FROM book_upload_jobs ORDER BY id DESC LIMIT 1;
SQL
```

---

## 12. チェックリスト
- [ ] git pull 済み
- [ ] `scripts/add_answer_choice_ids.php --apply` 済み
- [ ] PHP/Nginx のタイムアウト・メモリ設定反映済み
- [ ] `/tmp`, `/tmp/book_upload`, `/var/lib/php/session` の所有者が `nginx`
- [ ] cron（summary_and_correct_answer_rate）が稼働しログに `[CorrectAnswerRate]` が出ている
- [ ] 書籍アップロード（データチェック→CSV→import）が成功
- [ ] `app/Logs/book_upload.log` に最新ジョブ記録

※ 本番環境へ適用する前に必ず AWS クローンでリハーサルし、必要に応じて本書を更新してください。
