<?php
require_once('./config.php');

if (!defined('WICHAT_MANAGE_ROOT'))
{
    //Fatal Error
    exit(0);
}
require_once(WICHAT_MANAGE_ROOT.'/lib/function_base.php');


// Parse request parameters
$params = array();
if (array_key_exists('action',$_REQUEST))
    $action = $_REQUEST['action'];
else
    $action = '';
$method = $_SERVER['REQUEST_METHOD'];
if ($method == 'POST')
{
    foreach ($_POST as $key=>$val)
    {
        if ($key != 'action')
        {
            $params[$key] = $val;
        }
    }
}
else if ($method == 'GET')
{
    foreach ($_GET as $key=>$val) {
      if ($key != 'action') {
         $params[$key] = $val;
      }
    }
}
else
{
    $action = 'invalid';
}

// Dispatch request according to $action
$view='';
session_start();
switch ($action)
{
    case 'login':
        require_once(WICHAT_MANAGE_ROOT.'/modules/log.php');
        login($params);
        require_once(WICHAT_MANAGE_ROOT.'/modules/index.php');
        break;
    case 'logout':
        require_once(WICHAT_MANAGE_ROOT.'/modules/log.php');
        logout();
        require_once(WICHAT_MANAGE_ROOT.'/modules/index.php');
        break;
    case 'change_info':
        require_once(WICHAT_MANAGE_ROOT.'/modules/info.php');
        updateUserInfo($params);
    case 'userinfo':
        require_once(WICHAT_MANAGE_ROOT.'/modules/info.php');
        getUserInfo($params);
        break;
    default:
        require_once(WICHAT_MANAGE_ROOT.'/modules/index.php');
}

// Output view according to $view
// If $view is empty, use $action as $view
header("Content-type: text/html; charset=utf-8");
require_once(WICHAT_MANAGE_ROOT.'/templates/html_head.html');
if ($view=='') $view=$action;
switch($view)
{
    case 'change_info':
    case 'userinfo':
        require_once(WICHAT_MANAGE_ROOT.'/templates/user_info.html');
        break;
    default:
        require_once(WICHAT_MANAGE_ROOT.'/templates/header.html');
        require_once(WICHAT_MANAGE_ROOT.'/templates/index.html');
}
require_once(WICHAT_MANAGE_ROOT.'/templates/login.html');
require_once(WICHAT_MANAGE_ROOT.'/templates/footer.html');

exit(0);
?>