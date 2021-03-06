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

class accRecord
{
	public $ID,$LastTime,$Session; //String; $LastTime includes SERVER_ID
	public $State,$LastDevice; //Int
	public $Msg; //String
}
class dataRecord
{
	public $from,$to;		//String
	public $type,$length;		//Int
	public $state;			//Int
	public $resID;			//String
	public $lastTime;		//String
}
class dataRecordIndex
{
	public $from;		//String
	public $type,$length;		//Int
	public $state;			//Int
	public $recordID;		//String
	public $lastTime;		//String
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

class accDB extends DB
{
	
	protected $defaultDB='/../db/account.dat',$dbPrefix='WiChatAD';
	protected $Ver=1;
	
	public function existRecord($ID) //Return:Bool
	{
		if (!$this->sync()) return false;
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
		$temp=fread($f,16);		$rec->Session=$temp;
		$temp=fread($f,1);		$rec->State=ord($temp);
		$temp=fread($f,1);		$rec->LastDevice=ord($temp);
		$temp=fread($f,64);		$rec->Msg=$temp;
		fclose($f);
		return $rec;		
	}
	public function setRecord($record) //Return:Bool
	{
		if (!$record) return false;
		if (!(checkID($record->ID) && checkKey($record->Session,16))) return false; //Necessary Requirement
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
		fwrite($f,gmdate(TIME_FORMAT).chr(SERVER_ID).$record->Session.chr($record->State).chr($record->LastDevice).$record->Msg);
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
	public function verify($ID,$Password) //Return:Bool
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
		if ($temp!=$ID) {fclose($f);return false;}
		$temp=fread($f,16);
		fclose($f);
		if (substr(sha1($Password,true),4,16)==$temp) return true; else return false;
	}
	public function setPW($ID,$PasswordOld,$PasswordNew) //Return:Bool
	{
		if (!$this->sync()) return false;
		if (!$this->verify($ID,$PasswordOld)) return false;
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
		if ($temp!=$ID) {fclose($f);return false;}
		fwrite($f,$PasswordNew.str_repeat(chr(0),16-strlen($newPassword)));
		fwrite($f,gmdate(TIME_FORMAT).chr(SERVER_ID));
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
		fwrite($f,chr($newState));
		flock($f,LOCK_UN);
		self::update($f);
		fclose($f);
		return $newState;			
	}
}

class accDB2 extends accDB
{
	protected $defaultDB='/../db/account2.dat',$dbPrefix='WiChatAD';
	protected $Ver=1;
	public function setPW($ID,$PasswordOld,$PasswordNew){} //Set empty
	public function verify($ID,$Password){} //Set empty
	public function getSalt($ID) //Return:String
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
			fseek($f,120,SEEK_CUR);
		}
		if ($temp!=$ID) {fclose($f); return '';}
		$temp=fread($f,16);
		fclose($f);		
		return $temp;			
	}
	public function setSalt($ID,$Salt) //Return:Bool
	{
		if (!(checkID($ID) && checkKey($Salt,16))) return false;
		if (!$this->sync()) return false;
		$f=fopen($this->db,'rb+');
		flock($f,LOCK_EX);
		$temp='';
		fseek($f,32);
		while(true)
		{
			$temp=fread($f,8);
			if ($temp==$ID) break;
			if (feof($f)) break;
			fseek($f,120,SEEK_CUR);
		}
		if ($temp!=$ID) 
		{
			if (!self::_inc($f)) {flock($f,LOCK_UN); fclose($f); return false;}
			fseek($f,0,SEEK_END);
			fwrite($f,$ID.str_repeat(chr(0),128-strlen($ID)));			
			fseek($f,-120,SEEK_END);
		}
		fwrite($f,$Salt.gmdate(TIME_FORMAT).chr(SERVER_ID));
		self::update($f);
		flock($f,LOCK_UN);
		fclose($f);	
		return true;		
	}
}

class recDB extends DB
{
	protected $defaultDB='/../db/rec.dat',$dbPrefix='WiChatSD';
	protected $Ver=1;
	
