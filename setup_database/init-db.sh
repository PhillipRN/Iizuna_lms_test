#!/bin/bash
set -e

echo "Initializing iizunaLMS database..."

# database.txtからテーブル作成
if [ -f /var/www/html/database.txt ]; then
    mysql -u root -p${MYSQL_ROOT_PASSWORD} ${MYSQL_DATABASE} < /var/www/html/database.txt
    echo "Base tables created from database.txt"
fi

# マイグレーションファイルを順次実行
MIGRATION_DIR="/var/www/html/setup_database"
if [ -d "$MIGRATION_DIR" ]; then
    for migration_file in $(ls -1 $MIGRATION_DIR/*.txt 2>/dev/null | sort); do
        echo "Applying migration: $(basename $migration_file)"
        mysql -u root -p${MYSQL_ROOT_PASSWORD} ${MYSQL_DATABASE} < "$migration_file" || true
    done
fi

echo "Database initialization complete!"

