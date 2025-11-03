var questionTypeTableHeaderHtml = "";
var modalSyubetuNo = 0;
var modalLevelNo = null;
var currentTitleNo = 0;
var selectedRevnos = {};
var selectedMidasinos = [];
var tooltipMode = 0;
let iconSameMidasi       = '<img src="../common/images/icon_same_midashi.png" alt="同じ見出し語">';
let iconCannotBeTogether = '<img src="../common/images/icon_cannot_be_together.png" alt="同時に出題できない問題">';

const TOOLTIP_MODE_NONE = 0;
const TOOLTIP_MODE_CHANGE_DISPLAY = 1;
const TOOLTIP_MODE_FREQUENCY = 2;

const CHANGE_DISPLAY_TYPE_NOT_SET = 0;
const CHANGE_DISPLAY_TYPE_QUESTION = 1;
const CHANGE_DISPLAY_TYPE_LEVEL = 2;

function resetManualConditions(initialize=false) {
    if (!initialize) {
        quizOption.manual.individualSelected = {};
        quizOption.manual.selectedShomonnos = [];
        $("#sectionNos").val("");
        $("#midasiNos").val("");
    }

    selectedRevnos = {};
    selectedMidasinos = [];

    if (Object.keys(quizOption.manual.individualSelected).length !== 0) {
        Object.entries(quizOption.manual.individualSelected).forEach(([modalKey, checkedItems]) => {
            selectedRevnos[modalKey] = [];
            Object.entries(checkedItems).forEach(([shomonNo, item]) => {
                if (item.REVNO != 0) selectedRevnos[modalKey].push(item.REVNO);
                selectedMidasinos.push(item.MIDASINO);
            });
        });
    }
}

function changeQuestionTypeDataNotInitialize() {
    changeQuestionTypeData(false);
}

// 問題の形式の内容を変更する
function changeQuestionTypeData(initialize=false) {
    // マニュアルモードでない場合は処理しない
    if (!$("#manualon").prop("checked")) return;

    let titleNo = $("#selectBook").val();

    if (currentTitleNo != titleNo) {
        currentTitleNo = titleNo;
    }

    // 範囲などが切り替わった際も選択されているものをリセットする
    resetManualConditions(initialize);

    if (titleNo == "") {
        setQuestionTypeData(null, false);
        return;
    }

    if (!initialize) {
        let selectRangeValue = $("#selectRange").val();
        // 章・節で指定する場合
        if (selectRangeValue == "chapter") {
            setSectionNosForForm();
        }
        // 見出し語を個別に指定する場合
        else if (selectRangeValue == "midasi") {
            setMidasiNosForForm();
        }
    }

    let form = $("#testCreator");

    $.ajax({
        url: "./quiz_manual_question_type.php",

        type: "post",
        data: form.serialize(),
        timeout: 30000,
    })
    .done(function(data) {
        // 通信が成功
        try {
            const obj = JSON.parse(data);

            if (obj.error != 0)
            {
                alert("問題の形式データ取得時にエラーが発生しました。(エラーコード : " + obj.error + ")");
                return;
            }

            setQuestionTypeData(obj.data, initialize);

        } catch (e) {
            alert("データ取得時にエラーが発生しました。(" + e + ")");
        }
    })
    .fail(function(data) {
        // 通信が失敗
        alert("通信に失敗しました。");
    })
    .always(function(data) {
        // ファイナライズ
    });
}

