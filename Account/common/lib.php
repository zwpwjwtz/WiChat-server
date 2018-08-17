<?php
if (!defined('RESPONSE_HEADER')) exit(0);
define('TIME_REG','/^([0-9]{4})\/([0-1][0-9])\/([0-3][0-9]),([0-1][0-9]|2[0-3]):([0-5][0-9]):([0-5][0-9])$/');

function checkKey($str,$fixedLen=0)
{
	if (!$str) return false;
	if ($fixedLen>0 && strlen($str)!=$fixedLen) return false;
	return true;
}
function checkID($str) //For un-encoded ID only.
{
	if (!$str) return false;
	$p=strpos($str,"\0");
	if ($p<1 || $p>7) return false;
	for($i=0;$i<$p;$i++)
	{
		$ch=$str[$i];
		if ($ch<'0' || $ch>'9') return false;
	}
	return true;
}
function intToBytes($value,$length)
{
	$temp='';
	for ($i=0;$i<$length;$i++)
	{
		$temp.=chr($value & 0xFF);
		$value>>=8;
	}
	return $temp;
}
function bytesToInt($value,$length)
{
	$temp=0;
	if (strlen($value)<$length) $length=strlen($value);
	for ($i=0;$i<$length;$i++)
		$temp+=ord($value[$i])<<($i*8);
	return $temp;
}
function bytesXOR($value1,$value2)
{
	if (!$value1 || !$value2) return '';
	if (strlen($value2) > strlen($value1))
	{
		$temp=$value2;
		$value2=$value1.str_repeat("\0",$value2-$value1);
		$value1=$temp;
	}
	for ($i=0;$i<strlen($value1);$i++)
	{
		$value1[$i]=chr(ord($value1[$i])^ord($value2[$i]));
	}
	return $value1;
}
function stringsToInts($value)  // Return: Array of Integers
{
	if ($value==NULL) return NULL;
	else if (count($value)==1) return (int)$value;
	else
	{
		$temp=array();
		foreach ($value as $item) array_push($temp,(int)$item);
		return $temp;
	}
}	
function timeDiff($time1,$time2='') // Return: Long
{
	date_default_timezone_set('UTC');
	if (!(preg_match(TIME_REG,$time1,$timeArray1)==1 && ($timeArray1=stringsToInts($timeArray1))!=NULL && checkdate($timeArray1[2],$timeArray1[3],$timeArray1[1]))) return 0;
	if ($time2=='')	return time()-mktime($timeArray1[4],$timeArray1[5],$timeArray1[6],$timeArray1[2],$timeArray1[3],$timeArray1[1]);
	else
	{
		if (!(preg_match(TIME_REG,$time2,$timeArray2)==1 && ($timeArray2=stringsToInts($timeArray2))!=NULL && checkdate($timeArray2[2],$timeArray2[3],$timeArray2[1]))) return 0;
		else return mktime($timeArray1[4],$timeArray1[5],$timeArray1[6],$timeArray1[2],$timeArray1[3],$timeArray1[1])-mktime($timeArray2[4],$timeArray2[5],$timeArray2[6],$timeArray2[2],$timeArray2[3],$timeArray2[1]);
	}
}

