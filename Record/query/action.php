<?php
require_once('../common/config.php');
require_once('./config.php');

function cleanDB($db)
{
	$cleanedID=$db->doCleaning(REC_STATE_BROKEN);
	if (count($cleanedID)>0)
	{
		foreach ($cleanedID as $temp)	unlink(CACHE_DIR.$temp);
	}
}
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
			if ($content==NULL || intTo4Bytes(crc32($content))!=substr($buffer,QUERY_HEADER_LEN+ACCOUNT_SESSION_LEN,4))  {$out.=chr(RESPONSE_INVALID).chr(0);}
			else //Certification passed.
			{
				$outContent='';
				
				$obj=ord($content[1]);
				$objID=($obj==REC_OBJ_SERVER)?REC_OBJID_SERVER:substr($content,2,8);				
				switch(ord($content[0]))
				{
					case REC_ACTION_NONE:
						$outContent.=chr(RESPONSE_SUCCESS).chr(0);
						break;
					case REC_ACTION_PULL:
						$blockSize=bytesToInt4(substr($content,10,4));
						$getAll=($content[14]==chr(0))?false:true;
						$pBlock=ord($content[15]);
						$db3=new recDB(RECORD_LIST);
						if (!$db3->OK) {$outContent.=chr(RESPONSE_FAILED).chr(0); break;}
						$record=$db3->getRecord($objID,$ID);
						if ($record==NULL) {$outContent.=chr(RESPONSE_SUCCESS).chr(RESPONSE_RES_NOT_EXIST); break;}
						if (!file_exists(CACHE_DIR.$record->resID))
						{
							$record->state=REC_STATE_NONE;
							$db3->setRecord($record);
							$outContent.=chr(RESPONSE_FAILED).chr(0);
							break;
						}
						if ($record->state!=REC_STATE_ACTIVE)
						{
							if ($record->state==REC_STATE_LOCKED_WRITING)
								$outContent.=chr(RESPONSE_BUSY).chr(0);
							else
							{
								$outContent.=chr(RESPONSE_FAILED).chr(0);
								cleanDB($db3);
							}
							break;
						}
						$updateDB=false;
						if ($getAll)
						{
							if ($record->length > $blockSize)
								$outContent.=chr(RESPONSE_SUCCESS).chr(RESPONSE_RES_SIZE_TOO_LARGE).intToBytes4($record->length);
							else
							{
								$sf=fopen(CACHE_DIR.$record->resID,'rb');
								if (!$sf) {$outContent.=chr(RESPONSE_FAILED).chr(0); break;}
								$outContent.=chr(RESPONSE_SUCCESS).chr(RESPONSE_RES_OK).fread($sf,filesize(CACHE_DIR.$record->resID));
								fclose($sf);
								if ($obj==REC_OBJ_CLIENT) 
								{
									$record->state=REC_STATE_NONE;
									$updateDB=true;
								}
							}									
						}
						else
						{
							if (($pBlock-1)*$blockSize > $record->length) {$outContent.=chr(RESPONSE_SUCCESS).chr(RESPONSE_RES_OUT_RANGE); break;}
							$sf=fopen(CACHE_DIR.$record->resID,'rb');
							if (!$sf) {$outContent.=chr(RESPONSE_FAILED).chr(0); break;}
							fseek($sf,$pBlock*$blockSize);
							$outContent=chr(RESPONSE_SUCCESS).chr(RESPONSE_RES_OK).fread($sf,$blockSize);
							if (ftell($sf)>=$record->length)
							{
								$outContent[1]=chr(RESPONSE_RES_EOF);
								$record->state=REC_STATE_NONE;
								$updateDB=true;
							}
							fclose($sf);
						}
						if ($updateDB) 
						{	
							$db3->setRecord($record);
							if (($record->state==REC_STATE_BROKEN || $record->state==REC_STATE_NONE) && file_exists(CACHE_DIR.$record->resID)) unlink(CACHE_DIR.$record->resID);	
						}					
						break;
					case REC_ACTION_PUSH:
						$contentLen=bytesToInt4(substr($content,10,4));
						$eof=($content[14]==chr(0))?false:true;
						$appending=($content[15]==chr(0))?false:true;
						$content=substr($content,16);
						$db3=new recDB(RECORD_LIST);
						if (!$db3->OK) {$outContent.=chr(RESPONSE_FAILED).chr(0); break;}
						$record=$db3->getRecord($ID,$objID);
						$updateDB=false;
						if ($record==NULL)
						{
							$record=new dataRecord();
							$record->resID=$db3->getNewResID();
							if ($record->resID=='') {$outContent.=chr(RESPONSE_FAILED).chr(0); break;}
							$record->from=$ID;
							$record->to=$objID;
							$record->type=($obj==REC_OBJ_SERVER)?REC_TYPE_FILE:REC_TYPE_HTML;
							$record->length=0;
							$record->state=REC_STATE_ACTIVE;
							$updateDB=true;
						}
						switch($record->state) 
						{
							case REC_STATE_NONE:
								$outContent.=chr(RESPONSE_DATE_EXPIRED).chr(0);
								break;
							case REC_STATE_ACTIVE:
								$record->length+=$contentLen;
								if ($appending && !$eof) $record->state=REC_STATE_LOCKED_WRITING;
								$updateDB=true;
								break;
							case REC_STATE_LOCKED_WRITING:
								if ($appending)
								{
									$record->length+=$contentLen;
									if ($eof) $record->state=REC_STATE_ACTIVE;
								}
								else	$record->state=REC_STATE_BROKEN;
								$updateDB=true;
								break;
							case REC_STATE_BROKEN:
								cleanDB($db3);
								$outContent.=chr(RESPONSE_FAILED).chr(0);
								break;
							case REC_STATE_LOCKED_READING:
								$outContent.=chr(RESPONSE_BUSY).chr(0);
								break;
							default:
								$outContent.=chr(RESPONSE_FAILED).chr(0);
						}
						if ($updateDB)
						{
							if(!$db3->setRecord($record))
								$outContent.=chr(RESPONSE_FAILED).chr(0);
						}
						$sf=fopen(CACHE_DIR.$record->resID,'ab+');
						if (!$sf) {$outContent.=chr(RESPONSE_FAILED).chr(0); break;}
						fwrite($sf,$content);
						fclose($sf);
						$outContent.=chr(RESPONSE_SUCCESS).chr(0);				
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