// 問題の形式の内容をセットする
function setQuestionTypeData(data, initialize) {
    let table = $("#questionType");

    if (questionTypeTableHeaderHtml == "") {
        questionTypeTableHeaderHtml = table.html();
    }

    var html = "";
    var disalbedText   = ( $("#selectIndividual").prop("checked")) ? "disabled" : "";
    var disalbedButton = (!$("#selectIndividual").prop("checked")) ? "disabled" : "";

    if (data == null) {
        html = '<tr><td colspan="4" class="center">書籍を選択してください</td></tr>';
    }
    else {
        var currentHead = "";
        for (var i=0; i<data.length; ++i) {
            var myData = data[i];
            var myName = myData.NAME;
            var myId = 'syubetu_num_' + myData.SYUBETUNO;
            var mySyubetuNo = myData.SYUBETUNO;
            var myLevelNo = null;

            // 表示切替が有効な場合は問題形式・レベルのいずれかを見出しの様に扱う
            if (quizOption.manual.changeDisplayType != CHANGE_DISPLAY_TYPE_NOT_SET) {
                var myHead = (quizOption.manual.changeDisplayType == CHANGE_DISPLAY_TYPE_QUESTION)
                           ? myData.NAME
                           : myData.LEVEL;

                // 現在値と異なる場合は見出しを追加する
                if (currentHead != myHead) {
                    html += '<tr><td colspan="4">' + myHead + '</td></tr>';

                    currentHead = myHead;
                }

                // myName値を切り替え
                if (quizOption.manual.changeDisplayType == CHANGE_DISPLAY_TYPE_QUESTION) {
                    myName = '　' + myData.LEVEL;
                }
                else if (quizOption.manual.changeDisplayType == CHANGE_DISPLAY_TYPE_LEVEL) {
                    myName = '　' + myData.NAME;
                }

                // myIdをSYUBETUNOとLEVELNOの組み合わせに変更
                myId = 'syubetu_num_' + myData.SYUBETUNO + '_' + myData.LEVELNO;

                myLevelNo = myData.LEVELNO;
            }

            var myValue = '';
            if (initialize) {
                let tmpSyubetuNo = myId.replace("syubetu_num_", "");
                if (quizOption.manual.syubetuNumbers[tmpSyubetuNo] != null) {
                    myValue = quizOption.manual.syubetuNumbers[tmpSyubetuNo];
                }
            }

            html += '<tr>'
                  + '<td>' + myName +  '</td>'
                  + '<td class="center">' + myData.NUM +  '</td>'
                  + '<td><input type="number" name="' + myId +  '" onchange="fixSyubetuNum(this, ' + myData.NUM +  ')" class="syubetu_num" id="' + myId + '" ' + disalbedText + ' value="' + myValue + '" /></td>'
                  + '<td class="center"><input type="button" value="選択" onclick="showSelectIndividualModal(' + mySyubetuNo +  ', ' + myLevelNo +  ')" class="selectIndividualButton" ' + disalbedButton + ' /></td>'
                  + '</tr>'
        }
    }

    table.html(questionTypeTableHeaderHtml + html);
}

// 今回の出題数の値を範囲内に調整する
function fixSyubetuNum(obj, max) {
    var baseVal = obj.value;
    var val = toHalfWidth(baseVal);

    if (val < 0)   val = 0;
    if (val > max) val = max;

    if (val != baseVal) {
        obj.value = val;
    }

    var total = calculateTotal();
    $("#totalNum").val(total);
}

// Totalの数を更新する
function fixTotal() {
    var calculatedNum = calculateTotal();
    var currentVal = $("#totalNum").val();

    if (calculatedNum > currentVal) {
        $("#totalNum").val(calculatedNum);
    }
}

// Totalを計算する
function calculateTotal() {
    var total = 0;
    $("#testCreator .syubetu_num").each(function(){
        if ($(this).val() == "") return;

        total += parseInt($(this).val());
    });

    return total;
}

// 個別選択ボタンの有効無効化
function toggleSelectIndividualButtons() {
    var individual = $("#selectIndividual").prop("checked");
    $(".selectIndividualButton").prop("disabled", !individual);

    // 今回の出題数は入力不可に
    $(".syubetu_num").prop("disabled", individual);

    // 今回の出題数をクリア
    $("#testCreator .syubetu_num").each(function(){
        $(this).val("");
    });

    // 問題を個別に選択を切り替える場合は、章選択などはリセットさせず、個別選択のみリセットする
    quizOption.manual.individualSelected = {};
    quizOption.manual.selectedShomonnos = [];
    selectedRevnos = {};
}

