let password_base = '1234567890abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
function genPassword(length = 20)
{
    let password = '';
    for (let i = 0; i < length; i++) {
        password += password_base.charAt(Math.floor(Math.random() * password_base.length));
    }
    return password;
}
