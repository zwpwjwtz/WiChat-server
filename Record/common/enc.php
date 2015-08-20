<?php
if (!defined('RESPONSE_HEADER')) exit(0);
define('ENC_KEY_SEED','9foiw2H$$GDSF#T$GS0fdWERWeG1u032FHd39f0wlog23a11idlk4IJG0gGRR0');
define('ENC_DELTA_DEFAULT','`-jvDj34hjG]vb 0-r 32-ug11`JWaepoj 1#@f12?#');

function genKey($len=16,$seed='')
{
  if ($seed=='') $seed=ENC_KEY_SEED;
  if(strlen($seed)<4) $seed=genKey(16);
  $seed=md5($seed);
  if (!$seed) return '';
  $seedLen=strlen($seed);
  $temp='';
  for($i=0;$i<$len;$i++) $temp.=((int)substr($seed,rand(0,$seedLen-1),1)+rand(0,9))%10;
  return $temp;
}
function intTo4Bytes($value) //Return:String
{
	return chr($value&0xFF).chr(($value>>8)&0xFF).chr(($value>>16)&0xFF).chr(($value>>24)&0xFF);
}
function encode($value,$key,$outLen=0,$dec=false) // Return:String
{
	if (strlen($key)<16) $key=ENC_KEY_SEED;
	$len=strlen($value); $klen=strlen($key);
	if ($outLen<1) 
	 if (strlen($value)<1) $outLen=16; else $outLen=$len;
	do
	{
		$value=sha1($value,true).$value;
		$len=strlen($value);
		if ($dec)
			for ($i=0;$i<$len;$i++) $value[$i]=chr((ord($value[$i])+ord($key[($i+22)%$klen]))%10+48);
		else
			for ($i=0;$i<$len;$i++)	$value[$i]=chr((ord($value[$i])+ord($key[($i+22)%$klen]))%256);					
	}while($len<$outLen);
	return substr($value,0,$outLen);
}
function fuse($value,$delta,$base=128) //Return:String
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
function fuse_R($value,$delta,$base=128)
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
class CSC1
{
	public $OK; //Bool
	const MinKeyLength=16; //Int
	const MaxFileBlock = 65000; //Int
	private static $nineCell=array(1,2,3,6,9,8,7,4,5);
	function __construct()
	{
		$this->OK=true;
	}
	private static function rotate($number, $angle)
	{
       	$angle %= 8;
		$temp='';
 		if ($angle == 0) $temp = $number;
		else
		{
			for ($i = 0;$i<strlen($number);$i++)
			{
   				$value = (int)$number[$i];
				if ($value == 0) $temp.='0';
				else if ($value == 5) $temp.='5';
				else 
				{
					for ($j = 0;$j<8;$j++) if (self::$nineCell[$j] == $value) break;
      					$temp.=self::$nineCell[($angle + $j) % 8];
      				}
      		}
		}
      	return $temp;
	}
	private static function rotate_R($number,$angle)
	{
		return self::rotate($number,8- $angle % 8);
	}
	private static function invert($number, $mirror)
	{
		$temp=''; $value=0;
		for ($i = 0; $i<strLen($number); $i++) 
		{
			$value = (int)$number[$i];
			if ($value == 0) $value = 5;
			else if ($value == 5) $value = 0;
			else $value = 10 - $value;
			$temp .= $value;
		}
		$temp = self::reflect($temp, $mirror);
		return $temp;
	}
	private static function invert_R($number, $mirror)
	{
 		$number=self::reflect_R($number,$mirror);
		$temp='';
		for ($i = 0; $i<strLen($number); $i++) 
		{
			$value = (int)$number[$i];
			if ($value == 0) $value = 5;
			else if ($value == 5) $value = 0;
			else $value = 10 - $value;
			$temp .= $value;
		}
		return $temp;
	}
	private static function reflect($number, $mirror)
	{
 		$temp=''; $value=0;
		for ($i = 0; $i<strLen($number); $i++) 
		{
			$value = (int)$number[$i];
			if ($value !=9 )
			switch($mirror % 4)
			{
				case 0: $value = ((int)($value / 3) + 2) % 3 * 3 + $value % 3; break;
    			case 1: $value = (int)($value / 3) * 3 + ($value + 1) % 3; break;
				case 2: $value = (int)($value / 3) * 3 + ($value + 2) % 3; break;
				case 3: $value = ((int)($value / 3) + 1) % 3 * 3 + $value % 3; break;
			}
			$temp.=$value;
		}
		return $temp;
	}
	private static function reflect_R($number,$mirror)
	{
		return self::reflect($number,3 - $mirror % 4);
	}
	private static function rotoreflect($number, $mixedVar)
 	{
 		return self::reflect(self::rotate($number, $mixedVar), $mixedVar);
 	}
	private static function rotoreflect_R($number, $mixedVar)
	{
		return self::rotate_R(self::reflect_R($number, $mixedVar), $mixedVar);
	}
	private function shift($number, $digit) //As String
	{
		$l=strlen($number);
		if ($digit >= 0)
		{
			$digit = $digit % $l;
  			return substr($number, $l-$digit,$digit).substr($number,0,$l - $digit);
 		}
		else
		{
			$digit = (-$digit) % $l;
  			return substr($number, $digit,$l - $digit).substr($number,0,$digit);
		}
	}