// 個別選択モーダル表示
function showSelectIndividualModal(syubetuNo, levelNo) {
    modalSyubetuNo = syubetuNo;
    modalLevelNo = levelNo;

    var addParameter = "&syubetuNo=" + syubetuNo;

    if (levelNo != null) {
        addParameter += "&levelNo=" + levelNo;
    }

    let params = $("#testCreator").serialize();
    params += addParameter;

    $.ajax({
        url: "./quiz_manual_individual_questions.php",
        type: "post",
        data: params,
        timeout: 30000,
    })
    .done(function (data) {
        // 通信が成功
        try {
            const obj = JSON.parse(data);

            if (obj.error != 0) {
                alert("問題の形式データ取得時にエラーが発生しました。(エラーコード : " + obj.error + ")");
                return;
            }

            _createIndividualModal(obj);

        } catch (e) {
            alert("データ取得時にエラーが発生しました。(" + e + ")");
        }
    })
    .fail(function (data) {
        // 通信が失敗
        alert("通信に失敗しました。");
    })
    .always(function (data) {
        // ファイナライズ
    });
}

// 個別選択モーダル生成
var individualModalTotalNum = 0;
function _createIndividualModal(obj) {
    // テーブル生成用パラメータ
    let keyParams = {
        QUESTIONNO: { name: "問題番号", is_center: true },
        LEVEL:      { name: "レベル", is_center: true },
        FREQUENCY:  { name: "頻度", is_center: true },
        MIDASI:     { name: "見出し語", is_center: true },
        BUN:        { name: "問題文", is_center: false },
        ANSWERFROM: { name: "出典", is_center: false }
    }

    modalMidasiNosCount = {};
    revNoMap = {};
    revpNoMap = {};
    dMap = {};

    var allChecked = true;
    var htmlBody = '';
    var count = 0;
    var isMidasiFlg = (currentBookData["midasi_flg"] > 0);

    if (isMidasiFlg) $('#midasiDescription').show();
    else             $('#midasiDescription').hide();

    individualModalTotalNum = obj.data.length;

    // まず特定の値の数を集計する為に一回ループを回す
    for (var i=0; i<individualModalTotalNum; ++i) {
        var myData     = obj.data[i];
        let myMidasiNo = Number(myData['MIDASINO']);
        if (modalMidasiNosCount[myMidasiNo]) ++modalMidasiNosCount[myMidasiNo];
        else                                   modalMidasiNosCount[myMidasiNo] = 1;
    }

    // ボディ作成
    for (var i=0; i<individualModalTotalNum; ++i) {
        var myData     = obj.data[i];
        let myShomonNo = Number(myData['SYOMONNO']);
        let myMidasiNo = Number(myData['MIDASINO']);
        let myRevNo    = Number(myData['REVNO']);
        let myRevpNo    = Number(myData['REVPNO']);

        // REVNO を扱いやすいように MAP を作る
        if (myRevNo > 0) {
            if (!revNoMap.hasOwnProperty(myRevNo)) revNoMap[myRevNo] = [];
            if (revNoMap[myRevNo].indexOf(myShomonNo) < 0) revNoMap[myRevNo].push(myShomonNo);
        }

        // REVPNO を扱いやすいように MAP を作る
        if (myRevpNo > 0) {
            if (!revpNoMap.hasOwnProperty(myRevpNo)) revpNoMap[myRevpNo] = [];
            if (revpNoMap[myRevpNo].indexOf(myShomonNo) < 0) revpNoMap[myRevpNo].push(myShomonNo);
        }

        // 重複管理用マップ初期化
        dMap[myShomonNo] = {
            rev: false,
            revp: false,
            isIcon: false
        };

        let isChecked  = (quizOption.manual.selectedShomonnos.includes(myShomonNo));
        //　「同じ見出し語の問題」、自分にチェックが入っている場合は同じモーダル内に複数同じ見出し語がある場合にアイコンを表示。
        // 自分にチェックが入っていない場合は、他で「同じ見出し語」が選択されていればアイコンを表示
        let isMidasi   = (myMidasiNo != "0" && ( (isChecked == true && modalMidasiNosCount[myMidasiNo] >= 2) || (isChecked == false && selectedMidasinos.includes(myMidasiNo)) ) );

        if (isChecked) ++count;
        else           allChecked = false;

        let classRevCheck  = 'revCheck_'  + myRevNo;
        let classRevpCheck = 'revpCheck_' + myRevpNo;
        let classMidasi    = 'midasi_'    + myMidasiNo;
        let classRev       = 'rev_'       + myRevNo;

        let myChecked = ((isChecked) ? "checked" : "");

        htmlBody += `<tr>`
                  + `<td class="center"><input type="checkbox" value="${myShomonNo}" data-midasi="${myMidasiNo}" data-rev="${myRevNo}" data-revp="${myRevpNo}" id="individual_${myShomonNo}" class="individualCheckbox ${classRevCheck} ${classRevpCheck}" onclick="checkIndividual(this)" ${myChecked}></td>`;

        // 同じ見出し語
        if (isMidasiFlg) {
            htmlBody += '<td class="center ' + classMidasi + '" data-no="' + myShomonNo + '">' + ((isMidasi) ? iconSameMidasi : '') + '</td>';
        }

        // 同時に出題できない問題
        htmlBody += `<td class="center icon_duplicate ${classRev}" data-no="${myShomonNo}" id="icon_duplicate_${myShomonNo}"></td>`;

        for (var ii=0; ii<obj.keys.length; ++ii) {
            let css = (keyParams[ obj.keys[ii] ].is_center)
                    ? 'class="center"' : "";
            let value = (myData[ obj.keys[ii] ] != null) ? myData[ obj.keys[ii] ] : "";
            htmlBody += '<td ' + css + '>' + value + '</td>';
        }

        htmlBody += '</tr>'
    }

    // ヘッダと合わせてHTML作成
    var html = '<table>'
        + '<thead>'
        + '<tr>'
        + '<th class="center"><input type="checkbox" id="toggleIndividualCheckbox" onclick="toggleAllCheckIndividual(this)" ' + ((allChecked) ? 'checked' : '') + '></th>';

    // 見出しフラグによって表示される列の数が変わる
    html += (isMidasiFlg)
          ? '<th></th><th></th>'
          : '<th></th>';

    for (var i=0; i<obj.keys.length; ++i) {
        html += '<th>' + keyParams[ obj.keys[i] ].name + '</th>';
    }

    html += '</tr>'
          + '</thead>'
          + '<tbody>'
          + htmlBody
          + '</tbody>'
          + '</table>';

    $("#individualTable").html(html);
    updateSelectedIndividualNumModal(count, individualModalTotalNum);

    // 行全体をチェックできるようにする(※クリックすると複数回クリックされてしまうので無効化)
    // $('#individualTable tr').click(function(event) {
    //     // チェックボックスがチェックされたら無視
    //     if (event.target.type !== 'checkbox') {
    //         $(':checkbox', this).trigger('click');
    //     }
    // });

    $("#modalWindow").show();

    // 高さ調整
    let footerHeight = $('#modalContent .footer').height();
    let footerBottom = Number( $('#modalContent .footer').css('bottom').replace('px', '') );
    $('#individualTable').css('bottom', (footerHeight + footerBottom) + 'px');

    // 重複禁止アイコン表示更新
    updateDuplicateIcon();
}

