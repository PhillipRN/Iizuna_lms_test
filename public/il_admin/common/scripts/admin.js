function deleteTeacher(id) {
    if (window.confirm("このデータを削除しますか？")) {
        location.href = "./teacher_register.php?del=" + id;
    }
}

function submitRegistrationKey() {
    if ($("#registrationTitleNo").val() == "") {
        alert("書籍を選択してください。")
        return false;
    }
    return true;
}

function disableRegistrationKey(id, hashKey) {
    if (window.confirm("「" + hashKey.slice(0,6) + "...」のキーを無効化しますか？")) {
        location.href = "./registration_key_disable.php?id=" + id;
    }
}

function checkZipCode(zipCode) {
    return zipCode.match(/^\d{7}$/) != null
}