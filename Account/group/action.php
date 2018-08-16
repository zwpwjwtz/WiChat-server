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
				if ($content==NULL || crc32sum(substr($content,4))!=substr($content,0,4)) $out.=chr(RESPONSE_INVALID).chr(0);
				else //Certification passed.
				{
					$db2=new groupDB(GROUP_LIST);
					$outContent='';
					$action=ord(substr($content,4,1));
					$option=ord(substr($content,5,1));
					$groupID=substr($content,6,GROUP_ID_MAXLEN);
					$content=substr($content,6 + GROUP_ID_MAXLEN);
					switch($action)
					{
						case ACCOUNT_GROUP_NONE:
							$outContent.=chr(RESPONSE_SUCCESS).chr(0);
							break;
						case ACCOUNT_GROUP_ADD_MEMBER:
							$group=$db2->getRecord($groupID);
							if (!$group) {$outContent.=chr(RESPONSE_FAILED).chr(RESPONSE_GROUP_NOT_EXIST); break;}
							$db3=new groupMemberIndex(GROUP_MEMBER_DB_DIR.rtrim($groupID).GROUP_INDEX_SUFFIX);
							$member=$db3->getRecord($ID);
							if ($member->role < GROUP_MEMBER_ROLE_ADMIN) {$outContent.=chr(RESPONSE_FAILED).chr(RESPONSE_GROUP_NO_PERMISSION); break;}
							$memberList=matchIDList($content);
							if ($memberList==NULL) {$outContent.=chr(RESPONSE_INVALID).chr(0); break;}
							
							$db4=new accDB(ACCOUNT_LIST);
							$failed=array();
							foreach ($memberList as $memberID)
							{
								if (!$db4->existRecord($memberID) || $db2->existRecord($memberID)) {array_push($failed,$memberID); continue;}
								$member->ID=$memberID;
								$member->role=GROUP_MEMBER_ROLE_DEFAULT;
								$member->state=GROUP_MEMBER_STATE_ACTIVE;
								$member->note='';
								$db3->setRecord($member);
								$group->memberCount++;
								
								// Update reverse index of the group membership
								$db5=new groupMemberIndex(GROUP_REVERSE_DB_DIR.rtrim($memberID).GROUP_INDEX_SUFFIX);
								$member->ID=$groupID;
								if (!$db5->setRecord($member)) array_push($failed,$memberID);
							}
							$db2->setRecord($group);
							$outContent.=chr(RESPONSE_SUCCESS).chr(0);
							if (count($failed)>0)
							{
								$outContent.='<IDList t=f>';
								foreach ($failed as $temp) $outContent.='<ID>'.$temp.'</ID>';
								$outContent.='</IDList>';
							}
							break;
						case ACCOUNT_GROUP_DEL_MEMBER:
							$group=$db2->getRecord($groupID);
							if (!$group) {$outContent.=chr(RESPONSE_FAILED).chr(RESPONSE_GROUP_NOT_EXIST); break;}
							$db3=new groupMemberIndex(GROUP_MEMBER_DB_DIR.rtrim($groupID).GROUP_INDEX_SUFFIX);
							$member=$db3->getRecord($ID);
							if ($member->role < GROUP_MEMBER_ROLE_ADMIN) {$outContent.=chr(RESPONSE_FAILED).chr(RESPONSE_GROUP_NO_PERMISSION); break;}
							$memberList=matchIDList($content);
							if ($memberList==NULL) {$outContent.=chr(RESPONSE_INVALID).chr(0); break;}
							
							$db4=new accDB(ACCOUNT_LIST);
							$failed=array();
							foreach ($memberList as $memberID)
							{
								if (!$db4->existRecord($memberID) || $group->creator==$memberID) {array_push($failed,$memberID); continue;}
								$group->memberCount--;
								$db3->delRecord($memberID);
								
								// Update reverse index of the group membership
								$db5=new groupMemberIndex(GROUP_REVERSE_DB_DIR.rtrim($memberID).GROUP_INDEX_SUFFIX);
								if (!$db5->delRecord($groupID)) array_push($failed,$memberID);
							}
							$db2->setRecord($group);
							$outContent=chr(RESPONSE_SUCCESS).chr(0);
							if (count($failed)>0)
							{
								$outContent.='<IDList t=f>';
								foreach ($failed as $temp) $outContent.='<ID>'.$temp.'</ID>';
								$outContent.='</IDList>';
							}
							break;
						case ACCOUNT_GROUP_GET_MEMBER:
							$db3=new groupMemberIndex(GROUP_MEMBER_DB_DIR.rtrim($groupID).GROUP_INDEX_SUFFIX);
							if (!$db3->existRecord($ID)) {$outContent.=chr(RESPONSE_FAILED).chr(RESPONSE_GROUP_NOT_MEMEBER); break;}
							
							$memberIDs=$db3->getRecordList(GROUP_MEMBER_STATE_ACTIVE);
							$db4=new accDB(ACCOUNT_LIST);
							$outContent.=chr(RESPONSE_SUCCESS).chr(0).'<IDList>';
							foreach ($memberIDs as $member)
								$outContent.='<ID s='.$db4->getState($member).'>'.$member.'</ID>';
							$outContent.='</IDList>';
							break;
						case ACCOUNT_GROUP_GET_NAME:
							$groupList=matchIDList($content);
							if ($groupList==NULL) {$outContent.=chr(RESPONSE_INVALID).chr(0); break;}
							$outContent=chr(RESPONSE_SUCCESS).chr(0).'<MList>';
							foreach ($groupList as $groupID)
								$outContent.='<ID>'.$groupID.'</ID><NAME>'.$db2->getName($groupID).'</NAME>';
							$outContent.='</MList>';
							break;
						case ACCOUNT_GROUP_GET_INFO:
							$group=$db2->getRecord($groupID);
							if (!$group) {$outContent.=chr(RESPONSE_FAILED).chr(RESPONSE_GROUP_NOT_EXIST); break;}
							$db3=new groupMemberIndex(GROUP_MEMBER_DB_DIR.rtrim($groupID).GROUP_INDEX_SUFFIX);
							$member=$db3->getRecord($ID);
							if (!$member) {$outContent.=chr(RESPONSE_FAILED).chr(RESPONSE_GROUP_NOT_MEMEBER); break;}
							$outContent=chr(RESPONSE_SUCCESS).chr(0).'<MList>';
							$outContent.='<ID>'.$group->ID.'</ID>';
							$outContent.='<COUNT>'.$group->memberCount.'</COUNT>';
							$outContent.='<TIME>'.$group->creationTime.'</TIME>';
							$outContent.='<NAME>'.$group->name.'</NAME>';
							$outContent.='<DESC>'.$group->description.'</DESC>';
							$outContent.='<ROLE>'.$member->role.'</ROLE>';
							$outContent.='</MList>';
							break;
						case ACCOUNT_GROUP_SET_NAME:
							$group=$db2->getRecord($groupID);
							if (!$group) {$outContent.=chr(RESPONSE_FAILED).chr(RESPONSE_GROUP_NOT_EXIST); break;}
							$db3=new groupMemberIndex(GROUP_MEMBER_DB_DIR.rtrim($groupID).GROUP_INDEX_SUFFIX);
							$member=$db3->getRecord($ID);
							if ($member->role < GROUP_MEMBER_ROLE_ADMIN) {$outContent.=chr(RESPONSE_FAILED).chr(RESPONSE_GROUP_NO_PERMISSION); break;}
							
							$group->name=$content;
							$db2->setRecord($group);
							$outContent=chr(RESPONSE_SUCCESS).chr(0);
							break;
						case ACCOUNT_GROUP_SET_DESCRIP:
							$group=$db2->getRecord($groupID);
							if (!$group) {$outContent.=chr(RESPONSE_FAILED).chr(RESPONSE_GROUP_NOT_EXIST); break;}
							$db3=new groupMemberIndex(GROUP_MEMBER_DB_DIR.rtrim($groupID).GROUP_INDEX_SUFFIX);
							$member=$db3->getRecord($ID);
							if ($member->role < GROUP_MEMBER_ROLE_ADMIN) {$outContent.=chr(RESPONSE_FAILED).chr(RESPONSE_GROUP_NO_PERMISSION); break;}
							
							$group->description=$content;
							$db2->setRecord($group);
							$outContent=chr(RESPONSE_SUCCESS).chr(0);
							break;
						case ACCOUNT_GROUP_DEL_GROUP:
							if (!$db2->existRecord($groupID)) {$outContent.=chr(RESPONSE_FAILED).chr(RESPONSE_GROUP_NOT_EXIST); break;}
							$member=$db3->getRecord($ID);
							if ($member->role < GROUP_MEMBER_ROLE_CREATOR) {$outContent.=chr(RESPONSE_FAILED).chr(RESPONSE_GROUP_NO_PERMISSION); break;}
							
							$db2->delRecord($groupID);
							unlink(GROUP_MEMBER_DB_DIR.rtrim($groupID).GROUP_INDEX_SUFFIX);
							$outContent=chr(RESPONSE_SUCCESS).chr(0);
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