// 問題選択数表示更新
function updateSelectedIndividualNumModal(num, total) {
    $("#individualSelectNum").html(total + "問中" + num + "問を選択")
}

// 個別選択モーダル非表示
function hideSelectIndividualModal() {
    $("#modalWindow").fadeOut(500, function(){
        $("#individualTable").html("");
    });
}

// 全チェック
function toggleAllCheckIndividual(myObj) {
    var checked = $(myObj).prop("checked");

    // 個別にチェックのONOFFを繰り返す
    $(".individualCheckbox").each(function(index, element) {
        if ($(element).prop('checked') != checked) {
            $(element).prop('checked', checked);
            checkIndividual(element);
        }
    });
}

var countMidasiNos = {};
var modalMidasiNosCount = {};
var revNoMap = {}; // key: REVNO, value: SHOMONNO Array
var revpNoMap = {}; // key: REVPNO, value: SHOMONNO Array
var dMap = {};
/**
 * dMap = {
 *     no: {
 *         rev: bool,
 *         revp: bool,
 *         isIcon: bool
 *     }
 * }
 */

// 個別選択モーダルでの問題チェック時処理
function checkIndividual(myObj) {
    var allChecked = true;
    var count = 0;
    let myMidasiNo = Number($(myObj).data('midasi'));
    let isCheck    = Number($(myObj).prop('checked'));
    let classMidasi   = 'midasi_'   + myMidasiNo;

    // 「同じ見出し語の問題」のアイコン処理
    if (myMidasiNo != 0) {
        if (isCheck) {
            countUpMidasiNos(myMidasiNo);

            // 「同じ見出し語の問題」が他に選択されていない場合、「同じ見出し語の問題」にアイコンを表示する
            // ただし、この行以外にも「同じ見出し語の問題」がない場合、アイコンを表示しない
            if (modalMidasiNosCount[myMidasiNo] >= 2) {
                $('.' + classMidasi).each(function(index, element) {
                    $(element).html(iconSameMidasi);
                });
            }
            else {
                $('.' + classMidasi).each(function(index, element) {
                    $(element).html('');
                });
            }
        }
        // 「同じ見出し語の問題」が全てなくなった場合にアイコンを非表示にする
        else {
            countDownMidasiNos(myMidasiNo);

            if (!countMidasiNos[myMidasiNo]) {
                $('.' + classMidasi).each(function(index, element) {
                    $(element).html('');
                });
            }
        }
    }

    // 「同時に出題できない問題」のアイコン処理
    adjustDuplicateCheck(myObj);
    updateDuplicateIcon();

    // 全チェック確認
    $(".individualCheckbox").each(function(index, element) {
        if (!$(element).prop("checked")) {
            allChecked = false;
        }
        else {
            ++count;
        }
    });

    $("#toggleIndividualCheckbox").prop("checked", allChecked);
    updateSelectedIndividualNumModal(count, individualModalTotalNum);
}

