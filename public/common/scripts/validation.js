function isPositiveInteger(value) {
    let myValue = parseInt(value);

    if (!Number.isInteger(myValue)) return false;

    return myValue > 0;
}