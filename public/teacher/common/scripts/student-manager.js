$(function() {
    // 初期化処理
    const StudentManager = {
        init: function() {
            // イベントハンドラの設定
            $('#resultClass').on('change', this.changeClass);
            $('#studentNumberHeader').on('click', this.changeSortStudentNumber);

            // 動的に生成された要素に対するイベント委任
            $(document).on('click', '.reset-password-btn', function() {
                const studentName = $(this).data('student-name');
                const studentId = $(this).data('student-id');
                StudentManager.resetPassword(studentName, studentId);
            });

            // ページネーションボタンのイベント
            $(document).on('click', '.page-nav-btn:not(.disabled)', function() {
                const page = $(this).data('page');
                StudentManager.changePage(page);
            });

            // disabled クラスを持つボタンをクリックできないようにする
            $(document).on('click', '.disabled', function(e) {
                e.preventDefault();
                return false;
            });

            // 全選択チェックボックスのイベント
            $('#selectAll').on('change', function() {
                $('.student-checkbox').prop('checked', $(this).prop('checked'));
            });

            // 個別チェックボックス変更時、全選択状態を更新
            $(document).on('change', '.student-checkbox', function() {
                const allChecked = $('.student-checkbox:checked').length === $('.student-checkbox').length;
                $('#selectAll').prop('checked', allChecked);
            });

            // 一括操作ドロップダウンの変更時
            $('#bulkActionSelect').on('change', function() {
                const action = $(this).val();
                // コード登録が選択されたらコード選択ドロップダウンを表示
                if (action === 'code') {
                    $('#bulkCodeSelect').show();
                } else {
                    $('#bulkCodeSelect').hide();
                }
            });

            // 一括操作実行ボタン
            $('#bulkActionExecute').on('click', function() {
                const action = $('#bulkActionSelect').val();
                if (!action) {
                    alert('操作を選択してください。');
                    return;
                }

                const selectedStudents = $('.student-checkbox:checked');
                if (selectedStudents.length === 0) {
                    alert('対象の生徒を選択してください。');
                    return;
                }

                if (action === 'delete') {
                    StudentManager.bulkDeleteStudents();
                } else if (action === 'code') {
                    StudentManager.bulkRegisterCode();
                } else if (action === 'password') {
                    StudentManager.bulkResetPassword();
                }
            });
        },

        changeClass: function() {
            $('#displayForm').submit();
        },

        changeSortStudentNumber: function() {
            const $sortStudentNumber = $('#sortStudentNumber');
            const currentVal = parseInt($sortStudentNumber.val(), 10);
            let newVal = 0;

            switch (currentVal) {
                case 0: newVal = 1; break;
                case 1: newVal = 2; break;
                case 2: newVal = 0; break;
            }

            $sortStudentNumber.val(newVal);
            $('#displayForm').submit();
        },

        changePage: function(page) {
            const $form = $('#displayForm');
            $('<input>').attr({
                type: 'hidden',
                name: 'page',
                value: page
            }).appendTo($form);

            $form.submit();
        },

        resetPassword: function(studentName, studentId) {
            if (!confirm(`「${studentName}」のパスワードをリセットします。\nよろしいですか？`)) {
                return;
            }

            $.ajax({
                url: 'student_password_reset.php',
                type: 'post',
                data: {
                    'student_id': studentId,
                    '_csrf': $('#csrf').val()
                },
                dataType: 'json',
                timeout: 30000
            })
                .done(function(result) {
                    if (result.error) {
                        alert(`${result.error.message} (${result.error.code})`);
                    } else if (result.result === 'OK') {
                        alert('パスワードを password にリセットしました。');
                        location.reload();
                    } else {
                        alert('予期せぬエラーが発生しました。');
                    }
                })
                .fail(function() {
                    alert('通信エラーが発生しました。');
                });
        },

        // 一括削除メソッドを追加
        bulkDeleteStudents: function() {
            const selectedStudents = $('.student-checkbox:checked');
            const studentNames = [];
            const studentIds = [];

            selectedStudents.each(function() {
                studentNames.push($(this).data('student-name'));
                studentIds.push($(this).data('student-id'));
            });

            if (!confirm(`選択された ${studentNames.length} 人の生徒を削除しようとしています。\n一度削除すると元に戻すことは出来ません。\n\n対象: ${studentNames.join(', ')}\n\n本当に削除しますか？`)) {
                return;
            }

            // プログレスカウンター
            let successCount = 0;
            let errorCount = 0;
            let totalCount = studentIds.length;

            // 各生徒を個別に削除
            const deleteNextStudent = function(index) {
                if (index >= studentIds.length) {
                    // 全ての削除処理が完了
                    alert(`削除処理が完了しました。\n成功: ${successCount}件\n失敗: ${errorCount}件`);
                    if (successCount > 0) {
                        location.reload();
                    }
                    return;
                }

                const studentId = studentIds[index];
                const studentName = studentNames[index];

                $.ajax({
                    url: 'student_delete.php',
                    type: 'post',
                    data: {
                        'student_id': studentId,
                        '_csrf': $('#csrf').val()
                    },
                    dataType: 'json',
                    timeout: 30000
                })
                    .done(function(result) {
                        if (result.error) {
                            console.error(`「${studentName}」の削除に失敗: ${result.error.message}`);
                            errorCount++;
                        } else if (result.result === 'OK') {
                            console.log(`「${studentName}」を削除しました。`);
                            successCount++;
                        } else {
                            console.error(`「${studentName}」の削除に失敗: 予期せぬエラー`);
                            errorCount++;
                        }
                        // 次の生徒を処理
                        deleteNextStudent(index + 1);
                    })
                    .fail(function() {
                        console.error(`「${studentName}」の削除に失敗: 通信エラー`);
                        errorCount++;
                        // 次の生徒を処理
                        deleteNextStudent(index + 1);
                    });
            };

            // 削除処理開始
            deleteNextStudent(0);
        },

        // 一括コード登録メソッドを追加
        bulkRegisterCode: function() {
            const selectedStudents = $('.student-checkbox:checked');
            const studentIds = [];

            selectedStudents.each(function() {
                studentIds.push($(this).data('student-id'));
            });

            const selectedCode = $('#bulkCodeSelect').val();
            const selectedCodeText = $('#bulkCodeSelect option:selected').text();

            if (!confirm(`選択されている生徒に「${selectedCodeText}」のコードを登録します、よろしいですか？\n※すでに同じコードが登録されている生徒は対象外となります。`)) {
                return;
            }

            $.ajax({
                url: 'student_register_code.php',
                type: 'post',
                data: {
                    'student_ids': studentIds,
                    'lms_code_id': selectedCode,
                    '_csrf': $('#csrf').val()
                },
                dataType: 'json',
                timeout: 30000
            })
                .done(function(result) {
                    if (result.error) {
                        alert(`コード登録に失敗しました: ${result.error.message} (${result.error.code})`);
                    } else if (result.result === 'OK') {
                        alert(`${result.registeredCount}人の生徒にコードを登録しました。\n${result.skippedCount}人の生徒は既に登録済みのためスキップしました。`);
                        location.reload();
                    } else {
                        alert('予期せぬエラーが発生しました。');
                    }
                })
                .fail(function() {
                    alert('通信エラーが発生しました。');
                });
        },

        // 一括パスワードリセットメソッド
        bulkResetPassword: function() {
            const selectedStudents = $('.student-checkbox:checked');
            const validStudents = []; // ログインIDを持つ有効な生徒
            let skippedCount = 0; // スキップする生徒数

            // 各生徒を確認し、login_idを持つ生徒のみを対象とする
            selectedStudents.each(function() {
                const studentId = $(this).data('student-id');
                const studentName = $(this).data('student-name');
                const loginId = $(this).data('login-id');

                if (loginId && loginId !== '') {
                    validStudents.push({id: studentId, name: studentName});
                } else {
                    skippedCount++;
                }
            });

            if (validStudents.length === 0) {
                alert('選択された生徒の中にログインIDを持つ生徒がいません。パスワードリセットをスキップします。');
                return;
            }

            if (!confirm(`選択された ${validStudents.length} 人の生徒のパスワードをリセットします。\n${skippedCount}人の生徒はログインIDがないためスキップされます。\nよろしいですか？`)) {
                return;
            }

            // プログレスカウンター
            let successCount = 0;
            let errorCount = 0;

            // 各生徒のパスワードを個別にリセット
            const resetNextPassword = function(index) {
                if (index >= validStudents.length) {
                    // 全ての処理が完了
                    alert(`パスワードリセット処理が完了しました。\n成功: ${successCount}件\n失敗: ${errorCount}件\nスキップ: ${skippedCount}件`);
                    return;
                }

                const student = validStudents[index];

                $.ajax({
                    url: 'student_password_reset.php',
                    type: 'post',
                    data: {
                        'student_id': student.id,
                        '_csrf': $('#csrf').val()
                    },
                    dataType: 'json',
                    timeout: 30000
                })
                    .done(function(result) {
                        if (result.error) {
                            console.error(`「${student.name}」のパスワードリセットに失敗: ${result.error.message}`);
                            errorCount++;
                        } else if (result.result === 'OK') {
                            console.log(`「${student.name}」のパスワードをリセットしました。`);
                            successCount++;
                        } else {
                            console.error(`「${student.name}」のパスワードリセットに失敗: 予期せぬエラー`);
                            errorCount++;
                        }
                        // 次の生徒を処理
                        resetNextPassword(index + 1);
                    })
                    .fail(function() {
                        console.error(`「${student.name}」のパスワードリセットに失敗: 通信エラー`);
                        errorCount++;
                        // 次の生徒を処理
                        resetNextPassword(index + 1);
                    });
            };

            // パスワードリセット処理開始
            resetNextPassword(0);
        }
    };

    // 初期化を実行
    StudentManager.init();
});