class loginRecord
{
	public $ID,$ID_Encoded,$Key,$LastTime; //String; $LastTime includes SERVER_ID
	public $Device; //Int
}
class accRecord
{
	public $ID,$Key,$Key_salted,$LastTime; //String; $LastTime includes SERVER_ID
	public $State,$LastDevice; //Int
	public $Msg; //String
}
class groupRecord
{
	public $ID; //String
	public $state,$type; //Int
	public $memberCount; //Int
	public $creator,$creationTime; //String
	public $name,$description; //String
}
class groupMember
{
	public $ID; //String
	public $role,$state; //Int
	public $joinTime; //String
	public $note; //String
}
class DB
{
	public $OK; //Bool
	protected $defaultDB='/../db/db.dat',$dbPrefix='WiChatDD'; //String
	protected $db=''; //String
	protected $Ver=1; //Int
	protected $nowPointer;
	function __construct($dbFile)
	{
		$this->OK=false;
		if (!$dbFile) $dbFile=dirname(__FILE__).$this->defaultDB;
		if (!file_exists($dbFile))
		{
			$f=fopen($dbFile,'wb');
			if (!$f) return;
			flock($f,LOCK_EX);
			fwrite($f,$this->dbPrefix);
			fwrite($f,gmdate(TIME_FORMAT).chr(SERVER_ID));
			fwrite($f,chr(SERVER_ID));
			fwrite($f,chr($this->Ver));
			fwrite($f,chr(0).chr(0));
			flock($f,LOCK_UN);
			fclose($f);
		}
		else
		{
			$f=fopen($dbFile,'rb');
			if (!$f) return; 
			if (fread($f,8)!=$this->dbPrefix) return;
			fseek($f,29);
			$ver=ord(fread($f,1));
			fclose($f);
			if ($ver!=$this->Ver) return;
		}
		$this->db=$dbFile;
		$this->OK=true;
	}
	public function count() //Return:Int; <0 Indicating error
	{
		if (!$this->sync()) return -1;
		$f=fopen($this->db,'rb');
		fseek($f,30);
		$temp=fread($f,2);
		fclose($f);
		return bytesToInt($temp,2);
	}
	protected static function _count($f) //Return:Int; <0 Indicating error; Permission of 'rb' required
	{
		$p=ftell($f);
		fseek($f,30);
		$temp=fread($f,2);
		fseek($f,$p);
		return bytesToInt($temp,2);
	}
	protected static function _inc($f) //Return:Bool; Permission of 'rb+'required
	{
		$p=ftell($f);
		fseek($f,30);
		$temp=bytesToInt(fread($f,2),2);
		if ($temp<65536) $temp+=1; else return false;
		fseek($f,30);
		fwrite($f,intToBytes($temp,2));
		fseek($f,$p);
		return true;
	}
	protected static function _dec($f) //Return:Bool; Permission of 'rb+'required
	{
		$p=ftell($f);
		fseek($f,30);
		$temp=bytesToInt(fread($f,2),2);
		if ($temp>0) $temp-=1; else return false;
		fseek($f,30);
		fwrite($f,intToBytes($temp,2));
		fseek($f,$p);
		return true;
	}
	protected function sync()
	{
		return true;
	}
	protected static function update($f) //Permission of 'rb+'required
	{	
		fseek($f,8);
		fwrite($f,gmdate(TIME_FORMAT).chr(SERVER_ID));
	}	
}

class loginDB extends DB
{
	protected $defaultDB='/../db/login.dat',$dbPrefix='WiChatLD';
	protected $Ver=2;
	
	public function existRecord($ID,$encoded)
	{
		if (!checkID($ID)) return false;
		if (!$this->sync()) return false;
		$f=fopen($this->db,'rb');
		if (self::_count($f)<1) {fclose($f); return false;}
		fseek($f,32);
		$temp='';
		while(true)
		{
			$temp=fread($f,8);
			if ($encoded==false && $temp==$ID) break;
			$temp=fread($f,8);
			if ($encoded==true && $temp==$ID) break;
			if (feof($f)) break;				
			fseek($f,48,SEEK_CUR);
		}
		fclose($f);
		if ($temp==$ID) return true; else return false;		
	}
	public function getRecord($ID,$encoded=false)
	{
		if (!$encoded && !checkID($ID)) return NULL;
		if (!$this->sync()) return NULL;
		$f=fopen($this->db,'rb');
		if (self::_count($f)>0)
		{
			fseek($f,32);
			$temp='';$temp2='';
			while(true)
			{
					$temp=fread($f,8);
					$temp2=fread($f,8);
					if ($encoded && $temp2==$ID || !$encoded && $temp==$ID) break;
					if (feof($f)) break;
					fseek($f,48,SEEK_CUR);
			}
			if (!$encoded && $temp!=$ID || $encoded && $temp2!=$ID) {fclose($f); return NULL;}
		}
		$tempRecord=new loginRecord();
		$tempRecord->ID=$temp; $tempRecord->ID_Encoded=$temp2;
		$temp=fread($f,16);	$tempRecord->Key=$temp;
		$temp=fread($f,20);	$tempRecord->LastTime=substr($temp,0,19);
		$temp=fread($f,1);	$tempRecord->Device=ord($temp);		
		fclose($f);		
		return $tempRecord;			
	}
	
	public function setRecord($record) //Return:Bool
	{
		if (!checkID($record->ID)) return false;
		if (!(checkKey($record->ID_Encoded,8) && checkKey($record->Key,16))) return false;
		if (!$this->sync()) return false;
		$f=fopen($this->db,'rb+');
		flock($f,LOCK_EX);
		$temp='';
		if (self::_count($f)>0)
		{
			fseek($f,32);
			while(true)
			{
				$temp=fread($f,8);
				if ($temp==$record->ID) break;
				if (feof($f)) break;
				fseek($f,56,SEEK_CUR);
			}
		}
		if ($temp!=$record->ID) 
		{
			if (!self::_inc($f)) {flock($f,LOCK_UN); fclose($f); return false;}
			fseek($f,0,SEEK_END);
			fwrite($f,$record->ID.str_repeat(chr(0),8-strlen($record->ID)));
		}
		fwrite($f,$record->ID_Encoded.$record->Key.gmdate(TIME_FORMAT).chr(SERVER_ID).chr($record->Device).str_repeat(chr(0),11));		
		self::update($f);	
		flock($f,LOCK_UN);
		fclose($f);
		
		return true;		
	}
}

