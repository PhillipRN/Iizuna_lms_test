# SSH 設定ガイド（AWS 複製環境用）

## 📍 現在の状況

✅ .ssh ディレクトリ: `/Users/phillipr.n./.ssh/` （存在確認済み）  
✅ SSH 設定ファイル: `/Users/phillipr.n./.ssh/config` （存在確認済み）  
➡️ **次：AWS keypair の配置と SSH 設定の追加**

---

## ステップ 1: AWS キーペアを配置（5 分）

### 1-1. キーペアファイルを .ssh ディレクトリにコピー

AWS コンソールからダウンロードした `spapp-dev-keypair.pem` を .ssh ディレクトリに配置します：

```bash
# ダウンロードフォルダから .ssh ディレクトリにコピー
# （ダウンロードフォルダにある場合）
cp ~/Downloads/spapp-dev-keypair.pem ~/.ssh/

# または、別の場所にある場合
# cp /path/to/spapp-dev-keypair.pem ~/.ssh/
```

### 1-2. パーミッション設定（重要！）

```bash
# キーファイルのパーミッションを 600 に設定（必須）
chmod 600 ~/.ssh/spapp-dev-keypair.pem

# 確認
ls -la ~/.ssh/spapp-dev-keypair.pem
# 期待される出力: -rw------- （600）
```

**なぜ 600 が必要？**
- SSH はセキュリティのため、秘密鍵のパーミッションが厳しくチェックされます
- 600 = オーナーのみ読み書き可能
- それ以外のパーミッションだと SSH 接続が拒否されます

---

## ステップ 2: SSH 設定ファイルに AWS 環境を追加（5 分）

### 2-1. 現在の設定を確認

```bash
# 現在の設定を表示
cat ~/.ssh/config
```

**現在の設定**：
```ini
Host uplab
    HostName testcreator-iizuna-shoten.com
    User uplab
    IdentityFile ~/Documents/KUTO/SCAT/dev/iizuna-testcreator-main/docs/uplab.key
    StrictHostKeyChecking no
```

### 2-2. AWS 環境の設定を追加

```bash
# 設定ファイルをエディタで開く
nano ~/.ssh/config

# または
code ~/.ssh/config
```

**以下を末尾に追加**：

```ini
# =============================================================================
# いいずな LMS - AWS 開発環境
# =============================================================================

# 既存の開発環境（参照のみ・触らない）
Host spapp-dev
  Hostname 15.152.199.165
  User ec2-user
  IdentityFile ~/.ssh/spapp-dev-keypair.pem
  ServerAliveInterval 60
  ServerAliveCountMax 3

# 複製した開発環境（あなた専用）
Host spapp-dev-clone
  Hostname 56.155.92.207
  User ec2-user
  IdentityFile ~/.ssh/spapp-dev-keypair.pem
  ServerAliveInterval 60
  ServerAliveCountMax 3

# 本番環境の踏み台サーバー（慎重に扱う）
Host spapp-prod-bastion
  Hostname 15.168.23.66
  User ec2-user
  IdentityFile ~/.ssh/spapp-prod-keypair.pem
  ServerAliveInterval 60
  ServerAliveCountMax 3

# =============================================================================
# ONIGIRI 英単語 - AWS 開発環境
# =============================================================================

# ONIGIRI 開発環境
Host onigiri-dev
  Hostname 15.152.67.248
  User ec2-user
  IdentityFile ~/.ssh/onigiri-dev-keypair.pem
  ServerAliveInterval 60
  ServerAliveCountMax 3
```

**保存して閉じる**：
- nano: `Ctrl + X` → `Y` → `Enter`
- VSCode: `Cmd + S`

### 2-3. 設定オプションの説明

```ini
ServerAliveInterval 60       # 60秒ごとに生存確認パケットを送信
ServerAliveCountMax 3        # 3回応答がなければ切断
```

これらの設定により、接続が途切れにくくなります。

---

## ステップ 3: SSH 接続テスト（5 分）

### 3-1. 複製環境への接続テスト

```bash
# 新しい複製環境に接続
ssh spapp-dev-clone
```

**初回接続時のメッセージ**：

```
The authenticity of host '56.155.92.207 (56.155.92.207)' can't be established.
ECDSA key fingerprint is SHA256:xxxxxxxxxxxxxxxxxxxxx.
Are you sure you want to continue connecting (yes/no/[fingerprint])?
```

**`yes`** と入力して Enter

### 3-2. 接続成功の確認

接続できたら、以下のようなプロンプトが表示されます：

```bash
[ec2-user@ip-56-155-92-207 ~]$
```

**成功！** 🎉

### 3-3. 簡単な動作確認

```bash
# ホスト名確認
hostname

# アプリケーションディレクトリ確認
ls -la /var/www/iizuna_lms/

# 接続を切る
exit
```

---

## ステップ 4: SSH 接続のショートカット作成（オプション）

### 4-1. 便利なエイリアスを追加

ターミナルの設定ファイルにエイリアスを追加すると便利です：

```bash
# Bash の場合
echo "alias lms-clone='ssh spapp-dev-clone'" >> ~/.bash_profile
source ~/.bash_profile

# Zsh の場合（macOS 標準）
echo "alias lms-clone='ssh spapp-dev-clone'" >> ~/.zshrc
source ~/.zshrc
```

これで、`lms-clone` と入力するだけで接続できます：

```bash
lms-clone
```

---

## 🔐 セキュリティベストプラクティス

### キーペアの管理

