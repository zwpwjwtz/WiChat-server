<?php
require_once('../common/config.php');
require_once('./config.php');

if (!(defined('ACCOUNT_SERVER') && defined('GROUP_LIST') && defined('RESPONSE_HEADER'))) //Fatal Error
{
	exit(0);
}
$out=RESPONSE_HEADER;
$buffer=file_get_contents("php://input");
if (substr($buffer,0,QUERY_HEADER_LEN)!=QUERY_HEADER) $out.=chr(RESPONSE_INVALID).chr(0);
else
{
	include_once('../common/lib.php');
	$db=new commDB(COMM_LIST);	
	if (!$db->OK) $out.=chr(RESPONSE_FAILED).chr(0);
	else 
	{
		$Session=substr($buffer,QUERY_HEADER_LEN,ACCOUNT_SESSION_LEN);
		$ID=$db->getIDBySession($Session);
		if (!$ID) $out.=chr(RESPONSE_FAILED).chr(0);
		else 
		{
			if (!($sessionKey=$db->getKey($ID))) $out.=chr(RESPONSE_FAILED).chr(0);
			else
			{
				include_once('../common/enc.php');
				$content=aes_decrypt(substr($buffer,QUERY_HEADER_LEN+ACCOUNT_SESSION_LEN),$sessionKey);
				if ($content==NULL || crc32sum(substr($content,4))!=substr($content,0,4))  $out.=chr(RESPONSE_INVALID).chr(0);
				else //Certification passed.
				{
					$outContent='';
					$action=ord($content[4]);
					$option=ord($content[5]);
					$content=substr($content,6);
					switch($action)
					{
						case ACCOUNT_GROUP_RELATION_NONE:
							$outContent.=chr(RESPONSE_SUCCESS).chr(0);
							break;
						case ACCOUNT_GROUP_RELATION_GETLIST:
							$ID=rtrim($ID);
							$db2=new groupMemberIndex(GROUP_REVERSE_DB_DIR.$ID.GROUP_INDEX_SUFFIX);
							if ($db2->OK)
								$groupList=$db2->getRecordList(GROUP_MEMBER_STATE_ACTIVE);
							else
								$groupList=array();
							$outContent.=chr(RESPONSE_SUCCESS).chr(0).'<IDList>';
							foreach ($groupList as $groupID) $outContent.='<ID>'.$groupID.'</ID>';
							$outContent.='</IDList>';
							break;
						case ACCOUNT_GROUP_RELATION_JOIN:
							$groupID=substr($content,0,GROUP_ID_MAXLEN);
							$db2=new groupMemberIndex(GROUP_MEMBER_DB_DIR.rtrim($groupID).GROUP_INDEX_SUFFIX);
							if (!$db2) {$outContent.=chr(RESPONSE_FAILED).chr(0); break;}
							if ($db2->existRecord($ID)) {$outContent.=chr(RESPONSE_FAILED).chr(RESPONSE_GROUP_ALREADY_MEMEBER); break;}
							$db3=new groupDB(GROUP_LIST);
							$group=$db3->getRecord($groupID);
							$group->memberCount++;
							$db3->setRecord($group);
							$member=new groupMember();
							$member->ID=$ID;
							$member->role=GROUP_MEMBER_ROLE_DEFAULT;
							$member->state=GROUP_MEMBER_STATE_WAIT;
							if (!$db2->setRecord($member)) {$outContent.=chr(RESPONSE_FAILED).chr(RESPONSE_GROUP_ALREADY_MEMEBER); break;}
							$db4=new groupMemberIndex(GROUP_REVERSE_DB_DIR.rtrim($ID).GROUP_INDEX_SUFFIX);
							$member->ID=$groupID;
							if (!$db4->setRecord($member))
								$outContent.=chr(RESPONSE_FAILED).chr(0);
							else
								$outContent.=chr(RESPONSE_SUCCESS).chr(0);
							break;
						case ACCOUNT_GROUP_RELATIONT_QUIT:
							$groupID=substr($content,0,GROUP_ID_MAXLEN);
							$db2=new groupMemberIndex(GROUP_MEMBER_DB_DIR.rtrim($groupID).GROUP_INDEX_SUFFIX);
							if (!$db2) {$outContent.=chr(RESPONSE_FAILED).chr(0); break;}
							if (!$db2->existRecord($ID)) {$outContent.=chr(RESPONSE_FAILED).chr(RESPONSE_GROUP_NOT_MEMEBER); break;}
							$db3=new groupDB(GROUP_LIST);
							$group=$db3->getRecord($groupID);
							$group->memberCount--;
							$db3->setRecord($group);
							if (!$db2->delRecord($ID)) {$outContent.=chr(RESPONSE_FAILED).chr(0); break;}
							$db4=new groupMemberIndex(GROUP_REVERSE_DB_DIR.rtrim($ID).GROUP_INDEX_SUFFIX);
							if (!$db4->delRecord($groupID))
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