class accDB extends DB
{
	
	protected $defaultDB='/../db/account.dat',$dbPrefix='WiChatAD';
	protected $Ver=2;
	
	public function existRecord($ID) //Return:Bool
	{
		if (!$this->sync()) return NULL;
		$f=fopen($this->db,'rb');
		if (self::_count($f)<1) {fclose($f);return false;}
		fseek($f,32);
		$temp=''; 
		while(true)
		{
			$temp=fread($f,8);
			if ($temp==$ID) break;
			if (feof($f)) break;
			fseek($f,120,SEEK_CUR);
		}
		fclose($f);
		if ($temp==$ID) return true; else return false;
	}
	public function getRecord($ID) //Return:class accRecord
	{
		if (!$this->sync()) return NULL;
		$f=fopen($this->db,'rb');
		//if (self::_count($f)<1) {fclose($f);return NULL;}
		fseek($f,32);
		$temp=''; 
		while(true)
		{
			$temp=fread($f,8);
			if ($temp==$ID) break;
			if (feof($f)) break;
			fseek($f,120,SEEK_CUR);
		}
		if ($temp!=$ID) {fclose($f);return NULL;}
		$rec=new accRecord;
		$rec->ID=$temp;
		fseek($f,16,SEEK_CUR);
		$temp=fread($f,20);		$rec->LastTime=substr($temp,0,19);
		$temp=fread($f,16);		$rec->Key_salted=$temp;
		$temp=fread($f,1);		$rec->State=ord($temp);
		$temp=fread($f,1);		$rec->LastDevice=ord($temp);
		$temp=fread($f,64);		$rec->Msg=$temp;
		fclose($f);
		return $rec;		
	}
	public function setRecord($record) //Return:Bool
	{
		if (!$record) return false;
		if (!(checkID($record->ID))) return false; //Necessary Requirement
		if (!$this->sync()) return false;
		$record->Msg=substr($record->Msg,0,64);
				
		$f=fopen($this->db,'rb+');
		flock($f,LOCK_EX);
		$temp='';
		fseek($f,32);
		while(true)
		{
				$temp=fread($f,8);
				if ($temp==$record->ID) break;
				if (feof($f)) break;
				fseek($f,120,SEEK_CUR);
		}
		if ($temp!=$record->ID) 
		{
			if (!self::_inc($f)) {flock($f,LOCK_UN); fclose($f); return false;}
			fseek($f,-8,SEEK_CUR);
			fwrite($f,$record->ID.str_repeat(chr(0),128-strlen($record->ID)));
			fseek($f,-120,SEEK_CUR);
		}		
		fseek($f,16,SEEK_CUR);
		fwrite($f,gmdate(TIME_FORMAT).chr(SERVER_ID));
		fseek($f,16,SEEK_CUR);
		fwrite($f,chr($record->State).chr($record->LastDevice).$record->Msg);
		self::update($f);
		flock($f,LOCK_UN);
		fclose($f);
		
		return true;
		
	}
	/*Temporarily unused
	public function	delRecord($ID) //Return:Bool
	{
		if (!checkID($ID)) return false;
		if (!$this->sync()) return false;
		$f=fopen($this->db,'rb+');
		//if (self::_count($f)<1) {fclose($f);return false;}
		flock($f,LOCK_EX);
		fseek($f,32);
		while(true)
		{
			$temp=fread($f,8);
			if ($temp==$ID) break;
			if (feof($f)) break;
			fseek($f,128,SEEK_CUR);
		}
		if ($temp==$ID) 
			if (self::_dec($f))
			{
				fwrite($f,KEY_NULL.gmdate(TIME_FORMAT).chr(SERVER_ID).KEY_NULL.chr(0).chr(0).str_repeat(chr(0),64)); //ID Information Reserved
				self::update($f);
				flock($f,LOCK_UN);
				fclose($f);
				return true;				
			}
		flock($f,LOCK_UN);
		fclose($f); 
		return false;	
	}
	*/
	public function getPW($ID) //Return:String
	{
		if (!$this->sync()) return false;
		$f=fopen($this->db,'rb');
		//if (self::_count($f)<1) {fclose($f);return false;}
		fseek($f,32);
		$temp='';
		while(true)
		{
			$temp=fread($f,8);
			if ($temp==$ID) break;
			if (feof($f)) break;
			fseek($f,120,SEEK_CUR);
		}
		if ($temp!=$ID) {fclose($f);return '';}
		$temp=fread($f,16);
		fclose($f);
		return $temp;
	}
	public function setPW($ID,$newPassword) //Return:Bool
	{
		if (!$this->sync()) return false;
		if (!checkKey($newPassword,16)) return false;
		$f=fopen($this->db,'rb+');
		flock($f,LOCK_EX);
		//if (self::_count($f)<1) {fclose($f);return false;}
		fseek($f,32);
		$temp='';
		while(true)
		{
			$temp=fread($f,8);
			if ($temp==$ID) break;
			if (feof($f)) break;
			fseek($f,120,SEEK_CUR);
		}
		if ($temp!=$ID) {flock($f,LOCK_UN);fclose($f);return false;}
		fwrite($f,$newPassword.str_repeat(chr(0),16-strlen($newPassword)));
		fwrite($f,gmdate(TIME_FORMAT).chr(SERVER_ID));
		self::update($f);
		flock($f,LOCK_UN);
		fclose($f);
		return true;
	}
	public function getPW2($ID) //Return:String
	{
		if (!$this->sync()) return false;
		$f=fopen($this->db,'rb');
		//if (self::_count($f)<1) {fclose($f);return false;}
		fseek($f,32);
		$temp='';
		while(true)
		{
			$temp=fread($f,8);
			if ($temp==$ID) break;
			if (feof($f)) break;
			fseek($f,120,SEEK_CUR);
		}
		if ($temp!=$ID) {fclose($f);return '';}
		fseek($f,36,SEEK_CUR);
		$temp=fread($f,16);
		fclose($f);
		return $temp;
	}
	public function setPW2($ID,$Password_salted) //Return:Bool
	{
		if (!$this->sync()) return false;
		if (!checkKey($Password_salted,16)) return false;
		$f=fopen($this->db,'rb+');
		flock($f,LOCK_EX);
		//if (self::_count($f)<1) {fclose($f);return false;}
		fseek($f,32);
		$temp='';
		while(true)
		{
			$temp=fread($f,8);
			if ($temp==$ID) break;
			if (feof($f)) break;
			fseek($f,120,SEEK_CUR);
		}
		if ($temp!=$ID) {flock($f,LOCK_UN);fclose($f);return false;}
		fseek($f,16,SEEK_CUR);
		fwrite($f,gmdate(TIME_FORMAT).chr(SERVER_ID));
		fwrite($f,$Password_salted);
		self::update($f);
		flock($f,LOCK_UN);
		fclose($f);
		return true;
	}
	public function getState($ID) // Return:Int; <0 indicating error
	{
		if (!$this->sync()) return -1;
		$f=fopen($this->db,'rb');
		//if (self::_count($f)<1) {fclose($f);return ACCOUNT_STATE_DEFAULT;}
		fseek($f,32);
		while(true)
		{
			$temp=fread($f,8);
			if ($temp==$ID) break;
			if (feof($f)) break;
			fseek($f,120,SEEK_CUR);
		}
		if ($temp==$ID) 
		{
			fseek($f,52,SEEK_CUR);
			$temp=fread($f,1);
			fclose($f);
			return ord($temp);	
		}
		else
		{
			fclose($f);
			return ACCOUNT_STATE_DEFAULT;
		}				
	}
	public function setState($ID,$newState) // Return:Int; <0 indicating error
	{
		if (!$this->sync()) return false;
		$f=fopen($this->db,'rb+');
		//if (self::_count($f)<1) {fclose($f);return -1;}
		flock($f,LOCK_EX);
		fseek($f,32);
		while(true)
		{
			$temp=fread($f,8);
			if ($temp==$ID) break;
			if (feof($f)) break;
			fseek($f,120,SEEK_CUR);
		}
		if ($temp!=$ID) {flock($f,LOCK_UN); fclose($f); return -1;}
		fseek($f,16,SEEK_CUR);
		fwrite($f,gmdate(TIME_FORMAT).chr(SERVER_ID));
		fseek($f,16,SEEK_CUR);
		fwrite($f,chr($newState),1);
		flock($f,LOCK_UN);
		self::update($f);
		fclose($f);
		return $newState;			
	}
}

