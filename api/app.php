<?php
/*
	[Destoon B2B System] Copyright (c) 2008-2015 www.destoon.com
	This is NOT a freeware, use is subject to license.txt
*/
$_COOKIE = array();
require '../common.inc.php';
$v = isset($_GET['v']) ? $_GET['v'] : '';
$url = $EXT['mobile_url'];
if($v == 'i') {
	if(preg_match("/^([0-9]{1,})@([a-z0-9]{16,})$/i", $EXT['mobile_ios'])) {
		$t = explode('@', $EXT['mobile_ios']);
		dheader('http://app.destoon.com/get.php?o=ios&u='.$t[0].'&k='.encrypt($url, $t[1]));
	}
} else if($v == 'a') {
	if(preg_match("/^([0-9]{1,})@([a-z0-9]{16,})$/i", $EXT['mobile_adr'])) {
		$t = explode('@', $EXT['mobile_adr']);
		dheader('http://app.destoon.com/get.php?o=adr&u='.$t[0].'&k='.encrypt($url, $t[1]));
	}
}
dheader($url);
?>