// 重複禁止問題がチェックされた際、チェックできない問題のチェック外す処理や重複禁止アイコンの表示を行う
function adjustDuplicateCheck(checkedObj)
{
    let myShomonNo = $(checkedObj).val();
    let myMidasiNo = Number($(checkedObj).data('midasi')) || 0;
    let myRevNo    = Number($(checkedObj).data('rev')) || 0;
    let myRevpNo   = Number($(checkedObj).data('revp')) || 0;
    let isCheck = $(checkedObj).prop('checked');

    /**
     * rev と revp がある
     * rev : 同じ値を指定している問題は同時に選択できない
     * revp: revp と同じ no の問題は同時に選択できない。
     *       revp は双方向で指定されていないため、予めどの値が指定されているかを収集しておかないと判定ができない。
     *         ・チェックした問題を revp で指定している問題達がわかれば良い
     *
     * 重複禁止管理する dMap を作る
     * dMap = {
     *     no: {
     *         rev: bool,
     *         revp: bool,
     *         isIcon: bool
     *     }
     * }
     *
     *
     * チェックのONOFFの重複チェックのみをまず実施する
     * その後、チェック状態から重複アイコン表示処理を実施する
     *
     * チェックされていない問題Aにチェックを入れるときの処理
     *   クリックした問題Aにチェックを入れる
     *   クリックした問題Aと同じ rev の値を指定している問題Bがある場合、問題Bのチェックを外す
     *   クリックした問題Aが指定している revp の問題Cのチェックを外す
     *   クリックした問題Aを revp で指定している問題達のチェックを外す
     *
     * チェックされている問題Aのチェックを外すときの処理
     *   チェック処理としては特に何もしない
     *
     * アイコンが表示されている問題をループで回し、isIcon の状態をセットしていく
     * チェックされている問題をループで回す
     *   チェックしている問題と同じ rev の値を指定している問題がある場合、対象の問題の dMap の rev を true にする
     *   チェックしている問題が指定している revp の問題の dMap の revp を true にする
     *   チェックしている問題を revp で指定している問題の dMap の revp を true にする
     *
     * dMap をループで回し、 rev または revp のどちらかが true になっており、isIcon が false のものにアイコンをつける
     * それ以外のものはアイコンを非表示にする
     *
     * チェックを入れたり外したりする際、 countMidasiNos の値を更新する必要あり
     *
     * 全チェックしたときに重くなるので、awaitいれる
     */

    // チェック処理
    if (isCheck) {
        // クリックした問題Aと同じ rev の値を指定している問題Bがある場合、問題Bのチェックを外す
        if (myRevNo != 0 && revNoMap.hasOwnProperty(myRevNo)) {
            revNoMap[myRevNo].forEach((shomonNo) => {
                if (myShomonNo == shomonNo) return;
                uncheckIndividualCheckbox(shomonNo);
                countDownMidasiNos(myMidasiNo);
            });
        }

        // クリックした問題Aが指定している revp の問題Cのチェックを外す
        if (myRevpNo != 0) {
            uncheckIndividualCheckbox(myRevpNo);
            countDownMidasiNos(myMidasiNo);
        }

        // クリックした問題Aを revp で指定している問題達のチェックを外す
        if (revpNoMap.hasOwnProperty(myShomonNo)) {
            revpNoMap[myShomonNo].forEach((shomonNo) => {
                uncheckIndividualCheckbox(shomonNo);
                countDownMidasiNos(myMidasiNo);
            });
        }
    }
    else {
        // チェックを外した時は特に何も処理しない
    }
}