class commDB extends DB
{
	protected $defaultDB='/../db/comm.dat',$dbPrefix='WiChatCD';
	protected $Ver=2;
	
	public function getKey($ID) //Return:String
	{
		if (!checkID($ID)) return false;
		if (!$this->sync()) return false;
		$f=fopen($this->db,'rb');
		fseek($f,32);
		$temp='';
		while(true)
		{
			$temp=fread($f,8);
			if ($temp==$ID) break;
			if (feof($f)) break;
			fseek($f,56,SEEK_CUR);
		}
		if ($temp!=$ID) {fclose($f); return '';}
		$temp=fread($f,16);
		fclose($f);		
		return $temp;			
	}
	public function setSession($ID,$newSession,$newKey) // Return:Bool
	{
		if (!$this->sync()) return false;
		if (!checkKey($newSession,16) || !checkKey($newKey,16)) return false;
		$f=fopen($this->db,'rb+');
		flock($f,LOCK_EX);
		fseek($f,32);
		$temp=fread($f,8);
		while(true)
		{
			if ($temp==$ID) break;
			if (feof($f)) break;
			fseek($f,56,SEEK_CUR);
			$temp=fread($f,8);
		}
		if ($temp!=$ID) 
		{
			if (!self::_inc($f)) {flock($f,LOCK_UN); fclose($f); return false;}
			fseek($f,0,SEEK_END);
			fwrite($f,$ID.str_repeat(chr(0),8-strlen($ID)));
		}
		fwrite($f,$newKey.gmdate(TIME_FORMAT).chr(SERVER_ID).$newSession);
		fwrite($f,str_repeat(chr(0),4));
		flock($f,LOCK_UN);
		self::update($f);
		fclose($f);
		return true;
		
	}
	public function getIDBySession($Session) //Return:String
	{
		if (!$this->sync()) return '';
		$f=fopen($this->db,'rb');
		fseek($f,76);
		$temp=fread($f,16);
		while(true)
		{
			if ($temp==$Session) break;
			if (feof($f)) break;
			fseek($f,48,SEEK_CUR);
			$temp=fread($f,16);
		}
		if ($temp==$Session)
		{
			fseek($f,-60,SEEK_CUR);
			$temp=fread($f,8);
			fclose($f);
			return $temp;
		}
		else
		{
			fclose($f);
			return '';
		}
	}
}

