<?php
require_once('../common/config.php');
require_once('./config.php');


if (!(defined('ACCOUNT_LIST') && defined('ACCOUNT_SCOMM_WEB_KEY') && defined('SERVER_RESPONSE_HEADER'))) //Fatal Error
{
	exit(0);
}
$out=SERVER_RESPONSE_HEADER;
$action=(int)$_POST['a'];
$buffer=urldecode($_POST['c']);
if (strlen($buffer) < 48) $out.=chr(RESPONSE_INVALID).chr(0);
else
{
	include_once('../common/enc.php');
	$content=aes_decrypt($buffer,ACCOUNT_SCOMM_WEB_KEY);
	if (substr($content,0,4) != crc32sum(substr($content,4)))
		$out.=chr(RESPONSE_INVALID).chr(0);
	else
	{
		include_once('../common/lib.php');
		$content=substr($content,4);
		$outContent='';
		switch($action)
		{
			case ACCOUNT_SCOMM_ACTION_CREATE_ACCOUNT:
				$record=new accRecord();
				$record->ID=substr($content,0,ACCOUNT_ID_MAXLEN);
				$record->LastDevice=0;
				$record->State=RELATION_STATE_NONE;
				$key=substr($content,ACCOUNT_ID_MAXLEN,ACCOUNT_KEY_RAW_LEN);
				
				$db=new accDB(ACCOUNT_LIST);
				if (!$db->OK) {$outContent.=chr(RESPONSE_FAILED).chr(0); break;}
				if ($db->existRecord($record->ID)) {$outContent.=chr(RESPONSE_FAILED).chr(RESPONSE_ACCOUNT_ID_EXISTS); break;}
				if (!$db->setRecord($record))
					$outContent.=chr(RESPONSE_FAILED).chr(0);
				else
				{
					// Hash full key using ID as salt, then save it
					$db->setPW($record->ID,substr(hmac($key,trim($record->ID)),0,ACCOUNT_KEY_LEN));
					
					// Hash partial key with standalone salt, then save them
					$key=substr($key,0,ACCOUNT_KEY_LEN);
					$keySalt=genKey(ACCOUNT_KEY_LEN - ACCOUNT_KEY_SALTED_LEN);
					$db->setPW2($record->ID,substr(hmac($key,$keySalt),0,ACCOUNT_KEY_SALTED_LEN).$keySalt);
					$outContent.=chr(RESPONSE_SUCCESS).chr(RESPONSE_ACCOUNT_ACCOUNT_OK);
				}
				break;
			default:
				$outContent.=chr(RESPONSE_INVALID).chr(0);
		}
		$out.=aes_encrypt(crc32sum($outContent).$outContent,ACCOUNT_SCOMM_WEB_KEY);
	}
}
echo($out);
exit(0);
?>