#!/bin/bash

if [ $# == 0 ]; then
    echo 'title_noを指定してください(例: 10001)'
    exit 1
elif [ $# != 1 ]; then
    echo '引数は1つのみ指定してください'
    exit 1
fi

TITLE_NO=$1

MYSQL_HOST="localhost"
MYSQL_DATABASE="iizunaLMS"

CMD_MYSQL="mysql --defaults-extra-file=mysql-dbaccess.cnf -h ${MYSQL_HOST} ${MYSQL_DATABASE}"

function ExecSQL()
{
    echo $1 | $CMD_MYSQL

    Ret=$?
    if [ $Ret -gt 0 ]; then
        echo "on error($Ret)"
        echo "FAILED"
        exit
    fi
}

function LoadData()
{
    FILE="$TITLE_NO/$1.csv"

    # ファイルがない場合は何もしない
    if [ ! -e $FILE ]; then
        echo "${FILE} not exists, SKIP."
        echo ""
        return
    fi

    # 先に入っているデータを削除
    SQL="DELETE FROM $1 WHERE title_no = $TITLE_NO;"

    echo $SQL | $CMD_MYSQL

    Ret=$?
    if [ $Ret -gt 0 ]; then
        echo "on error($Ret)"
        echo "FAILED"
        exit
    else
        echo "DELETE $TITLE_NO RECORDS FROM $1"
    fi

    # データロード
    SQL="LOAD DATA LOCAL INFILE '${FILE}' INTO TABLE $1 FIELDS TERMINATED BY ',' ENCLOSED BY '\"' LINES TERMINATED BY '\n' IGNORE 1 LINES;"

    echo $SQL | $CMD_MYSQL

    Ret=$?
    if [ $Ret -gt 0 ]; then
        echo "on error($Ret)"
        echo "FAILED"
        exit
    fi

    echo "$1 loaded."
    echo ""
}

LoadData "ebook_example"
LoadData "ebook_quiz"

echo 'FINISH'