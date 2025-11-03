var currentBookData = null;
var isAuthorize = false;
var isInitialized = false;

const ERROR_AUTH_EXPIRED = 9001;

$(function() {
    changeSelectBookType();

    $("#testCreator").submit(createTest);

    $(".toggleConditionSubRangeRow").on('click', toggleConditionSubRangeRow);

    $("#selectIndividual").change(toggleSelectIndividualButtons);

    // 個別選択モーダル
    $("#modalBg").click(hideSelectIndividualModal);
    $("#individualButtonCancel").click(clickIndividualButtonCancel);
    $("#individualButtonOK").click(clickIndividualButtonOK);

    // 表示切替・出題頻度
    $("#changeDisplayButton").click(displayChangeDisplayTooltip);
    $("#frequencyButton").click(displayFrequencyTooltip);
    $("#tooltipBg").click(hideTooltip);
    $(".changeDisplay").click(checkChangeDisplay);

    $('#totalNum').on('blur',function(e){
        // 半角指定の項目は自動で半角変換
        $(this).val(toHalfWidth($(this).val()));

        // マニュアル選択している数より少なくなっている可能性があるので調整する
        fixTotal();
    });

    $('#testNum').on('blur',function(e){
        // 半角指定の項目は自動で半角変換
        $(this).val(toHalfWidth($(this).val()));
    });

    isInitialized = true;
});

function changeSelectBookValue() {
    var val = "";

    if ($("input[name=selectBookType]:checked").val() == 0) {
        val = $("#selectBook_0").val();
    }
    else {
        val = $("#selectBook_1").val();
    }

    $("#selectBook").val(val);
    selectBook();
}


function selectRange() {
    let str = $(this).val();
    changeSelectRange(str);
    quizOption.rangeType = str;
    changeQuestionTypeData();
}

// ページ番号が変更された時
function blurPageConditions() {
    var val = $(this).val();
    let name = $(this).attr("name");

    // 空になった時はそのまま終了
    if (val !== 0 && val == "") return;

    val = parseInt( val );
    val = fixMinMaxValue(val, name, "page", "page")
    $(this).val(val);

    changeQuestionTypeData();
}

// 問題番号が変更された時
function blurNumberConditions() {
    let val = parseInt( $(this).val() );
    let name = $(this).attr("name");

    // 空になった時はそのまま終了
    if (val !== 0 && val == "") return;

    val = fixMinMaxValue(val, name, "number", "midasi_no")
    $(this).val(val);

    changeQuestionTypeData();
}

// 見出し番号が変更された時
function blurMidasiNumberConditions() {
    let val = parseInt( $(this).val() );
    let name = $(this).attr("name");

    // 空になった時はそのまま終了
    if (val !== 0 && val == "") return;

    val = fixMinMaxValue(val, name, "midasi_number", "midasi_no")
    $(this).val(val);

    changeQuestionTypeData();
}

// ページ番号、問題番号の範囲指定の数値を修正する
function fixMinMaxValue(val, name, namePrefix, dataPrefix) {
    if (val < parseInt( currentBookData[dataPrefix + "_min"] )) {
        val = currentBookData[dataPrefix + "_min"];
    }
    else if (val > parseInt( currentBookData[dataPrefix + "_max"] )) {
        val = currentBookData[dataPrefix + "_max"];
    }

    if (name.indexOf(namePrefix + "_from_") >= 0) {
        let index = name.replace(namePrefix + "_from_", "");
        let toVal = $("#" + namePrefix + "to_" + index).val();

        if (toVal != "" && val > toVal) {
            val = toVal;
        }
    }
    else {
        let index = name.replace(namePrefix + "_to_", "");
        let fromVal = $("#" + namePrefix + "_from_" + index).val();

        if (fromVal != "" && val < fromVal) {
            val = fromVal;
        }
    }

    return val;
}

// 実行ボタンを無効化
function disableButtons() {
    $("#createButton").prop('disabled', true);
    $("#copyFolderButton").prop('disabled', true);
}

// 実行ボタンを有効化
function enableButtons() {
    $("#createButton").prop('disabled', ($("#selectBook").val() != "") ? false : true);
    $("#copyFolderButton").prop('disabled', false);
}

// フォーム送信前のバリデーション
function validation() {
    var result = true;

    let totalNum = $("#totalNum").val();
    if (totalNum =="" || totalNum <= 0  || totalNum > 400) {
        $("#errorTotal").show();
        result = false;
    }

    let testNum = $("#testNum").val();
    if (testNum =="" || testNum <= 0  || testNum > 10) {
        $("#errorTestNum").show();
        result = false;
    }

    let classNum = $("#classNum").val();
    if (classNum =="" || classNum <= 0  || classNum > 99) {
        $("#errorClass").show();
        result = false;
    }

    let title = $("#title").val();
    if (title =="") {
        $("#errorTitle").show();
        result = false;
    }

    let openDateValue = $("#openDate").val();
    let expireDateValue = $("#expireDate").val();
    if (openDateValue != "" && expireDateValue != "") {
        const openDate = new Date(openDateValue);
        const expireDate = new Date(expireDateValue);

        if (openDate >= expireDate) {
            $("#errorDate").show();
            result = false;
        }
    }

    return result;
}

