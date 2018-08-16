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

define('RELATION_STATE_NONE',0);
define('RELATION_STATE_WAITING',1);
define('RELATION_STATE_ESTABLISHED',2);
define('RELATION_STATE_BREAKING',3);
//define('RELATION_STATE_REFUSED',4);

define('ACCOUNT_ID_MAXLEN',8);
define('GROUP_ID_MAXLEN',8);
define('ACCOUNT_ID_NULL',"\0\0\0\0\0\0\0\0");
define('KEY_NULL','0000000000000000');
define('ACCOUNT_KEY_LEN',16);
define('ACCOUNT_KEY_SALTED_LEN',8);
define('ACCOUNT_SESSION_LEN',16);
define('ACCOUNT_COMMKEY_LEN',16);
define('ACCOUNT_COMMRAND_LEN',4);
define('ACCOUNT_ACTIVE_TIME',3600); //Time in second
define('ACCOUNT_MAX_TRY_TIMES',3);

define('ACCOUNT_SERVER','127.0.0.1/Account/');
define('SERVER_ID',1);

define('ACCOUNT_DB_DIR','../db');
define('GROUP_DB_DIR',ACCOUNT_DB_DIR.'/group');
define('GROUP_MEMBER_DB_DIR',GROUP_DB_DIR.'/member/');
define('GROUP_REVERSE_DB_DIR',GROUP_DB_DIR.'/reverse/');
define('LOGIN_LIST',ACCOUNT_DB_DIR.'/login.dat');
define('ACCOUNT_LIST',ACCOUNT_DB_DIR.'/account.dat');
define('COMM_LIST',ACCOUNT_DB_DIR.'/comm.dat');
define('RELATION_LIST',ACCOUNT_DB_DIR.'/relation.dat');
define('GROUP_LIST',ACCOUNT_DB_DIR.'/group.dat');

define('GROUP_INDEX_SUFFIX','.index');

define('TIME_FORMAT','Y/m/d,H:i:s');
define('TIME_LEN',19);
define('TIME_ZERO','0001/01/01,00:00:00');

$validVersion=array(255);
// Constants and Absolute URLs Here ONLY
?>
