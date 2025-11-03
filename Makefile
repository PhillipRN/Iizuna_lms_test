# いいずなLMS - 開発環境 Makefile

.PHONY: help setup up down restart logs ps shell mysql test clean

help: ## このヘルプメッセージを表示
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-20s\033[0m %s\n", $$1, $$2}'

setup: ## 初回セットアップ（設定ファイル作成 + Docker起動 + DB初期化）
	@echo "📦 初回セットアップを開始します..."
	@if [ ! -f app/config.ini ]; then \
		cp app/config.docker.ini app/config.ini; \
		echo "✓ app/config.ini を作成しました"; \
	else \
		echo "⚠ app/config.ini は既に存在します"; \
	fi
	@if [ ! -f app/token.ini ]; then \
		cp app/token.ini.example app/token.ini; \
		chmod 666 app/token.ini; \
		echo "✓ app/token.ini を作成しました"; \
	else \
		echo "⚠ app/token.ini は既に存在します"; \
	fi
	@echo "🚀 Docker環境を起動します..."
	docker-compose up -d
	@echo "⏳ データベースの起動を待機中..."
	@sleep 10
	@echo "📊 DynamoDBテーブルを作成します..."
	@chmod +x scripts/setup-dynamodb-local.sh
	./scripts/setup-dynamodb-local.sh
	@echo ""
	@echo "✅ セットアップ完了!"
	@echo ""
	@echo "📍 アクセスURL:"
	@echo "  - アプリケーション: http://localhost:8080"
	@echo "  - phpMyAdmin:       http://localhost:8081"
	@echo "  - MailHog:          http://localhost:8025"
	@echo ""
	@echo "🔑 管理者ログイン: admin / admin123"

up: ## Docker環境を起動
	docker-compose up -d

down: ## Docker環境を停止
	docker-compose down

restart: ## Docker環境を再起動
	docker-compose restart

logs: ## ログを表示（Ctrl+Cで終了）
	docker-compose logs -f

ps: ## コンテナの状態を確認
	docker-compose ps

shell: ## アプリケーションコンテナに入る
	docker exec -it iizuna-lms-app bash

mysql: ## MySQLに接続
	docker exec -it iizuna-lms-db mysql -u iizunaLMS -pGawbvgt2f983mru iizunaLMS

mysql-onigiri: ## ONIGIRIデータベースに接続
	docker exec -it iizuna-onigiri-db mysql -u onigiri -ponigiri_pass onigiri

test: ## PHPUnitテストを実行
	docker exec -it iizuna-lms-app ./vendor/phpunit/phpunit/phpunit

composer-install: ## Composer依存関係をインストール
	docker exec -it iizuna-lms-app composer install

composer-update: ## Composer依存関係を更新
	docker exec -it iizuna-lms-app composer update

composer-dump: ## Composer autoloadを再生成
	docker exec -it iizuna-lms-app composer dump-autoload

dynamodb-setup: ## DynamoDBテーブルを作成
	./scripts/setup-dynamodb-local.sh

clean: ## 環境を完全削除（データも削除）
	@echo "⚠️  警告: すべてのコンテナ、ボリューム、データが削除されます"
	@read -p "本当に削除しますか? [y/N] " yn; \
	case "$$yn" in [yY]*) ;; *) echo "キャンセルしました"; exit 1 ;; esac
	docker-compose down -v
	rm -f app/config.ini app/token.ini
	@echo "✓ 環境を削除しました"

rebuild: ## Dockerイメージを再ビルド
	docker-compose build --no-cache
	docker-compose up -d

status: ## システムの状態を確認
	@echo "📊 システム状態:"
	@echo ""
	@echo "🐳 Dockerコンテナ:"
	@docker-compose ps
	@echo ""
	@echo "🔍 DynamoDBテーブル:"
	@aws dynamodb list-tables --endpoint-url http://localhost:8000 --region ap-northeast-1 2>/dev/null || echo "  ⚠ AWS CLIがインストールされていないか、DynamoDBが起動していません"
	@echo ""
	@echo "📍 アクセスURL:"
	@echo "  - アプリケーション: http://localhost:8080"
	@echo "  - phpMyAdmin:       http://localhost:8081"
	@echo "  - MailHog:          http://localhost:8025"

