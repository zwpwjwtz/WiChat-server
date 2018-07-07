<?php
require_once('../common/config.php');
require_once('./config.php');

function cleanDB($db)
{
	$cleanedID=$db->doCleaning(REC_STATE_BROKEN);
	if (count($cleanedID)>0)
	{
		foreach ($cleanedID as $temp)	unlink(RECORD_CACHE_DIR.$temp);
	}
}
function recordListToMList($recordList)
{
	$length=count($recordList);
	if ($length<1) return NULL;
	$temp='<MList>';
	for ($i=0;$i<$length;$i++)
		$temp.='<SRC>'.$recordList[$i]->from.'</SRC><TIME>'.$recordList[$i]->lastTime.'</TIME><LEN>'.$recordList[$i]->length.'</LEN>';
	$temp.='</MList>';
	return $temp;
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
	include_once('../common/enc.php');
	$db=new commDB(COMM_LIST);
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
				$obj=ord($content[1]);
				$objID=($obj==REC_OBJ_SERVER)?REC_OBJID_SERVER:substr($content,2,8);				
				switch(ord($content[0]))
				{
					case REC_ACTION_NONE:
						$outContent.=chr(RESPONSE_SUCCESS).chr(0);
						break;
					case REC_ACTION_PULL:
						$blockSize=bytesToInt(substr($content,10,4),4);
						$getAll=($content[14]==chr(0))?false:true;
						$pBlock=ord($content[15]);
						$db3=new recDB(RECORD_LIST);
						if (!$db3->OK) {$outContent.=chr(RESPONSE_FAILED).chr(0); break;}
						$record=$db3->getRecord($objID,$ID);
						if ($record==NULL) {$outContent.=chr(RESPONSE_SUCCESS).chr(RESPONSE_RES_NOT_EXIST); break;}
						if (!file_exists(RECORD_CACHE_DIR.$record->resID))
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
						$recordIndexFile=new recIndex(RECORD_CACHE_DIR.$record->resID.REC_INDEX_SUFFIX);
						$updateDB=false;
						if ($getAll)
						{
							if ($record->length > $blockSize)
								$outContent.=chr(RESPONSE_SUCCESS).chr(RESPONSE_RES_SIZE_TOO_LARGE).intToBytes($record->length,4);
							else
							{
								$sf=fopen(RECORD_CACHE_DIR.$record->resID,'rb');
								if (!$sf) {$outContent.=chr(RESPONSE_FAILED).chr(0); break;}
								$outContent.=chr(RESPONSE_SUCCESS).chr(RESPONSE_RES_OK).recordListToMList($recordIndexFile->getAllRecords());
								$outContent.=fread($sf,filesize(RECORD_CACHE_DIR.$record->resID));
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
							$sf=fopen(RECORD_CACHE_DIR.$record->resID,'rb');
							if (!$sf) {$outContent.=chr(RESPONSE_FAILED).chr(0); break;}
							fseek($sf,$pBlock*$blockSize);
							$outContent.=chr(RESPONSE_SUCCESS).chr(RESPONSE_RES_OK).recordListToMList($recordIndexFile->getRecordsByPosition($pBlock*$blockSize,($pBlock+1)*$blockSize));
							$outContent.=fread($sf,$blockSize);
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
							if (($record->state==REC_STATE_BROKEN || $record->state==REC_STATE_NONE) && file_exists(RECORD_CACHE_DIR.$record->resID))
							{
								unlink(RECORD_CACHE_DIR.$record->resID);
								unlink(RECORD_CACHE_DIR.$record->resID.REC_INDEX_SUFFIX);
							}
						}					
						break;
					case REC_ACTION_PUSH:
						$contentLen=bytesToInt(substr($content,10,4),4);
						$eof=($content[14]==chr(0))?false:true;
						$appending=($content[15]==chr(0))?false:true;
						$recordIndexID=substr($content,16,16);
						$content=substr($content,32);
						$db3=new recDB(RECORD_LIST);
						if (!$db3->OK) {$outContent.=chr(RESPONSE_FAILED).chr(0); break;}
						$record=$db3->getRecord($ID,$objID);
						$updateDB=false;
						$updateRecord=false;
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
						$recordIndexFile=new recIndex(RECORD_CACHE_DIR.$record->resID.REC_INDEX_SUFFIX);
						switch($record->state) 
						{
							case REC_STATE_NONE:
								$outContent.=chr(RESPONSE_DATE_EXPIRED).chr(0);
								break;
							case REC_STATE_ACTIVE:
								$record->length+=$contentLen;
								$recordIndexEntry=new dataRecordIndex();
								$recordIndexEntry->recordID=$recordIndexFile->getNewRecordID();
								$recordIndexEntry->type=REC_INDEX_TYPE_NORMAL;
								$recordIndexEntry->length=$contentLen;
								$recordIndexEntry->state=REC_INDEX_STATE_ACTIVE;
								$recordIndexEntry->from=$record->from;
								if ($appending && !$eof) $record->state=REC_STATE_LOCKED_WRITING;
								$updateDB=true;
								$updateRecord=true;
								break;
							case REC_STATE_LOCKED_WRITING:
								if ($appending)
								{
									$record->length+=$contentLen;
									$recordIndexEntry=$recordIndexFile->getLastRecord();
									if ($recordIndexEntry->recordID!= $recordIndexID)
									{
										$outContent.=chr(RESPONSE_BUSY).chr(0);
										break;
									}
									$recordIndexEntry->length+=$contentLen;
									if ($eof) $record->state=REC_STATE_ACTIVE;
									$updateRecord=true;
								}
								else
								{
									$record->state=REC_STATE_BROKEN;
									$outContent.=chr(RESPONSE_FAILED).chr(0);
								}
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
							{
								$outContent.=chr(RESPONSE_FAILED).chr(0);
								break;
							}
						}
						if ($updateRecord)
						{
							$sf=fopen(RECORD_CACHE_DIR.$record->resID,'ab+');
							if (!$sf) $outContent.=chr(RESPONSE_FAILED).chr(0);
							else
							{
								flock($sf,LOCK_EX);
								fwrite($sf,$content);
								flock($sf,LOCK_UN);
								$recordIndexFile->setRecord($recordIndexEntry);
								$outContent.=chr(RESPONSE_SUCCESS).chr(0);
								if ($recordIndexEntry->recordID!= $recordIndexID)
									$outContent.=$recordIndexEntry->recordID;
							}
							fclose($sf);
						}
						break;								
					default:
						$outContent.=chr(RESPONSE_INVALID).chr(0);
				}
				$out.=aes_encrypt(crc32sum($outContent).$outContent,$sessionKey);
			}			
		}
	}
}
echo($out);
exit(0);
		
?>
