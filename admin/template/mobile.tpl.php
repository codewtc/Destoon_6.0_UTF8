<?php
defined('DT_ADMIN') or exit('Access Denied');
include tpl('header');
?>
<?php if(!isset($js)) { ?><div class="tt"><?php echo $mobile;?></div><?php } ?>
<table cellpadding="2" cellspacing="1" class="tb">
<tr>
<td>&nbsp;<?php echo mobile2area($mobile);?></td>
</tr>
</table>
<?php include tpl('footer');?>