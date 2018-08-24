<?php
define('REC_ACTION_NONE',0);
define('REC_ACTION_PULL',1);
define('REC_ACTION_PUSH',2);
define('REC_ACTION_GUEST',3);

define('REC_GET_NONE',0);
define('REC_GET_MSG',1);

define('REC_OBJ_NONE',0);
define('REC_OBJ_SERVER',1);
define('REC_OBJ_CLIENT',2);

define('REC_OBJID_SERVER','10000');

define('REC_TYPE_NONE',0);
define('REC_TYPE_HTML',1);
define('REC_TYPE_FILE',2);

define('REC_STATE_NONE',0);
define('REC_STATE_ACTIVE',1);
define('REC_STATE_LOCKED_WRITING',2);
define('REC_STATE_BROKEN',3);
define('REC_STATE_LOCKED_READING',4);

define('REC_INDEX_TYPE_NONE',0);
define('REC_INDEX_TYPE_NORMAL',1);

define('REC_INDEX_STATE_NONE',0);
define('REC_INDEX_STATE_ACTIVE',1);
define('REC_INDEX_STATE_BROKEN',3);

define('RESPONSE_RES_OK',0);
define('RESPONSE_RES_NOT_EXIST',1);
define('RESPONSE_RES_SIZE_TOO_LARGE',2);
define('RESPONSE_RES_EOF',3);
define('RESPONSE_RES_OUT_RANGE',4);
define('RESPONSE_RES_NO_PERMISSION',5);

define('MAX_SESSION_CACHE_TIME',1800);

define('REG_ID_LIST','/^<IDList>(.+)<\/IDList>$/');
define('REG_ID_LIST_ID','/<ID>(\d{1,7}\0{1,7})<\/ID>/');
?>
