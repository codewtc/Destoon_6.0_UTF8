<?php
require '../../../common.inc.php';
require 'init.inc.php';
dheader(WX_CONNECT_URL.'?appid='.WX_ID.'&redirect_uri='.urlencode(WX_CALLBACK).'&response_type=code&scope=snsapi_login#wechat_redirect');
?>