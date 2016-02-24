<?php
require '../common.inc.php';
if(strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') === false) exit('Not IN WeiXin');
function is_openid($openid) {
	return preg_match("/^[0-9a-zA-Z_]{10,}$/", $openid);
}
if($action == 'login') {
	$openid = get_cookie('weixin_openid');
	if($openid) $openid = decrypt($openid, DT_KEY.'WXID');
	if(is_openid($openid)) {
		$r = $db->get_one("SELECT username FROM {$DT_PRE}weixin_user WHERE openid='$openid'");
		if($r && $r['username']) {
			include load('member.lang');
			$MOD = cache_read('module-2.php');
			include DT_ROOT.'/include/module.func.php';
			include DT_ROOT.'/module/member/member.class.php';
			$do = new member;
			$user = $do->login($r['username'], '', 0, true);
			set_cookie('weixin_openid', '');
		}
		$url = get_cookie('weixin_url');
		dheader($url ? $url : 'my.php');
	}
} else if($action == 'bind') {
	$openid = get_cookie('weixin_openid');
	if($openid) $openid = decrypt($openid, DT_KEY.'WXID');
	if($_userid && is_openid($openid)) {
		$r = $db->get_one("SELECT itemid FROM {$DT_PRE}weixin_user WHERE username='$_username'");
		if(!$r) {
			$r = $db->get_one("SELECT username FROM {$DT_PRE}weixin_user WHERE openid='$openid'");
			if($r && !$r['username']) {
				$db->query("UPDATE {$DT_PRE}weixin_user SET username='$_username' WHERE openid='$openid'");
				set_cookie('weixin_openid', '');
			}
		}
	}
	$url = get_cookie('weixin_url');
	dheader($url ? $url : 'my.php');
} else if($action == 'member') {
	isset($auth) or $auth = '';
	if($auth) {
		$openid = decrypt($auth, DT_KEY.'WXID');
		if(is_openid($openid)) {
			set_cookie('weixin_openid', $auth);
			set_cookie('weixin_url', 'my.php');
			dheader('weixin.php?action=login&reload='.$DT_TIME);
		}
	}
} else if($action == 'callback') {
	if($code) {
		include DT_ROOT.'/api/weixin/config.inc.php';
		$url = 'https://api.weixin.qq.com/sns/oauth2/access_token?appid='.WX_APPID.'&secret='.WX_APPSECRET.'&code='.$code.'&grant_type=authorization_code';
		$rec = dcurl($url);
		$arr = json_decode($rec, true);
		if($arr['openid']) {
			$openid = $arr['openid'];
			set_cookie('weixin_openid', encrypt($openid, DT_KEY.'WXID'));
			dheader('weixin.php?action=login&reload='.$DT_TIME);
		}
	}
} else {
	isset($url) or $url = 'my.php';
	if($moduleid > 2) $url = mobileurl($moduleid);
	if($_userid) dheader($url);
	set_cookie('weixin_url', $url);
	if(get_cookie('weixin_openid')) dheader('weixin.php?action=login&reload='.$DT_TIME);
	include DT_ROOT.'/api/weixin/config.inc.php';
	dheader('https://open.weixin.qq.com/connect/oauth2/authorize?appid='.WX_APPID.'&redirect_uri='.urlencode($EXT['mobile_url'].'weixin.php?action=callback').'&response_type=code&scope=snsapi_base&state=1#wechat_redirect');
}
dheader('index.php?reload='.$DT_TIME);
?>