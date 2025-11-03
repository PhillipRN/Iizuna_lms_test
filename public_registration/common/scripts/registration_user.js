function zen2han(elm) {
    elm.value = elm.value.replace(/[Ａ-Ｚａ-ｚ０-９！-～]/g, function(s) {
        console.log(elm.value)
        return String.fromCharCode(s.charCodeAt(0) - 0xFEE0);
    });
}

function checkZip(str) {
    return str.match(/^\d{3}-\d{4}$/);
}