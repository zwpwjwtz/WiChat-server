<?php
require_once('../common/config.php');
require_once('./config.php');

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
						case ACCOUNT_ACTION_NONE:
							$outContent.=chr(RESPONSE_SUCCESS).chr(0);
							break;
						case ACCOUNT_ACTION_INFO_SESSION_CHANGE:
							$found=false;
							for ($i=0;$i<ACCOUNT_MAX_TRY_TIMES;$i++)
							{
								$newSession=genKey(ACCOUNT_SESSION_LEN);
								if($db2->getIDBySession($newSession)=='') {$found=true; break;}
							}
							if (!$found) {$outContent.=chr(RESPONSE_FAILED).chr(0); break;}
							$newKey=genKey(ACCOUNT_COMMKEY_LEN);
							if (!$db2->setSession($ID,$newSession,$newKey))
								$outContent.=chr(RESPONSE_FAILED).chr(0);
							else
								$outContent.=chr(RESPONSE_SUCCESS).chr(0).$newSession.$newKey;
							break;
						case ACCOUNT_ACTION_INFO_MSG_GET:
							$tempRecord=$db->getRecord($ID);
							if (!$tempRecord) {$outContent.=chr(RESPONSE_FAILED).chr(0); break;}
							$outContent.=chr(RESPONSE_SUCCESS).chr(0).'<MSG>'.$tempRecord->Msg.'</MSG>';
							break;
						case ACCOUNT_ACTION_INFO_MSG_SET:
							$msg=array();
							if (!preg_match(REG_MSG,$content,$msg) || count($msg)!=2) {$outContent.=chr(RESPONSE_INVALID).chr(0); break;}
							$tempRecord=$db->getRecord($ID);
							if (!$tempRecord) {$outContent.=chr(RESPONSE_FAILED).chr(0); break;}
							$tempRecord->Msg=$msg[1];
							$db->setRecord($tempRecord);
							$outContent.=chr(RESPONSE_SUCCESS).chr(0);
							break;
						case ACCOUNT_ACTION_INFO_STATE_SET:
							if(!($option==ACCOUNT_STATE_ONLINE || $option==ACCOUNT_STATE_OFFLINE || $option==ACCOUNT_STATE_BUSY || $option==ACCOUNT_STATE_HIDE || $option==ACCOUNT_STATE_DEFAULT)) {$outContent.=chr(RESPONSE_INVALID).chr(0);break;}
							if ($option==ACCOUNT_STATE_DEFAULT)
								$outContent.=chr(RESPONSE_SUCCESS).chr($db->getState($ID));
							else
							{
								if ($db->setState($ID,$option)!=$option)
									$outContent.=chr(RESPONSE_FAILED).chr(0);
								else
									$outContent.=chr(RESPONSE_SUCCESS).chr($option);
							}
							break;
						case ACCOUNT_ACTION_INFO_PW_CHANGE:
							$password=explode(chr(0),$content);
							if (count($password) < 2)
							{$outContent.=chr(RESPONSE_INVALID).chr(0);break;}
							$password[0]=sha256sum($password[0]);
							$password[1]=sha256sum($password[1]);
							$oldKey=substr(hmac($password[0],$ID),0,ACCOUNT_KEY_LEN);
							$newKey=substr(hmac($password[1],$ID),0,ACCOUNT_KEY_LEN);
							if ($oldKey != $db->getPW($ID))
							{$outContent.=chr(RESPONSE_SUCCESS).chr(RESPONSE_ACCOUNT_PASSWORD_INCORRECT); break;}
							if (!$db->setPW($ID,$newKey))
							{$outContent.=chr(RESPONSE_FAILED).chr(0); break;}
							$key_salt=genkey(ACCOUNT_KEY_LEN - ACCOUNT_KEY_SALTED_LEN);
							$saltedKey=hmac(substr($password[1],0,ACCOUNT_KEY_LEN),$key_salt);
							if (!$db->setPW2($ID,substr($saltedKey,0,ACCOUNT_KEY_SALTED_LEN).$key_salt))
								$outContent.=chr(RESPONSE_FAILED).chr(0);
							else
								$outContent.=chr(RESPONSE_SUCCESS).chr(RESPONSE_ACCOUNT_PASSWORD_OK);
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
