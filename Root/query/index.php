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
		else
		{
			$option=ord(substr($buffer,QUERY_HEADER_LEN+1,1));
			if ($option==QUERY_TYPE_NONE)
				$out.=chr(RESPONSE_SUCCESS).chr(0);
			else
			{
				$serverList='';
				if ($option & QUERY_TYPE_GET_ACC)
				{
					$serverList.=chr(RESPONSE_SUCCESS).chr(count($AccServer));
					foreach ($AccServer as $item) $serverList.=$item.chr(0);
				}
				if ($option & QUERY_TYPE_GET_REC) 
				{
					$serverList.=chr(RESPONSE_SUCCESS).chr(count($RecServer));
					foreach ($RecServer as $item) $serverList.=$item.chr(0);
				}
				if ($serverList=='')
					$out.=chr(RESPONSE_INVALID);
				else
					$out.=$serverList;
			}
		}
	}
}
echo($out);
exit(0);			
?>
