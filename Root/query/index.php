<?php
require_once('../common/config.php');
require_once('./config.php');

function serverListToString($serverList,$port=true)
{
	$result=chr(RESPONSE_SUCCESS).chr(count($serverList));
	foreach ($serverList as $server)
	{
		$result.=$server['address'].chr(0);
		if ($port) $result.=$server['port'].chr(0);
	}
	return $result;
}

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
		$version=ord(substr($buffer,QUERY_HEADER_LEN,1));
		if (!in_array($version,$validVersion)) $out.=chr(RESPONSE_DEVICE_UNSUPPORTED);
		else
		{
			$option=ord(substr($buffer,QUERY_HEADER_LEN+1,1));
			$usePort=($version>5);
			if ($option==QUERY_TYPE_NONE)
				$out.=chr(RESPONSE_SUCCESS).chr(0);
			else
			{
				$outContent='';
				if ($option & QUERY_TYPE_GET_ACC)
					$outContent.=serverListToString($AccServer,$usePort);
				if ($option & QUERY_TYPE_GET_REC) 
					$outContent.=serverListToString($RecServer,$usePort);
				if ($option & QUERY_TYPE_GET_WEB) 
					$outContent.=serverListToString($WebServer,$usePort);
				if ($outContent=='')
					$out.=chr(RESPONSE_INVALID);
				else
					$out.=$outContent;
			}
		}
	}
}
echo($out);
exit(0);			
?>
