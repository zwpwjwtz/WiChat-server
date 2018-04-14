<?php
error_reporting(0);
define('SERVER_IN_MAINTANANCE',false);

define('QUERY_HEADER','WiChatCQ');
define('QUERY_HEADER_LEN',8);

define('RESPONSE_HEADER','WiChatSR');
define('RESPONSE_NONE',0); //Also RESPONSE_ERROR
define('RESPONSE_SUCCESS',1);
define('RESPONSE_BUSY',2);
define('RESPONSE_INVALID',3);
define('RESPONSE_DEVICE_UNSUPPORTED',4);
define('RESPONSE_FAILED',5);
define('RESPONSE_NEED_LOGIN',6);
define('RESPONSE_DATE_EXPIRED',7);
define('RESPONSE_IN_MAINTANANCE',8);

define('ACCOUNT_STATE_DEFAULT',0);
define('ACCOUNT_STATE_ONLINE',1);
define('ACCOUNT_STATE_OFFLINE',2);
//define('ACCOUNT_STATE_LOGOUT',3);
define('ACCOUNT_STATE_BUSY',4);
define('ACCOUNT_STATE_HIDE',5);

define('ACCOUNT_ID_MAXLEN',8);
define('ACCOUNT_ID_NULL',"\0\0\0\0\0\0\0\0");
define('KEY_NULL','0000000000000000');
define('ACCOUNT_SESSION_LEN',16);
define('ACCOUNT_COMMKEY_LEN',16);

//define('ACCOUNT_SERVER','acc2.wichat.org/Account/');
define('ACCOUNT_SERVER','127.0.0.1./Account/');
define('RECORD_SERVER','rec1.wichat.org/Record/');
define('SERVER_ID',1);

define('TIME_FORMAT','Y/m/d,H:i:s');
define('TIME_LEN',19);
define('TIME_ZERO','0001/01/01,00:00:00');

$validVersion=array(255);
// Constants and Absolute URLs Here ONLY
?>
