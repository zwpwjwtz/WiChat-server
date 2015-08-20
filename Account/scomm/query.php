<?php
require_once('../common/config.php');
require_once('./config.php');


if (!(defined('ACCOUNT_SERVER') && defined('ACCOUNT_LIST') && defined('ACCOUNT_SCOMM_KEY') && defined('RESPONSE_HEADER'))) //Fatal Error
{
	exit(0);
}
$out=SERVER_RESPONSE_HEADER;
$action=$_GET['a'];
$Session=$_GET['s'];
include_once('../common/lib.php');
if (!checkKey($Session,ACCOUNT_SESSION_LEN)) $out.=chr(RESPONSE_INVALID).chr(0);
else
{
	include_once('../common/enc.php');
	$outContent='';
	switch($action)
	{
		case ACCOUNT_SCOMM_ACTION_NONE:
			$outContent.=chr(RESPONSE_SUCCESS).chr(0);
			break;
		case ACCOUNT_SCOMM_ACTION_GET_ID_STATE:
			$db=new accDB(ACCOUNT_LIST);		
			if (!$db->OK) $outContent.=chr(RESPONSE_FAILED).chr(0);
			else
			{
				$ID=$db->getIDBySession($Session);
				if ($ID=='') $outContent.=chr(RESPONSE_FAILED).chr(0);
				else
				{
					$state=$db->getState($ID);
					$outContent.=chr(RESPONSE_SUCCESS).chr(0).$ID.chr($state);
				}
			}
			break;
		case ACCOUNT_SCOMM_ACTION_GET_ID_KEY:
			$db=new accDB(ACCOUNT_LIST);		
			if (!$db->OK) $outContent.=chr(RESPONSE_FAILED).chr(0);
			else
			{
				$ID=$db->getIDBySession($Session);
				if ($ID=='') $outContent.=chr(RESPONSE_FAILED).chr(0);
				else
				{
					$db2=new commDB(COMM_LIST);
					if (!$db2->OK) $outContent.=chr(RESPONSE_FAILED).chr(0);
					else
					{
						$sessionKey=$db2->getKey($ID);
						if ($sessionKey=='') $outContent.=chr(RESPONSE_FAILED).chr(0);
						else $outContent.=$ID.str_repeat(chr(0),8-strlen($ID)).$sessionKey;
					}
				}
			}
			break;	
		default:
			$outContent.=chr(RESPONSE_INVALID).chr(0);
	}
	$encoder=new CSC1();
	if (!$encoder->OK) $out.=chr(RESPONSE_FAILED).chr(0);
	else $out.=intTo4Bytes(crc32($outContent)).$encoder->encrypt($outContent,ACCOUNT_SCOMM_KEY);
}
echo($out);
exit(0);
		
?>