/**
 * テストを作成する
 */
var processKey;
var currentClassNum;
var totalClassNum;

function createTest(event) {
    // htmlでのform処理をキャンセル
    event.preventDefault();

    var res = confirm(
        "問題を作成してもよろしいですか？\n" +
        "\n" +
        "※選択したテストに現在設定されている問題は全て削除されます。"
    );
    if(!res) return;

    processKey = "";
    currentClassNum = 1;
    $("#createResult").html("");
    $('#createTestEachClasses').html("");
    $('#syomonNos').val("");
    $('#individualSelected').val("");

    // エラー表示を全部クリア
    $(".error").hide();

    if (!validation()) return;

    // 章・節で指定する場合
    if ($("#selectRange").val() == "chapter") {
        setSectionNosForForm();
    }
    // 見出し語を個別に指定する場合
    else if ($("#selectRange").val() == "midasi") {
        setMidasiNosForForm();
    }

    // マニュアルモード取得
    var manualMode = ($("#manualon").prop("checked")) ? 1 : 0;
    $("#manualMode").val(manualMode);

    if (manualMode) {
        // 選択されているSyomonNosをセットする
        setSelectedSyomonNos();

        setIndividualSelected();
    }

    disableButtons();
    $("#loading").show();

    totalClassNum = $("#classNum").val();

    let form = $("#testCreator");

    $.ajax({
        url: form.attr('action'),
        type: form.attr('method'),
        data: form.serialize()
    })
    .done(function(data) {
        // 通信が成功
        try {
            const obj = JSON.parse(data);

            if (obj.error != 0)
            {
                if (obj.error_message) alert(obj.error_message);
                else alert("テスト作成時にエラーが発生しました。(エラーコード : " + obj.error + ")");

                return;
            }

            alert("テストが作成できました。");
        } catch (e) {
            alert("テスト作成時にエラーが発生しました(" + e + ")");
        }
    })
    .fail(function(data) {
        // 通信が失敗
        alert("通信に失敗しました。");
    })
    .always(function(data) {
        enableButtons();
        $("#loading").hide();
    });
}

//　書籍変更時の処理
function selectBook() {
    let titleNo = $("#selectBook").val();

    $("#selectBook_0").off("change", changeSelectBookValue);
    $("#selectBook_1").off("change", changeSelectBookValue);
    $("#selectRange").off("change", selectRange);
    $(".pageConditions").off("blur", blurPageConditions);
    $(".numberConditions").off("blur", blurNumberConditions);
    $(".midasiNumberConditions").off("blur", blurMidasiNumberConditions);
    $("input[name=selectBookType]:radio").off("change", changeSelectBookType);
    $("input[name=mode]:radio").off("change", changeReadyOrOmakase);

    resetManualConditions(!isInitialized);

    setUpSelectRange(titleNo);

    if (isInitialized) {
        // 出題頻度のデータをクリアする
        $("#frequencyTooltipContent").html("");

        // マニュアルモードで選択しているものをリセットする
        resetManualConditions(!isInitialized);
        $("#selectIndividual").prop("checked", false);
        $(".frequency").prop("checked", false);
        resetChangeDisplay();

        // 出題の範囲をクリアする
        $(".pageConditions").val('');
        $(".numberConditions").val('');
        $(".midasiNumberConditions").val('');
    }

    initializeSelectRange(quizOption.rangeType);

    // マニュアルモード用処理
    if ($("#manualon").prop("checked")) {
        // 表示切替ボタン対応
        $("#changeDisplayButtonWrapper").hide();
        $("#frequencyButtonWrapper").hide();

        if (currentBookData != null && currentBookData["level_flg"] > 0) {
            $("#changeDisplayButtonWrapper").show();
        }

        if (currentBookData != null && currentBookData["frequency_flg"] > 0) {
            SetupFrequencyButton();
        }
        else {
            // 問題の形式表示
            // Note: 出題頻度の項目がある場合はその処理の後に下記が実行される
            changeQuestionTypeData(!isInitialized);
        }

        if (isInitialized) {
            // 表示切替のチェックを外す
            resetChangeDisplay();
        }
    }

    $(".pageConditions").attr("disabled", (titleNo != "") ? false : true);
    $("#createButton").prop('disabled', (titleNo != "") ? false : true);

    if (isInitialized) {
        $("#showQuestionNo").prop('checked', false).change();
        $("#showMidasiNo").prop('checked', false).change();
    }

    $("#selectBook_0").on("change", changeSelectBookValue);
    $("#selectBook_1").on("change", changeSelectBookValue);
    $("#selectRange").on("change", selectRange);
    $(".pageConditions").on("blur", blurPageConditions);
    $(".numberConditions").on("blur", blurNumberConditions);
    $(".midasiNumberConditions").on("blur", blurMidasiNumberConditions);
    $("input[name=selectBookType]:radio").on("change", changeSelectBookType);
    $("input[name=mode]:radio").on("change", changeReadyOrOmakase);
}

