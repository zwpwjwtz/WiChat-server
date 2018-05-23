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

define('ROOT_SERVER','127.0.0.1/Root/');
define('SERVER_ID',1);

$validVersion=array(255);
// Constants and Absolute URLs Here ONLY
?>