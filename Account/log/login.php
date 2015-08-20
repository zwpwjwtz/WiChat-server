<?php
require_once('../common/config.php');
require_once('./config.php');


if (!(defined('ACCOUNT_SERVER') && defined('ACCOUNT_LIST') && defined('RESPONSE_HEADER'))) //Fatal Error
{
	exit(0);
}
$out=RESPONSE_HEADER;
$buffer=file_get_contents("php://input");
if (substr($buffer,0,QUERY_HEADER_LEN)!=QUERY_HEADER) $out.=chr(RESPONSE_INVALID);
else
{
	if (!in_array(ord(substr($buffer,QUERY_HEADER_LEN,1)),$validVersion)) $out.=chr(RESPONSE_DEVICE_UNSUPPORTED);
	else 
	{
		$option=ord(substr($buffer,QUERY_HEADER_LEN+1,1));
		if (!($option==QUERY_LOGIN_TYPE_NONE || $option==QUERY_LOGIN_TYPE_GET_KEY || $option==QUERY_LOGIN_TYPE_LOGIN)) $out.=chr(RESPONSE_INVALID);
		else
		{
			include_once('../common/lib.php');
			include_once('../common/enc.php');
			switch($option)
			{
			case QUERY_LOGIN_TYPE_NONE:
				$out.=chr(RESPONSE_SUCCESS);
				break;
			case QUERY_LOGIN_TYPE_GET_KEY:
				$ID=substr($buffer,QUERY_HEADER_LEN+2,QUERY_LOGIN_ACCOUNT_LEN);
				$key=substr($buffer,QUERY_HEADER_LEN+2+QUERY_LOGIN_ACCOUNT_LEN,QUERY_LOGIN_KEY_LEN);
				if (!checkKey($key,QUERY_LOGIN_KEY_LEN)) {$out.=chr(RESPONSE_INVALID); break;}
				$ID=fuse_R($ID,$key);
				if (!checkID($ID)) { $out.=chr(RESPONSE_INVALID); break;} 
				$db=new loginDB(LOGIN_LIST);
				if (!$db->OK) {$out.=chr(RESPONSE_FAILED); break;}
				$tempRecord=new loginRecord();
				$tempRecord->ID=$ID;
				$tempRecord->ID_Encoded=encode($ID,$key,ACCOUNT_ID_MAXLEN-1).chr(0);
				$tempRecord->Key=$key;
				$key=genKey(QUERY_LOGIN_KEY_LEN,$key);
				$tempRecord->Key=encode($tempRecord->Key,$key,QUERY_LOGIN_KEY_LEN,true);				
				$tempRecord->Device=$device;
				if (!$db->setRecord($tempRecord)) $out.=chr(RESPONSE_FAILED);
				else $out.=chr(RESPONSE_SUCCESS).$key.intToBytes(LOGIN_TIME_EXPIRE);				
				break;
			case QUERY_LOGIN_TYPE_LOGIN:
				$state=ord(substr($buffer,QUERY_HEADER_LEN+2+QUERY_LOGIN_ACCOUNT_LEN+QUERY_LOGIN_PW_LEN,1));
				if (!($state==ACCOUNT_STATE_HIDE || $state==ACCOUNT_STATE_ONLINE || $state==ACCOUNT_STATE_BUSY)) {$out.=chr(RESPONSE_INVALID);break;}
				$ID=substr($buffer,QUERY_HEADER_LEN+2,QUERY_LOGIN_ACCOUNT_LEN);
				$password=substr($buffer,QUERY_HEADER_LEN+2+QUERY_LOGIN_ACCOUNT_LEN,QUERY_LOGIN_PW_LEN);
				if (!checkKey($password,QUERY_LOGIN_PW_LEN)) {$out.=chr(RESPONSE_INVALID); break;}
				$db=new loginDB(LOGIN_LIST);
				if (!$db->OK) {$out.=chr(RESPONSE_FAILED); break;}
				$tempRecord=$db->getRecord($ID,true);
				if (!$tempRecord) $out.=chr(RESPONSE_FAILED);
				else
				{
					if (timeDiff($lastTime)>LOGIN_TIME_EXPIRE) {$out.=chr(RESPONSE_FAILED); fclose($f); break;}
					$db2=new accDB(ACCOUNT_LIST);
					if (!$db2->OK) {$out.=chr(RESPONSE_FAILED); break;}
					$encoder=new CSC1();
					if (!$db2->verify($tempRecord->ID,$encoder->decrypt($password,$tempRecord->Key))) {$out.=chr(RESPONSE_FAILED); break;}
					$account=$db2->getRecord($tempRecord->ID);
					if (!$account) {$out.=chr(RESPONSE_FAILED); break;}
					$account->State=$state;
					$account->LastDevice=$device;
					$account->Session=genKey(ACCOUNT_SESSION_LEN,$password);
					$db3=new commDB(COMM_LIST);
					if (!$db3->OK) {$out.=chr(RESPONSE_FAILED); break;}
					if (!$db3->setKey($tempRecord->ID,encode($account->Session,$tempRecord->Key,ACCOUNT_COMMKEY_LEN,true))) {$out.=chr(RESPONSE_FAILED); break;}						
					if (!$db2->setRecord($account)) $out.=chr(RESPONSE_FAILED); 
					else $out.=chr(RESPONSE_SUCCESS).$account->Session.intToBytes(ACCOUNT_ACTIVE_TIME).chr($account->State).$encoder->encrypt($account->Msg.chr(0),$tempRecord->Key);
				}				
				break;
			default: $out.=chr(RESPONSE_INVALID); //Should never reach here
			}
		}
	}
}
echo($out);
exit(0);			
?>
