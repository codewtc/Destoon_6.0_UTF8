<?php
defined('IN_DESTOON') or exit('Access Denied');
dhttp(403, $DT_BOT);
$head_title = lang('message->without_permission');
exit(include template('noright', 'message'));
?>