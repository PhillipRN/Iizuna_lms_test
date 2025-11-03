# AWS開発環境の複製手順

## 🎯 目的

既存の開発環境（spapp-dev-ec2）を**触らずに**、あなた専用の検証環境を作成します。

---

## 📋 複製方法の選択肢

### **方法A: EC2インスタンスのAMI作成（推奨）** ⭐

既存の開発サーバーからAMI（Amazon Machine Image）を作成し、新しいインスタンスを起動します。

#### メリット
✅ 既存環境と完全に同じ構成  
✅ 設定済みの環境がそのままコピーされる  
✅ 最も早く構築できる（30分程度）  

#### デメリット
❌ AWS管理コンソールへのアクセス権限が必要  
❌ 月額費用が発生（EC2 t3.small で約$20/月）  

#### 手順

##### 1. 既存開発環境のAMI作成

**AWSコンソール > EC2 > インスタンス**

1. `spapp-dev-ec2` インスタンスを選択
2. **アクション** → **イメージとテンプレート** → **イメージを作成**
3. AMI設定:
   ```
   イメージ名: spapp-dev-clone-20250103-yourname
   イメージの説明: 開発環境複製（検証用）
   再起動なし: ✓（本番稼働中の場合）
   ```
4. **イメージを作成** をクリック
5. AMI作成完了まで待機（5-10分）

##### 2. セキュリティグループの複製

1. **EC2 > セキュリティグループ**
2. `spapp-dev-ec2` のセキュリティグループを確認
3. **アクション** → **セキュリティグループをコピー**
4. 名前を変更: `spapp-dev-clone-sg`

##### 3. 新しいインスタンスの起動

1. **EC2 > AMI** で作成したAMIを選択
2. **AMIから起動** をクリック
3. インスタンス設定:
   ```
   名前: spapp-dev-clone-yourname
   インスタンスタイプ: t3.small（または既存と同じ）
   キーペア: spapp-dev-keypair（既存と同じ）
   セキュリティグループ: spapp-dev-clone-sg
   ストレージ: 20GB（既存と同じ）
   ```
4. **インスタンスを起動**

##### 4. SSH接続設定

`~/.ssh/config` に追加:

```ini
Host spapp-dev-clone
  Hostname <新しいインスタンスのIPアドレス>
  User ec2-user
  IdentityFile ~/.ssh/spapp-dev-keypair.pem
```

##### 5. 接続確認

```bash
ssh spapp-dev-clone

# アプリケーションディレクトリ確認
cd /var/www/iizuna_lms/
ls -la
```

##### 6. 設定ファイルの修正

複製したインスタンスの設定を開発用に変更:

```bash
# 設定ファイル編集
sudo vim /var/www/iizuna_lms/app/config.ini

# 変更が必要な項目:
# - DEBUG_MODE = 1（デバッグモード有効）
# - DISPLAY_ERROR_ALL = 1（エラー表示）
# - WWW_ROOT_URL（新しいインスタンスのURL）
```

##### 7. データベース接続の確認

```bash
# MySQL接続テスト
mysql -h db-dev.spapp-db.localdomain -u iizunaLMS -p iizunaLMS

# または独自のRDSを作成する場合は後述
```

---

### **方法B: 独立したDB付き完全複製環境**

EC2だけでなく、RDSも複製して完全に独立した環境を作ります。

#### メリット
✅ 既存DBに一切影響なし  
✅ 完全に自由にテストできる  
✅ 本番環境の練習に最適  

#### デメリット
❌ セットアップに時間がかかる（1-2時間）  
❌ 費用が高い（EC2 + RDS で約$50/月）  

#### 手順

##### 1. RDSスナップショットから復元

**RDSコンソール > スナップショット**

1. 既存の開発DB（`db-dev.spapp-db.localdomain`）のスナップショットを作成
2. スナップショットから復元:
   ```
   DBインスタンス識別子: iizuna-lms-dev-clone
   インスタンスクラス: db.t3.micro
   VPC: 既存と同じ
   セキュリティグループ: 既存と同じ
   ```

##### 2. EC2インスタンスの作成（方法Aと同じ）

方法Aの手順1-4を実施

##### 3. 新しいRDSへの接続設定

```bash
ssh spapp-dev-clone

# 設定ファイル編集
sudo vim /var/www/iizuna_lms/app/config.ini

# DB接続先を変更
DB_HOST = iizuna-lms-dev-clone.xxxxxxxx.ap-northeast-3.rds.amazonaws.com
DB_NAME = iizunaLMS
DB_USER = iizunaLMS
DB_PASS = Gawbvgt2f983mru
```

##### 4. 動作確認

