# AWS 複製環境の Git 管理ガイド

## 🎯 目的

AWS 複製環境を既存の開発環境と区別して管理するため、Git の設定を整理します。

---

## 📋 推奨アプローチ：専用ブランチで管理

既存のリポジトリを使いながら、あなた専用のブランチで作業します。

---

## ステップ 1: 現在の Git 状態を確認（5 分）

### 1-1. リモートリポジトリの確認

```bash
# 現在のリモートリポジトリを確認
git remote -v

# 期待される出力例:
# origin  git@github.com:organization/iizuna-lms.git (fetch)
# origin  git@github.com:organization/iizuna-lms.git (push)
```

### 1-2. 現在のブランチと状態を確認

```bash
# ブランチ一覧
git branch -a

# 現在の状態
git status

# 未コミットの変更があるか確認
```

---

## ステップ 2: クリーンな状態にする（3 分）

### 2-1. 未コミットの変更を確認

```bash
# 変更されているファイルを確認
git status

# 変更がある場合は stash（一時保存）
git stash save "複製環境セットアップ前の状態"

# stash 確認
git stash list
```

---

## ステップ 3: 安定版ブランチに切り替え（2 分）

### 3-1. develop ブランチに移動

```bash
# develop ブランチに切り替え
git checkout develop

# 最新の状態を取得
git fetch origin
git pull origin develop

# 現在のコミットを確認
git log -1 --oneline
```

**develop ブランチがない場合**：

```bash
# main ブランチを使用
git checkout main
git pull origin main
```

---

## ステップ 4: 専用ブランチの作成（5 分）

### 4-1. あなた専用のブランチを作成

```bash
# ブランチ命名規則: dev/[名前]-clone-env
git checkout -b dev/prn-clone-env

# ブランチ確認
git branch

# 期待される出力:
#   develop
# * dev/prn-clone-env  ← 緑色の * が現在のブランチ
```

### 4-2. ブランチの説明をコミット

```bash
# 環境情報ファイルを作成
cat > CLONE_ENV_INFO.md << 'EOF'
# AWS 複製環境情報

## 環境
- **インスタンス名**: spapp-dev-clone-20250103-prn
- **インスタンス ID**: i-0601d7c3565281158
- **IP アドレス**: 56.155.92.207
- **作成日**: 2025-01-03
- **目的**: 開発・検証用複製環境

## このブランチについて
このブランチは AWS 複製環境専用です。
既存の開発環境（spapp-dev-ec2）とは独立して動作します。

## 担当者
- prn

## 注意事項
- 既存の開発環境に影響を与えないように管理
- 本番環境へのデプロイは行わない
- 学習・検証目的での使用
EOF

# ファイルを確認
cat CLONE_ENV_INFO.md

# Git に追加
git add CLONE_ENV_INFO.md
git commit -m "docs: AWS複製環境の情報を追加 (dev/prn-clone-env)"

# コミット確認
git log -1
```

---

## ステップ 5: 設定ファイルの編集とコミット（10 分）

### 5-1. 設定ファイルをバックアップ

```bash
# バックアップ作成
cp app/config.ini app/config.ini.backup

# .gitignore 確認（config.ini は通常 gitignore 済み）
cat .gitignore | grep config.ini
```

### 5-2. 設定ファイルを編集

```bash
# エディタで開く
nano app/config.ini

# または vim
vim app/config.ini
```

**変更する項目**：

```ini
# デバッグモード有効化
DEBUG_MODE = 1
DISPLAY_ERROR_ALL = 1

# 管理者情報
ADMIN_LOGIN_ID = admin
ADMIN_LOGIN_PW = admin123

# WWW_ROOT_URL（複製環境のIPアドレス）
WWW_ROOT_URL = http://56.155.92.207

# データベース接続（既存の開発DBを使用）
DB_HOST = db-dev.spapp-db.localdomain
DB_NAME = iizunaLMS
DB_USER = iizunaLMS
DB_PASS = Gawbvgt2f983mru

ONIGIRI_DB_HOST = db-dev.onigiri-db.localdomain
ONIGIRI_DB_NAME = onigiri
ONIGIRI_DB_USER = onigiri
ONIGIRI_DB_PASS = onigiri_pass

# DynamoDB（開発環境の設定をそのまま使用）
USE_DYNAMO_DB = 1
DYNAMO_DB_ACCESS_TOKEN_TABLE = dev-access-token
DYNAMO_DB_LOGIN_TOKEN_TABLE = dev-login-token
DYNAMO_DB_AUTO_LOGIN_TOKEN_TABLE = dev-auto-login-token

# その他は既存の設定のまま
```

保存して閉じる：
- nano: `Ctrl + X` → `Y` → `Enter`
- vim: `Esc` → `:wq` → `Enter`

### 5-3. 設定ファイルのサンプルを作成（オプション）

```bash
# 複製環境用の設定サンプルを作成（パスワードなどは除外）
cat > app/config.clone-env.ini.example << 'EOF'
# AWS複製環境用設定サンプル

DEBUG_MODE = 1
DISPLAY_ERROR_ALL = 1
ADMIN_LOGIN_ID = admin
ADMIN_LOGIN_PW = change_this_password

# 複製環境のIPアドレス
WWW_ROOT_URL = http://56.155.92.207

# データベース接続（開発環境を使用）
DB_HOST = db-dev.spapp-db.localdomain
DB_NAME = iizunaLMS
DB_USER = iizunaLMS
DB_PASS = your_password_here

ONIGIRI_DB_HOST = db-dev.onigiri-db.localdomain
ONIGIRI_DB_NAME = onigiri
ONIGIRI_DB_USER = onigiri
ONIGIRI_DB_PASS = your_password_here

# 以降は app/config.ini.example を参照
EOF

# Git に追加
git add app/config.clone-env.ini.example
git commit -m "config: AWS複製環境用の設定サンプルを追加"
```

