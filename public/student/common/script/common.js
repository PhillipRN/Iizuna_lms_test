//creat grades-top chartText
function gradesTopTimer(src,src2){
    var countElm = src,
    countSpeed = 9;
    countElm.each(
        function(){
            var self = $(this),
            countMax = src2,
            thisCount = self.text(),
            countTimer;
            countTimer = setInterval(function(){
                var countNext = thisCount++;

                if(countNext > countMax){
                    clearInterval(countTimer);
                }
                else {
                    self.text(countNext);
                }
        },countSpeed);
        });
}

function IsNotOpen(openDate)
{
    return (Date.now() < openDate);
}

function GetIsNotOpenText(openDate)
{
    return 'このテストは、' + timestampToTime(openDate) + 'から受けることができます。';
}

function IsExpired(expireDate)
{
    return (Date.now() > expireDate);
}

function GetLimitText(expireDate, timeLimit)
{
    let current = Date.now();
    let remainTime = expireDate - current;

    // 期間を過ぎている場合
    if (remainTime < 0) {
        if (timeLimit > 0) {
            return '制限時間は' + (timeLimit / 60000) + '分です、';
        }
        else {
            return '';
        }
    }
    else if (timeLimit > 0) {
        // 制限時間以上の時間が残っている場合
        if (remainTime > timeLimit) {
            return '制限時間は' + (timeLimit / 60000) + '分です、';
        }
        else {
            if (remainTime < 60000) {
                return '残り時間は1分未満です、';
            }
            else {
                let minutes = Math.floor(remainTime / 60000);

                return '残り時間は' + minutes + '分です、';
            }
        }
    }

    return '';
}

function GetDialogMessage(expireDate, timeLimit)
{
    var dialogMessage = '';
    let limitTimeText = GetLimitText(expireDate, timeLimit);

    if (IsExpired(expireDate)) {
        dialogMessage += '<div class="text-danger">※回答期限を過ぎているため、受験しても未提出扱いになります。ご注意ください。</div>'
    }

    dialogMessage += 'テストを開始します。<br />';
    if (limitTimeText != '') {
        dialogMessage += limitTimeText;
    }
    else {
        dialogMessage += '制限時間はありません、';
    }
    dialogMessage += 'よろしいですか？';

    dialogMessage += getLimitDate(expireDate)

    return dialogMessage;
}

function GetDialogMessageForOnigiri(expireDate, timeLimit)
{
    let limitTimeText = GetLimitText(expireDate, timeLimit);

    var dialogMessage = '';
    if (limitTimeText != '') {
        dialogMessage += limitTimeText;
    }
    else {
        dialogMessage += '制限時間はありません、';
    }
    dialogMessage += 'よろしいですか？';

    dialogMessage += getLimitDate(expireDate)

    return dialogMessage;
}

function getLimitDate(expireDate) {
    if (expireDate == 0) return '<br />(期限: なし)';

    return '<br />(期限: ' + timestampToTime(expireDate) + ' まで)';
}

function timestampToTime (timestamp)  {
    const date = new Date(timestamp);
    const yyyy = `${date.getFullYear()}`;
    // .slice(-2)で文字列中の末尾の2文字を取得する
    // `0${date.getHoge()}`.slice(-2) と書くことで０埋めをする
    const MM = date.getMonth() + 1; // getMonth()の返り値は0が基点
    const dd = date.getDate();
    const HH = `0${date.getHours()}`.slice(-2);
    const mm = `0${date.getMinutes()}`.slice(-2);
    const ss = `0${date.getSeconds()}`.slice(-2);

    return `${yyyy}年${MM}月${dd}日 ${HH}:${mm}`;
}


function logout() {
    bootbox.confirm({
        centerVertical: true,
        closeButton: false,
        message: 'ログアウトしますか？',
        callback: function (result) {
            if (result) {
                location.href = 'logout.php';
            }
        }
    });
}

function zen2han(elm) {
    elm.value = elm.value.replace(/[Ａ-Ｚａ-ｚ０-９！-～]/g, function(s) {
        console.log(elm.value)
        return String.fromCharCode(s.charCodeAt(0) - 0xFEE0);
    });
}