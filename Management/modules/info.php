<?php

function getUserInfo($params)
{
    loadUI('userinfo');
    if (!isset($_SESSION['Name'])) return;
    global $usrInfoList;
    $usrInfoList['Name']=$_SESSION['Name'];
}

function updateUserInfo($params)
{
    if (!isset($_SESSION['Name'])) return;
    
    $usrInfoList=array();
    $usrInfoList['Name']=$_SESSION['Name'];
    $usrInfoList['Code']=WICHAT_MANAGE_ADMIN_KEY_HASH;

    if (isset($params['INFO_ACTION']))
    {
        $info_action = $params['INFO_ACTION'];
        switch($info_action)
        {
            case 'change_all_info':
                $newPW = $params['newPW'];
                $checkPW = $params['checkPW'];
                $curPW = hash_hmac("sha256",$params['curPW'],WICHAT_MANAGE_KEY_SALT);
            
                if ($params['curPW'] == '')
                {
                    setSysMsg('result','Please input the old password!');
                    break;
                }
                
                if ($curPW != $usrInfoList['Code'])
                {
                    setSysMsg('result','Old password is invalid.');
                    break;
                }

                if ($newPW != $checkPW)
                {
                    setSysMsg('result','New password mismatch!');
                    break;
                }

                if ($newPW != null)
                {
                    $usrInfoList['Code'] = hash_hmac("sha256",$newPW,WICHAT_MANAGE_KEY_SALT);
                    setSysMsg('result','Password changed successfully.');
                }
            default:;
        }
    }
    loadUI('userinfo');
}
?>
