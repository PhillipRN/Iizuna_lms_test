$(function() {
    $('#chapterCondition')
        .on('changed.jstree', function (e, data) {
            if (data.changed.selected.length == 0 && data.changed.deselected.length == 0) return;

            var isChanged = false;
            var changedCount = 0;
            for (var i = 0; i < data.changed.selected.length; ++i) {
                var key = data.changed.selected[i];
                if (key.indexOf("sec_id_") >= 0) {
                    ++changedCount;
                }
            }

            if (changedCount != Object.keys(quizOption.sectionNumbers).length) {
                isChanged = true;
            }
            else if (data.changed.deselected.length > 0) {
                isChanged = true;
            }
            else {
                for (var i = 0; i < data.changed.selected.length; ++i) {
                    var key = data.changed.selected[i];

                    if (key.indexOf("sec_id_") < 0) continue;

                    if (!quizOption.sectionNumbers[key]) {
                        isChanged = true;
                        break;
                    }
                }
            }

            if (isChanged){
                quizOption.sectionNumbers = {};
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

// 章・節で指定するリストを作る
function createChapterCondition() {
    let initialize = !isInitialized;
    let form = $("#chapterForm");

    let val = $("#selectBook").val();
    let input = $("#chapterTitleNo");
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
                    alert("章データ取得時にエラーが発生しました。(エラーコード : " + obj.error + ")");
                    return;
                }

                if (initialize) {
                    // obj.midasinos の下に state.selected = true を追加する
                    for (var i=0; i<obj.chapters.length; ++i) {
                        if (obj.chapters[i].children == null) {
                            if (quizOption.sectionNumbers[obj.chapters[i].id]) {
                                obj.chapters[i].state = {selected: true};
                            }
                            continue;
                        }

                        var children = obj.chapters[i].children;

                        for (var j=0; j<children.length; ++j) {
                            if (quizOption.sectionNumbers[children[j].id]) {
                                obj.chapters[i].children[j].state = {selected: true};
                            }
                        }
                    }
                }

                $('#chapterCondition').jstree(true).deselect_all();
                $('#chapterCondition').jstree(true).settings.core.data = obj.chapters;
                $('#chapterCondition').jstree(true).refresh(true, true);
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
            $("#chapterCondition").show();
        });
}

// フォームにsectionNosの値をセットする
function setSectionNosForForm() {
    let checked = $('#chapterCondition').jstree(true).get_checked(false);
    var ids = [];

    for (var i=0; i<checked.length; ++i) {
        let id = checked[i];
        if (id.indexOf("sec_id_") >= 0) {
            ids.push(id.replace("sec_id_", ""));
        }
    }

    $("#sectionNos").val(ids.join(","));
}