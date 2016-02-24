<?php
$_SERVER['REQUEST_URI'] = '';
$_DPOST = $_POST;
$_DGET = $_GET;
require '../../../common.inc.php';
$_POST = $_DPOST;
$_GET = $_DGET;
if(!$_POST && !$_GET) exit('fail');
$bank = 'alipay';
$PAY = cache_read('pay.php');
if(!$PAY[$bank]['enable']) exit('fail');
if(!$PAY[$bank]['partnerid']) exit('fail');
if(strlen($PAY[$bank]['keycode']) < 10) exit('fail');
#log_write($_POST, 'alipay-notify-post', 1);
function log_result($word) {
	log_write($word, 'ralipay');
}
$receive_url = '';
require DT_ROOT.'/api/pay/'.$bank.'/notify.class.php';
require DT_ROOT.'/api/pay/'.$bank.'/config.inc.php';
$alipay = new alipay_notify($partner,$security_code,$sign_type,$_input_charset,$transport);
$verify_result = $alipay->notify_verify();
if($verify_result) {
	$out_trade_no = intval($out_trade_no);
	$r = $db->get_one("SELECT * FROM {$DT_PRE}finance_charge WHERE itemid='$out_trade_no'");
	if($r) {
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
				exit('success');
			} else {
				$note = '充值金额不匹配S:'.$charge_money.'R:'.$total_fee;
				$db->query("UPDATE {$DT_PRE}finance_charge SET status=1,receivetime='$DT_TIME',editor='$editor',note='$note' WHERE itemid=$charge_orderid");//支付失败
				exit('fail');
			}
		} else if($r['status'] == 1) {
			exit('fail');
		} else if($r['status'] == 2) {
			exit('fail');
		} else {
			exit('success');
		}
	} else {
		exit('fail');
	}
} 
exit('fail');
?>