	private static function checkDigits($number) //Return:Bool
	{
 		$flag = true;
 		for ($i = 0; $i<strlen($number); $i++)
 			if (ord($number[$i])> 57 || ord($number[$i])< 48) {$flag = false; break;}
		return $flag;
	}
	private static function checkKey($key) //Return:Bool
	{
		if ($key == '' || strlen($key) < self::MinKeyLength) return false;
 		if (!self::checkDigits($key)) return false;
		return true;
	}
	private static function buildKey($key) // Return:Bool
	{
 		if (!self::checkKey($key)) return null;
 		$keyArray=array_pad(array(),(int)(strlen($key)/2)*2,0);
 		for ($i=0;$i<count($keyArray);$i++)
 		{
 			$keyArray[$i]=(int)$key[$i] % 4;
			$i++;
 			$keyArray[$i]=(int)$key[$i];
 		}
		return $keyArray;
	}
	private static function arrayReset(&$arr)
	{
		for ($i=0;$i<count($arr);$i++) $arr[$i]="\0";
		return;
	}
	public function encrypt($value,$key) //Return:String
	{
		$keyArray=self::buildKey($key);
		if (!$keyArray) return null;
		$fileEnd=false;

  		$kl = count($keyArray);
  		$pF = 0; $pK = 2;
   		$tempByte = 0; $tempChar = '';
   		$bufferOut=str_repeat("\0",(int)(self::MaxFileBlock * 1.25));
  		while(!$fileEnd)
  		{
			if ($pF + self::MaxFileBlock < strlen($value))
		    	{
		        	 $bufferIn=substr($value,$pF,self::MaxFileBlock);
		        	 $pF+=self::MaxFileBlock;
		  	  }
		    	else
		    	{
				$bufferIn=substr($value,$pF,strlen($value) - $pF);
				$bufferOut=str_repeat("\0",(int)((strlen($value) - $pF - 1) * 1.25) + 2);
				$fileEnd = true;
			}
			$pBO = 0;
			for ($pBI = 0; $pBI< strlen($bufferIn); $pBI++)
			{
				$tempChar = self::shift(sprintf('%03d',ord($bufferIn[$pBI])),$keyArray[$pK]+$keyArray[$pK+1]);
     			switch($keyArray[$pK])
    			{
    				case 0: $tempChar = self::rotate($tempChar, $keyArray[$pK+1]);break;
    				case 1: $tempChar = self::invert($tempChar, $keyArray[$pK+1]);break;
					case 2: $tempChar = self::reflect($tempChar, $keyArray[$pK+1]);break;
					case 3: $tempChar = self::rotoreflect($tempChar, $keyArray[$pK+1]);
				}
				$bufferOut[$pBO] = chr($tempByte * pow(4 , (4 - $pBI % 4) % 4) | (int)((int)$tempChar / pow(4 ,$pBI % 4 + 1)));
     			$tempByte = (pow(4 , $pBI % 4 + 1) - 1) & (int)$tempChar;
				$pK = ($pK + ord($bufferIn[$pBI])*2) % count($keyArray);
				$pBO++;
     			if ($pBO % 5 == 4)
				{
					$bufferOut[$pBO] = chr($tempByte);
					$tempByte = 0;
					$pBO++;
				}
			}
   			if  ($pBI  % 4 > 0 && $pBO <= strlen($bufferOut)) $bufferOut[$pBO] = chr(pow(4 , 3 - ($pBI - 1) % 4) * $tempByte);
     	}
 		self::arrayReset($keyArray);
 		return $bufferOut;
	}
	public function decrypt($value,$key) // Return:String
	{
		$keyArray=self::buildKey($key);
		if (!$keyArray) return null;
		$fileEnd=false;
  		$kl = count($keyArray);
  		$pF = 0; $pK = 2;
  		$tempByte = 0; $tempChar = '';
   		$bufferOut=str_repeat("\0",self::MaxFileBlock);
		while(!$fileEnd)
		{
			if ($pF + self::MaxFileBlock * 1.25 < strlen($value))
		 	{
				$bufferIn=substr($value,$pF,self::MaxFileBlock * 1.25);
				$pF += self::MaxFileBlock * 1.25;
			}
			else
			{
				$bufferIn=substr($value,$pF,strlen($value) - $pF);
				$bufferOut=str_repeat("\0",(int)((strlen($value) - $pF + 4) / 1.25) - 3);
				$fileEnd = true;
			}
			$pBO = 0;
			for ($pBI = 0; $pBI<strlen($bufferIn)-1; $pBI++)
			{
				$tempVar = (ord($bufferIn[$pBI]) & pow(4 ,4 - $pBI % 5) - 1) * pow(4,($pBI + 1) % 5) | (int)(ord($bufferIn[$pBI + 1]) / pow(4 , 3 - $pBI % 5));
     				$tempChar = self::shift(sprintf('%03d',$tempVar),-$keyArray[$pK]-$keyArray[$pK+1]);
     				switch($keyArray[$pK])
				{
					case 0: $tempChar = self::rotate_R($tempChar,$keyArray[$pK+1]); break;
					case 1: $tempChar = self::invert_R($tempChar, $keyArray[$pK+1]); break;
					case 2: $tempChar = self::reflect_R($tempChar, $keyArray[$pK+1]); break;
					case 3: $tempChar = self::rotoreflect_R($tempChar, $keyArray[$pK+1]);
				}
				$bufferOut[$pBO] = chr((int)$tempChar);
				$pK = ($pK + ord($bufferOut[$pBO])*2) % count($keyArray);
    			$pBO++;
     			if($pBI % 5 == 3) $pBI++;
			}
		}
  		self::arrayReset($keyArray);
	  	return $bufferOut;
	}
}	
?>