// 重複禁止アイコン表示処理
function updateDuplicateIcon()
{
    // アイコン表示処理 --------------------------------------------------------
    // dMap リセット
    for (var no in dMap) {
        if (dMap.hasOwnProperty(no)) {
            var obj = dMap[no];
            obj.rev = false;
            obj.revp = false;
            obj.isIcon = false;
        }
    }

    // アイコンが表示されている問題をループで回し、isIcon の状態をセットしていく
    $('.icon_duplicate').each(function() {
        if ($(this).html() == '') return;

        const tempShomonNo = $(this).data('no');
        dMap[tempShomonNo].isIcon = true;
    });

    // チェックされている問題をループで回す
    $('.individualCheckbox:checked').each(function() {
        const checkedShomonNo = $(this).val();
        const checkedRevNo = Number($(this).data('rev')) || 0;
        const checkedRevpNo = Number($(this).data('revp')) || 0;

        // チェックしている問題と同じ rev の値を指定している問題がある場合、対象の問題の dMap の rev を true にする
        if (checkedRevNo != 0 && revNoMap.hasOwnProperty(checkedRevNo)) {
            revNoMap[checkedRevNo].forEach((shomonNo) => {
                if (checkedShomonNo == shomonNo) return;
                dMap[shomonNo].rev = true;
            });
        }

        // チェックしている問題が指定している revp の問題の dMap の revp を true にする
        if (checkedRevpNo != 0 && dMap.hasOwnProperty(checkedRevpNo)) {
            dMap[checkedRevpNo].revp = true;
        }

        // チェックしている問題を revp で指定している問題の dMap の revp を true にする
        if (revpNoMap.hasOwnProperty(checkedShomonNo)) {
            revpNoMap[checkedShomonNo].forEach((shomonNo) => {
                dMap[shomonNo].revp = true;
            });
        }
    });

    var currentModalKey = getCurrentModalKey();

    // モーダル外で選択されている問題と同じ rev を指定している問題がある場合、対象の問題の dMap の rev を true にする
    $('.individualCheckbox').not(':checked').each(function() {
        const uncheckedShomonNo = $(this).val();
        const uncheckedRevNo = Number($(this).data('rev')) || 0;

        if (uncheckedRevNo != 0) {
            for (let modalKey in selectedRevnos) {
                if (modalKey == currentModalKey) continue;

                let revnos = selectedRevnos[modalKey];

                if (revnos.includes(uncheckedRevNo)) {
                    dMap[uncheckedShomonNo].rev = true;
                    break;
                }
            }
        }
    });

    // モーダル外で選択されている問題が指定している revp の問題の dMap の revp を true にする
    Object.entries(quizOption.manual.individualSelected).forEach(([modalKey, checkedItems]) => {
        if (modalKey == currentModalKey) return;

        Object.entries(checkedItems).forEach(([shomonNo, item]) => {
            if (item.REVPNO != 0 && dMap.hasOwnProperty(item.REVPNO)) {
                dMap[item.REVPNO].revp = true;
            }
        });
    });

    // モーダル外で選択されている問題を revp で指定している問題の dMap の revp を true にする
    quizOption.manual.selectedShomonnos.forEach(selectedShomonNo => {
        if (revpNoMap.hasOwnProperty(selectedShomonNo)) {
            revpNoMap[selectedShomonNo].forEach((shomonNo) => {
                dMap[shomonNo].revp = true;
            });
        }
    });

    // dMap をループで回し、 rev または revp のどちらかが true になっており、isIcon が false のものにアイコンをつける
    // それ以外のものはアイコンを非表示にする
    for (var no in dMap) {
        if (dMap.hasOwnProperty(no)) {
            var obj = dMap[no];

            if (obj.rev || obj.revp) {
                if (!obj.isIcon) $(`#icon_duplicate_${no}`).html(iconCannotBeTogether);
            }
            else {
                if (obj.isIcon) $(`#icon_duplicate_${no}`).html('');
            }
        }
    }
}