	private static function _locateRecord($f,$resID) //Return: Int
	{
		fseek($f,48);
		while(true)
		{
			$state=ord(fread($f,1));
			fseek($f,5,SEEK_CUR);
			$tempID=fread($f,16);
			if ($resID==$tempID && $state>0) break;
			if (feof($f)) break;
			fseek($f,42,SEEK_CUR);
		}
		if ($resID==$tempID && $state>0) return ftell($f)-38; else return 0;
	}
	public function getRecord($sender,$receptor)	//Return: dataRecord
	{
		if (!(checkID($sender) && checkID($receptor))) return NULL;
		if (!$this->sync()) return NULL;
		$f=fopen($this->db,'rb');
		$temp1='';$temp2='';$state=-1;
		fseek($f,32);
		while(true)
		{
			$temp1=fread($f,8);
			$temp2=fread($f,8);
			$state=ord(fread($f,1));
			if ($temp1==$sender && $temp2==$receptor) break;
			if (feof($f)) break;
			fseek($f,47,SEEK_CUR);
		}
		if (!($state>0 && $temp1==$sender && $temp2==$receptor)) {fclose($f); return NULL;}
		$tempRecord=new dataRecord();
		$tempRecord->from=$temp1;
		$tempRecord->to=$temp2;
		$tempRecord->state=$state;
		$temp=fread($f,1);	$tempRecord->type=ord($temp);
		$temp=fread($f,4);	$tempRecord->length=bytesToInt($temp,4);
		$temp=fread($f,16);	$tempRecord->resID=$temp;
		$temp=fread($f,19);	$tempRecord->lastTime=$temp;
		fclose($f);	
		return $tempRecord;			
	}	
	public function setRecord($data)	//Return: Bool
	{
		if ($data==NULL) return false;
		if (!(checkID($data->from) && checkID($data->to))) return false;
		if (!$this->sync()) return false;
		$f=fopen($this->db,'rb+');
		flock($f,LOCK_EX);
		$temp=self::_locateRecord($f,$data->resID);
		if ($temp>0)
		{
			fseek($f,$temp);
		}
		else
		{
			fseek($f,48);
			while(true)
			{
				$temp=fread($f,1);
				if (feof($f)) break;
				if ($temp==chr(0)) break;
				fseek($f,63,SEEK_CUR);
			}
			if ($temp==chr(0)) 	fseek($f,-17,SEEK_CUR);
			else
			{
				if (!self::_inc($f)) {flock($f,LOCK_UN); fclose($f); return false;}
				fseek($f,0,SEEK_END);
			}
		}
		fwrite($f,$data->from.str_repeat(chr(0),8-strlen($data->from)).$data->to.str_repeat(chr(0),8-strlen($data->to)).chr($data->state).chr($data->type).intToBytes($data->length,4).$data->resID.gmdate(TIME_FORMAT).chr(SERVER_ID).str_repeat(chr(0),6));	
		self::update($f);
		flock($f,LOCK_UN);
		fclose($f);
		return true;
	}	
	public function fetchRecord($receptor,$state)	//Return: Array of String
	{
		$tempList=array();
		if (!checkID($receptor)) return $tempList;
		if (!$this->sync()) return $tempList;
		$f=fopen($this->db,'rb');
		fseek($f,32);
		while(true)
		{
			$temp1=fread($f,8);
			$temp2=fread($f,8);
			$tempState=ord(fread($f,1));
			if ($temp2==$receptor && $state==$tempState) 
			{
				array_push($tempList,$temp1);
			}	
			if (feof($f)) break;
			fseek($f,47,SEEK_CUR);
		}
		fclose($f);
		return $tempList;
	}
	private static function genKey16()	//Return:String
	{
		$temp='';
		for($i=0;$i<16;$i++)	$temp.=rand(0,9);
		return $temp;
	}
	public function getNewResID()	//Return: String
	{
		if (!$this->sync()) return $tempList;
		$f=fopen($this->db,'rb');
		while(true)
		{
			$found=false;
			$temp=self::genKey16();
			fseek($f,54);
			while(true)
			{
				if ($temp==fread($f,16)) {$found=true;break;}
				if (feof($f)) break;
				fseek($f,48,SEEK_CUR);
			}
			if (!$found) break;
		}
		return $temp;
	}
	public function freeResID($resID)	//Return: Bool
	{
		if (!$this->sync()) return $tempList;
		$f=fopen($this->db,'rb+');
		flock($f,LOCK_EX);
		$found=false;
		fseek($f,54);
		while(true)
		{
			if($resID==fread($f,16))
			{
				fseek($f,-22,SEEK_CUR);
				fwrite($f,chr(0));
				$found=true;
				fseek($f,69,SEEK_CUR);
			}
			else
			{
				if (feof($f)) break;
				fseek($f,48,SEEK_CUR);
			}
		}
		self::update($f);
		flock($f,LOCK_UN);
		fclose($f);
		return $found;
	}
	public function doCleaning($cleanState) //Return: List of strings of cleaned ResID
	{
		if (!$cleanState) return NULL;
		if (!$this->sync()) return NULL;
		$cleaned=array();
		$f=fopen($this->db,'rb+');
		flock($f,LOCK_EX);
		fseek($f,48);
		while(true)
		{
			if (fread($f,1)==chr($cleanState))
			{
				fseek($f,-1,SEEK_CUR);
				fwrite($f,chr(0));
				fseek($f,5,SEEK_CUR);
				array_push($cleaned,fread($f,16));
				fseek($f,42,SEEK_CUR);
			}
			else
			{
				if (feof($f)) break;
				fseek($f,63,SEEK_CUR);
			}
		}
		if (count($cleaned)>0) self::update($f);
		flock($f,LOCK_UN);
		fclose($f);
		return $cleaned;
	}
}

