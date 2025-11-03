<style>
blockquote {
	background: rgba(127, 127, 127, 0.1);
	border-color: rgba(0, 204, 61, 0.5);
}
</style>

## サーバーURL

<table>
<tr>
<td>本番サーバー ベースURL</td><td>https://iizuna-lmstc.com/</td>
</tr>
<tr>
<td>開発サーバー ベースURL</td><td>https://spapp-dev.iizuna-lms.com/</td>
</tr>
</table>

---

## 各種トークン有効期限

| 種別 | 有効期限 |
| --- | --- |
| リフレッシュトークン | 無期限 |
| アクセストークン | 24時間 |
| ログイントークン | 1分 |

---

## ユーザー認証＆リフレッシュトークン取得
> [POST] /student/authorization.php

### パラメータ
| パラメータ | 必須 | 概要 |
| --- | --- | --- |
| authorization_key | ○ | 認証キー |

### 結果サンプル
<pre>
{
    "result":"OK",
    "refresh_token":"gufJ:_2qA[vu@VN+$4%]Xb)>;<8?=1;T"
}
</pre>

---

## ユーザー認証完了
認証キーをデータベースから削除する
> [POST] /student/authorization_finish.php

### パラメータ
| パラメータ | 必須 | 概要 |
| --- | --- | --- |
| authorization_key | ○ | 認証キー |

### 結果サンプル
<pre>
{
    "result":"OK"
}
</pre>

---

## アクセストークン取得
> [POST] /student/access_token_generate.php

### パラメータ
| パラメータ | 必須 | 概要 |
| --- | --- | --- |
| refresh_token | ○ | リフレッシュトークン |

### 結果サンプル
<pre>
{
    "result":"OK",
    "access_token":"%7%F!Vn}Y0<#({IL13.:%lSgjKi}da|F"
}
</pre>

---

## ログイントークン取得
> [POST] /student/login_generate_token.php

### パラメータ
| パラメータ | 必須 | 概要 |
| --- | --- | --- |
| access_token | ○ | アクセストークン |

### 結果サンプル
<pre>
{
    "result":"OK",
    "login_token":"1jZvftliZUd6QY8PPmjTtJm9uap_0ySl"
}
</pre>

---

## ログイン＆トップページ表示
> [GET/POST] /student/login_by_token.php

リダイレクトしてトップページのHTMLコンテンツを返す

### パラメータ
| パラメータ | 必須 | 概要 |
| --- | --- | --- |
| lt | ○ | ログイントークン |

### URL例
<pre>https://spapp-dev.iizuna-lms.com/student/login_by_token.php?lt=1jZvftliZUd6QY8PPmjTtJm9uap_0ySl</pre>

### 結果サンプル
<pre>
トップページのHTMLコンテンツ
</pre>

---

## 問題取得
> [POST] /ebook/?m=quiz

### リクエストパラメータ
| パラメータ | 必須 | 形式 | 概要 |
| --- | --- | --- | --- |
| t | ○ | int | 書籍ID |
| p | ※1 | int | ページ |
| g | ※1 | int | 問題ジャンル<br>複数指定する場合は「_」で繋ぎ、「1_2」のように指定する<br>1: 参考書そのままの問題<br>2: 参考書から改題した問題 |
| k | ※2 | int | 問題の種類<br>1: チェック問題<br>2: ターゲット例文問題<br>3: 章末問題・チャレンジ問題 |
| c | ※2 | int | 章・節<br>4-1-2 のように「-」繋ぎで指定する。<br>複数指定する場合は、さらにそれぞれを「_」で繋ぎ、 1_2-1_3-2-1_4-3-2-1 のように指定する。 |
| n | ※2 | int | 問題数 |
| i | ※2 | int | 入力問題あり/なし<br>1: 入力問題あり（デフォルト）<br>0: 入力問題なし |
| r | | int | ランダム<br>1: ランダム（デフォルト）<br>0: 問題順 |

※1 参考書内の「問題あり」のところはページと問題ジャンルを指定する。<br>
※2 「問題を解く」の場合はこれらのパラメータを指定してする。

#### 書籍ID
| 値 | 概要 |
| --- | --- |
| 10052 | 総合英語 Evergreen |
| 10086 | 総合英語 be 4th Edition |
| 10093 | 総合英語 Harmony New Edition |
| 99052 | Sample 総合英語 Evergreen |
| 99086 | Sample 総合英語 be 4th Edition |
| 99093 | Sample 総合英語 Harmony New Edition |

### 補足
* 問題数が不足している場合でも、問題は返すようにします。
* 問題はグループごとにまとめて返します。
* 問題文内に &lt;zz&gt; がある場合、その箇所が入力欄になります。

