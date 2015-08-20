<?php
require_once('../common/config.php');
require_once('./config.php');

if (!(defined('ACCOUNT_SERVER') && defined('COMM_LIST') && defined('RESPONSE_HEADER'))) //Fatal Error
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
		if (!($option==QUERY_LOGIN_TYPE_NONE || $option==QUERY_LOGIN_TYPE_SET_KEY)) $out.=chr(RESPONSE_INVALID);
		else
		{
			switch($option)
			{
			case QUERY_LOGIN_TYPE_NONE:
				$out.=chr(RESPONSE_SUCCESS);
				break;
			case QUERY_LOGIN_TYPE_SET_KEY:
				include_once('../common/lib.php');
				$Session=substr($buffer,QUERY_HEADER_LEN+2,QUERY_LOGIN_SESSION_LEN);
				$key=substr($buffer,QUERY_HEADER_LEN+2+QUERY_LOGIN_SESSION_LEN,QUERY_LOGIN_KEY_LEN);
				if (!checkKey($key,QUERY_LOGIN_KEY_LEN)) {$out.=chr(RESPONSE_INVALID); break;}
				$sf=file_get_contents('http://'.ACCOUNT_SERVER.'scomm/query.php?a='.ACCOUNT_SCOMM_ACTION_GET_ID_KEY.'&s='.$Session);
				if (substr($sf,0,SERVER_RESPONSE_HEADER_LEN)!=SERVER_RESPONSE_HEADER) $out.=chr(RESPONSE_FAILED).chr(0); 
				else
				{
					include_once('../common/enc.php');
					$encoder=new CSC1();
					$content=$encoder->decrypt(substr($sf,SERVER_RESPONSE_HEADER_LEN+4,strlen($sf)-SERVER_RESPONSE_HEADER_LEN),ACCOUNT_SCOMM_KEY);
					if (substr($sf,SERVER_RESPONSE_HEADER_LEN,4)!=intTo4Bytes(crc32($content)) || strlen($content)<ACCOUNT_ID_MAXLEN+ACCOUNT_COMMKEY_LEN) $out.=chr(RESPONSE_FAILED).chr(0); 
					else
					{
						$ID=substr($content,0,ACCOUNT_ID_MAXLEN);
						if (!checkID($ID)) {$out.=chr(RESPONSE_FAILED).chr(0); break;}
						$db=new accDB2(ACCOUNT_LIST_CACHE);
						if (!$db->OK) {$out.=chr(RESPONSE_FAILED); break;}
						$salt=$db->getSalt($ID);
						if ($salt=='')
						{
							$salt=encode(genKey(ACCOUNT_COMMKEY_LEN),genKey(ACCOUNT_COMMKEY_LEN),ACCOUNT_COMMKEY_LEN);							
							$db->setSalt($ID,$salt);
						}
						$db->setSession($ID,$Session);
						$db=new commDB(COMM_LIST);
						if (!$db->setKey($ID,encode(substr($content,ACCOUNT_ID_MAXLEN,ACCOUNT_COMMKEY_LEN),$key,ACCOUNT_COMMKEY_LEN,true))) $out.=chr(RESPONSE_FAILED).chr(0);					
						else $out.=chr(RESPONSE_SUCCESS).chr(0).$salt;
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