class recIndex extends DB
{
	protected $defaultDB='',$dbPrefix='WiChatSI';
	protected $Ver=1;
	
	private static function _locateRecord($f,$recordID) //Return: Int
	{
		fseek($f,32);
		while(true)
		{
			$tempID=fread($f,16);
			$state=ord(fread($f,1));
			if ($recordID==$tempID && $state>0) break;
			if (feof($f)) break;
			fseek($f,47,SEEK_CUR);
		}
		if ($recordID==$tempID && $state>0) return ftell($f)-17; else return 0;
	}
	public function getRecord($recordID)	//Return: dataRecordIndex
	{
		if (!$this->sync()) return NULL;
		$f=fopen($this->db,'rb');
		$temp=self::_locateRecord($f,$recordID);
		if ($temp<=0) return NULL;
		fseek($f,$temp);
		$tempRecord=new dataRecordIndex();
		$temp=fread($f,16);	$tempRecord->recordID=$temp;
		$temp=fread($f,1);	$tempRecord->state=ord($temp);
		$temp=fread($f,1);	$tempRecord->type=ord($temp);
		$temp=fread($f,4);	$tempRecord->length=bytesToInt($temp,4);
		$temp=fread($f,8);	$tempRecord->from=$temp;
		fseek($f,8,SEEK_CUR);
		$temp=fread($f,19);	$tempRecord->lastTime=$temp;
		fclose($f);	
		return $tempRecord;			
	}	
	
