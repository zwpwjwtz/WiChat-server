<?php
error_reporting(0);
define('SERVER_IN_MAINTANANCE',false);

define('QUERY_LEN',10);
define('QUERY_HEADER','WiChatCQ');
define('QUERY_HEADER_LEN',8);

define('QUERY_TYPE_NONE',0);
define('QUERY_TYPE_GET_ACC',1);
define('QUERY_TYPE_GET_REC',2);

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

define('ROOT_SERVER',' dns.wichat.org/Root/');
$AccServer=array('127.0.0.1');
//$AccServer=array('acc2.wichat.org');
$RecServer=array('127.0.0.1');
//$RecServer=array('rec1.wichat.org');

$validVersion=array(1,2,3,4,255);
// Constants and Absolute URLs Here ONLY
?>