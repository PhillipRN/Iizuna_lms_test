// CSV学生アップロード機能
$(function() {
    // 初期化
    const CsvUploadManager = {
        // モーダルHTML
        modalHtml: `
            <div id="csvUploadModal" class="modal">
                <div class="modal-content">
                    <div class="modal-header">
                        <h3>CSV新規登録</h3>
                        <span class="close">&times;</span>
                    </div>
                    <div class="modal-body">
                        <p id="uploadClassInfo"></p>
                        <div class="file-upload-area">
                            <input type="file" id="csvFileInput" accept=".csv" />
                            <div class="template-download">
                                <a href="student_csv_template.php" class="template-link">CSVテンプレートをダウンロード</a>
                            </div>
                        </div>
                        <div id="uploadMessage" class="message-area"></div>
                        <div id="previewArea" style="display: none;">
                            <h4>登録予定の生徒一覧</h4>
                            <div id="duplicateWarning" class="warning-message" style="display: none; color: red;"></div>
                            <div id="passwordWarning" class="warning-message" style="display: none; color: red;"></div>
                            <div class="preview-table-container">
                                <table id="previewTable" class="separateTable">
                                    <thead>
                                        <tr>
                                            <th>氏名</th>
                                            <th>学籍番号</th>
                                            <th>ログインID</th>
                                            <td>学校名</td>
                                            <td>学年</td>
                                            <td>クラス</td>
                                            <th>状態</th>
                                        </tr>
                                    </thead>
                                    <tbody></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <!-- 初期ステップ（ファイル選択画面）のフッター -->
                        <div id="initialFooter">
                            <button id="closeModalBtn" class="normalButton small disabled">キャンセル</button>
                            <button id="submitCsvBtn" class="normalButton small">送信</button>
                        </div>
                        <!-- プレビュー確認画面のフッター -->
                        <div id="confirmFooter" style="display: none;">
                            <p>生徒の新規登録を実行しますか？</p>
                            <button id="cancelUploadBtn" class="normalButton small">キャンセル</button>
                            <button id="executeUploadBtn" class="normalButton small">実行</button>
                        </div>
                    </div>
                </div>
            </div>
        `,

        // スタイル
        modalStyle: `
            <style>
                .modal {
                    display: none;
                    position: fixed;
                    z-index: 1000;
                    left: 0;
                    top: 0;
                    width: 100%;
                    height: 100%;
                    background-color: rgba(0,0,0,0.4);
                    overflow: auto; /* 全体をスクロール可能に */
                }
                .modal-content {
                    background-color: #fff;
                    margin: 5vh auto; /* 上下のマージンを画面高さの5%に変更 */
                    padding: 20px;
                    border: 1px solid #888;
                    width: 80%;
                    max-width: 800px;
                    max-height: 90vh; /* モーダルの最大高さを画面の90%に制限 */
                    display: flex;
                    flex-direction: column;
                    position: relative;
                }
                .modal-header {
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    margin-bottom: 15px;
                    flex-shrink: 0; /* ヘッダーは縮小しない */
                }
                .close {
                    color: #aaa;
                    font-size: 28px;
                    font-weight: bold;
                    cursor: pointer;
                    position: absolute;
                    right: 20px;
                    top: 15px;
                }
                .modal-body {
                    margin-bottom: 20px;
                    overflow-y: auto; /* 内容が多い場合にスクロール */
                    flex: 1; /* 残りのスペースを占有 */
                }
                .modal-footer {
                    text-align: right;
                    flex-shrink: 0; /* フッターは縮小しない */
                    padding-top: 10px;
                    border-top: 1px solid #eee;
                }
                .file-upload-area {
                    margin: 15px 0;
                }
                .template-download {
                    margin: 10px 0;
                    font-size: 14px;
                }
                .template-link {
                    color: #0066cc;
                    text-decoration: underline;
                }
                .message-area {
                    margin: 10px 0;
                    color: #cc0000;
                }
                .preview-table-container {
                    max-height: calc(90vh - 400px);
                    overflow-y: auto;
                    margin: 15px 0;
                    border: 1px solid #ddd;
                }
                .warning-message {
                    padding: 10px;
                    margin: 10px 0;
                    background-color: #fff8f8;
                    border-left: 4px solid #cc0000;
                }
                tr.duplicate {
                    background-color: #ffdddd;
                }
                tr.login-duplicate {
                    background-color: #ffeecc;
                }
                tr.password-error {
                    background-color: #ffcccc;
                }
                #confirmArea {
                    margin-top: 20px;
                    text-align: right;
                }
                
                /* 画面サイズに応じたレスポンシブ対応 */
                @media screen and (max-height: 620px) {
                    .modal-content {
                        margin: 0 auto; /* 上下のマージンを削除 */
                        height: 100vh; /* モーダルを画面いっぱいに */
                        max-height: 100vh;
                        border-radius: 0;
                    }
                    .preview-table-container {
                        max-height: 150px; /* より小さい画面での高さ制限 */
                    }
                }
            </style>
        `,

        init: function() {
            // モーダルとスタイルをbodyに追加
            if ($('#csvUploadModal').length === 0) {
                $('body').append(this.modalStyle + this.modalHtml);
            }

            // CSV新規登録ボタンのクリックイベント
            $('#csvUploadBtn').on('click', this.openModal);

            // モーダル内の各種イベント設定
            $(document).on('click', '.close, #closeModalBtn', this.closeModal);
            $(document).on('click', '#submitCsvBtn', this.validateAndSubmitCsv);
            $(document).on('click', '#cancelUploadBtn', this.resetPreview);
            $(document).on('click', '#executeUploadBtn', this.executeUpload);

            // ウィンドウリサイズ時のモーダル調整
            $(window).on('resize', this.adjustModalForScreenSize);
        },

        // 画面サイズに合わせてモーダルを調整
        adjustModalForScreenSize: function() {
            const windowHeight = $(window).height();
            const $modalContent = $('.modal-content');

            if (windowHeight <= 580) {
                // 小さい画面の場合の調整（580px以下）
                $modalContent.css({
                    'margin': '0 auto',
                    'height': '100vh',
                    'max-height': '100vh'
                });

                // プレビューテーブルの高さ調整
                $('.preview-table-container').css('max-height', '150px');
            } else {
                // 通常サイズの画面の場合
                $modalContent.css({
                    'margin': '5vh auto',
                    'height': 'auto',
                    'max-height': '90vh'
                });

                // プレビューテーブルの高さを元に戻す
                $('.preview-table-container').css('max-height', 'calc(90vh - 400px)');
            }
        },

        openModal: function() {
            const selectedClass = $('#resultClass option:selected').text();
            $('#uploadClassInfo').text(`「${selectedClass}」に生徒を新規登録しますか？
登録する場合はCSVファイルを選択し、送信を実行してください。`);

            // リセット
            $('#csvFileInput').val('');
            $('#uploadMessage').text('').hide();
            $('#previewArea').hide();
            $('#confirmFooter').hide();
            $('#initialFooter').show();

            $('#csvUploadModal').show();

            // モーダルを開いた後に画面サイズに合わせて調整
            CsvUploadManager.adjustModalForScreenSize();
        },

        closeModal: function() {
            $('#csvUploadModal').hide();
            CsvUploadManager.resetPreview();
        },

        validateAndSubmitCsv: function() {
            const fileInput = document.getElementById('csvFileInput');
            const file = fileInput.files[0];

            if (!file) {
                $('#uploadMessage').text('CSVファイルを選択してください。').show();
                return;
            }

            if (file.type !== 'text/csv' && !file.name.endsWith('.csv')) {
                $('#uploadMessage').text('CSVファイル形式のみアップロード可能です。').show();
                return;
            }

            // ファイルアップロード実行
            CsvUploadManager.uploadCsvForPreview(file);
        },

        uploadCsvForPreview: function(file) {
            const formData = new FormData();
            formData.append('csv_file', file);
            formData.append('lms_code_id', $('#resultClass').val());
            formData.append('_csrf', $('#csrf').val());

            $.ajax({
                url: 'student_csv_preview.php',
                type: 'POST',
                data: formData,
                contentType: false,
                processData: false,
                dataType: 'json',
                success: function(response) {
                    if (response.error) {
                        $('#uploadMessage').text(`エラー: ${response.error.message}`).show();
                        return;
                    }

                    // プレビュー表示
                    CsvUploadManager.displayPreview(response);
                },
                error: function() {
                    $('#uploadMessage').text('サーバー通信エラーが発生しました。').show();
                }
            });
        },

        displayPreview: function(data) {
            const $previewTable = $('#previewTable tbody');
            $previewTable.empty();

            let hasDuplicates = false;
            let hasPasswordErrors = false;

            data.students.forEach(function(student) {
                let rowClass = '';
                if (student.hasDuplicates) {
                    rowClass = 'duplicate';
                    hasDuplicates = true;
                } else if (student.hasLoginIdDuplicate) {
                    rowClass = 'login-duplicate';
                } else if (student.hasPasswordError) {
                    rowClass = 'password-error';
                    hasPasswordErrors = true;
                }

                const row = `
                    <tr class="${rowClass}">
                        <td>${student.name}</td>
                        <td>${student.student_number || ''}</td>
                        <td>${student.login_id || ''}</td>
                        <td>${student.school_name || ''}</td>
                        <td>${student.school_grade || ''}</td>
                        <td>${student.school_class || ''}</td>
                        <td>${student.status || '新規登録'}</td>
                    </tr>
                `;
                $previewTable.append(row);
            });

            // 重複警告表示
            if (hasDuplicates) {
                const duplicateCount = data.duplicateCount || '複数';
                $('#duplicateWarning').html(`同姓同名の生徒が既に${duplicateCount}名登録されています。<br />CSV内に不要な行がある場合は削除をしてから再度ファイルを送信しなおしてください。`).show();
            } else {
                $('#duplicateWarning').hide();
            }

            // ログインID重複警告
            if (data.hasLoginIdDuplicates) {
                const loginIdCount = data.loginIdDuplicates ? data.loginIdDuplicates.length : 0;
                const loginIdsText = data.loginIdDuplicates ? data.loginIdDuplicates.join(', ') : '';

                // 警告メッセージをduplicateWarningに追加、または新しい要素を作成
                let warningHtml = $('#duplicateWarning').html();

                if (warningHtml) {
                    warningHtml += `<br><br>以下のログインIDは既に使用されています: ${loginIdsText}<br>ログインIDが重複している生徒は登録できません。`;
                } else {
                    warningHtml = `以下のログインIDは既に使用されています: ${loginIdsText}<br>ログインIDが重複している生徒は登録できません。`;
                }

                $('#duplicateWarning').html(warningHtml).show();
            }

            // パスワードエラー警告
            if (data.hasPasswordErrors) {
                $('#passwordWarning').html(`
                    パスワードが条件を満たしていない生徒が含まれています。<br>
                    パスワードは以下の条件を満たす必要があります：<br>
                    ・8文字以上<br>
                    ・半角英数字のみ（記号は「_」と「-」のみ使用可）<br>
                    ・英字と数字の両方を含む<br>
                    パスワードエラーがある生徒は登録できません。
                `).show();
            } else {
                $('#passwordWarning').hide();
            }

            // 登録不可のエラーがある場合、実行ボタンを無効化
            if (data.hasLoginIdDuplicates || data.hasPasswordErrors) {
                $('#executeUploadBtn').prop('disabled', true).addClass('disabled');
            } else {
                $('#executeUploadBtn').prop('disabled', false).removeClass('disabled');
            }

            // ステップの表示を切り替え
            $('#initialFooter').hide();
            $('#confirmFooter').show();
            $('#previewArea').show();

            // 画面サイズに合わせた調整を再適用
            CsvUploadManager.adjustModalForScreenSize();
        },

        resetPreview: function() {
            $('#previewArea').hide();
            $('#confirmFooter').hide();
            $('#initialFooter').show();
            $('#previewTable tbody').empty();
            $('#duplicateWarning').hide();
            $('#passwordWarning').hide();
            $('#executeUploadBtn').prop('disabled', false).removeClass('disabled');

            // 画面サイズに合わせた調整を再適用
            CsvUploadManager.adjustModalForScreenSize();
        },

        executeUpload: function() {
            const formData = new FormData();
            formData.append('csv_file', $('#csvFileInput')[0].files[0]);
            formData.append('lms_code_id', $('#resultClass').val());
            formData.append('_csrf', $('#csrf').val());
            formData.append('execute', '1');

            $.ajax({
                url: 'student_csv_register.php',
                type: 'POST',
                data: formData,
                contentType: false,
                processData: false,
                dataType: 'json',
                success: function(response) {
                    if (response.error) {
                        $('#uploadMessage').text(`エラー: ${response.error.message}`).show();
                        return;
                    }

                    // 成功表示
                    alert(`${response.registeredCount}人の生徒を登録しました。`);
                    CsvUploadManager.closeModal();

                    // 画面リロード
                    location.reload();
                },
                error: function() {
                    $('#uploadMessage').text('サーバー通信エラーが発生しました。').show();
                }
            });
        }
    };
    // 初期化
    CsvUploadManager.init();
});