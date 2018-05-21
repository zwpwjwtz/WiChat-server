<?php

define('COMM_LIST','../db/comm.dat');
define('RECORD_LIST','../db/rec.dat');
define('ACCOUNT_LIST_CACHE','../db/account2.dat');
define('CACHE_DIR','../db/');
define('REC_INDEX_SUFFIX','.index');

define('REC_ACTION_NONE',0);
define('REC_ACTION_PULL',1);
define('REC_ACTION_PUSH',2);

define('REC_GET_NONE',0);
define('REC_GET_MSG',1);
define('REC_GET_FILE_INFO',2);

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

define('RESPONSE_RES_OK',0);
define('RESPONSE_RES_NOT_EXIST',1);
define('RESPONSE_RES_SIZE_TOO_LARGE',2);
define('RESPONSE_RES_EOF',3);
define('RESPONSE_RES_OUT_RANGE',4);

define('MAX_SESSION_CACHE_TIME',1800);

?>
