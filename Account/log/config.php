<?php
define('QUERY_LOGIN_TYPE_NONE',0);//Also LOGIN_TYPE_REFRESH
define('QUERY_LOGIN_TYPE_GET_KEY',1);
define('QUERY_LOGIN_TYPE_LOGIN',2);
define('QUERY_CHANGE_STATE',1);

define('QUERY_LOGIN_ACCOUNT_LEN',8);
define('QUERY_LOGIN_KEY_LEN',16);
define('QUERY_LOGIN_PW_LEN',20);

define('LOGIN_LIST','../db/login.dat');
define('COMM_LIST','../db/comm.dat');
define('ACCOUNT_LIST','../db/account.dat');

define('LOGIN_TIME_EXPIRE',60); //Time In Second
?>
