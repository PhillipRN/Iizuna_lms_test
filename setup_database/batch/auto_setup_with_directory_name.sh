#!/bin/bash

dirs=`find ./ -maxdepth 1 -type d`

for dir in $dirs;
do
    # echo $dir
    
    dirName=${dir/.\//}

    if [ -z "$dirName" ]; then
        continue
    fi
    
    echo "$dirName START"
    
    # ここから実行処理を記述
    ./load_data.sh $dirName
done