<?php
defined('IN_ECJIA') or exit('No permission resources.');

class done_module implements ecjia_interface {

    public function run(ecjia_api & $api) {
		
		/**
         * bonus 0 //红包
         * how_oos 0 //缺货处理
         * integral 0 //积分
         * payment 3 //支付方式
         * postscript //订单留言
         * shipping 3 //配送方式
         * surplus 0 //余额
         * inv_type 4 //发票类型
         * inv_payee 发票抬头
         * inv_content 发票内容
         */
    	
    	EM_Api::authSession();
    	$rec_id = _POST('rec_id');
    	if (isset($_SESSION['cart_id'])) {
    		$rec_id = empty($rec_id) ? $_SESSION['cart_id'] : $rec_id;
    	}
    	$cart_id = array();
    	if (!empty($rec_id)) {
    		$cart_id = explode(',', $rec_id);
    	} 
    	
    	/* 取得购物类型 */
    	$flow_type = isset($_SESSION['flow_type']) ? intval($_SESSION['flow_type']) : CART_GENERAL_GOODS;
    	
    	/* 获取收货信息*/
    	$address_id = _POST('address_id', 0);
    	
    	$_POST['how_oos']		= isset($_POST['how_oos']) ? intval($_POST['how_oos']) : 0;
    	$_POST['card_message']	= isset($_POST['card_message']) ? htmlspecialchars($_POST['card_message']) : '';//礼品卡信息
    	$_POST['inv_type']		= !empty($_POST['inv_type']) ? $_POST['inv_type'] : '';//发票信息
    	$_POST['inv_payee']		= isset($_POST['inv_payee']) ? htmlspecialchars($_POST['inv_payee']) : '';
    	$_POST['inv_content']	= isset($_POST['inv_content']) ? htmlspecialchars($_POST['inv_content']) : '';
    	$_POST['postscript']	= isset($_POST['postscript']) ? htmlspecialchars_decode($_POST['postscript']) : '';
//     	$how_oosLang			= RC_Lang::lang("oos/$_POST[how_oos]");
    	$order = array(
    			'shipping_id'	=> intval($_POST['shipping_id']),
    			'pay_id'		=> intval($_POST['pay_id']),
    			'pack_id'		=> isset($_POST['pack']) ? intval($_POST['pack']) : 0,
    			'card_id'		=> isset($_POST['card']) ? intval($_POST['card']) : 0,
    			'card_message'	=> trim($_POST['card_message']),
    			'surplus'		=> isset($_POST['surplus']) ? floatval($_POST['surplus']) : 0.00,
    			'integral'		=> isset($_POST['integral']) ? intval($_POST['integral']) : 0,
    			'bonus_id'		=> isset($_POST['bonus']) ? intval($_POST['bonus']) : 0,
    			'need_inv'		=> empty($_POST['need_inv']) ? 0 : 1,
    			'inv_type'		=> $_POST['inv_type'],
    			'inv_payee'		=> trim($_POST['inv_payee']),
    			'inv_content'	=> $_POST['inv_content'],
    			'postscript'	=> trim($_POST['postscript']),
//     			'how_oos' => isset($how_oosLang) ? addslashes($how_oosLang) : '',
    			'need_insure'	=> isset($_POST['need_insure']) ? intval($_POST['need_insure']) : 0,
    			'user_id'		=> $_SESSION['user_id'],
    			'add_time'		=> RC_Time::gmtime(),
    			'order_status'	=> OS_UNCONFIRMED,
    			'shipping_status' => SS_UNSHIPPED,
    			'pay_status'	=> PS_UNPAYED,
//     			'agency_id' => get_agency_by_regions(array(
//     					$consignee['country'],
//     					$consignee['province'],
//     					$consignee['city'],
//     					$consignee['district']
//     			))
    			'agency_id'		=> 0,
    			'expect_shipping_time' => $_POST['expect_shipping_time'],
    	);
    	
    	$result = RC_Api::api('cart', 'flow_done', array('cart_id' => $cart_id, 'order' => $order, 'address_id' => $address_id, 'flow_type' => $flow_type, 'bonus_sn' => $_POST['bonus_sn']));
    	
    	return $result;
    }
}

// end