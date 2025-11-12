#!/bin/bash
if [ -z "$1" ]; then
  echo "Usage: $0 <TC番号 (例: TC10061)>"
  exit 1
fi
PREFIX=$1
TABLES=(TC02 TC03 TC04 TC05 TC06 TC07 TC08 other_answer answer_index)
SQL=""
for table in "${TABLES[@]}"; do
  SQL+="DROP TABLE IF EXISTS ${PREFIX}_${table};\n"
  SQL+="DROP TABLE IF EXISTS ${PREFIX}_${table}_backup;\n"
  SQL+="DROP TABLE IF EXISTS ${PREFIX}_${table}_tmp;\n"
  SQL+="DROP TABLE IF EXISTS ${PREFIX}_${table}_dev;\n"
fi
cat <<SQL | docker compose exec -T mysql-iizuna mysql -u iizunaLMS -pGawbvgt2f983mru iizunaLMS
${SQL}
SQL
