<?php
function login($params) {
    $username = $params['username'];
    $password = $params['password'];
        
    if ($username == WICHAT_MANAGE_ADMIN_USERNAME &&
        hash_hmac('sha256',$password,WICHAT_MANAGE_KEY_SALT) == WICHAT_MANAGE_ADMIN_KEY_HASH)
    {
        $_SESSION['Name']  = $username;
        setSysMsg('result','You have logged in successfully.');
    }
    else
    {
        setSysMsg('result','Login failed.');
        setSysMsg('help','Please try again.');
    }
}

function logout() {
    unset($_SESSION['Name']);
    setSysMsg('result','You have logged out successfully.');
}
?>
