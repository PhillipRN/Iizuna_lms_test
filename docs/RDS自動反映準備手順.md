# RDS自動反映準備手順

書籍アップロードフォームからAWS複製RDS（および将来の本番RDS）に自動反映させるために、事前に満たしておくべき設定と確認項目をまとめる。基本フローは `docs/開発ワークフローガイド.md` に従い、`ReadMe.md` は参照しないこと。

## 1. RDS接続情報の整理
1. `app/config.ini`（環境別）に以下のキーを追加する想定：
   - `RDS_HOST`, `RDS_USER`, `RDS_PASSWORD`, `RDS_DB`。
2. ローカルDockerでは `app/config.docker.ini`、AWS複製環境では `/var/www/iizuna_lms/app/config.ini` に同値を設定。
3. 機密値は `.example` へは空欄のまま項目だけ追加し、Gitには平文でコミットしない。

## 2. セキュリティグループ／ネットワーク
1. AWSコンソールで複製RDSのセキュリティグループを開く。
2. **インバウンドルール** にて以下を許可：
   - タイプ: `MySQL/Aurora (3306)`
   - ソース: ローカル開発時はVPN出口IP、AWS複製EC2のプライベートIP（例: `172.31.33.123/32`）。
3. `aws ec2 describe-security-groups` でJSON保存→`docs/no_use/network/`に控えを置く。
4. RDSが`Publicly accessible: Yes` であることをAWSコンソールまたは `aws rds describe-db-instances` で確認。

## 3. DBユーザー権限の確認
1. RDSへ接続：
   ```bash
   mysql -h <RDS_HOST> -u iizunaLMS -p
   ```
2. 権限確認：
   ```sql
   SHOW GRANTS FOR 'iizunaLMS'@'%';
   ```
3. `book_range` および `TC%%%%_*` テーブル群への `INSERT`, `UPDATE`, `DELETE` が含まれているか確認。
4. 必要なら管理者に依頼し、`GRANT INSERT,UPDATE,DELETE,SELECT ON iizunaLMS.* TO 'iizunaLMS'@'%';` を実行してもらう。

## 4. `setup_database` ディレクトリ権限
1. WebコンテナまたはAWS複製EC2で以下を実行し、www-dataが書き込み可能にする：
   ```bash
   sudo chown -R www-data:www-data setup_database/iizuna_lms/import setup_database/iizuna_lms/export setup_database/batch
   sudo chmod -R 775 setup_database/iizuna_lms/import setup_database/iizuna_lms/export setup_database/batch
   ```
2. `setup_database/batch/auto_setup_with_directory_name.sh` に実行権限が付いているか確認（`chmod +x`）。
3. `mysql-dbaccess.cnf` 内の `host` を複製RDSエンドポイントに更新し、`user`/`password` が上記設定と一致しているか確認。

## 5. PHPからの外部プロセス呼び出し設定
1. `php.ini`（コンテナ: `/usr/local/etc/php/php.ini`、AWS複製: `/etc/php-fpm.d/www.conf`）で以下を確認：
   - `disable_functions` に `proc_open` や `shell_exec` が含まれていない。
   - `max_execution_time` をCSV生成+DB登録に十分な120秒以上に設定。
2. 必要に応じて `php -i | grep disable_functions` で確認。
3. PHP-FPM再起動：
   ```bash
   sudo systemctl restart php-fpm
   ```

## 6. ログ・監査の準備
1. `app/Logs` ディレクトリを作成し、www-data書き込み可能に：
   ```bash
   mkdir -p app/Logs
   sudo chown www-data:www-data app/Logs
   sudo chmod 775 app/Logs
   ```
2. `book_upload.log` のローテーションはAWS複製環境で `logrotate` に登録（例: `/etc/logrotate.d/iizuna-book-upload`）。
3. ログフォーマット例：`[2025-11-05 12:34:56][admin01][BOOK-20251105-001] SUCCESS converter:12s auto_setup:20s`

## 7. 動作確認チェックリスト
1. ダミーExcelをローカルでアップロード → converterとauto_setupが完走し、RDSの `TCxxxx_*` にレコードが追加される。
2. `book_upload.log` にSUCCESS行が出力される。
3. ドライランモードで `.sql` が `/tmp/book_upload/<ID>.sql` に生成され、DBは未更新であることを確認。
4. AWS複製環境でも同じ手順を繰り返し、VPN越しでも問題がないことを確認。

## 8. トラブルシュート
- **接続拒否**: Security GroupのIP許可漏れ、または`mysql-dbaccess.cnf` のホスト名ミス。
- **Permission denied (ディレクトリ)**: `setup_database/...` フォルダの所有者をwww-dataに戻す。
- **converter失敗**: `setup_database/iizuna_lms/logs` や標準出力を確認し、Excelの列ズレを修正。
- **auto_setup失敗**: `/tmp/sql_error_<処理ID>.log` を確認。RDSユーザーの権限不足か、既存データとの重複が原因。

以上を満たせば、アップロードフォームからRDSへの自動反映を安全に実行できる。
