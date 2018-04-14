<?php
if (!defined('RESPONSE_HEADER')) exit(0);
define('ENC_DELTA_DEFAULT','`-jvDj34hjG]vb 0-r 32-ug11`JWaepoj 1#@f12?#');
define('ENC_AES_ALGO', 'AES-256-CBC');
define('ENC_AES_KEY_LEN', 32);
define('ENC_AES_IV_LEN', 16);

function genKey($len=16, $seed='', $dec=false)
{
	if (!$seed) $seed='';
	if ($seed=='' || strlen($seed)<4) $seed=md5(time());
	$seedLen=strlen($seed);
	$temp='';
	if ($dec)
		for($i=0;$i<$len;$i++)
			$temp.=(ord($seed[rand(0,$seedLen-1)])+mt_rand(0,255))%10;
	else
		
		for($i=0;$i<$len;$i++)
			$temp.=chr(ord($seed[rand(0,$seedLen-1)])+mt_rand(0,255));
	return $temp;
}
function fuse($value, $delta='', $base=128) //Return:String
{
	$j=0;
	if (strlen($delta)<8) $delta=ENC_DELTA_DEFAULT;
	for ($i=0;$i<strlen($value);$i++)
	{
		$value[$i]=chr((ord($value[$i])+ord($delta[$j])*3+$base)%256);
		$j=($j+1)%strlen($delta);
	}
	return $value;
}
function fuse_R($value, $delta='', $base=128)
{
	$j=0;
	if (strlen($delta)<8) $delta=ENC_DELTA_DEFAULT;
	for ($i=0;$i<strlen($value);$i++)
	{
		$value[$i]=chr((256+(ord($value[$i])-ord($delta[$j])*3-$base)%256)%256);
		$j=($j+1)%strlen($delta);
	}
	return $value;
}
function crc32sum($value)
{
    $var=crc32($value);
    return chr($var&0xFF).chr($var>>8 & 0xFF).chr($var>>16 & 0xFF).chr($var>>24 & 0xFF);
}
function sha256sum($value)
{
    return hash("sha256", $value, true);
}
function hmac($value, $key)
{
	return hash_hmac('sha256', $value, $key, true);
}
function aes_encrypt($value, $key)
{
    if (strlen($key) < ENC_AES_KEY_LEN) $key=hash("sha256", $key, true);
    $iv=genKey(ENC_AES_IV_LEN);
    return $iv.openssl_encrypt($value, ENC_AES_ALGO, $key, OPENSSL_RAW_DATA, $iv);
}
function aes_decrypt($value, $key)
{
    if (strlen($key) < ENC_AES_KEY_LEN) $key=hash("sha256", $key, true);
    $iv=substr($value, 0, ENC_AES_IV_LEN);
    return openssl_decrypt(substr($value, ENC_AES_IV_LEN), ENC_AES_ALGO, $key, OPENSSL_RAW_DATA, $iv);
}
?>