```bash
# MySQL接続確認
mysql -h iizuna-lms-dev-clone.xxxxxxxx.ap-northeast-3.rds.amazonaws.com -u iizunaLMS -p

# アプリケーション動作確認
curl http://localhost/
```

---

### **方法C: AWS Lightsail（簡易版）**

AWSの簡易サービスで、より手軽に環境を作成します。

#### メリット
✅ シンプルな管理画面  
✅ 固定料金（月額$10-20）  
✅ セットアップが簡単  

#### デメリット
❌ 既存環境と構成が異なる  
❌ ゼロから構築が必要  

#### 手順

1. **Lightsailコンソール**にアクセス
2. **インスタンスを作成**
3. プラットフォーム: **Linux/Unix**
4. 設計図: **PHP 8.2**
5. プラン: **$20/月（2GB RAM）**
6. インスタンス名: `iizuna-lms-dev-clone`
7. SSH接続してアプリケーションをデプロイ

---

## 💰 コスト比較

| 方法 | 初期費用 | 月額費用 | セットアップ時間 |
|------|---------|---------|----------------|
| **ローカルDocker** | $0 | $0 | 5分 |
| **方法A（EC2のみ）** | $0 | ~$20 | 30分 |
| **方法B（EC2+RDS）** | $0 | ~$50 | 1-2時間 |
| **方法C（Lightsail）** | $0 | $10-20 | 1時間 |

---

## 🎯 推奨フロー

### **段階1: ローカルDocker（今すぐ）**
```bash
make setup
```
- 費用: ゼロ
- リスク: ゼロ
- 学習: システムの基本理解

### **段階2: AWS環境の理解（1-2週間後）**
- 既存の`spapp-dev-ec2`のドキュメント確認
- ReadMe.mdの本番デプロイ手順を読む
- AWS構成図を作成

### **段階3: 必要に応じてAWS複製（機能追加前）**
- 方法Aでサクッと複製
- 本番と同じ環境で最終テスト

---

## ⚠️ 重要な注意事項

### AWS権限の確認

以下の権限が必要です：
- EC2インスタンスの作成・管理
- AMIの作成
- （方法Bの場合）RDSの作成・スナップショット
- セキュリティグループの管理

権限がない場合は、AWS管理者に相談してください。

### 費用の管理

```bash
# 使わない時は停止してコスト削減
aws ec2 stop-instances --instance-ids i-xxxxxxxxx

# 再開
aws ec2 start-instances --instance-ids i-xxxxxxxxx
```

停止中は**ストレージ費用のみ**（月額$2程度）。

### タグの設定

リソースには必ずタグを付けて識別可能に：

```
Name: spapp-dev-clone-yourname
Environment: Development
Purpose: Learning/Testing
Owner: yourname
AutoStop: true  # 自動停止対象
```

---

## 🔍 既存環境を触らない確認チェックリスト

作業前に必ず確認：

- [ ] 作業対象が**新しいインスタンス**である
- [ ] 既存の`spapp-dev-ec2`には**接続していない**
- [ ] データベースが**複製版**または**別のRDS**である
- [ ] 設定ファイルのURLが**新しいインスタンスのもの**である

---

## 🛡️ 安全な学習のために

### ローカルDocker環境を最優先

理由：
1. **完全にリスクゼロ**
2. コストゼロ
3. 何度でもやり直せる
4. システムの理解に最適

### AWS複製は「必要になってから」

以下の場合のみAWS複製を検討：
- ローカル環境で動作確認済み
- Firebase等の外部サービス連携のテストが必要
- 本番デプロイ前の最終確認

---

## 📞 サポート

### AWS権限がない場合

1. AWS管理者に以下を依頼：
   ```
   「開発環境の複製インスタンスを作成したいです。
   既存のspapp-dev-ec2からAMIを作成し、
   新しいインスタンスを起動する権限が必要です」
   ```

2. または管理者に複製を依頼：
   ```
   「spapp-dev-ec2のAMIから
   spapp-dev-clone-[yourname]という
   インスタンスを作成していただけますか？」
   ```

### 費用が心配な場合

**まずはローカルDocker環境のみ**で十分です。

本番デプロイ前の最終確認でのみAWS環境を使用すれば、
費用を最小限に抑えられます。

---

## ✅ まとめ

### 今すぐできること

```bash
cd /Users/phillipr.n./Documents/KUTO/いいずな/iizuna_apps_dev/iizuna-lms-main
make setup
```

### 将来的な選択肢

1. **ローカルDocker**で基礎を学ぶ（推奨・今すぐ）
2. 必要に応じて**AWS環境を複製**（数週間後）
3. 本番デプロイ前に**既存の開発環境**で最終確認

**焦らず、段階的に進めることが成功の鍵です。**

