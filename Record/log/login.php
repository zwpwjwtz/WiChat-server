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
	if (strlen($buffer)<QUERY_HEADER_LEN+QUERY_LOGIN_SESSION_LEN+4+ACCOUNT_COMMKEY_LEN) $out.=chr(RESPONSE_INVALID);
	else 
	{
		// Query account server for session key
		include_once('../scomm/query.php');
		include_once('../scomm/config.php');
		$Session=substr($buffer,QUERY_HEADER_LEN,QUERY_LOGIN_SESSION_LEN);
		$content=queryAccountServer(ACCOUNT_SCOMM_ACTION_GET_ID_KEY, $Session);
		if (strlen($content)<ACCOUNT_ID_MAXLEN+ACCOUNT_COMMKEY_LEN) $out.=chr(RESPONSE_FAILED).chr(0);
		else
		{
			// Extract session information
			$ID=substr($content,0,ACCOUNT_ID_MAXLEN);
			$commKey=substr($content,ACCOUNT_ID_MAXLEN,ACCOUNT_COMMKEY_LEN);
			if (!checkID($ID)) $out.=chr(RESPONSE_FAILED).chr(0);
			else
			{
				// Parse client request
				include_once('../common/enc.php');
				include_once('../common/lib.php');
				$content=aes_decrypt(substr($buffer,QUERY_HEADER_LEN+QUERY_LOGIN_SESSION_LEN+4),$commKey);
				if (substr($buffer,QUERY_HEADER_LEN+QUERY_LOGIN_SESSION_LEN,4)!=intToBytes(crc32($content),4)) $out.=chr(RESPONSE_FAILED).chr(0);
				else //Certification passed.
				{
					// Extract pre-shared key
					$key=substr($content,0,ACCOUNT_COMMKEY_LEN);
					
					// Get key salt for client-side encryption
					$db=new accDB2(ACCOUNT_LIST_CACHE);
					$salt=$db->getSalt($ID);
					if ($salt=='')
					{
						$salt=genKey(ACCOUNT_COMMKEY_LEN);
						$db->setSalt($ID,$salt);
					}
					
					// Generate session information, write it to database,
					// then return it to client
					$db2=new commDB(COMM_LIST);
					$key=substr(hmac($commKey,$key),0,ACCOUNT_COMMKEY_LEN);
					if (!$db2->setSession($ID,$Session,$key)) $outContent=chr(RESPONSE_FAILED).chr(0);
					else $outContent=chr(RESPONSE_SUCCESS).chr(0).$salt;
					$out.=intToBytes(crc32($outContent),4).aes_encrypt($outContent,$commKey);
				}
			}
		}
	}
}
echo($out);
exit(0);			
?>
