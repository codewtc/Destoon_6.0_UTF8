<?php
defined('DT_ADMIN') or exit('Access Denied');
include tpl('header');
?>
<?php if(!isset($js)) { ?><div class="tt"><?php echo $ip;?></div><?php } ?>
<table cellpadding="2" cellspacing="1" class="tb">
<tr>
<td>&nbsp;<?php echo ip2area($ip);?></td>
</tr>
</table>
<?php include tpl('footer');?>