# AWS 複製環境セットアップ手順（AMI 起動後）

## 📍 現在の状況

✅ AMI から新しいインスタンスを起動完了  
➡️ **次：SSH 接続とアプリケーション設定**

---

## ステップ 1: インスタンス情報の確認（5 分）

### AWS コンソールで確認

**EC2 > インスタンス** で新しいインスタンスを選択し、以下を確認：

```
インスタンス名: spapp-dev-clone-yourname（作成したもの）
インスタンスID: i-xxxxxxxxxxxxxxxxx
パブリックIPアドレス: XX.XX.XX.XX  ← これをメモ！
状態: 実行中
キーペア名: spapp-dev-keypair
```

**重要**: パブリック IP アドレスをメモしてください。

---

## ステップ 2: SSH 接続設定（5 分）

### 2-1. SSH 設定ファイルを編集

ターミナルで以下を実行：

```bash
# SSH設定ファイルを開く
nano ~/.ssh/config

# または
vim ~/.ssh/config

# またはVSCodeで開く
code ~/.ssh/config
```

### 2-2. 新しいホスト設定を追加

ファイルの**末尾**に以下を追加（IP アドレスを実際のものに置き換え）：

```ini
# 複製した開発環境（あなた専用）
Host spapp-dev-clone
  Hostname XX.XX.XX.XX  ← 先ほどメモしたIPアドレス
  User ec2-user
  IdentityFile ~/.ssh/spapp-dev-keypair.pem
  ServerAliveInterval 60
  ServerAliveCountMax 3
```

**保存して閉じる**：

- nano: `Ctrl + X` → `Y` → `Enter`
- vim: `Esc` → `:wq` → `Enter`

### 2-3. SSH キーのパーミッション確認

```bash
# キーファイルのパーミッションを確認・修正
chmod 600 ~/.ssh/spapp-dev-keypair.pem

# 設定ファイルのパーミッション確認・修正
chmod 600 ~/.ssh/config
```

---

## ステップ 3: SSH 接続テスト（5 分）

### 3-1. 初回接続

```bash
# 新しいインスタンスに接続
ssh spapp-dev-clone
```

**初回接続時の確認メッセージ**が表示されたら：

```
The authenticity of host 'XX.XX.XX.XX (XX.XX.XX.XX)' can't be established.
ECDSA key fingerprint is SHA256:xxxxxxxxxxxxxxxxxxxxx.
Are you sure you want to continue connecting (yes/no/[fingerprint])?
```

**`yes`** と入力して Enter

### 3-2. 接続成功の確認

接続できたら、以下のようなプロンプトが表示されます：

```bash
[ec2-user@ip-XX-XX-XX-XX ~]$
```

**成功！** 次のステップに進みます。

### 3-3. 接続できない場合のトラブルシューティング

#### エラー: Permission denied (publickey)

```bash
# SSHキーのパーミッションを再確認
ls -la ~/.ssh/spapp-dev-keypair.pem

# 600（-rw-------）になっているか確認
# なっていなければ：
chmod 600 ~/.ssh/spapp-dev-keypair.pem
```

#### エラー: Connection timed out

```bash
# セキュリティグループを確認
# AWSコンソール > EC2 > セキュリティグループ
# インバウンドルールに以下があるか確認：
# - タイプ: SSH
# - ポート: 22
# - ソース: 0.0.0.0/0 または あなたのIPアドレス
```

#### エラー: No such host

```bash
# ~/.ssh/config の設定を確認
cat ~/.ssh/config | grep -A 4 "spapp-dev-clone"

# Hostname が正しいIPアドレスになっているか確認
```

---

## ステップ 4: アプリケーションディレクトリの確認（5 分）

SSH 接続できたら、アプリケーションの状態を確認：

```bash
# アプリケーションディレクトリに移動
cd /var/www/iizuna_lms/

# ファイル確認
ls -la

# 期待される出力：
# drwxr-xr-x  app/
# drwxr-xr-x  public/
# -rw-r--r--  composer.json
# -rw-r--r--  ReadMe.md
# など

# 現在のブランチ確認
git branch

# Gitの状態確認
git status
```

