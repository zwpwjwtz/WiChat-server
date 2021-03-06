<?php
define('ACCOUNT_GROUP_NONE',0);
define('ACCOUNT_GROUP_ADD_MEMBER',1);
define('ACCOUNT_GROUP_DEL_MEMBER',2);
define('ACCOUNT_GROUP_GET_MEMBER',3);
define('ACCOUNT_GROUP_GET_NAME',4);
define('ACCOUNT_GROUP_GET_INFO',5);
define('ACCOUNT_GROUP_SET_NAME',6);
define('ACCOUNT_GROUP_SET_DESCRIP',7);
define('ACCOUNT_GROUP_DEL_GROUP',8);

define('ACCOUNT_GROUP_RELATION_NONE',0);
define('ACCOUNT_GROUP_RELATION_GETLIST',1);
define('ACCOUNT_GROUP_RELATION_JOIN',2);
define('ACCOUNT_GROUP_RELATIONT_QUIT',3);

define('RESPONSE_GROUP_OK',0);
define('RESPONSE_ACCOUNT_NOT_EXIST',3);
define('RESPONSE_GROUP_NOT_EXIST',4);
define('RESPONSE_GROUP_CANNOT_JOIN',5);
define('RESPONSE_GROUP_NOT_MEMEBER',6);
define('RESPONSE_GROUP_ALREADY_MEMEBER',7);
define('RESPONSE_GROUP_NO_PERMISSION',8);

define('GROUP_STATE_DEFAULT',0);
define('GROUP_STATE_OPEN',1);
define('GROUP_STATE_CLOSED',2);

define('GROUP_TYPE_NORMAL',0);

define('GROUP_MEMBER_ROLE_DEFAULT',0);
define('GROUP_MEMBER_ROLE_ADMIN',1);
define('GROUP_MEMBER_ROLE_SUPER',2);

define('GROUP_MEMBER_STATE_DEFAULT',0);
define('GROUP_MEMBER_STATE_ACTIVE',1);
define('GROUP_MEMBER_STATE_WAIT',2);

define('REG_ID_LIST','/^<IDList>(.+)<\/IDList>$/');
define('REG_ID_LIST_ID','/<ID>(\d{1,7}\0{1,7})<\/ID>/');
?>