---

## ステップ 6: リモートブランチにプッシュ（5 分）

### 6-1. リモートにプッシュ

```bash
# 初回プッシュ（upstream設定付き）
git push -u origin dev/prn-clone-env

# プッシュ成功の確認
git branch -vv
```

### 6-2. GitHub/GitLab でブランチを確認

ブラウザでリポジトリを開いて、新しいブランチが作成されていることを確認：

```
https://github.com/organization/iizuna-lms/tree/dev/prn-clone-env
```

---

## ステップ 7: 開発フローの確立（理解）

### 7-1. 日常的な開発フロー

```bash
# 1. ローカルマシンで開発（VSCodeなど）
cd /Users/phillipr.n./Documents/KUTO/いいずな/iizuna_apps_dev/iizuna-lms-main

# 2. 変更をコミット
git add .
git commit -m "feat: 新機能追加"

# 3. リモートにプッシュ
git push origin dev/prn-clone-env

# 4. AWS複製環境で最新コードを取得
ssh spapp-dev-clone
cd /var/www/iizuna_lms/
git pull origin dev/prn-clone-env

# 5. ブラウザで動作確認
# http://56.155.92.207
```

### 7-2. 本家の更新を取り込む

```bash
# 1. develop ブランチの最新を取得
git fetch origin develop

# 2. 自分のブランチに取り込む
git checkout dev/prn-clone-env
git merge origin/develop

# 3. コンフリクトがあれば解決
# （通常は config.ini など環境固有ファイルのみ）

# 4. プッシュ
git push origin dev/prn-clone-env
```

---

## 📊 ブランチ戦略まとめ

```
main/master          ← 本番環境（触らない）
  ↓
develop             ← 開発環境の基準ブランチ
  ↓
dev/prn-clone-env   ← あなたの複製環境専用ブランチ ⭐
```

---

## 🔀 オプション：完全に新しいリポジトリにする場合

既存のリポジトリから完全に独立したい場合：

### 新しいリポジトリの作成

1. **GitHub/GitLab で新しいリポジトリを作成**
   - リポジトリ名: `iizuna-lms-clone-prn`
   - Private に設定

2. **リモートを変更**

```bash
# 既存のリモートを確認
git remote -v

# 既存のリモートを upstream にリネーム
git remote rename origin upstream

# 新しいリモートを origin として追加
git remote add origin git@github.com:your-username/iizuna-lms-clone-prn.git

# リモート確認
git remote -v
# upstream: 本家（読み取り専用）
# origin: あなたのリポジトリ

# プッシュ
git push -u origin develop
```

3. **本家の更新を取り込む場合**

```bash
# 本家から更新を取得
git fetch upstream develop

# 取り込む
git merge upstream/develop

# 自分のリポジトリにプッシュ
git push origin develop
```

---

## 🛡️ .gitignore の確認

重要なファイルが Git にコミットされないように確認：

```bash
# .gitignore の内容確認
cat .gitignore

# 確認すべき項目:
# ✅ app/config.ini
# ✅ app/token.ini
# ✅ app/firebase_auth.json
# ✅ vendor/
```

**もし config.ini が gitignore されていない場合**：

```bash
# .gitignore に追加
echo "app/config.ini" >> .gitignore

# 既にコミットされている場合は削除
git rm --cached app/config.ini

# コミット
git add .gitignore
git commit -m "chore: config.iniをgitignoreに追加"
```

---

## ✅ 完了チェックリスト

- [ ] リモートリポジトリを確認した
- [ ] 専用ブランチ（dev/prn-clone-env）を作成した
- [ ] CLONE_ENV_INFO.md を作成・コミットした
- [ ] 設定ファイル（config.ini）を編集した
- [ ] 設定サンプル（config.clone-env.ini.example）を作成した
- [ ] リモートにプッシュした
- [ ] GitHub/GitLab でブランチを確認した
- [ ] .gitignore に機密情報が含まれていることを確認した

---

## 💡 便利な Git コマンド

### ブランチ管理

```bash
# すべてのブランチを表示（リモート含む）
git branch -a

# ブランチの切り替え
git checkout develop
git checkout dev/prn-clone-env

# ブランチの削除（ローカル）
git branch -d old-branch-name

# ブランチの削除（リモート）
git push origin --delete old-branch-name
```

### コミット管理

```bash
# 直前のコミットを修正
git commit --amend

# コミット履歴を表示
git log --oneline --graph --all

# 特定のファイルの履歴
git log -p app/config.ini.example
```

### リモート管理

```bash
# リモートの詳細表示
git remote show origin

# リモートのブランチ一覧
git branch -r

# リモートの最新情報を取得（マージしない）
git fetch origin
```

---

## 🎯 次のステップ

Git の設定が完了したら、次はアプリケーションの動作確認です：

1. **データベース接続確認**
   ```bash
   mysql -h db-dev.spapp-db.localdomain -u iizunaLMS -p
   ```

2. **Web サーバー起動確認**
   ```bash
   sudo systemctl status httpd
   ```

3. **ブラウザで動作確認**
   ```
   http://56.155.92.207
   ```

詳細は `docs/AWS複製環境セットアップ手順.md` の **ステップ 6** 以降を参照してください。

---

**Git 環境の整理、お疲れ様でした！これで安全に開発を進められます。** 🎉

