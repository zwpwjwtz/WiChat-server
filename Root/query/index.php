<?php
require_once('./config.php');
if (!(defined('ROOT_SERVER') && defined('RESPONSE_HEADER') && isset($AccServer,$RecServer))) //Fatal Error
{
	exit(0);
}
$out=RESPONSE_HEADER;
if (define('SERVER_IN_MAINTANANCE')) {$out.=chr(RESPONSE_IN_MAINTANANCE).chr(0);exit(0);}

$buffer=file_get_contents("php://input");
if (strlen($buffer)!=QUERY_LEN) $out.=chr(RESPONSE_INVALID);
else
{
	//Start from Pos 0 instead of 1!!!
	if (substr($buffer,0,QUERY_HEADER_LEN)!=QUERY_HEADER) $out.=chr(RESPONSE_INVALID);
	else
	{
		if (!in_array(ord(substr($buffer,QUERY_HEADER_LEN,1)),$validVersion)) $out.=chr(RESPONSE_DEVICE_UNSUPPORTED);
		else switch(ord(substr($buffer,QUERY_HEADER_LEN+1,1)))
		{
			case QUERY_TYPE_NONE:
				if (false) $out.=chr(RESPONSE_BUSY).chr(0);
				else $out.=chr(RESPONSE_SUCCESS).chr(0);
				break;
			case QUERY_TYPE_GET_ACC:
				if (false) $out.=chr(RESPONSE_BUSY).chr(0);
				else 
				{
					$out.=chr(RESPONSE_SUCCESS).chr(count($AccServer));
					foreach ($AccServer as $item) $out.=$item.chr(0);
				}
				break;
			case QUERY_TYPE_GET_REC:
				if (false) $out.=chr(RESPONSE_BUSY).chr(0);
				else 
				{
					$out.=chr(RESPONSE_SUCCESS).chr(count($RecServer));
					foreach ($RecServer as $item) $out.=$item.chr(0);
				}
				break;
			default: $out.=chr(RESPONSE_INVALID);
		}
	}
}
echo($out);
exit(0);			
?>
