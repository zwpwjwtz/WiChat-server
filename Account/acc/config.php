<?php
define('ACCOUNT_ACTION_NONE',0);
define('ACCOUNT_ACTION_FRI_GETLIST',1);
define('ACCOUNT_ACTION_FRI_ADD',2);
define('ACCOUNT_ACTION_FRI_DEL',3);
define('ACCOUNT_ACTION_FRI_CHECK',4);
define('ACCOUNT_ACTION_INFO_SESSION_CHANGE',5);
define('ACCOUNT_ACTION_INFO_MSG_GET',6);
define('ACCOUNT_ACTION_INFO_MSG_SET',7);
define('ACCOUNT_ACTION_INFO_STATE_SET',8);
define('ACCOUNT_ACTION_INFO_PW_CHANGE',9);
define('ACCOUNT_ACTION_FRI_GETINFO',10);
define('ACCOUNT_ACTION_FRI_GETNOTE',11);
define('ACCOUNT_ACTION_FRI_SETNOTE',12);

define('ACCOUNT_ACTION_OPTION_NORMAL',0);
define('ACCOUNT_ACTION_OPTION_GETDATE',1);
define('ACCOUNT_ACTION_OPTION_GETSTATE',2);

define('RESPONSE_ACCOUNT_PASSWORD_OK',1);
define('RESPONSE_ACCOUNT_PASSWORD_INCORRECT',2);

define('REG_ID_LIST','/^<IDList>(.+)<\/IDList>$/');
define('REG_ID_LIST_ID','/<ID>(\d{1,7}\0{1,7})<\/ID>/');
define('REG_MSG','/^<MSG>([\x0-\xFF]{1,64})<\/MSG>$/');

define('ACCOUNT_LIST','../db/account.dat');
define('COMM_LIST','../db/comm.dat');
define('RELATION_LIST','../db/relation.dat');

?>