function countUpMidasiNos(midasiNo) {
    if (countMidasiNos[midasiNo]) ++countMidasiNos[midasiNo];
    else                            countMidasiNos[midasiNo] = 1;
}

function countDownMidasiNos(midasiNo) {
    if (countMidasiNos[midasiNo]) --countMidasiNos[midasiNo];

    // マイナスにはしない
    if (countMidasiNos[midasiNo] < 0) countMidasiNos[midasiNo] = 0;
}

function uncheckIndividualCheckbox(shomonNo) {
    let myCheckbox = $(`#individual_${shomonNo}`);
    if (myCheckbox == null) return;
    myCheckbox.prop('checked', false);
}

// 個別選択モーダルを閉じて選択している個別設問データをセットする処理
// 処理する際に現在集計中のウインドウ以外で選択されている問題で重複禁止がある場合は除外する
function setSelectedIndividual() {
    var checked = {};
    let revnos = [];
    let revpnos = [];

    $(".individualCheckbox").each(function(index, element) {
        if ($(element).prop("checked")) {
            let myShomonno = Number($(element).val()) || 0;
            let myMidasino = Number($(element).data('midasi')) || 0;
            let myRevno    = Number($(element).data('rev')) || 0;
            let myRevpno   = Number($(element).data('revp')) || 0;

            // 各値はDBのテーブルのカラムに合わせている
            checked[ myShomonno ] = {
                "MIDASINO": myMidasino,
                "REVNO": myRevno,
                "REVPNO": myRevpno
            }

            if (myRevno > 0) revnos.push(myRevno);
            if (myRevpno > 0) revpnos.push(myRevpno);
        }
    });

    var currentModalKey = getCurrentModalKey();

    // 計算用に保持しておく
    quizOption.manual.individualSelected[currentModalKey] = checked;

    // 現在選択されている値を集計しなおす
    quizOption.manual.selectedShomonnos = [];
    selectedRevnos = {};
    selectedMidasinos = [];

    var countNums = {};

    for (var modalKey in quizOption.manual.individualSelected) {
        var count = 0;
        var tmpSelected = quizOption.manual.individualSelected[modalKey];
        selectedRevnos[modalKey] = [];

        for (var myShomonno in tmpSelected) {
            var myObj = tmpSelected[myShomonno];

            // 現在集計中のウインドウ以外で同じRevnoを持つデータはdeleteする
            if (currentModalKey != modalKey && revnos.includes(myObj.REVNO)) {
                // 同じ見出し語の問題カウントからカウントダウンする
                if (countMidasiNos[myObj.MIDASINO]) --countMidasiNos[myObj.MIDASINO];

                delete(quizOption.manual.individualSelected[modalKey][myShomonno])
                continue;
            }

            quizOption.manual.selectedShomonnos.push(Number(myShomonno));
            selectedMidasinos.push(myObj.MIDASINO);
            if (myObj.REVNO > 0) selectedRevnos[modalKey].push(myObj.REVNO);

            ++count;
        }

        countNums[modalKey] = count;
    }

    // 今回の出題数を更新
    for (var key in countNums) {
        $("#syubetu_num_" + key).val( countNums[key] );
    }

    // TOTAL更新
    var calculatedNum = calculateTotal();
    $("#totalNum").val(calculatedNum);
}

function getCurrentModalKey() {
    return (modalLevelNo == null)
        ? modalSyubetuNo
        : modalSyubetuNo + '_' + modalLevelNo;
}

// キャンセルボタン処理
function clickIndividualButtonCancel() {
    hideSelectIndividualModal();
}

// OKボタン処理
function clickIndividualButtonOK() {
    setSelectedIndividual();
    hideSelectIndividualModal();
}

