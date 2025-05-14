<?php
$order_no = $_REQUEST["billNo"]; //商户订单号
$tradeStatus = $_REQUEST["tradeStatus"]; //支付成功状态

if ($tradeStatus==1){
	//商户处理订单逻辑
	echo "SUCCESS";
}
?>