**ここまで来れば、AMI が正しくコピーされています！**

---

## ステップ 5: 設定ファイルの確認と修正（10 分）

### 5-1. 現在の設定ファイルを確認

```bash
# 設定ファイルの内容を確認
cat app/config.ini
```

### 5-2. 設定ファイルのバックアップ

```bash
# 元の設定をバックアップ
cp app/config.ini app/config.ini.backup

# バックアップ確認
ls -la app/config.ini*
```

### 5-3. 設定ファイルを編集

```bash
# エディタで開く（好きな方を選択）
vim app/config.ini
# または
nano app/config.ini
```

**変更が必要な項目**：

```ini
# デバッグモードを有効化（開発環境用）
DEBUG_MODE = 1
DISPLAY_ERROR_ALL = 1

# 管理者情報（必要に応じて変更）
ADMIN_LOGIN_ID = admin
ADMIN_LOGIN_PW = admin123

# データベース接続（既存の開発DBを使う場合はそのまま）
DB_HOST = db-dev.spapp-db.localdomain
DB_NAME = iizunaLMS
DB_USER = iizunaLMS
DB_PASS = Gawbvgt2f983mru

ONIGIRI_DB_HOST = db-dev.onigiri-db.localdomain
ONIGIRI_DB_NAME = onigiri
ONIGIRI_DB_USER = onigiri
ONIGIRI_DB_PASS = onigiri_pass

# WWW_ROOT_URL（新しいインスタンスのIPに変更）
WWW_ROOT_URL = http://XX.XX.XX.XX  ← 新しいIPアドレス

# DynamoDB（開発環境の設定をそのまま使用）
USE_DYNAMO_DB = 1
DYNAMO_DB_ACCESS_TOKEN_TABLE = dev-access-token
DYNAMO_DB_LOGIN_TOKEN_TABLE = dev-login-token
DYNAMO_DB_AUTO_LOGIN_TOKEN_TABLE = dev-auto-login-token

# その他は既存の設定のままでOK
```

**保存して閉じる**：

- vim: `Esc` → `:wq` → `Enter`
- nano: `Ctrl + X` → `Y` → `Enter`

### 5-4. 設定ファイルのパーミッション確認

```bash
# パーミッションを確認
ls -la app/config.ini

# 必要に応じて変更
chmod 644 app/config.ini
```

---

## ステップ 6: データベース接続確認（5 分）

### 6-1. MySQL 接続テスト

```bash
# iizunaLMS データベースに接続
mysql -h db-dev.spapp-db.localdomain -u iizunaLMS -pGawbvgt2f983mru iizunaLMS
```

**パスワード入力を求められた場合**：

- パスワード: `Gawbvgt2f983mru`

**接続成功時の出力**：

```sql
MySQL [(none)]>
```

### 6-2. データベーステーブル確認

```sql
-- データベース選択
USE iizunaLMS;

-- テーブル一覧表示
SHOW TABLES;

-- 期待される出力：
-- +------------------------+
-- | Tables_in_iizunaLMS    |
-- +------------------------+
-- | teacher                |
-- | student                |
-- | school                 |
-- | json_quiz              |
-- | ...（その他多数）       |
-- +------------------------+

-- 終了
EXIT;
```

**テーブルが表示されれば DB 接続成功！**

### 6-3. ONIGIRI データベースも確認（オプション）

```bash
# onigiri データベースに接続
mysql -h db-dev.onigiri-db.localdomain -u onigiri -ponigiri_pass onigiri
```

```sql
-- テーブル確認
SHOW TABLES;

-- 終了
EXIT;
```

---

## ステップ 7: Web サーバーの起動確認（5 分）

### 7-1. Apache の状態確認

```bash
# Apacheの状態確認
sudo systemctl status httpd

# 期待される出力:
# ● httpd.service - The Apache HTTP Server
#    Loaded: loaded
#    Active: active (running)  ← これが重要！
```

