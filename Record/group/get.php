<?php
require_once('../common/config.php');
require_once('./config.php');

function matchIDList($list)
{
	if (!preg_match(REG_ID_LIST,$list,$IDList)) return NULL;
	if (!preg_match_all(REG_ID_LIST_ID,$IDList[1],$IDList)) return NULL;
	return $IDList[1];
}

if (!(defined('ACCOUNT_SERVER') && defined('RECORD_SERVER') && defined('GROUP_RECORD_LIST') && defined('RESPONSE_HEADER'))) //Fatal Error
{
	exit(0);
}
$out=RESPONSE_HEADER;
$buffer=file_get_contents("php://input");
if (substr($buffer,0,QUERY_HEADER_LEN)!=QUERY_HEADER) $out.=chr(RESPONSE_INVALID).chr(0);
else
{
	$Session=substr($buffer,QUERY_HEADER_LEN,ACCOUNT_SESSION_LEN);
	include_once('../common/lib.php');
	include_once('../common/enc.php');
	$db=new commDB(GROUP_COMM_LIST);
	$ID='';
	if ($db->OK)
	{
		$ID=$db->getIDBySession($Session);
		if ($ID<>'')
		{
			// Get cached account record for this ID
			$db2=new accDB2(ACCOUNT_LIST_CACHE);
			$tempRecord=$db2->getRecord($ID);
			if (timeDiff($tempRecord->LastTime)>MAX_SESSION_CACHE_TIME || $tempRecord->State==ACCOUNT_STATE_NONE)
			{
				// Try to get a new record from the acccount server
				include_once('../scomm/query.php');
				include_once('../scomm/config.php');
				$content=queryAccountServer(ACCOUNT_SCOMM_ACTION_GET_ID_STATE, $Session);
				if ($content=='' || ord($content[0])!=RESPONSE_SUCCESS)
				{
					$out.=chr(RESPONSE_FAILED).chr(0);
					$ID='';
				}
				else
				{
					// See if the account state is online or not
					$state=ord($content[2+ACCOUNT_ID_MAXLEN]);
					if (!($state==ACCOUNT_STATE_ONLINE || $state==ACCOUNT_STATE_BUSY || $state==ACCOUNT_STATE_HIDE)) $out.=chr(RESPONSE_NEED_LOGIN).chr(0); 
					else
					{
						// Write new record to database
						$ID=substr($content,2,ACCOUNT_ID_MAXLEN);
						$db2->setState($ID,$state);
					}
				}
			}
			else
			{
				// See if the account state is online or not
				if (!($tempRecord->State==ACCOUNT_STATE_ONLINE || $tempRecord->State==ACCOUNT_STATE_BUSY || $tempRecord->State==ACCOUNT_STATE_HIDE))
				{
					$ID='';
					$out.=chr(RESPONSE_FAILED).chr(0);
				}
			}
		}
	}
	if (checkID($ID))
	{
		if (!($sessionKey=$db->getKey($ID))) $out.=chr(RESPONSE_FAILED).chr(0);
		else
		{	
			$content=aes_decrypt(substr($buffer,QUERY_HEADER_LEN+ACCOUNT_SESSION_LEN),$sessionKey);
			if ($content==NULL || crc32sum(substr($content,4))!=substr($content,0,4))  {$out.=chr(RESPONSE_INVALID).chr(0);}
			else //Certification passed.
			{
				$outContent='';
				$content=substr($content,4);
				switch(ord($content[0]))
				{
					case REC_GET_NONE:
						$outContent.=chr(RESPONSE_SUCCESS).chr(0);
						break;
					case REC_GET_MSG:
						$db3=new groupRecDB(GROUP_RECORD_LIST);
						if (!$db3->OK) {$outContent.=chr(RESPONSE_FAILED).chr(0); break;}
						$lastTime=substr($content,2,TIME_LEN);
						if (strlen($lastTime)<TIME_LEN) {$outContent.=chr(RESPONSE_INVALID).chr(0); break;}
						
						$outContent.=chr(RESPONSE_SUCCESS).chr(0).'<IDList t=v>';
						$groupList=matchIDList(substr($content,2 + TIME_LEN));
						$recordList=$db3->fetchRecord($groupList,$lastTime);
						foreach ($recordList as $record) $outContent.='<ID>'.$record.'</ID>';
						$outContent.='</IDList>';
						break;
					default:
						$outContent.=chr(RESPONSE_INVALID).chr(0);
				}
				$out.=aes_encrypt(crc32sum($outContent).$outContent,$sessionKey);
			}
		}
	}
	else
        $out.=chr(RESPONSE_FAILED).chr(0);
}
echo($out);
exit(0);
		
?>
