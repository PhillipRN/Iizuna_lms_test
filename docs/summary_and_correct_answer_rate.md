# summary_and_correct_answer_rate 実行ガイド

集計系画面（`/teacher/quiz_statistics.php` など）は `json_quiz_result_summary` / `json_quiz_result_statistics` の内容を参照します。  
生徒の受験後にサマリーが表示されない場合は、このスクリプトを実行して集計データを更新してください。

---

## 1. 前提条件
- `calc_correct_answer_rate = 1` の `json_quiz` レコードが対象になります（`json_quiz_result` に新規結果が入ると `RegisterResult` がフラグを立てます）。
- `json_quiz_result_summary` / `json_quiz_result_statistics` に対象クイズのレコードがない場合でも、コマンドが自動で INSERT します。
- 集計対象は「`is_first_result=1` の解答のみ」です。再受験（`is_first_result=0`）は統計に含まれません。

## 2. 実行コマンド

```bash
cd /Users/phillipr.n./Documents/KUTO/いいずな/iizuna_apps_dev/iizuna-lms-main
docker compose exec app php app/Commands/summary_and_correct_answer_rate.php
```

実行後、`json_quiz` の `calc_correct_answer_rate` が 0 に戻り、`json_quiz_result_summary` / `json_quiz_result_statistics` が更新されます。

## 3. 確認クエリ

```bash
# 対象クイズの summary / statistics を確認
docker compose exec mysql-iizuna \
  mysql -uiizunaLMS -pGawbvgt2f983mru iizunaLMS \
  -e "SELECT * FROM json_quiz_result_summary WHERE json_quiz_id=1312\G"

docker compose exec mysql-iizuna \
  mysql -uiizunaLMS -pGawbvgt2f983mru iizunaLMS \
  -e "SELECT * FROM json_quiz_result_statistics WHERE json_quiz_id=1312\G"
```

`correct_answer_rates_json` や `answer_rates_json.total` に値が入っていれば OK です。

## 4. トラブルシューティング

| 症状 | 対応 |
| --- | --- |
| `json_quiz_result_summary` が作成されない | `app/Commands/CorrectAnswerRate.php` のログで例外が出ていないか確認。`app/Logs/summary_and_correct_answer_rate.log` を参照し、INSERT 失敗のエラーがあれば再実行。 |
| `answer_rates_json.total` が 0 のまま | `json_quiz_result` の `is_first_result` を確認。すべて 0 の場合は初回受験が存在しないため、統計に反映されません。 |
| `calc_correct_answer_rate` が 1 のまま | コマンドが途中で失敗しています。`docker compose logs app` を確認し、例外が出ていないか確認してください。 |

---

## 5. 定期実行（cron）

`scripts/run_summary_and_correct_answer_rate.sh` はリポジトリルートから `summary_and_correct_answer_rate.php` を実行し、`app/Logs/summary_and_correct_answer_rate.log` に標準出力/エラーを追記します。サーバー上では以下の手順でセットアップしてください。

```bash
cd /var/www/iizuna_lms
chmod +x scripts/run_summary_and_correct_answer_rate.sh
```

例: 10分おきに実行し、専用ログに追記する cron 設定

```
*/10 * * * * /var/www/iizuna_lms/scripts/run_summary_and_correct_answer_rate.sh >> /var/log/iizuna/summary_cron.log 2>&1
```

- `summary_and_correct_answer_rate.php` は Onigiri 側の集計も含むため、上記スクリプトのみで両方の統計が更新されます。
- ログに `[CorrectAnswerRate] quiz_id=...` が出力されるので、failures は `summary=inserted/updated` の行が無いことで検知できます。

定期的なバッチや手動実行の運用にこのガイドを参照してください。***