```bash
# ✅ 推奨：.ssh ディレクトリに集約
~/.ssh/spapp-dev-keypair.pem
~/.ssh/spapp-prod-keypair.pem
~/.ssh/onigiri-dev-keypair.pem

# ❌ 非推奨：バラバラの場所に配置
~/Downloads/keypair.pem
~/Desktop/key.pem
```

### パーミッション確認コマンド

```bash
# すべてのキーファイルのパーミッションを確認
ls -la ~/.ssh/*.pem

# 期待される出力：
# -rw------- (600) が正しい
```

### バックアップ

```bash
# キーペアをバックアップ（暗号化推奨）
# 例：外付けドライブや暗号化USBメモリに保存
cp ~/.ssh/spapp-dev-keypair.pem /Volumes/Backup/ssh-keys/

# または暗号化zipで保存
zip -e ~/Backups/ssh-keys-$(date +%Y%m%d).zip ~/.ssh/*.pem
```

---

## 🐛 トラブルシューティング

### エラー 1: Permission denied (publickey)

**原因**: キーファイルのパーミッションが正しくない

**解決策**:
```bash
# パーミッションを修正
chmod 600 ~/.ssh/spapp-dev-keypair.pem

# 再接続
ssh spapp-dev-clone
```

### エラー 2: Connection timed out

**原因**: セキュリティグループで SSH(22 番ポート)が開いていない、または IP アドレスが間違っている

**解決策**:
```bash
# 1. IP アドレスを確認
cat ~/.ssh/config | grep -A 3 "spapp-dev-clone"

# 2. AWS コンソールでセキュリティグループ確認
# インバウンドルール: SSH (22) が 0.0.0.0/0 または あなたの IP に許可されているか
```

### エラー 3: WARNING: REMOTE HOST IDENTIFICATION HAS CHANGED!

**原因**: 同じ IP アドレスの別のサーバーに接続しようとしている

**解決策**:
```bash
# known_hosts から該当エントリを削除
ssh-keygen -R 56.155.92.207

# 再接続
ssh spapp-dev-clone
```

### エラー 4: No such file or directory

**原因**: キーペアファイルが指定した場所にない

**解決策**:
```bash
# キーファイルの場所を確認
ls -la ~/.ssh/spapp-dev-keypair.pem

# ない場合は、正しい場所からコピー
cp /path/to/actual/key.pem ~/.ssh/spapp-dev-keypair.pem
chmod 600 ~/.ssh/spapp-dev-keypair.pem
```

---

## 📊 SSH 接続情報まとめ

### あなたの環境

| 項目 | 値 |
|------|-----|
| **ホスト名（config）** | `spapp-dev-clone` |
| **IP アドレス** | `56.155.92.207` |
| **ユーザー名** | `ec2-user` |
| **キーペアファイル** | `~/.ssh/spapp-dev-keypair.pem` |
| **接続コマンド** | `ssh spapp-dev-clone` |
| **インスタンス名** | `spapp-dev-clone-20250103-prn` |
| **インスタンス ID** | `i-0601d7c3565281158` |

### 既存の開発環境（参照のみ）

| 項目 | 値 |
|------|-----|
| **ホスト名（config）** | `spapp-dev` |
| **IP アドレス** | `15.152.199.165` |
| **接続コマンド** | `ssh spapp-dev` |
| **注意** | **触らない！** |

---

## ✅ セットアップ完了チェックリスト

以下がすべて ✅ になれば SSH 設定完了：

- [ ] `.ssh` ディレクトリが存在する
- [ ] `spapp-dev-keypair.pem` を `.ssh` ディレクトリにコピーした
- [ ] キーファイルのパーミッションが 600 になっている
- [ ] `~/.ssh/config` に AWS 環境の設定を追加した
- [ ] `ssh spapp-dev-clone` で接続できる
- [ ] `hostname` コマンドで正しいサーバー名が表示される
- [ ] `/var/www/iizuna_lms/` ディレクトリが確認できる

---

## 🎯 次のステップ

SSH 接続ができたら、次は **AWS 複製環境セットアップ手順.md** の **ステップ 4** に進んでください：

```bash
# アプリケーションディレクトリの確認
cd /var/www/iizuna_lms/
ls -la
git branch
```

---

## 💡 便利なコマンド集

### SSH 接続関連

```bash
# 複製環境に接続
ssh spapp-dev-clone

# 接続状態でコマンド実行（接続せずに）
ssh spapp-dev-clone "uptime"
ssh spapp-dev-clone "cat /var/www/iizuna_lms/app/config.ini"

# ファイル転送（ローカル → リモート）
scp local-file.txt spapp-dev-clone:/tmp/

# ファイル転送（リモート → ローカル）
scp spapp-dev-clone:/var/www/iizuna_lms/app/config.ini ./

# ディレクトリ転送（再帰的）
scp -r local-dir spapp-dev-clone:/tmp/
```

### SSH 設定確認

```bash
# 現在の SSH 設定を表示
cat ~/.ssh/config

# 特定のホストの設定のみ表示
cat ~/.ssh/config | grep -A 5 "spapp-dev-clone"

# SSH 接続のデバッグ（接続がうまくいかない場合）
ssh -v spapp-dev-clone
ssh -vv spapp-dev-clone  # より詳細
ssh -vvv spapp-dev-clone # 最も詳細
```

### キーペア管理

```bash
# すべてのキーファイル一覧
ls -la ~/.ssh/*.pem

# キーファイルのパーミッション一括修正
chmod 600 ~/.ssh/*.pem

# キーファイルのフィンガープリント確認
ssh-keygen -l -f ~/.ssh/spapp-dev-keypair.pem
```

---

**SSH 設定が完了したら、次は AWS 複製環境のアプリケーション設定に進みましょう！**

