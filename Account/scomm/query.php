<?php
require_once('../common/config.php');
require_once('./config.php');


if (!(defined('ACCOUNT_SERVER') && defined('ACCOUNT_LIST') && defined('ACCOUNT_SCOMM_KEY') && defined('RESPONSE_HEADER'))) //Fatal Error
{
	exit(0);
}
$out=SERVER_RESPONSE_HEADER;
$action=$_GET['a'];
$Session=urldecode($_GET['s']);
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
			$db=new commDB(COMM_LIST);
			if (!$db->OK) $outContent.=chr(RESPONSE_FAILED).chr(0);
			else
			{
				$ID=$db->getIDBySession($Session);
				if ($ID=='') $outContent.=chr(RESPONSE_FAILED).chr(0);
				else
				{
					$db2=new accDB(ACCOUNT_LIST);
					$state=$db2->getState($ID);
					$outContent.=chr(RESPONSE_SUCCESS).chr(0).$ID.chr($state);
				}
			}
			break;
		case ACCOUNT_SCOMM_ACTION_GET_ID_KEY:
			$db=new commDB(COMM_LIST);
			if (!$db->OK) $outContent.=chr(RESPONSE_FAILED).chr(0);
			else
			{
				$ID=$db->getIDBySession($Session);
				if ($ID=='') $outContent.=chr(RESPONSE_FAILED).chr(0);
				else
				{
					$sessionKey=$db->getKey($ID);
					if ($sessionKey=='') $outContent.=chr(RESPONSE_FAILED).chr(0);
					else $outContent.=$ID.str_repeat(chr(0),ACCOUNT_ID_MAXLEN-strlen($ID)).$sessionKey;
				}
			}
			break;
		case ACCOUNT_SCOMM_ACTION_CHECK_GROUP_MEMBER:
			$db=new commDB(COMM_LIST);
			if (!$db->OK) {$outContent.=chr(RESPONSE_FAILED).chr(0); break;}
			$ID=$db->getIDBySession($Session);
			if ($ID=='') {$outContent.=chr(RESPONSE_FAILED).chr(0); break;}
			$groupID=$_GET['g'];
			if (!checkID($groupID)) {$outContent.=chr(RESPONSE_FAILED).chr(0); break;}
			$outContent.=chr(RESPONSE_SUCCESS);
			$db2=new groupMemberIndex(GROUP_MEMBER_DB_DIR.rtrim($groupID).GROUP_INDEX_SUFFIX);
			if (!$db2->OK)
				$outContent.=chr(ACCOUNT_SCOMM_RESPONSE_NONE);
			else
			{
				if ($db2->existRecord($ID)) 
					$outContent.=chr(ACCOUNT_SCOMM_RESPONSE_TRUE);
				else
					$outContent.=chr(ACCOUNT_SCOMM_RESPONSE_FALSE);
			}
			break;
		default:
			$outContent.=chr(RESPONSE_INVALID).chr(0);
	}
	$out.=aes_encrypt(crc32sum($outContent).$outContent,ACCOUNT_SCOMM_KEY);
}
echo($out);
exit(0);
		
?>
