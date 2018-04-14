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
	// Check validity of device version
	$device=ord(substr($buffer,QUERY_HEADER_LEN,1));
	if (!in_array($device,$validVersion)) $out.=chr(RESPONSE_DEVICE_UNSUPPORTED);
	else 
	{
		// Check validity of option ID
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
				// Extract real ID
				$ID=substr($buffer,QUERY_HEADER_LEN+2,QUERY_LOGIN_ACCOUNT_LEN);
				$key=substr($buffer,QUERY_HEADER_LEN+2+QUERY_LOGIN_ACCOUNT_LEN);
				$ID2=$ID;
				$ID=fuse_R($ID,$key);
				if (!checkID($ID)) {$out.=chr(RESPONSE_INVALID); break;}
				
				// Extract pre-login key
				$db=new accDB(ACCOUNT_LIST);
				if (!$db->OK) {$out.=chr(RESPONSE_FAILED); break;}
				$password=$db->getPW($ID);
				$key=aes_decrypt($key,$password);
				if (!checkKey($key,QUERY_LOGIN_KEY_LEN)) {$out.=chr(RESPONSE_INVALID); break;}	
				
				// Generate pre-encryption key
				$randKey=genKey(QUERY_LOGIN_KEY_LEN);
				$key=bytesXOR($randKey,$key);
				
				// Write pre-encryption information to database,
				// then return the pre-encryption key to client
				$db2=new loginDB(LOGIN_LIST);
				if (!$db2->OK) {$out.=chr(RESPONSE_FAILED); break;}
				$tempRecord=new loginRecord();
				$tempRecord->ID=$ID;
				$tempRecord->ID_Encoded=substr(hmac($ID,$randKey),0,QUERY_LOGIN_ACCOUNT_LEN);
				$tempRecord->Key=$randKey;
				$tempRecord->LastTime=gmdate(TIME_FORMAT);
				$tempRecord->Device=$device;
				if (!$db2->setRecord($tempRecord)) $out.=chr(RESPONSE_FAILED);
				else $out.=chr(RESPONSE_SUCCESS).aes_encrypt($key,$password);
				break;
			case QUERY_LOGIN_TYPE_LOGIN:
				// Extract hashed ID
				$ID=substr($buffer,QUERY_HEADER_LEN+2,QUERY_LOGIN_ACCOUNT_LEN);
				
				// Read pre-encryption information from database
				$db=new loginDB(LOGIN_LIST);
				if (!$db->OK) {$out.=chr(RESPONSE_FAILED); break;}
				$tempRecord=$db->getRecord($ID,true);
				if (!$tempRecord) $out.=chr(RESPONSE_FAILED);
				else
				{
					// Reject device change or login timeout
					if ($tempRecord->Device != $device) {$out.=chr(RESPONSE_FAILED); break;}
					if (timeDiff($tempRecord->LastTime)>LOGIN_TIME_EXPIRE) {$out.=chr(RESPONSE_DATE_EXPIRED); break;}
					
					// Read double-hashed password from database
					$db2=new accDB(ACCOUNT_LIST);
					if (!$db2->OK) {$out.=chr(RESPONSE_FAILED); break;}
					$password_salted=substr($db2->getPW2($ID),0,ACCOUNT_KEY_SALTED_LEN);
					
					// Extract hashed password, 
					// then re-hash it with the given salt
					$plainText=aes_decrypt(substr($buffer,QUERY_HEADER_LEN+2+QUERY_LOGIN_ACCOUNT_LEN),$tempRecord->Key);
					$password=substr(hmac(substr($plainText, 0, ACCOUNT_KEY_LEN),substr($password_salted,ACCOUNT_KEY_SALTED_LEN)),0,ACCOUNT_KEY_SALTED_LEN);
					
					// Compare double-hashed password
					if (bytes_diff($password_salted,$password)) {$out.=chr(RESPONSE_FAILED); break;}
					
					// Extract online state
					$state=ord(substr($plainText,ACCOUNT_KEY_LEN,1));
					if (!($state==ACCOUNT_STATE_HIDE || $state==ACCOUNT_STATE_ONLINE || $state==ACCOUNT_STATE_BUSY)) {$out.=chr(RESPONSE_INVALID);break;}
					
					// Update account information to database
					$account=$db2->getRecord($tempRecord->ID);
					$account->State=$state;
					$account->LastDevice=$device;
					if (!$db2->setRecord($account)) {$out.=chr(RESPONSE_FAILED); break;}
					
					// Generate session information, write it to database,
					// then return it to client
					$sessionID=genKey(ACCOUNT_SESSION_LEN);
					$sessionKey=genKey(ACCOUNT_COMMKEY_LEN,$tempRecord->Key);
					
					$db3=new commDB(COMM_LIST);
					if (!$db3->OK) {$out.=chr(RESPONSE_FAILED); break;}					
					if (!$db3->setSession($tempRecord->ID,$sessionID,$sessionKey)) $out.=chr(RESPONSE_FAILED);
					else
					{
						$outContent=$sessionKey.$account->Msg;
						$out.=chr(RESPONSE_SUCCESS).$sessionID.intToBytes(ACCOUNT_ACTIVE_TIME,2).chr($account->State).aes_encrypt(crc32sum($outContent).$outContent,$tempRecord->Key);
					}
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