### 7-2. Apache が停止している場合

```bash
# Apacheを起動
sudo systemctl start httpd

# 自動起動を有効化
sudo systemctl enable httpd

# 状態を再確認
sudo systemctl status httpd
```

### 7-3. PHP の確認

```bash
# PHPバージョン確認
php -v

# 期待される出力:
# PHP 8.2.x (cli) ...
```

---

## ステップ 8: アプリケーション動作確認（10 分）

### 8-1. ブラウザでアクセス

**ローカルマシンのブラウザ**で以下にアクセス：

```
http://XX.XX.XX.XX/
```

（XX.XX.XX.XX は新しいインスタンスの IP アドレス）

### 8-2. 期待される動作

- ✅ LMS のログイン画面が表示される
- ✅ 画像や CSS が正しく読み込まれる
- ✅ エラーが表示されない

### 8-3. 管理者ログインテスト

```
ログインID: admin
パスワード: （config.iniで設定したもの）
```

**ログインできればアプリケーション起動成功！**

---

## ステップ 9: ログの確認（5 分）

### 9-1. Apache エラーログ

```bash
# エラーログの最新20行を表示
sudo tail -n 20 /var/log/httpd/error_log

# リアルタイムでログを監視
sudo tail -f /var/log/httpd/error_log
```

### 9-2. Apache アクセスログ

```bash
# アクセスログの最新20行を表示
sudo tail -n 20 /var/log/httpd/access_log
```

### 9-3. PHP エラーログ（あれば）

```bash
# PHPエラーログの場所を確認
php -i | grep error_log

# ログ確認
sudo tail -f /var/log/php_errors.log
```

---

## ステップ 10: Composer パッケージの確認（5 分）

### 10-1. 依存関係の確認

```bash
# vendorディレクトリが存在するか確認
ls -la vendor/

# composer.lockが存在するか確認
ls -la composer.lock
```

### 10-2. 必要に応じて再インストール

```bash
# Composer自体のバージョン確認
composer --version

# 依存関係を再インストール（必要な場合のみ）
composer install --no-dev

# autoload再生成
composer dump-autoload
```

---

## ✅ セットアップ完了チェックリスト

以下がすべて ✅ になれば、セットアップ完了です：

- [ ] SSH 接続ができる
- [ ] アプリケーションディレクトリに移動できる
- [ ] 設定ファイル（config.ini）を編集した
- [ ] MySQL データベースに接続できる
- [ ] Apache が起動している（active running）
- [ ] ブラウザで LMS 画面が表示される
- [ ] 管理者ログインができる
- [ ] エラーログにエラーがない

---

## 🎯 次のステップ（開発開始）

セットアップが完了したら：

### 1. Git ブランチを作成

```bash
# 作業用ブランチを作成
git checkout -b feature/your-feature-name

# ブランチ確認
git branch
```

### 2. ローカルとの連携設定

```bash
# ローカルマシンで
cd /Users/phillipr.n./Documents/KUTO/いいずな/iizuna_apps_dev/iizuna-lms-main

# リモートリポジトリ確認
git remote -v

# 新しいブランチをpush
git push origin feature/your-feature-name
```

### 3. 開発フロー

```bash
# 【ローカル】コード編集
code app/Controllers/SomeController.php

# 【ローカル】変更をコミット
git add .
git commit -m "機能追加: XXX"

# 【ローカル】プッシュ
git push origin feature/your-feature-name

# 【AWS複製環境】最新コードを取得
ssh spapp-dev-clone
cd /var/www/iizuna_lms/
git pull origin feature/your-feature-name

# 【AWS複製環境】ブラウザで動作確認
```

---

## 🐛 トラブルシューティング

### 問題 1: ブラウザでアクセスできない

#### 確認 1: セキュリティグループ

AWS コンソール > EC2 > セキュリティグループ

**インバウンドルールに以下があるか確認**：

