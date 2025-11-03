/**
 * Quiz submission recovery functionality
 * Checks for pending quiz submissions in localStorage and attempts to submit them
 */

/**
 * Save quiz submission data to localStorage
 * @param {string} quizId - The ID of the quiz
 * @param {object} formData - The form data to be submitted
 */
function saveQuizSubmissionToLocalStorage(quizId, formData) {
    const submissionData = {
        formData: formData,
        submissionTime: new Date().toISOString()
    };
    localStorage.setItem('pendingQuizSubmission_' + quizId, JSON.stringify(submissionData));
}

/**
 * Remove quiz submission data from localStorage
 * @param {string} quizId - The ID of the quiz
 */
function removeQuizSubmissionFromLocalStorage(quizId) {
    localStorage.removeItem('pendingQuizSubmission_' + quizId);
}

/**
 * Submit pending quiz data from localStorage
 * @param {boolean} showSuccessMessage - Whether to show a success message after submissions (default: false)
 * @param {boolean} showErrorMessage - Whether to show error messages for failed submissions (default: false)
 */
function submitPendingQuizData(showSuccessMessage = false, showErrorMessage = false) {
    let pendingSubmissions = [];
    
    for (let i = 0; i < localStorage.length; i++) {
        const key = localStorage.key(i);
        if (key && key.startsWith('pendingQuizSubmission_')) {
            try {
                const submissionData = JSON.parse(localStorage.getItem(key));
                pendingSubmissions.push({
                    key: key,
                    data: submissionData
                });
            } catch (e) {
                console.error('Invalid submission data:', e);
                localStorage.removeItem(key);
            }
        }
    }
    
    if (pendingSubmissions.length === 0) return;
    
    let processedCount = 0;
    
    function processNextSubmission(index) {
        if (index >= pendingSubmissions.length) {
            if (showSuccessMessage && processedCount > 0) {
                bootbox.alert({
                    centerVertical: true,
                    closeButton: false,
                    message: '未送信のテスト結果を送信しました。',
                    callback: function () {
                        window.location.reload();
                    }
                });
            }
            return;
        }
        
        const submission = pendingSubmissions[index];
        
        $.ajax({
            url: 'quiz_register.php',
            type: 'post',
            data: submission.data.formData,
            timeout: 30000,
            cache: false,
            headers: {
                'Cache-Control': 'no-cache, no-store, must-revalidate',
                'Pragma': 'no-cache',
                'Expires': '0'
            }
        })
        .done(function(result) {
            if (result.error == 0 && result.result_id > 0) {
                localStorage.removeItem(submission.key);
                processedCount++;
            } else if (showErrorMessage) {
                bootbox.alert({
                    centerVertical: true,
                    message: "未送信のテスト結果の送信時にエラーが発生しました。(エラーコード : " + result.error + ")"
                });
            }
        })
        .fail(function (jqXHR, textStatus, errorThrown) {
            var errorMessage = '';

            switch (textStatus)
            {
                case 'timeout':
                    errorMessage = '未送信のテスト結果の送信がタイムアウトしました。';
                    break;
                case 'error':
                    errorMessage = '未送信のテスト結果の送信中にエラーが発生しました。';
                    break;
                case 'abort':
                    errorMessage = '未送信のテスト結果の送信中に通信が中断されました。';
                    break;
                case 'parsererror':
                    errorMessage = '未送信のテスト結果送信後のデータの取得に失敗しました。';
                    break;
                default:
                    errorMessage = 'その他のエラーが発生しました。(' + jqXHR.status + ' : ' + textStatus + ')';
                    break;
            }

            bootbox.alert({
                centerVertical: true,
                message: errorMessage
            });
        })
        .always(function() {
            processNextSubmission(index + 1);
        });
    }
    
    processNextSubmission(0);
}

/**
 * Check for pending submissions and submit them with success message
 * Used primarily on the index page
 */
function checkPendingSubmissions() {
    submitPendingQuizData(true, true);
}
