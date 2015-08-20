<?php
require_once('../common/config.php');
require_once('./config.php');


if (!(defined('ACCOUNT_SERVER') && defined('RECORD_SERVER') && defined('RECORD_LIST') && defined('RESPONSE_HEADER'))) //Fatal Error
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
	$db=new accDB2(ACCOUNT_LIST_CACHE);
	$ID='';
	if ($db->OK)
	{
		$ID=$db->getIDBySession($Session);
		if ($ID<>'')
		{
			$tempRecord==$db->getRecord($ID);
			if (timeDiff($tempRecord->LastTime)>MAX_SESSION_CACHE_TIME)
				 $ID='';
			else
				if (!($tempRecord->State==ACCOUNT_STATE_ONLINE || $tempRecord->State==ACCOUNT_STATE_BUSY || $tempRecord->State==ACCOUNT_STATE_HIDE)) $ID='';
		}
		if ($ID=='')
		{
			$sf=file_get_contents('http://'.ACCOUNT_SERVER.'scomm/query.php?a='.ACCOUNT_SCOMM_ACTION_GET_ID_STATE.'&s='.$Session);
			if (substr($sf,0,SERVER_RESPONSE_HEADER_LEN)==SERVER_RESPONSE_HEADER) 
			{
				include_once('../common/enc.php');
				$encoder=new CSC1();
				$content=$encoder->decrypt(substr($sf,SERVER_RESPONSE_HEADER_LEN+4),ACCOUNT_SCOMM_KEY);
				if (substr($sf,SERVER_RESPONSE_HEADER_LEN,4)!=intTo4Bytes(crc32($content)) || $content[0]!=chr(RESPONSE_SUCCESS)) $out.=chr(RESPONSE_FAILED).chr(0); 
				else
				{
					$state=ord($content[2+ACCOUNT_ID_MAXLEN]);
					if (!($state==ACCOUNT_STATE_ONLINE || $state==ACCOUNT_STATE_BUSY || $state==ACCOUNT_STATE_HIDE)) $out.=chr(RESPONSE_NEED_LOGIN).chr(0); 
					else
					{
						$ID=substr($content,2,ACCOUNT_ID_MAXLEN);
						$db->setSession($ID,$Session);
						$db->setState($ID,$state);
					}
				}
			}
		}
	}
	if (!checkID($ID)) 
		$out.=chr(RESPONSE_FAILED).chr(0); 
	else
	{
		$db=new commDB(COMM_LIST);
		if (!$db->OK || !($sessionKey=$db->getKey($ID))) $out.=chr(RESPONSE_FAILED).chr(0);
		else
		{	
			include_once('../common/enc.php');
			$content=$encoder->decrypt(substr($buffer,QUERY_HEADER_LEN+ACCOUNT_SESSION_LEN+4),$sessionKey);
			if ($content==NULL || intTo4Bytes(crc32($content))!=substr($buffer,QUERY_HEADER_LEN+ACCOUNT_SESSION_LEN,4))  $out.=chr(RESPONSE_INVALID).chr(0);
			else //Certification passed.
			{
				$outContent='';
			
				switch(ord($content[0]))
				{
					case REC_GET_NONE:
						$outContent.=chr(RESPONSE_SUCCESS).chr(0);
						break;
					case REC_GET_MSG:
						$db3=new recDB(RECORD_LIST);
						if (!$db3->OK) {$outContent.=chr(RESPONSE_FAILED).chr(0); break;}
						$outContent.=chr(RESPONSE_SUCCESS).chr(0).'<IDList t=v>';
						$record=$db3->fetchRecord($ID,REC_STATE_ACTIVE);
						foreach ($record as $temp) $outContent.='<ID>'.$temp.'</ID>';
						$outContent.='</IDList>';						
						break;
					case REC_GET_FILE_INFO:			
						break;
					default:
						$outContent.=chr(RESPONSE_INVALID).chr(0);
				}
				$out.=intTo4Bytes(crc32($outContent)).$encoder->encrypt($outContent,$sessionKey);
			}
		}			
	}
}
echo($out);
exit(0);
		
?>
