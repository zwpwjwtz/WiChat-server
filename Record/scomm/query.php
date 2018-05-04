<?php
require_once('../common/config.php');
require_once('./config.php');

if (!(defined('ACCOUNT_SERVER') && defined('RESPONSE_HEADER'))) //Fatal Error
{
    exit(0);
}

function queryAccountServer($action, $session)
{
    $sf=file_get_contents('http://'.ACCOUNT_SERVER.'scomm/query.php?a='.$action.'&s='.urlencode($session));
    if (substr($sf,0,SERVER_RESPONSE_HEADER_LEN)!=SERVER_RESPONSE_HEADER) return '';
    
    // Pharse response from account server
    include_once('../common/enc.php');
    include_once('../common/lib.php');
    $content=aes_decrypt(substr($sf,SERVER_RESPONSE_HEADER_LEN),ACCOUNT_SCOMM_KEY);
    if (substr($content,0,4)!=crc32sum(substr($content,4))) return '';
    else return substr($content,4);
}
?>