function initializeSelectRange(optionValue) {
    var isExist = false;
    $("#selectRange option").each(function(i){
        if ($(this).val() == optionValue) isExist = true;
    });

    if (!isExist) {
        optionValue = "page";
        quizOption.rangeType = optionValue;
    }

    $("#selectRange").val(optionValue);
    changeSelectRange(optionValue);
}

// 出題の範囲を設定する
function setUpSelectRange(titleNo) {
    currentBookData = null;

    // 範囲設定のoptionを再生成
    $("#selectRange > option").remove();
    $("#selectRange").append( $("<option>").html("ページで指定する").val("page") );

    $("#pageMin").html(0);
    $("#pageMax").html(0);

    $("#questionNoMin").html(0);
    $("#questionNoMax").html(0);

    $("#showQuestionNoContainer").hide();
    $("#showMidasiNoContainer").hide();

    // データがない場合はここで終了
    if (bookData[titleNo] == undefined) return;

    currentBookData = bookData[titleNo];

    $("#pageMin").html(currentBookData["page_min"]);
    $("#pageMax").html(currentBookData["page_max"]);

    $("#questionNoMin").html(currentBookData["midasi_no_min"]);
    $("#questionNoMax").html(currentBookData["midasi_no_max"]);

    $("#midasiNoMin").html(currentBookData["midasi_no_min"]);
    $("#midasiNoMax").html(currentBookData["midasi_no_max"]);

    if (currentBookData["question_no_flg"] > 0) {
        $("#selectRange").append( $("<option>").html("問題番号で指定する").val("questionNo") );
        $("#showQuestionNoContainer").show();
    }

    if (currentBookData["chapter_flg"] > 0) {
        $("#selectRange").append( $("<option>").html("章・節で指定する").val("chapter") );
    }

    if (currentBookData["midasi_no_flg"] > 0) {
        $("#selectRange").append( $("<option>").html("見出し語番号で指定する").val("midasiNo") );
        $("#showMidasiNoContainer").show();
    }

    if (currentBookData["midasi_flg"] > 0) {
        $("#selectRange").append( $("<option>").html("見出し語を個別に指定する").val("midasi") );
    }
}

// 出題範囲切り替え
function changeSelectRange(val) {
    $("#pageCondition").hide();
    $("#numberCondition").hide();
    $("#chapterCondition").hide();
    $("#midasiNumberCondition").hide();
    $("#midasiCondition").hide();

    switch(val) {
        case "page":
            $("#pageCondition").show();
            break;

        case "questionNo":
            $("#numberCondition").show();
            break;

        case "chapter":
            createChapterCondition();
            break;

        case "midasiNo":
            $("#midasiNumberCondition").show();
            break;

        case "midasi":
            createMidasiCondition();
            break;
    }
}

/**
 * 認証期限切れ処理
 */
function authExpired() {
    alert("認証が切れました、ログイン画面に戻ります。");
    window.location.href = "./login.php";
}

/**
 * おまかせテスト作成する書籍のタイプ変更処理
 */
function changeSelectBookType() {
    if ($("input[name=selectBookType]:checked").val() == 1) {
        $("#selectBook_0").val("");
        $("#selectBook_0").hide();
        $("#selectBook_1").show();
    }
    else {
        $("#selectBook_0").show();
        $("#selectBook_1").val("");
        $("#selectBook_1").hide();
    }

    $("#selectBook").val("");
    currentBookData = null;

    changeSelectBookValue();
}

function changeReadyOrOmakase() {
    selectBook();
}


// conditionSubRangeRow の表示切替
var conditionSubRangeRowFlag = false;
function toggleConditionSubRangeRow() {
    conditionSubRangeRowFlag = !conditionSubRangeRowFlag;

    if (conditionSubRangeRowFlag) $(".conditionSubRangeRow").show();
    else                          $(".conditionSubRangeRow").hide();
}

//全角　→　半角
function toHalfWidth(input) {
    return input.replace(/[！-～]/g,
        function(input){
            return String.fromCharCode(input.charCodeAt(0)-0xFEE0);
        }
    );
}