	public function getLastRecord()	//Return: dataRecordIndex
	{
		if (!$this->sync()) return NULL;
		$f=fopen($this->db,'rb');
		fseek($f,0,SEEK_END);
		if (ftell($f) <= 32) return NULL;
		fseek($f,-64,SEEK_CUR);
		$tempRecord=new dataRecordIndex();
		$temp=fread($f,16);	$tempRecord->recordID=$temp;
		$temp=fread($f,1);	$tempRecord->state=ord($temp);
		$temp=fread($f,1);	$tempRecord->type=ord($temp);
		$temp=fread($f,4);	$tempRecord->length=bytesToInt($temp,4);
		$temp=fread($f,8);	$tempRecord->from=$temp;
		fseek($f,8,SEEK_CUR);
		$temp=fread($f,19);	$tempRecord->lastTime=$temp;
		fclose($f);	
		return $tempRecord;			
	}	
	public function getRecordsByPosition($begin,$end)	//Return: Array of dataRecordIndex
	{
		if (!$this->sync()) return NULL;
		$recordList=array();
		$f=fopen($this->db,'rb');
		fseek($f,32);
		$pos=0;
		$tempRecord=new dataRecordIndex();
		while(true)
		{
			$temp=fread($f,16);
			$state=ord(fread($f,1));
			if ($state>0)
			{
				$tempRecord->recordID=$temp;
				$tempRecord->state=ord($state);
				$temp=fread($f,1);	$tempRecord->type=ord($temp);
				$temp=fread($f,4);	$tempRecord->length=bytesToInt($temp,4);
				$temp=fread($f,8);	$tempRecord->from=$temp;
				fseek($f,8,SEEK_CUR);
				$temp=fread($f,19);	$tempRecord->lastTime=$temp;
				if ($pos>=$begin)
					array_push($recordList,clone $tempRecord);
				$pos+=$tempRecord->length;
			}
			if (feof($f)) break;
			if ($pos>=$end) break;
			fseek($f,7,SEEK_CUR);
		}
		fclose($f);
		return $recordList;
	}
	public function getAllRecords()	//Return: Array of dataRecordIndex
	{
		if (!$this->sync()) return NULL;
		$recordList=array();
		$f=fopen($this->db,'rb');
		fseek($f,32);
		$tempRecord=new dataRecordIndex();
		while(true)
		{
			$temp=fread($f,16);
			$state=ord(fread($f,1));
			if ($state>0)
			{
				$tempRecord->recordID=$temp;
				$tempRecord->state=$state;
				$temp=fread($f,1);	$tempRecord->type=ord($temp);
				$temp=fread($f,4);	$tempRecord->length=bytesToInt($temp,4);
				$temp=fread($f,8);	$tempRecord->from=$temp;
				fseek($f,8,SEEK_CUR);
				$temp=fread($f,19);	$tempRecord->lastTime=$temp;
				array_push($recordList,clone $tempRecord);
			}
			if (feof($f)) break;
			fseek($f,7,SEEK_CUR);
		}
		fclose($f);	
		return $recordList;			
	}	
	public function setRecord($data)	//Return: Bool
	{
		if ($data==NULL || $data->recordID=='') return false;
		if (!$this->sync()) return false;
		$f=fopen($this->db,'rb+');
		flock($f,LOCK_EX);
		$temp=self::_locateRecord($f,$data->recordID);
		if ($temp>0)
		{
			fseek($f,$temp);
		}
		else
		{
			if (!self::_inc($f)) {flock($f,LOCK_UN); fclose($f); return false;}
				fseek($f,0,SEEK_END);
		}
		fwrite($f,$data->recordID.chr($data->state).chr($data->type).intToBytes($data->length,4).$data->from.str_repeat(chr(0),8-strlen($data->from)).str_repeat(chr(0),8).gmdate(TIME_FORMAT).chr(SERVER_ID).str_repeat(chr(0),6));	
		self::update($f);
		flock($f,LOCK_UN);
		fclose($f);
		return true;
	}	
	private static function genKey16()	//Return:String
	{
		$temp='';
		for($i=0;$i<16;$i++)	$temp.=rand(0,9);
		return $temp;
	}
	public function getNewRecordID()	//Return: String
	{
		if (!$this->sync()) return $tempList;
		$f=fopen($this->db,'rb');
		while(true)
		{
			$temp=self::genKey16();
			if (self::_locateRecord($f,$temp)<=0) break;
		}
		return $temp;
	}
	public function freeRecordID($recordID)	//Return: Bool
	{
		if (!$this->sync()) return $tempList;
		$f=fopen($this->db,'rb+');
		flock($f,LOCK_EX);
		$found=false;
		fseek($f,32);
		while(true)
		{
			if($recordID==fread($f,16))
			{
				fwrite($f,chr(0));
				$found=true;
				fseek($f,69,SEEK_CUR);
			}
			else
			{
				if (feof($f)) break;
				fseek($f,48,SEEK_CUR);
			}
		}
		self::update($f);
		flock($f,LOCK_UN);
		fclose($f);
		return $found;
	}
	public function doCleaning($cleanState) //Return: List of strings of cleaned ResID
	{
		if (!$cleanState) return NULL;
		if (!$this->sync()) return NULL;
		$cleaned=array();
		$f=fopen($this->db,'rb+');
		flock($f,LOCK_EX);
		fseek($f,48);
		while(true)
		{
			if (fread($f,1)==chr($cleanState))
			{
				fseek($f,-17,SEEK_CUR);
				array_push($cleaned,fread($f,16));
				fwrite($f,chr(0));
				fseek($f,47,SEEK_CUR);
			}
			else
			{
				if (feof($f)) break;
				fseek($f,63,SEEK_CUR);
			}
		}
		if (count($cleaned)>0) self::update($f);
		flock($f,LOCK_UN);
		fclose($f);
		return $cleaned;
	}
}