### 結果サンプル
<pre>
{
    "result" : [
        {
            "header":"現在完了形にしなさい。",
            "questions": [
                {
                    // id
                    "id":"3040001",

                    // 問題形式(1選択問題,2整序問題,3入力問題)
                    "type":"3",

                    // 設問1
                    "body":"I ( finish ) my work. So I can go shopping.",
                    
                    // 設問2
                    "subBody":"",

                    // 選択肢
                    "choices":[],

                    // 解答
                    "answers":[
                        "have finished"
                    ],

                    // 解答大文字判定フラグ
                    // 0:無効,1:整序問題・入力問題で解答の先頭が大文字になる
                    "is_upper_case_judgement":"0",

                    // 音声ファイル名
                    "voice_file":"",

                    // （入試問題）大学名
                    "entrance_exam_college_name" => "〇〇大学",

                    // （入試問題）学部・他情報
                    "entrance_exam_faculty" => "〇〇学部",

                    // （入試問題）出題年度
                    "entrance_exam_year" => "〇〇",

                    // subBody フラグ
                    "subBody_flag" => 0,

                    // 入力欄外側フラグ
                    "outer_input_field_flag" => 1,

                    // html タグが含まれているフラグ
                    "include_html_flag" => 0
                }
            ],
        },
        {
            "header":"適切なものを選びなさい。",
            "questions": [
                {
                    "id":"3040008",
                    "type":"1",
                    "body":"「かぎを見つけましたか？」「いいえ，まだです。」",
                    "subBody":"“(　　) your keys?” “No, not yet.”",
                    "choices":[
                        "Did you find",
                        "Have you found"
                    ],
                    "answers":[
                        "Have you found"
                    ],
                    "is_upper_case_judgement":"0",
                    "voice_file":"",
                    "entrance_exam_college_name" => "〇〇大学",
                    "entrance_exam_faculty" => "〇〇学部",
                    "entrance_exam_year" => "〇〇",
                    "subBody_flag" => 1,
                    "outer_input_field_flag" => 1,
                    "include_html_flag" => 0
                },
                {
                    ...
                }
            ],
        },
        {
            "header":"語句を並びかえて英文を完成させなさい。ただし，不要な語（句）が1つある。",
            "questions": [
                {
                    "id":"3040020",
                    "type":"2",
                    "body":"どのくらい勉強していたんですか。",
                    "subBody":"How long (　　)?",
                    "choices":[
                        "studying",
                        "been",
                        "have",
                        "studied",
                        "you"
                    ],
                    "answers":[
                        "have you been studying"
                    ],
                    "is_upper_case_judgement":"0",
                    "voice_file":"",
                    "entrance_exam_college_name" => "〇〇大学",
                    "entrance_exam_faculty" => "〇〇学部",
                    "entrance_exam_year" => "〇〇",
                    "subBody_flag" => 1,
                    "outer_input_field_flag" => 1,
                    "include_html_flag" => 0
                },
                {
                    ...
                }
            ],
        },
        {
            "header":"英文を完成させなさい。",
            "questions": [
                {
                    "id":"3040011",
                    "type":"3",
                    "body":"私はまだクリスマスカードを書いていません。",
                    "subBody":"I &lt;zz&gt; not &lt;zz&gt; my Christmas cards yet.",
                    "choices":[],
                    "answers":[
                        "have written"
                    ],
                    "is_upper_case_judgement":"0",
                    "voice_file":"",
                    "entrance_exam_college_name" => "〇〇大学",
                    "entrance_exam_faculty" => "〇〇学部",
                    "entrance_exam_year" => "〇〇",
                    "subBody_flag" => 1,
                    "outer_input_field_flag" => 0,
                    "include_html_flag" => 0
                },
                {
                    ...
                }
            ],
        },

        ...

    ],
    "error": { // エラーが発生している場合は error が返ってきます
        "code":"30102",
        "message":"問題数が不足しています"
    }
}
</pre>

---

## 今日の5問
> [POST] /ebook/?m=quiz_today

### リクエストパラメータ
| パラメータ | 必須 | 形式 | 概要 |
| --- | --- | --- | --- |
| t | ○ | string | 書籍ID<br>複数の書籍を指定する場合は「_」で繋ぎ、「10052_10053_10054」のように指定する  |
| l | ○ | int | 難しさ<br>1: やさしい<br>2: 普通<br>3: 難しい  |

※ API 側ではどの書籍を持っているか識別できないため、必要な書籍IDをすべて渡してください。

