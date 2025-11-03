#!/bin/bash

# DynamoDB Localにテーブルを作成

AWS_ENDPOINT="http://localhost:8000"
AWS_REGION="ap-northeast-1"

echo "=========================================="
echo "DynamoDB Local テーブル作成スクリプト"
echo "=========================================="

# AWS CLI が利用可能か確認
if ! command -v aws &> /dev/null; then
    echo "❌ AWS CLI がインストールされていません"
    echo "インストール方法: https://aws.amazon.com/cli/"
    exit 1
fi

echo "✓ AWS CLI が利用可能です"
echo ""

# DynamoDB Local が起動しているか確認
if ! curl -s $AWS_ENDPOINT > /dev/null 2>&1; then
    echo "❌ DynamoDB Local に接続できません"
    echo "Docker環境を起動してください: docker-compose up -d"
    exit 1
fi

echo "✓ DynamoDB Local に接続できました"
echo ""

# Access Token テーブル
echo "📦 Access Token テーブルを作成中..."
aws dynamodb create-table \
    --table-name dev-access-token \
    --attribute-definitions \
        AttributeName=access_token,AttributeType=S \
    --key-schema \
        AttributeName=access_token,KeyType=HASH \
    --provisioned-throughput \
        ReadCapacityUnits=5,WriteCapacityUnits=5 \
    --endpoint-url $AWS_ENDPOINT \
    --region $AWS_REGION \
    2>/dev/null && echo "✓ dev-access-token 作成完了" || echo "⚠ dev-access-token は既に存在します"

# TTL設定
aws dynamodb update-time-to-live \
    --table-name dev-access-token \
    --time-to-live-specification "Enabled=true, AttributeName=ttl" \
    --endpoint-url $AWS_ENDPOINT \
    --region $AWS_REGION \
    2>/dev/null || true

# Login Token テーブル
echo "📦 Login Token テーブルを作成中..."
aws dynamodb create-table \
    --table-name dev-login-token \
    --attribute-definitions \
        AttributeName=login_token,AttributeType=S \
    --key-schema \
        AttributeName=login_token,KeyType=HASH \
    --provisioned-throughput \
        ReadCapacityUnits=5,WriteCapacityUnits=5 \
    --endpoint-url $AWS_ENDPOINT \
    --region $AWS_REGION \
    2>/dev/null && echo "✓ dev-login-token 作成完了" || echo "⚠ dev-login-token は既に存在します"

# TTL設定
aws dynamodb update-time-to-live \
    --table-name dev-login-token \
    --time-to-live-specification "Enabled=true, AttributeName=ttl" \
    --endpoint-url $AWS_ENDPOINT \
    --region $AWS_REGION \
    2>/dev/null || true

# Auto Login Token テーブル
echo "📦 Auto Login Token テーブルを作成中..."
aws dynamodb create-table \
    --table-name dev-auto-login-token \
    --attribute-definitions \
        AttributeName=auto_login_token,AttributeType=S \
    --key-schema \
        AttributeName=auto_login_token,KeyType=HASH \
    --provisioned-throughput \
        ReadCapacityUnits=5,WriteCapacityUnits=5 \
    --endpoint-url $AWS_ENDPOINT \
    --region $AWS_REGION \
    2>/dev/null && echo "✓ dev-auto-login-token 作成完了" || echo "⚠ dev-auto-login-token は既に存在します"

# TTL設定
aws dynamodb update-time-to-live \
    --table-name dev-auto-login-token \
    --time-to-live-specification "Enabled=true, AttributeName=ttl" \
    --endpoint-url $AWS_ENDPOINT \
    --region $AWS_REGION \
    2>/dev/null || true

echo ""
echo "=========================================="
echo "✅ DynamoDB Local セットアップ完了!"
echo "=========================================="
echo ""
echo "作成されたテーブル一覧:"
aws dynamodb list-tables --endpoint-url $AWS_ENDPOINT --region $AWS_REGION 2>/dev/null

echo ""
echo "DynamoDB Local 管理画面: http://localhost:8000"

