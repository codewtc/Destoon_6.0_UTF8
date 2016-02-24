<?php
$_SERVER['REQUEST_URI'] = '';
require '../../../common.inc.php';
#log_write($DT_IP."\nPOST:\n".var_export($_POST, true)."\nGET:".var_export($_GET, true)."\nGLB:".var_export($GLOBALS["HTTP_RAW_POST_DATA"], true), 'wxp', 1);
function wx_exit($type = '') {
	exit($type == 'ok' ? '<xml><return_code><![CDATA[SUCCESS]]></return_code><return_msg><![CDATA[OK]]></return_msg></xml>' : '<xml><return_code><![CDATA[FAIL]]></return_code><return_msg><![CDATA['.$type.']]></return_msg></xml>');
}
function make_sign($arr, $key) {
	ksort($arr);
	$str = '';
	foreach($arr as $k=>$v) {
		if($v) $str .= $k.'='.$v.'&';
	}
	$str .= 'key='.$key;
	return strtoupper(md5($str));
}
$xml = $GLOBALS["HTTP_RAW_POST_DATA"];
$xml or wx_exit();
$bank = 'weixin';
$PAY = cache_read('pay.php');
if(!$PAY[$bank]['enable']) wx_exit();
if(strlen($PAY[$bank]['keycode']) < 7) wx_exit();
if(strpos($xml, 'result_code') !== false) {
	$x = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);
	$x = (array)$x;
	$post = array();
	foreach($x as $k=>$v) {
		$post[$k] = $v;
	}
	unset($post['sign']);
	if($post['result_code'] == 'SUCCESS' && make_sign($post, $PAY[$bank]['keycode']) == $x['sign']) {
		$itemid = intval($post['out_trade_no']);
		$total_fee = $post['total_fee']/100;
		$r = $db->get_one("SELECT * FROM {$DT_PRE}finance_charge WHERE itemid='$itemid'");
		if($r) {
			if($r['status'] == 3) wx_exit('ok');
			if($r['status'] == 0) {
				$charge_orderid = $r['itemid'];
				$charge_money = $r['amount'] + $r['fee'];
				$charge_amount = $r['amount'];
				$editor = 'N'.$bank;
				if($total_fee == $charge_money) {
					$db->query("UPDATE {$DT_PRE}finance_charge SET status=3,money=$charge_money,receivetime='$DT_TIME',editor='$editor' WHERE itemid=$charge_orderid");
					require DT_ROOT.'/include/module.func.php';
					money_add($r['username'], $r['amount']);
					money_record($r['username'], $r['amount'], $PAY[$bank]['name'], 'system', '在线充值', '流水号:'.$charge_orderid);
					$MOD = cache_read('module-2.php');
					if($MOD['credit_charge'] > 0) {
						$credit = intval($r['amount']*$MOD['credit_charge']);
						if($credit > 0) {
							credit_add($r['username'], $credit);
							credit_record($r['username'], $credit, 'system', '充值奖励', '充值'.$r['amount'].$DT['money_unit']);
						}
					}
					wx_exit('ok');
				} else {
					$note = '充值金额不匹配S:'.$charge_money.'R:'.$total_fee;
					$db->query("UPDATE {$DT_PRE}finance_charge SET status=1,receivetime='$DT_TIME',editor='$editor',note='$note' WHERE itemid=$charge_orderid");//支付失败
				}
			}
		}
	}
}
wx_exit();