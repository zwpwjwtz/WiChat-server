<?php
require_once('../common/config.php');
require_once('./config.php');

function matchIDList($list)
{
	if (!preg_match(REG_ID_LIST,$list,$friendList)) return NULL;
	if (!preg_match_all(REG_ID_LIST_ID,$friendList[1],$friendList)) return NULL;
	return $friendList[1];
}

if (!(defined('ACCOUNT_SERVER') && defined('ACCOUNT_LIST') && defined('RESPONSE_HEADER'))) //Fatal Error
{
	exit(0);
}
$out=RESPONSE_HEADER;
$buffer=file_get_contents("php://input");
if (substr($buffer,0,QUERY_HEADER_LEN)!=QUERY_HEADER) $out.=chr(RESPONSE_INVALID).chr(0);
else
{
	include_once('../common/lib.php');
	$db2=new commDB(COMM_LIST);	
	if (!$db2->OK) $out.=chr(RESPONSE_FAILED).chr(0);
	else 
	{
		$Session=substr($buffer,QUERY_HEADER_LEN,ACCOUNT_SESSION_LEN);
		$ID=$db2->getIDBySession($Session);
		if (!$ID) $out.=chr(RESPONSE_FAILED).chr(0);
		else 
		{
			if (!($sessionKey=$db2->getKey($ID))) $out.=chr(RESPONSE_FAILED).chr(0);
			else
			{
				include_once('../common/enc.php');
				$content=aes_decrypt(substr($buffer,QUERY_HEADER_LEN+ACCOUNT_SESSION_LEN),$sessionKey);
				if ($content==NULL || crc32sum(substr($content,4))!=substr($content,0,4))  $out.=chr(RESPONSE_INVALID).chr(0);
				else //Certification passed.
				{
					$db=new accDB(ACCOUNT_LIST);
					$outContent='';
					$content=substr($content,4);
					$action=ord(substr($content,0,1));
					$option=ord(substr($content,1,1));
					$content=substr($content,2);
					switch($action)
					{
						case ACCOUNT_ACTION_FRIEND_NONE:
							$outContent.=chr(RESPONSE_SUCCESS).chr(0);
							break;
						case ACCOUNT_ACTION_FRIEND_GETLIST:
							$db3=new relDB(RELATION_LIST);
							if (!$db3->OK) {$outContent.=chr(RESPONSE_FAILED).chr(0); break;}
							$friendList=$db3->getFriend($ID,RELATION_STATE_ESTABLISHED);
							$outContent.=chr(RESPONSE_SUCCESS).chr(0).'<IDList t=c>';
							if ($option & ACCOUNT_FRIEND_OPTION_GETSTATE)
								foreach ($friendList as $temp)
										$outContent.='<ID s='.$db->getState($temp).'>'.$temp.'</ID>';
							else
								foreach ($friendList as $temp) $outContent.='<ID>'.$temp.'</ID>';
							$outContent.='</IDList>';
							$friendList=$db3->getFriend($ID,RELATION_STATE_BREAKING);
							if (count($friendList)>0)
							{
								$outContent.='<IDList t=b>';
								foreach ($friendList as $temp) $outContent.='<ID>'.$temp.'</ID>';
								$outContent.='</IDList>';
							}
							$friendList=$db3->getFriend($ID,RELATION_STATE_WAITING);
							if (count($friendList)>0)
							{
								$outContent.='<IDList t=w>';
								foreach ($friendList as $temp) $outContent.='<ID>'.$temp.'</ID>';
								$outContent.='</IDList>';
							};
							break;
						case ACCOUNT_ACTION_FRIEND_ADD: //Multiple Addition Supported
							$friendList=matchIDList($content);
							if ($friendList==NULL) {$outContent.=chr(RESPONSE_INVALID).chr(0); break;}					
							$db3=new relDB(RELATION_LIST);
							if (!$db3->OK) {$outContent.=chr(RESPONSE_FAILED).chr(0); break;}
							$failed=array();
							foreach ($friendList as $temp)
							{
								if (!$db->existRecord($temp)) {array_push($failed,$temp); continue;}
								if (!$db3->setFriend($ID,$temp)) array_push($failed,temp);
							}
							if (count($failed)>0)
							{
								$outContent.=chr(RESPONSE_FAILED).chr(0).'<IDList t=f>';
								foreach ($failed as $temp)	$outContent.='<ID>'.$temp.'</ID>';
								$outContent.='</IDList>';
							}
							else $outContent.=chr(RESPONSE_SUCCESS).chr(0);
							break;	
						case ACCOUNT_ACTION_FRIEND_DEL:
							$friendList=matchIDList($content);
							if ($friendList==NULL) {$outContent.=chr(RESPONSE_INVALID).chr(0); break;}					
							$db3=new relDB(RELATION_LIST);
							if (!$db3->OK) {$outContent.=chr(RESPONSE_FAILED).chr(0); break;}
							$failed=array();
							foreach ($friendList as $temp)
								if (!$db3->delFriend($ID,$temp)) array_push($failed,$temp);
							if (count($failed)>0)
							{
								$outContent.=chr(RESPONSE_FAILED).chr(0).'<IDList t=f>';
								foreach ($friendList as $temp)
									$outContent.='<ID>'.$temp.'</ID>';
								$outContent.='</IDList>';
							}
							else $outContent.=chr(RESPONSE_SUCCESS).chr(0);
							break;
						case ACCOUNT_ACTION_FRIEND_CHECK:
							$friendList=matchIDList($content);
							if ($friendList==NULL) {$outContent.=chr(RESPONSE_INVALID).chr(0); break;}					
							$db3=new relDB(RELATION_LIST);
							if (!$db3->OK) {$outContent.=chr(RESPONSE_FAILED).chr(0); break;}
							$outContent.=chr(RESPONSE_SUCCESS).chr(0).'<MList>';
							foreach ($friendList as $temp)
							{
								$tempDate=$db3->getFriendDate($ID,$temp);
								if ($tempDate!='')
								{
									$outContent.='<ID>'.$temp.'</ID>';
									if ($option==ACCOUNT_FRIEND_OPTION_GETDATE) $outContent.='<DATE>'.$tempDate.'</DATE>';
								}
							}
							$outContent.='</MList>';
							break;
						case ACCOUNT_ACTION_FRIEND_GETINFO:
							$friendList=matchIDList($content);
							if ($friendList==NULL) {$outContent.=chr(RESPONSE_INVALID).chr(0); break;}
							$outContent=chr(RESPONSE_SUCCESS).chr(0).'<MList>';
							foreach ($friendList as $temp)
							{
								$tempRecord=$db->getRecord($temp);
								if ($tempRecord!=NULL) $outContent.='<ID>'.$tempRecord->ID.'</ID><MSG>'.$tempRecord->Msg.'</MSG>';
							}
							$outContent.='</MList>';
							break;
						case ACCOUNT_ACTION_FRIEND_GETNOTE:
							$friendList=matchIDList($content);
							if ($friendList==NULL) {$outContent.=chr(RESPONSE_INVALID).chr(0); break;}
							$db3=new relDB(RELATION_LIST);
							if (!$db3->OK) {$outContent.=chr(RESPONSE_FAILED).chr(0); break;}
							$outContent=chr(RESPONSE_SUCCESS).chr(0).'<MList>';
							foreach ($friendList as $temp)
							{
								$tempRecord=$db3->getNote($ID,$temp);
								$outContent.='<ID>'.$temp.'</ID><NOTE>'.$tempRecord.'</NOTE>';
							}
							$outContent.='</MList>';
							break;
						case ACCOUNT_ACTION_FRIEND_SETNOTE:
							$noteLength=bytesToInt(substr($content,8,2),2);
							if ($noteLength>32) {$outContent.=chr(RESPONSE_INVALID).chr(0); break;}
							$db3=new relDB(RELATION_LIST);
							if (!$db3->OK) {$outContent.=chr(RESPONSE_FAILED).chr(0); break;}
							if (!$db3->setNote($ID,substr($content,0,8),substr($content,10,$noteLength)))
								$outContent.=chr(RESPONSE_FAILED).chr(0);
							else
								$outContent.=chr(RESPONSE_SUCCESS).chr(0);
							break;
						default:
							$outContent.=chr(RESPONSE_INVALID).chr(0);
					}
					$out.=aes_encrypt(crc32sum($outContent).$outContent,$sessionKey);
				}
			}			
		}
	}
}
echo($out);
exit(0);
		
?>
 
