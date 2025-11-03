# LMS・おまかせ用書籍追加 更新手順マニュアル

## 1. データの準備

### 1.1 エクセルファイルの確認
- エクセルのすべてのシートを開く
- クイックフィルタを外す

### 1.2 データの検証・修正
- 「02_問題形式」のシートなどで最終行を確認
- 余計なデータが含まれている場合は削除する
  - 例：配分比率の集計した数字が入っていることがある

## 2. ファイルの配置

### 2.1 フォルダの作成
`iizuna_lms` プロジェクト内の以下のパスに任意の名前のフォルダを作成する：
```
setup_database/iizuna_lms/import/
```

### 2.2 エクセルファイルの配置
作成したフォルダ内に対象のエクセルファイルを配置する。

**例：**
```
setup_database
└ iizuna_lms
  └ import
    └ 250628
      ├ LMSTC10061_Evergreen English Grammar 27 Lessons updated_250129.xlsx
      └ LMSTC20041_国語頻出問題1200五訂版（漢文なし）_250519.xlsx
```

## 3. ファイルの変換

### 3.1 変換実行
以下のコマンドを実行してファイルを変換する：
```bash
cd setup_database/iizuna_lms
php converter.php
```

### 3.2 変換結果の確認
`setup_database/iizuna_lms/export` の下に変換されたファイルが出力される。

**例：**
```
setup_database
└ iizuna_lms
  └ export
    └ 250628
      ├ TC10061
      │   ├ answer_index.csv
      │   ├ ・・・
      │   └ TC07.csv
      └ TC20041
          ├ answer_index.csv
          ├ other_answer.csv
          ├ TC02.csv
          ├ TC03.csv
          ├ TC04.csv
          ├ TC05.csv
          └ TC07.csv
```

## 4. 開発サーバー（spapp-dev-ec2）のDBに反映

### 4.1 CSVファイルのアップロード
開発サーバー（spapp-dev-ec2）の `setup_database/batch` の下に、`TC10061` などのフォルダごとCSVをアップロードする。

### 4.2 データベース設定の確認
`setup_database/batch/mysql-dbaccess.cnf` の設定が開発のDBに合わせて設定されていることを確認する。

### 4.3 データベースへの反映
以下のコマンドを実行してDBに反映する：
```bash
cd setup_database/batch
./auto_setup_with_directory_name.sh
```

### 4.4 範囲設定の生成
以下のコマンドを実行し、範囲設定などを生成する：
```bash
php app/Commands/setup_book_range.php
```

## 5. 本番サーバーのDBに反映

### 5.1 バックアップ対象テーブルの確認
バックアップ対象のテーブル一覧を表示する：
```bash
mysql -h db-dev.spapp-db.localdomain -u iizunaLMS -p iizunaLMS -e"SHOW TABLES" | grep -E "TC10061|TC20041"
```

### 5.2 データベースダンプの作成
`book_range` と対象テーブルをダンプする：
```bash
mysqldump --single-transaction --no-tablespaces -h db-dev.spapp-db.localdomain -u iizunaLMS -p --set-gtid-purged=OFF --skip-column-statistics iizunaLMS book_range TC10061_TC02 TC10061_TC03 TC10061_TC04 TC10061_TC05 TC10061_answer_index TC10061_other_answer TC20041_TC02 TC20041_TC03 TC20041_TC04 TC20041_TC05 TC20041_TC07 TC20041_answer_index TC20041_other_answer > xxxx_iizuna_lms_update.dump
```

### 5.3 文字コードの修正（必要に応じて）
ダンプしたファイル内の以下の部分を置換する：
- 変更前：`COLLATE=utf8mb4_0900_ai_ci;`
- 変更後：`COLLATE=utf8mb4_general_ci;`

### 5.4 本番環境への反映
1. ダンプしたファイルを本番サーバーAMI用環境（spapp-prod-ec2-ami-base-2503）にアップロードする
2. 以下のコマンドでDBに反映する：
```bash
mysql -h db-prod.spapp-db.localdomain -u iizunaLMS -p iizunaLMS < xxxx_iizuna_lms_update.dump
```

---

## 注意事項
- 各ステップで エラーが発生した場合は、ログを確認して原因を特定する
- 本番環境への反映前に、必ずバックアップを取得する
- テスト環境での動作確認を十分に行ってから本番反映を実施する