class relDB extends DB
{
	protected $defaultDB='/../db/relation.dat',$dbPrefix='WiChatRD';
	protected $Ver=1;
	
	public function getFriend($ID,$state=RELATION_STATE_ESTABLISHED) // Return: Array of Strings
	{
		if (!checkID($ID)) return false;
		if (!$this->sync()) return false;
		$f=fopen($this->db,'rb');
		$group=array();
		
		$temp1='';$temp2='';
		fseek($f,32);
		while(true)
		{
			$temp1=fread($f,8);
			$temp2=fread($f,8);
			if (feof($f)) break;
			if ($temp1!=$ID && $temp2!=$ID) {fseek($f,112,SEEK_CUR); continue;}
			fseek($f,20,SEEK_CUR);
			if (fread($f,1)==chr($state))
			switch($state)
			{
				case RELATION_STATE_ESTABLISHED:
					if ($temp1==$ID) array_push($group,$temp2);
					else array_push($group,$temp1);
					break;	
				case RELATION_STATE_BREAKING:
					$deleter=ord(fread($f,1));
					if ($deleter==1) {if ($temp2==$ID) array_push($group,$temp1);}
					else {if ($temp1==$ID) array_push($group,$temp2);}
					fseek($f,-1,SEEK_CUR);
					break;
				case RELATION_STATE_WAITING:
					if ($ID==$temp1) continue; else array_push($group,$temp1);
					break;					
			}
			fseek($f,91,SEEK_CUR);
		}
		fclose($f);
		return $group;
	}
	public function getFriendDate($person1,$person2) //Return: String
	{
		if (!(checkID($person1) && checkID($person1))) return '';
		if (!$this->sync()) return '';
		$temp='';
		$f=fopen($this->db,'rb');
		$temp1='';$temp2='';
		fseek($f,32);
		while(true)
		{
			$temp1=fread($f,8);
			$temp2=fread($f,8);
			if ($temp1==$person1 && $temp2==$person2 || $temp2==$person1 && $temp1==$person2)
			{
				$temp=substr(fread($f,20),0,19);
				if (fread($f,1)!=chr(RELATION_STATE_ESTABLISHED)) $temp='';
				break;
			}
			if (feof($f)) break;
			fseek($f,112,SEEK_CUR);
		}
		fclose($f);
		return $temp;
	}
	public function setFriend($inviter,$invited) // Return: Bool
	{
		if (!(checkID($inviter) && checkID($inviter))) return false;
		if (!$this->sync()) return false;
		$f=fopen($this->db,'rb+');
		flock($f,LOCK_EX);
		fseek($f,32);
		while(true)
		{
			$temp1=fread($f,8);
			$temp2=fread($f,8);
			if ($temp1==$inviter && $temp2==$invited || $temp2==$inviter && $temp1==$invited)	break;
			if (feof($f)) break;
			fseek($f,112,SEEK_CUR);
		}
		if (feof($f))	
		{
			if (!self::_inc($f)) {flock($f,LOCK_UN); fclose($f); return false;}
			fwrite($f,str_repeat(chr(0),8-strlen($inviter)).$inviter.str_repeat(chr(0),8-strlen($invited)).$invited.gmdate(TIME_FORMAT).chr(SERVER_ID).chr(RELATION_STATE_WAITING).str_repeat(chr(0),91));
		}
		else
		{
			fseek($f,20,SEEK_CUR);
			if (fread($f,1)==chr(RELATION_STATE_WAITING))
			{
				if ($inviter!=$temp1)
				{
					fseek($f,-21,SEEK_CUR);
					fwrite($f,gmdate(TIME_FORMAT).chr(SERVER_ID).chr(RELATION_STATE_ESTABLISHED));
				}
			}
			else 
			{
				fseek($f,-37,SEEK_CUR);
				fwrite($f,$inviter.$invited.gmdate(TIME_FORMAT).chr(SERVER_ID).chr(RELATION_STATE_WAITING));
			}
		}
		self::update($f);
		flock($f,LOCK_UN);
		fclose($f);
		return true;		
	}
	public function delFriend($deleter,$deleted) //Return: Bool
	{
		if (!(checkID($deleter) && checkID($deleted))) return false;
		if (!$this->sync()) return false;
		$f=fopen($this->db,'rb+');
		flock($f,LOCK_EX);
		fseek($f,32);
		while(true)
		{
			$temp1=fread($f,8);
			$temp2=fread($f,8);
			if ($temp1==$deleter && $temp2==$deleted || $temp2==$deleter && $temp1==$deleted)	break;
			if (feof($f)) break;
			fseek($f,112,SEEK_CUR);
		}
		if (feof($f))
		{	
			flock($f,LOCK_UN);
			fclose($f);
			return false;
		}	
		else
		{
			fseek($f,20,SEEK_CUR);
			$state=ord(fread($f,1));
			fseek($f,-21,SEEK_CUR);
			switch($state)
			{
				case RELATION_STATE_ESTABLISHED:
					fwrite($f,gmdate(TIME_FORMAT).chr(SERVER_ID).chr(RELATION_STATE_BREAKING).chr($deleter==$temp1?1:2)); break;
				case RELATION_STATE_BREAKING:
					fwrite($f,gmdate(TIME_FORMAT).chr(SERVER_ID).chr(RELATION_STATE_NONE).str_repeat(chr(0),91)); break;
				case RELATION_STATE_WAITING:
					fwrite($f,gmdate(TIME_FORMAT).chr(SERVER_ID).chr(RELATION_STATE_NONE)); break;
			}
			self::update($f);
			flock($f,LOCK_UN);
			fclose($f);
			return true;
		}
	}
	public function getNote($noter,$noted) //Return:String
	{
		if (!(checkID($noter) && checkID($noted))) return '';
		if (!$this->sync()) return '';
		$temp='';
		$f=fopen($this->db,'rb');
		$temp1='';$temp2='';
		fseek($f,32);
		while(true)
		{
			$temp1=fread($f,8);
			$temp2=fread($f,8);
			if ($temp1==$noter && $temp2==$noted || $temp2==$noter && $temp1==$noted)
			{
				fseek($f,20,SEEK_CUR);
				if (fread($f,1)!=chr(RELATION_STATE_ESTABLISHED)) {$temp='';break;}
				if ($temp1==$noter)	fseek($f,1,SEEK_CUR); else	fseek($f,33,SEEK_CUR);		
				$temp=fread($f,32);
				break;
			}
			if (feof($f)) break;
			fseek($f,112,SEEK_CUR);
		}
		fclose($f);
		return $temp;
	}
	public function setNote($noter,$noted,$notation) //Return:Bool
	{
		if (!(checkID($noter) && checkID($noted))) return '';
		if (!$this->sync()) return '';
		$temp='';
		$f=fopen($this->db,'rb+');
		flock($f,LOCK_EX);
		$temp1='';$temp2='';
		fseek($f,32);
		while(true)
		{
			$temp1=fread($f,8);
			$temp2=fread($f,8);
			if ($temp1==$noter && $temp2==$noted || $temp2==$noter && $temp1==$noted)
			{
				fseek($f,20,SEEK_CUR);
				if (fread($f,1)!=chr(RELATION_STATE_ESTABLISHED)){flock($f,LOCK_UN);fclose($f);return false;}
				if ($temp1==$noter) fseek($f,1,SEEK_CUR); else fseek($f,33,SEEK_CUR);
				fwrite($f,$notation,32);				
				break;
			}
			if (feof($f)) {flock($f,LOCK_UN);fclose($f);return false;}
			fseek($f,112,SEEK_CUR);
		}
		self::update($f);
		flock($f,LOCK_UN);
		fclose($f);
		return true;
	}
}

