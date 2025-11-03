$(function() {
    $("#registrationForm").submit(registrationSubmit);
});

function registrationSubmit(event) {
    $("#hash_key").val()

    if ($("#hash_key").val() == "") {
        alert("書籍キーを入力してください。");
        return false;
    }

    return true;
}