```
タイプ: HTTP
プロトコル: TCP
ポート範囲: 80
ソース: 0.0.0.0/0
説明: HTTP access
```

**追加方法**：

1. セキュリティグループを選択
2. 「インバウンドルールを編集」
3. 「ルールを追加」
4. 上記の設定を入力
5. 「ルールを保存」

#### 確認 2: Apache の起動

```bash
sudo systemctl status httpd
# 停止していたら：
sudo systemctl start httpd
```

#### 確認 3: ファイアウォール

```bash
# iptablesの確認
sudo iptables -L -n

# 必要に応じて80番ポートを開放
sudo iptables -I INPUT -p tcp --dport 80 -j ACCEPT
```

### 問題 2: データベース接続エラー

#### エラーメッセージ例

```
SQLSTATE[HY000] [2002] Connection refused
```

#### 解決策

```bash
# 1. config.iniのDB設定を確認
cat app/config.ini | grep DB_

# 2. MySQL接続テスト
mysql -h db-dev.spapp-db.localdomain -u iizunaLMS -pGawbvgt2f983mru

# 3. 接続できない場合はセキュリティグループ確認
# RDSのセキュリティグループに新しいEC2からの接続を許可
```

### 問題 3: Composer エラー

#### エラーメッセージ例

```
Fatal error: Class 'XXX' not found
```

#### 解決策

```bash
# 依存関係を再インストール
cd /var/www/iizuna_lms/
composer install --no-dev
composer dump-autoload --optimize

# Apacheを再起動
sudo systemctl restart httpd
```

### 問題 4: Permission denied エラー

```bash
# ディレクトリのオーナーを確認
ls -la /var/www/iizuna_lms/

# 必要に応じて変更
sudo chown -R ec2-user:apache /var/www/iizuna_lms/
sudo chmod -R 755 /var/www/iizuna_lms/

# 特定のディレクトリに書き込み権限
sudo chmod -R 777 /var/www/iizuna_lms/app/smarty_template_c/
sudo chmod -R 777 /var/www/iizuna_lms/app/Temps/
```

---

## 📊 環境情報の記録

セットアップ完了後、以下の情報を記録しておきましょう：

```markdown
# AWS 複製環境情報

## インスタンス情報

- インスタンス名: spapp-dev-clone-yourname
- インスタンス ID: i-xxxxxxxxxxxxxxxxx
- IP アドレス: XX.XX.XX.XX
- SSH 接続: ssh spapp-dev-clone

## データベース情報

- iizunaLMS DB: db-dev.spapp-db.localdomain
- onigiri DB: db-dev.onigiri-db.localdomain

## アクセス URL

- アプリケーション: http://XX.XX.XX.XX/
- 管理者: admin / admin123

## 作成日: 2025-01-03

## 目的: 開発・検証用環境
```

---

## 💡 便利なコマンド集

### よく使うコマンド

```bash
# SSH接続
ssh spapp-dev-clone

# アプリケーションディレクトリ
cd /var/www/iizuna_lms/

# Apache操作
sudo systemctl status httpd
sudo systemctl restart httpd
sudo systemctl stop httpd
sudo systemctl start httpd

# ログ確認
sudo tail -f /var/log/httpd/error_log
sudo tail -f /var/log/httpd/access_log

# MySQL接続
mysql -h db-dev.spapp-db.localdomain -u iizunaLMS -pGawbvgt2f983mru iizunaLMS

# Git操作
git status
git branch
git pull origin develop

# Composer
composer install
composer dump-autoload
```

---

## 🎉 完了！

すべてのステップが完了したら、**安全に開発できる環境が整いました**！

### 次のアクション

1. **ローカル Docker 環境と比較**

   - 両方の環境で同じ機能をテスト
   - 違いを理解する

2. **小さな変更でテスト**

   - CSS の色変更
   - テキスト変更
   - デバッグログ追加

3. **開発フローの確立**
   - ローカル → Git → AWS 複製環境
   - 動作確認 → 修正 → 再テスト

**何か問題があればいつでも質問してください！**