### 補足
* 問題数が不足している場合でも、問題は返すようにします。
* 問題はグループごとにまとめて返します。
* 問題文内に &lt;zz&gt; がある場合、その箇所が入力欄になります。

### 結果サンプル
<pre>
問題取得(quiz)と同様
</pre>

---

## 問題・音声のあるページ情報取得
> [GET/POST] /ebook/?m=ebook_information

### リクエストパラメータ
| パラメータ | 必須 | 形式 | 概要 |
| --- | --- | --- | --- |
| t | ○ | int | 書籍ID |

### 結果サンプル
<pre>
{
    "result" : [
        {
            // 問題のあるページ一覧
            "quizzes":[88,90,91,93,94,95,97,98,99,100,102,103,104],

            // 音声のあるページ一覧
            "voices":[88,90,91,93,94,95,97,98,99,100,102,103,104]
        }
    ]
}
</pre>

---

## フラッシュカード取得（例文を覚える）
> [POST] /ebook/?m=flash_card

### リクエストパラメータ
| パラメータ | 必須 | 形式 | 概要 |
| --- | --- | --- | --- |
| t | ○ | int | 書籍ID |
| c | ○ | string | 章<br>範囲指定する場合は「_」で繋ぎ、「1_3」のように指定する |
| n | | int | 問題数<br>指定がない場合は対象範囲の全データ |
| nt_ja | | int | 和文タグなしフラグ<br>0: タグあり<br>1: タグなし |
| nt_en | | int | 英文タグなしフラグ<br>0: タグあり<br>1: タグなし |
| r | | int | ランダム<br>1: ランダム（デフォルト）<br>0: 問題順 |

※ 形式の「フラッシュカード」、「テスト形式」や、「英→日」、「日→英」の指定はAPIにはありません。英文と和文両方の値が返されるので、アプリ側にて表示の出し分けをお願いします。

### 補足
* 問題数が不足している場合でも、問題は返すようにします。

### 結果サンプル
<pre>
{
    "result" : [
        {
            // id
            "id":"10086",

            // 英文
            "en":"I have known Paul since we were children.",

            // 和文
            "ja":"子どものころからポールとは知り合いだ。",

            // 音声ファイル名
            "voice_file":"Evergreen_86"
        },

        ...

    ],
    "error": { // エラーが発生している場合は error が返ってきます
        "code":"30202",
        "message":"問題数が不足しています"
    }
}
</pre>

---

## ページ中のボイスを取得する
> [POST] /ebook/?m=voice

### リクエストパラメータ
| パラメータ | 必須 | 形式 | 概要 |
| --- | --- | --- | --- |
| t | ○ | int | 書籍ID |
| p | ○ | int | ページ |

### 結果サンプル
<pre>
{
    "result" : [
        {
            // id
            "id":"10086",

            // 英文
            "en":"I have known Paul since we were children.",

            // 音声ファイル名
            "voice_file":"Evergreen_86"
        },

        ...

    ]
}
</pre>

---

## 学校の書籍ステータスを取得する
> [POST] /ebook/?m=school_book

### リクエストパラメータ
| パラメータ | 必須 | 形式 | 概要 |
| --- | --- | --- | --- |
| lms_code | ○ | 学校指定コード<br>（「 _ 」でつないで複数指定可能） |

### 結果サンプル
<pre>
{
    "result" : {
        "10052": {
            // 書籍ID
            "title_no":"10052",

            // 購入済みフラグ (0 or 1)
            "is_buy":"1",

            // 表示フラグ (0 or 1)
            "is_display":"0"
        },

        ...
    }
}
</pre>

---

## iizunaLMS 登録キー発行
> [POST] /ebook/?m=iizuna_lms_user_register

### リクエストパラメータ
| パラメータ | 必須 | 概要 |
| --- | --- | --- |
| lms_code | ○ | 学校指定コード<br>（「 _ 」でつないで複数指定可能） |
| school_name |  | 学校名 |
| school_grade |  | 学年 |
| school_class |  | クラス |
| student_number |  | 学生番号 |
| name |  | 氏名 |
| nickname |  | ニックネーム |
| user_id | ○ | 参考書おにぎりユーザーID |

### 結果サンプル
<pre>
{
    "result":"OK",
    "authorization_key":"kafg6p2l9i1rw"
}
</pre>

---

## コードカウントアップ
> [POST] /ebook/?m=code_count_up

### リクエストパラメータ
| パラメータ | 必須 | 概要 |
| --- | --- | --- |
| lms_code | ○ | 学校指定コード<br>（「 _ 」でつないで複数指定可能） |

### 結果サンプル
<pre>
{
    "result":"OK"
}
</pre>