class groupRecDB extends recDB
{
	protected $defaultDB='/../db/rec_group.dat',$dbPrefix='WiChatPD';
	protected $Ver=1;
	
	public function getRecord($receptor) //Return: dataRecord
	{
		return parent::getRecord("0000000\0",$receptor);
	}
	public function setRecord($data)	//Return: Bool
	{
		$data->from="0000000\0";
		return parent::setRecord($data);
	}
	public function fetchRecord($groupList,$lastTime) //Return: Array of String
	{
		$tempList=array();
		if (!$this->sync()) return $tempList;
		$f=fopen($this->db,'rb');
		fseek($f,40);
		while(true)
		{
			$tempID=fread($f,8);
			$tempState=ord(fread($f,1));
			fseek($f,21,SEEK_CUR);
			$tempTime=fread($f,19);
			if (in_array($tempID,$groupList) && $tempState>0 && $tempTime>$lastTime) array_push($tempList,$tempID);
			if (feof($f)) break;
			fseek($f,14,SEEK_CUR);
		}
		fclose($f);
		return $tempList;
	}
}

class groupRecIndex extends recIndex
{
	protected $defaultDB='',$dbPrefix='WiChatPI';
	protected $Ver=1;

	public function getRecordsByTime($lastTime) //Return: Array of dataRecordIndex
	{
		$tempList=array();
		if (!$this->sync()) return $tempList;
		$f=fopen($this->db,'rb');
		fseek($f,32);
		$tempRecord=new dataRecordIndex();
		while(true)
		{
			$ID=fread($f,16);
			$state=ord(fread($f,1));
			fseek($f,21,SEEK_CUR);
			$tempTime=fread($f,19);
			if ($state>0 && $tempTime>$lastTime)
			{
				$tempRecord->recordID=$ID;
				$tempRecord->state=$state;
				$tempRecord->lastTime=$tempTime;
				fseek($f,-40,SEEK_CUR);
				$temp=fread($f,1);	$tempRecord->type=ord($temp);
				$temp=fread($f,4);	$tempRecord->length=bytesToInt($temp,4);
				$temp=fread($f,8);	$tempRecord->from=$temp;
				array_push($tempList,clone $tempRecord);
				fseek($f,27,SEEK_CUR);
			}
			if (feof($f)) break;
			fseek($f,7,SEEK_CUR);
		}
		fclose($f);
		return $tempList;
	}
	public function getFilePosByID($recordID)
	{
		if (!$this->sync()) return -1;
		$f=fopen($this->db,'rb');
		fseek($f,32);
		$pos=0;
		while(true)
		{
			$tempID=fread($f,16);
			$tempState=ord(fread($f,1));
			if ($tempState>0 && $tempID==$recordID) break;
			fread($f,1);
			$pos+=bytesToInt(fread($f,4),4);
			if (feof($f)) break;
			fseek($f,42,SEEK_CUR);
		}
		fclose($f);
		return $pos;
	}
}
?>