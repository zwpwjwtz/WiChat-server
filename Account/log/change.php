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
	$option=ord(substr($buffer,QUERY_HEADER_LEN,1));
	switch($option)
	{
		case QUERY_CHANGE_STATE:
			$state=ord(substr($buffer,QUERY_HEADER_LEN+1,1));
			if (!($state==ACCOUNT_STATE_DEFAULT || $state==ACCOUNT_STATE_ONLINE || $state==ACCOUNT_STATE_OFFLINE || $state==ACCOUNT_STATE_BUSY || $state==ACCOUNT_STATE_HIDE )) {$out.=chr(RESPONSE_INVALID);break;}
			else
			{
				include_once('../common/lib.php');
				$db=new accDB();				
				if (!$db->OK) {$out.=chr(RESPONSE_BUSY);break;}
				else 
				{	
					$session=substr($buffer,QUERY_HEADER_LEN+2,16);
					$ID=$db->getIDBySession($session);
					if (!checkID($ID)) {$out.=chr(RESPONSE_FAILED);break;}
					else switch($state)
					{
						case ACCOUNT_STATE_DEFAULT:
							$out.=chr(RESPONSE_SUCCESS).chr($db->getState($ID));
							break;
						case ACCOUNT_STATE_ONLINE:
							$out.=chr(RESPONSE_SUCCESS).chr($db->changeState($ID,ACCOUNT_STATE_ONLINE));
							break;
						case ACCOUNT_STATE_OFFLINE:
							$out.=chr(RESPONSE_SUCCESS).chr($db->changeState($ID,ACCOUNT_STATE_OFFLINE));
							break;
						case ACCOUNT_STATE_BUSY:
							$out.=chr(RESPONSE_SUCCESS).chr($db->changeState($ID,ACCOUNT_STATE_BUSY));
							break;
						case ACCOUNT_STATE_HIDE:
							$out.=chr(RESPONSE_SUCCESS).chr($db->changeState($ID,ACCOUNT_STATE_HIDE));
							break;
						default: $out.=chr(RESPONSE_INVALID); //Should never reach here
					}
				}
			}
			break;
		default:
			$out.=chr(RESPONSE_INVALID);
	}		
}
echo($out);
exit(0);
		
?>
