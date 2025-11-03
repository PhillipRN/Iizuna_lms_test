function openPreview(key) {
    var url = '../student/onigiri_quiz.php?quiz_id=' + key + '&preview=1';
    var options = 'width=400px,height=800px,menubar=no';
    window.open(url, 'PreviewWindow', options);
}

function openResultPreview(key) {
    var url = '../student/onigiri_quiz_result_preview.php?quiz_id=' + key;
    var options = 'width=400px,height=800px,menubar=no';
    window.open(url, 'PreviewWindow', options);
}