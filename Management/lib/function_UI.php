<?php
/**
 * Project: NJUOPEN/Portal-BBS
 * Contributor:WTZ
 * Filename: function_UI.php
 */

function loadUI($param) // Load CSS/Javascript files
{
	global $cssList,$jsList;
	__add_to_list($cssList,'/css/stylesheet-general.css');
	switch($param)
	{
		case 'index': // Index page
			__add_to_list($cssList,'/css/stylesheet-index.css');
			break;
		case 'userinfo': // User information page
			__add_to_list($cssList,'/css/stylesheet-userinfo.css');
			break;
		default:
		
	}
}

function setSysMsg($item,$value) // Show system messages to user
{
	global $sysMsg;
	$sysMsg[$item]=$value;
}

// Resource lists used by frontend
$cssList=array();
$jsList=array();
$sysMsg=array();
?>
