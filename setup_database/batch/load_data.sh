#!/bin/bash

set -u

if [ $# == 0 ]; then
    echo 'テーブルのプレフィックスを指定してください(例: TC10001)'
    exit 1
elif [ $# != 1 ]; then
    echo '引数は1つのみ指定してください'
    exit 1
fi

TABLE_PREFIX=$1

MYSQL_HOST="${MYSQL_HOST:-localhost}"
MYSQL_DATABASE="${MYSQL_DATABASE:-iizunaLMS}"
MYSQL_CNF_FILE="${MYSQL_CNF_FILE:-mysql-dbaccess.cnf}"

if ! command -v mysql >/dev/null 2>&1; then
    echo 'mysql コマンドが見つかりません。クライアントをインストールするか PATH を設定してください。'
    exit 127
fi

CMD_MYSQL="mysql --defaults-extra-file=${MYSQL_CNF_FILE} -h ${MYSQL_HOST} ${MYSQL_DATABASE}"

function ExecSQL()
{
    echo $1 | $CMD_MYSQL

    Ret=$?
    if [ $Ret -gt 0 ]; then
        echo "on error($Ret)"
        echo "FAILED"
        exit $Ret
    fi
}

function CreateTable()
{
    TABLE_NAME="$1_$2"

    if [ "$2" = "TC02" ]; then
      SQL="CREATE TABLE ${TABLE_NAME} (SYUBETUNO INTEGER UNSIGNED NOT NULL UNIQUE, NAME TEXT, RATE REAL, PRIMARY KEY (SYUBETUNO)) ENGINE=InnoDB;"
      ExecSQL "$SQL"
    fi

    if [ "$2" = "TC03" ]; then
      SQL="CREATE TABLE ${TABLE_NAME} (CHAPNO INTEGER UNSIGNED NOT NULL, SECNO INTEGER UNSIGNED NOT NULL UNIQUE, CHAPNAME TEXT, SECNAME TEXT, PRIMARY KEY (SECNO)) ENGINE=InnoDB;"
      ExecSQL "$SQL"
    fi

    if [ "$2" = "TC04" ]; then
      SQL="CREATE TABLE ${TABLE_NAME} (DAIMONNO INTEGER UNSIGNED NOT NULL UNIQUE, SORTNO INTEGER, BUN TEXT, PRIMARY KEY (DAIMONNO)) ENGINE=InnoDB;"
      ExecSQL "$SQL"
    fi

    if [ "$2" = "TC05" ]; then
      SQL="CREATE TABLE ${TABLE_NAME} (SYOMONNO INTEGER UNSIGNED NOT NULL UNIQUE, DAIMONNO INTEGER NOT NULL, SECNO INTEGER, SYUBETUNO INTEGER NOT NULL, SEQNO INTEGER, MIDASINO INTEGER, MIDASINAME TEXT, REVNO INTEGER, REVPNO INTEGER, LEVELNO INTEGER, FREQENCYNO INTEGER, BUN TEXT, PAGE INTEGER, ANSLENGTH INTEGER, ANSNUM INTEGER, ANSBUN TEXT, CHOICES TEXT, CHOICESNUM INTEGER, ANSWERFROM TEXT, FILENAME TEXT, ANSBUNFULL TEXT, COMMENT TEXT, SEARCHLABEL TEXT, PRIMARY KEY (SYOMONNO)) ENGINE=InnoDB;"
      ExecSQL "$SQL"
    fi

    if [ "$2" = "TC06" ]; then
      SQL="CREATE TABLE ${TABLE_NAME} (LEVELNO INTEGER UNSIGNED NOT NULL UNIQUE, NAME TEXT, PRIMARY KEY (LEVELNO)) ENGINE=InnoDB;"
      ExecSQL "$SQL"
    fi

    if [ "$2" = "TC07" ]; then
      SQL="CREATE TABLE ${TABLE_NAME} (FREQUENCYNO INTEGER UNSIGNED NOT NULL UNIQUE, NAME TEXT, PRIMARY KEY (FREQUENCYNO)) ENGINE=InnoDB;"
      ExecSQL "$SQL"
    fi

    if [ "$2" = "TC08" ]; then
      SQL="CREATE TABLE ${TABLE_NAME} (MIDASINO INTEGER UNSIGNED NOT NULL UNIQUE, NAME TEXT, PRIMARY KEY (MIDASINO)) ENGINE=InnoDB;"
      ExecSQL "$SQL"
    fi

    if [ "$2" = "other_answer" ]; then
      SQL="CREATE TABLE ${TABLE_NAME} (syomon_no INTEGER UNSIGNED NOT NULL, answer TEXT NOT NULL) ENGINE=InnoDB;"
      ExecSQL "$SQL"

      INDEX_TABLE_NAME="$1_other_answer"
      SQL="CREATE INDEX index_${INDEX_TABLE_NAME} ON ${INDEX_TABLE_NAME}(syomon_no);"
      ExecSQL "$SQL"
    fi

    if [ "$2" = "answer_index" ]; then
      SQL="CREATE TABLE ${TABLE_NAME} (syomon_no INTEGER UNSIGNED NOT NULL, answer_index INTEGER UNSIGNED NOT NULL) ENGINE=InnoDB;"
      ExecSQL "$SQL"

      INDEX_TABLE_NAME="$1_answer_index"
      SQL="CREATE INDEX index_${INDEX_TABLE_NAME} ON ${INDEX_TABLE_NAME}(syomon_no);"
      ExecSQL "$SQL"
    fi

    echo "$2 created."
}

function LoadData()
{
    FILE="$TABLE_PREFIX/$1.csv"

    # まずテーブル全削除
    SQL="DROP TABLE ${TABLE_PREFIX}_$1;"

    echo $SQL | $CMD_MYSQL

    Ret=$?
    if [ $Ret -gt 0 ]; then
        echo "${TABLE_PREFIX}_$1 is NONE."
    else
        echo "DROP TABLE ${TABLE_PREFIX}_$1"
    fi

    if [ ! -e $FILE ]; then
        echo "${FILE} not exists, SKIP."
        echo ""
        return
    fi

    CreateTable "$TABLE_PREFIX" "$1"

    # その後データロード
    SQL="LOAD DATA LOCAL INFILE '${FILE}' INTO TABLE ${TABLE_PREFIX}_$1 FIELDS TERMINATED BY ',' ENCLOSED BY '\"' LINES TERMINATED BY '\n' IGNORE 1 LINES;"

    echo $SQL | $CMD_MYSQL

    Ret=$?
    if [ $Ret -gt 0 ]; then
        echo "on error($Ret)"
        echo "FAILED"
        exit $Ret
    fi

    echo "${TABLE_PREFIX}_$1 loaded."
    echo ""
}

LoadData "TC02"
LoadData "TC03"
LoadData "TC04"
LoadData "TC05"
LoadData "TC06"
LoadData "TC07"
LoadData "TC08"
LoadData "other_answer"
LoadData "answer_index"

echo 'FINISH'