class groupDB extends DB
{
	
	protected $defaultDB='/../db/group.dat',$dbPrefix='WiChatGD';
	protected $Ver=2;
	const nullID="\0\0\0\0\0\0\0\0";
	
	private static function _locateRecord($f,$recordID) //Return: Int
	{
		fseek($f,32);
		while(true)
		{
			$tempID=fread($f,8);
			if ($recordID==$tempID) break;
			if (feof($f)) break;
			fseek($f,120,SEEK_CUR);
		}
		if ($recordID==$tempID) return ftell($f)-8; else return 0;
	}
	
	public function existRecord($ID) //Return:Bool
	{
		if (!$this->sync()) return NULL;
		if ($ID==self::nullID) return false;
		$f=fopen($this->db,'rb');
		$pos=self::_locateRecord($f,$ID);
		fclose($f);
		if ($pos<=0) return false;
		else return true;
	}
	public function getRecord($ID) //Return:class groupRecord
	{
		if (!$this->sync()) return NULL;
		$f=fopen($this->db,'rb');
		$pos=self::_locateRecord($f,$ID);
		if ($pos<=0) return NULL;
		$rec=new groupRecord;
		$rec->ID=$ID;
		fseek($f,$pos+8);
		$temp=fread($f,8);		$rec->creator=$temp;
		$temp=fread($f,20);		$rec->creationTime=substr($temp,0,19);
		$temp=fread($f,1);		$rec->state=ord($temp);
		$temp=fread($f,1);		$rec->type=ord($temp);
		$temp=fread($f,2);		$rec->memberCount=bytesToInt($temp,2);
		$temp=fread($f,32);		$rec->name=$temp;
		$temp=fread($f,60);		$rec->description=$temp;
		fclose($f);
		return $rec;
	}
	public function setRecord($record) //Return:Bool
	{
		if (!$record) return false;
		if (!(checkID($record->ID))) return false; //Necessary Requirement
		if (!$this->sync()) return false;
		$record->name=substr($record->name,0,32);
		$record->description=substr($record->description,0,56);
		
		$f=fopen($this->db,'rb+');
		flock($f,LOCK_EX);
		$pos=self::_locateRecord($f,$record->ID);
		if ($pos<=0) 
		{
			if (!self::_inc($f)) {flock($f,LOCK_UN); fclose($f); return false;}
			$pos=self::_locateRecord($f,self::nullID);
			if ($pos>0) fseek($f,$pos);
			else fseek($f,0,SEEK_END);
			fwrite($f,$record->ID.$record->creator.str_repeat(chr(0),8-strlen($record->creator)).gmdate(TIME_FORMAT).chr(SERVER_ID));
			fseek($f,-22,SEEK_CUR);
		}
		else
			fseek($f,$pos+36);
		fwrite($f,chr($record->state).chr($record->type).intToBytes($record->memberCount,2));
		fwrite($f,$record->name.str_repeat(chr(0),32-strlen($record->name)));
		fwrite($f,$record->description.str_repeat(chr(0),56-strlen($record->description)));
		self::update($f);
		flock($f,LOCK_UN);
		fclose($f);
		return true;
	}
	public function	delRecord($ID) //Return:Bool
	{
		if (!checkID($ID)) return false;
		if (!$this->sync()) return false;
		$f=fopen($this->db,'rb+');
		flock($f,LOCK_EX);
		$pos=self::_locateRecord($f,$ID);
		if ($pos<=0 || !self::_dec($f)) {flock($f,LOCK_UN); fclose($f); return false;}
		
		fseek($f,$pos);
		fwrite($f,self::nullID.str_repeat(chr(0),16).gmdate(TIME_FORMAT).chr(SERVER_ID).str_repeat(chr(0),92));
		self::update($f);
		flock($f,LOCK_UN);
		fclose($f);
		return true;
	}
	public function countMember($ID) // Return:Int; <0 indicating error
	{
		if (!$this->sync()) return -1;
		$f=fopen($this->db,'rb');
		$pos=self::_locateRecord($f,$ID);
		if ($pos<=0) {fclose($f); return -1;}
		fseek($f,$pos+38);
		$temp=fread($f,2);
		fclose($f);
		return bytesToInt($temp,2);
	}
	public function getName($ID) // Return:String
	{
		if (!$this->sync()) return -1;
		$f=fopen($this->db,'rb');
		$pos=self::_locateRecord($f,$ID);
		if ($pos<=0) {fclose($f); return -1;}
		fseek($f,$pos+40);
		$temp=fread($f,32);
		fclose($f);
		return $temp;
	}
}

