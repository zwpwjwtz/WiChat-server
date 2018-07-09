<?php
// Actions handled by interface "query.php"
define('ACCOUNT_SCOMM_ACTION_NONE',0);
define('ACCOUNT_SCOMM_ACTION_GET_ID_STATE',1);
define('ACCOUNT_SCOMM_ACTION_GET_ID_KEY',2);

// Actions handled by interface "web.php"
define('ACCOUNT_SCOMM_ACTION_CREATE_ACCOUNT',128);

define('SERVER_RESPONSE_HEADER','WiChatSR');
define('SERVER_RESPONSE_HEADER_LEN','8');

define('RESPONSE_ACCOUNT_NONE',0);
define('RESPONSE_ACCOUNT_PASSWORD_OK',1);
define('RESPONSE_ACCOUNT_ID_EXISTS',2);
define('RESPONSE_ACCOUNT_ACCOUNT_OK',3);

define('ACCOUNT_KEY_RAW_LEN',32);

// Keys for communication between each interface and remote servers
define('ACCOUNT_SCOMM_QUERY_KEY','XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX');
define('ACCOUNT_SCOMM_WEB_KEY','XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX');
?>
