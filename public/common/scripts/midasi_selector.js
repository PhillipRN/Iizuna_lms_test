$(function() {
    $('#midasiCondition')
        .on('changed.jstree', function (e, data) {
            if (data.changed.selected.length == 0 && data.changed.deselected.length == 0) return;

            var isChanged = false;

            if (data.changed.selected.length != Object.keys(quizOption.midasiNumbers).length) {
                isChanged = true;
            }
            else if (data.changed.deselected.length > 0) {
                isChanged = true;
            }
            else {
                for (var i = 0; i < data.changed.selected.length; ++i) {

                    var key = data.changed.selected[i];

                    if (!quizOption.midasiNumbers[key]) {
                        isChanged = true;
                        break;
                    }
                }
            }

            if (isChanged) {
                quizOption.midasiNumbers = {};
                changeQuestionTypeDataNotInitialize();
            }
        })
        .jstree({
        'core': {
            'themes': {
                'icons': false,
                'dots': false
            }
        },
        'plugins': ['checkbox', 'changed']
    });
});

// 見出し語を個別に指定するリストを作る
function createMidasiCondition() {
    let initialize = !isInitialized;
    let form = $("#midasiForm");

    let val = $("#selectBook").val();
    let input = $("#midasiTitleNo");
    input.val(val);

    $.ajax({
        url: form.attr('action'),
        type: form.attr('method'),
        data: form.serialize(),
        timeout: 10000,
    })
        .done(function(data) {
            // 通信が成功
            try {
                const obj = JSON.parse(data);

                if (obj.error != 0)
                {
                    alert("見出しデータ取得時にエラーが発生しました。(エラーコード : " + obj.error + ")");
                    return;
                }

                if (initialize) {
                    // obj.midasinos の下に state.selected = true を追加する
                    for (var i=0; i<obj.midasinos.length; ++i) {

                        if (quizOption.midasiNumbers[obj.midasinos[i].id]) {
                            obj.midasinos[i].state = {selected: true};
                        }
                    }
                }

                $('#midasiCondition').jstree(true).deselect_all();
                $('#midasiCondition').jstree(true).settings.core.data = obj.midasinos;
                $('#midasiCondition').jstree(true).refresh(true, true);
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
            $("#midasiCondition").show();
        });
}

// フォームにmidasiNosの値をセットする
function setMidasiNosForForm() {
    let checked = $('#midasiCondition').jstree(true).get_checked(false);
    var ids = [];

    for (var i=0; i<checked.length; ++i) {
        let id = checked[i];
        if (id.indexOf("midasino_") >= 0) {
            ids.push(id.replace("midasino_", ""));
        }
    }

    $("#midasiNos").val(ids.join(","));
}