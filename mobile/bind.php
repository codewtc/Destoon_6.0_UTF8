<?php
require 'common.inc.php';
if(!$_userid) {
	$auth = decrypt(get_cookie('bind'), DT_KEY.'BIND');
	if(strpos($auth, '|') !== false) {
		$t = explode('|', $auth);
		$itemid = intval($t[0]);
		$U = $db->get_one("SELECT * FROM {$DT_PRE}oauth WHERE itemid=$itemid");
		if($U && $U['site'] = $t[1]) {
			$OAUTH = cache_read('oauth.php');
			$head_title = $L['bind_title'].$DT['seo_delimiter'].$head_title;
			$foot = 'my';
			include template('bind', 'mobile');
			if(DT_CHARSET != 'UTF-8') toutf8();
			exit;
		}
	}
}
dheader('my.php?reload='.$DT_TIME);
?>