class groupMemberIndex extends DB
{
	protected $defaultDB='',$dbPrefix='WiChatGI';
	protected $Ver=2;
	const nullID="\0\0\0\0\0\0\0\0";
	
	private static function _locateRecord($f,$recordID) //Return: Int
	{
		fseek($f,32);
		while(true)
		{
			$tempID=fread($f,8);
			if ($recordID==$tempID) break;
			if (feof($f)) break;
			fseek($f,56,SEEK_CUR);
		}
		if ($recordID==$tempID) return ftell($f)-8; else return 0;
	}
	public function existRecord($recordID) //Return: Bool
	{
        if (!$this->sync()) return false;
		$f=fopen($this->db,'rb');
		$pos=self::_locateRecord($f,$recordID);
		fclose($f);
		if ($pos<=0) return false;
		else return true;
	}
	public function getRecord($recordID)	//Return: groupMember
	{
		if (!$this->sync()) return NULL;
		$f=fopen($this->db,'rb');
		$pos=self::_locateRecord($f,$recordID);
		if ($pos<=0) return NULL;
		fseek($f,$pos);
		$tempRecord=new groupMember();
		$temp=fread($f,8);	$tempRecord->ID=$temp;
		$temp=fread($f,1);	$tempRecord->state=ord($temp);
		$temp=fread($f,1);	$tempRecord->role=ord($temp);
		$temp=fread($f,19);	$tempRecord->joinTime=$temp;
		$temp=fread($f,32);	$tempRecord->note=$temp;
		fclose($f);
		return $tempRecord;
	}
	public function getRecordList($state)	//Return: Array of string
	{
		if (!$this->sync()) return NULL;
		$recordList=array();
		$f=fopen($this->db,'rb');
		fseek($f,32);
		while(true)
		{
			$ID=fread($f,8);
			$tempState=ord(fread($f,1));
			if ($tempState==$state)
			{
				array_push($recordList,$ID);
			}
			if (feof($f)) break;
			fseek($f,55,SEEK_CUR);
		}
		fclose($f);
		return $recordList;
	}	
	public function setRecord($data)	//Return: Bool
	{
		if ($data==NULL) return false;
		if (!(checkID($data->ID))) return false;
		if (!$this->sync()) return false;
		$data->note=substr($data->note,0,32);
		
		$f=fopen($this->db,'rb+');
		flock($f,LOCK_EX);
		$pos=self::_locateRecord($f,$data->ID);
		if ($pos>0)
			fseek($f,$pos+8);
		else
		{
			if (!self::_inc($f)) {flock($f,LOCK_UN); fclose($f); return false;}
			$pos=self::_locateRecord($f,self::nullID);
			if ($pos>0) fseek($f,$pos);
			else fseek($f,0,SEEK_END);
			fwrite($f,$data->ID.chr(0).chr(0).gmdate(TIME_FORMAT).chr(SERVER_ID));
			fseek($f,-22,SEEK_CUR);
		}
		fwrite($f,chr($data->state).chr($data->role));
		fseek($f,20,SEEK_CUR);
		fwrite($f,$data->note.str_repeat(chr(0),34-strlen($data->note)));
		self::update($f);
		flock($f,LOCK_UN);
		fclose($f);
		return true;
	}
	public function delRecord($ID) //Return:Bool
	{
		if (!checkID($ID)) return false;
		if (!$this->sync()) return false;
		$f=fopen($this->db,'rb+');
		flock($f,LOCK_EX);
		$pos=self::_locateRecord($f,$ID);
		if ($pos<=0) return false;
		if (!self::_dec($f)) {flock($f,LOCK_UN); fclose($f); return false;}
		fseek($f,$pos);
		fwrite($f,self::nullID. chr(0).chr(0).gmdate(TIME_FORMAT).chr(SERVER_ID).str_repeat(chr(0),34));
		self::update($f);
		flock($f,LOCK_UN);
		fclose($f); 
		return true;
	}
}
?>
