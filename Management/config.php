<?php
error_reporting(E_ALL);

define('WICHAT_ACCOUNT_SERVER_DIR','../../Account');
define('WICHAT_ACCOUNT_DB_DIR',WICHAT_ACCOUNT_SERVER_DIR.'/db');

define('WICHAT_MANAGE_ROOT',dirname(__FILE__));
define('WICHAT_MANAGE_WEB_ROOT','http://localhost/Management');
define('WICHAT_MANAGE_WEB_TEMPLATE',WICHAT_MANAGE_WEB_ROOT.'/templates');

define('WICHAT_MANAGE_ADMIN_USERNAME','admin');
define('WICHAT_MANAGE_ADMIN_KEY_HASH','8a27c85f02d1b8d2b3df658be130bb818656a3bd08620f9254ee73ba37f971eb');
define('WICHAT_MANAGE_KEY_SALT','1234567890');
?>