// 選択されているSyomonNosをセットする
function setSelectedSyomonNos() {
    $("#syomonNos").val( quizOption.manual.selectedShomonnos.join(",") );
}

// individualSelected フォームデータにセットする
function setIndividualSelected() {
    $("#individualSelected").val(  JSON.stringify(quizOption.manual.individualSelected) );
}

// 表示切替ウインドウを表示する
function displayChangeDisplayTooltip() {
    let modal = $("#changeDisplayTooltipContent");
    let button = $("#changeDisplayButton");

    tooltipMode = TOOLTIP_MODE_CHANGE_DISPLAY;
    _showTooltipByButton(modal, button);
}

// 出題頻度ウインドウを表示する
function displayFrequencyTooltip() {
    let modal = $("#frequencyTooltipContent");
    let button = $("#frequencyButton");

    tooltipMode = TOOLTIP_MODE_FREQUENCY;
    _showTooltipByButton(modal, button);
}

function _showTooltipByButton(modal, button) {
    let height = button.height();
    let top  = Math.ceil(button.offset().top + height);
    let left = Math.ceil(button.offset().left);

    modal.css("top", top);
    modal.css("left", left);
    modal.show();

    $("#tooltipBg").show();
}

// ツールチップを非表示にする
function hideTooltip() {
    $("#changeDisplayTooltipContent").hide();
    $("#frequencyTooltipContent").hide();
    $("#tooltipBg").hide();

    tooltipMode = TOOLTIP_MODE_NONE;
}

// 表示切替のチェック
function checkChangeDisplay() {
    var checked = $(this).prop("checked");

    // まずリセット
    resetChangeDisplay();
    resetManualConditions();

    // チェックしたもののみチェックを付けなおす
    $(this).prop("checked", checked);

    if (checked) {
        quizOption.manual.changeDisplayType = Number($(this).val());
    }
    else {
        quizOption.manual.changeDisplayType = CHANGE_DISPLAY_TYPE_NOT_SET;
    }

    changeQuestionTypeData();
    hideTooltip();
}

// 表示切替のチェックを外す
function resetChangeDisplay() {
    $(".changeDisplay").each(function(index, element) {
        $(element).prop("checked", false);
    });

    quizOption.manual.changeDisplayType = CHANGE_DISPLAY_TYPE_NOT_SET;
}

// 出題頻度 -----------------------------------------------------
function SetupFrequencyButton() {
    let initialize = !isInitialized;
    let form = $("#testCreator");

    $.ajax({
        url: "./quiz_manual_frequency.php",
        type: "post",
        data: form.serialize(),
        timeout: 30000,
    })
    .done(function(data) {
        // 通信が成功
        try {
            const obj = JSON.parse(data);

            if (obj.error != 0)
            {
                alert("問題の形式データ取得時にエラーが発生しました。(エラーコード : " + obj.error + ")");
                return;
            }

            setFrequencyData(obj.data, initialize);

        } catch (e) {
            alert("データ取得時にエラーが発生しました。(" + e + ")");
        }
    })
    .fail(function(data) {
        // 通信が失敗
        alert("通信に失敗しました。");
    })
    .always(function(data) {
        // ファイナライズ
    });
}

// 出題頻度の内容をセットする
function setFrequencyData(data, initialize) {
    if (data == null || data.length == 0) return;

    $("#frequencyButtonWrapper").show();

    var html = '';

    for (var i=0; i<data.length; ++i) {
        var myData = data[i];
        var myNo = myData.FREQUENCYNO;
        var myName = myData.NAME;

        var checked = "";
        if (initialize && quizOption.manual.frequencies.indexOf(myNo) >= 0) {
            checked = 'checked';
        }

        html += '<input type="checkbox" name="frequency[]" value="' + myNo + '" class="frequency" id="frequency_' + myNo + '" ' + checked + '><label for="frequency_' + myNo + '"> ' + myName + '</label><br />'
    }

    $("#frequencyTooltipContent").html(html);
    $(".frequency").click(checkFrequency);

    // 問題の型式表示
    changeQuestionTypeData(initialize);
}

// 出題頻度のチェック
function checkFrequency() {
    changeQuestionTypeData();
    hideTooltip();
}