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
    	
    	if (empty($rec_id)) {
    		return new ecjia_error('not_found_goods', '请选择您所需要购买的商品！');
    	} else {
    		$cart_id = explode(',', $rec_id);
    	}
    	
//     	RC_Loader::load_app_func('cart','cart');
//     	RC_Loader::load_app_func('order','orders');
    	
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
    	);
    	
    	$result = RC_Api::api('cart', 'flow_done', array('cart_id' => $cart_id, 'order' => $order, 'address_id' => $address_id, 'flow_type' => $flow_type, 'bonus_sn' => $_POST['bonus_sn']));
    	
    	return $result;
    	
//     	if ($address_id == 0) {
//     		$consignee = get_consignee($_SESSION['user_id']);
//     	} else {
//     		$db_user_address = RC_Loader::load_app_model('user_address_model','user');
//     		$consignee = $db_user_address->find(array('address_id' => $address_id, 'user_id' => $_SESSION['user_id']));
//     	}
//     	$consignee['tel'] = empty($consignee['tel']) ? $consignee['mobile'] : $consignee['tel'];
    	
//     	/* 检查收货人信息是否完整 */
//     	if (! check_consignee_info($consignee, $flow_type)) {
//     		/* 如果不完整则转向到收货人信息填写界面 */
//     		EM_Api::outPut(10001);
//     	}
    	
        
        
//         //获取所需购买购物车id  will.chen
        
// //         $cart_id = array(480, 481);
        
        
//         /* 检查购物车中是否有商品 */
// 		$db_cart = RC_Loader::load_app_model('cart_model', 'cart');
		
// 		$cart_where = array('parent_id' => 0 , 'is_gift' => 0 , 'rec_type' => $flow_type);
// 		if (!empty($cart_id)) {
// 			$cart_where = array_merge($cart_where, array('rec_id' => $cart_id));
// 		}
// 		if ($_SESSION['user_id']) {
// 			$cart_where = array_merge($cart_where, array('user_id' => $_SESSION['user_id']));
// 			$count = $db_cart->where($cart_where)->count();
// 		} else {
// 			$cart_where = array_merge($cart_where, array('session_id' => SESS_ID));
// 			$count = $db_cart->where($cart_where)->count();
// 		}
        
//         if ($count == 0) {
//             EM_Api::outPut(10002);
//         }
//         /* 检查商品库存 */
//         /* 如果使用库存，且下订单时减库存，则减少库存 */
//         if (ecjia::config('use_storage') == '1' && ecjia::config('stock_dec_time') == SDT_PLACE) {
        	
// 			$cart_goods_stock = get_cart_goods($cart_id);
//             $_cart_goods_stock = array();
//             foreach ($cart_goods_stock['goods_list'] as $value) {
//                 $_cart_goods_stock[$value['rec_id']] = $value['goods_number'];
//             }
//             $result = flow_cart_stock($_cart_goods_stock);
//             if (is_ecjia_error($result)) {
//             	EM_Api::outPut($result);
//             }
//             unset($cart_goods_stock, $_cart_goods_stock);
//         }
        
//         /* 检查用户是否已经登录 如果用户已经登录了则检查是否有默认的收货地址 如果没有登录则跳转到登录和注册页面  */
//         if (empty($_SESSION['direct_shopping']) && $_SESSION['user_id'] == 0) {
//             /* 用户没有登录且没有选定匿名购物，转向到登录页面 */
//             EM_Api::outPut(100);
//         }
        
    	
        
//         $_POST['how_oos'] = isset($_POST['how_oos']) ? intval($_POST['how_oos']) : 0;
//         $_POST['card_message'] = isset($_POST['card_message']) ? htmlspecialchars($_POST['card_message']) : '';
//         $_POST['inv_type'] = ! empty($_POST['inv_type']) ? $_POST['inv_type'] : '';
//         $_POST['inv_payee'] = isset($_POST['inv_payee']) ? htmlspecialchars($_POST['inv_payee']) : '';
//         $_POST['inv_content'] = isset($_POST['inv_content']) ? htmlspecialchars($_POST['inv_content']) : '';
//         $_POST['postscript'] = isset($_POST['postscript']) ? htmlspecialchars_decode($_POST['postscript']) : '';
//         $how_oosLang = RC_Lang::lang("oos/$_POST[how_oos]");
//         $order = array(
//             'shipping_id' => intval($_POST['shipping_id']),
//             'pay_id' => intval($_POST['pay_id']),
//             'pack_id' => isset($_POST['pack']) ? intval($_POST['pack']) : 0,
//             'card_id' => isset($_POST['card']) ? intval($_POST['card']) : 0,
//             'card_message' => trim($_POST['card_message']),
//             'surplus' => isset($_POST['surplus']) ? floatval($_POST['surplus']) : 0.00,
//             'integral' => isset($_POST['integral']) ? intval($_POST['integral']) : 0,
//             'bonus_id' => isset($_POST['bonus']) ? intval($_POST['bonus']) : 0,
//             'need_inv' => empty($_POST['need_inv']) ? 0 : 1,
//             'inv_type' => $_POST['inv_type'],
//             'inv_payee' => trim($_POST['inv_payee']),
//             'inv_content' => $_POST['inv_content'],
//             'postscript' => trim($_POST['postscript']),
//             'how_oos' => isset($how_oosLang) ? addslashes($how_oosLang) : '',
//             'need_insure' => isset($_POST['need_insure']) ? intval($_POST['need_insure']) : 0,
//             'user_id' => $_SESSION['user_id'],
//             'add_time' => RC_Time::gmtime(),
//             'order_status' => OS_UNCONFIRMED,
//             'shipping_status' => SS_UNSHIPPED,
//             'pay_status' => PS_UNPAYED,
//             'agency_id' => get_agency_by_regions(array(
//                 $consignee['country'],
//                 $consignee['province'],
//                 $consignee['city'],
//                 $consignee['district']
//             ))
//         );
        
//         /* 扩展信息 */
//         if (isset($_SESSION['flow_type']) && intval($_SESSION['flow_type']) != CART_GENERAL_GOODS) {
//             $order['extension_code'] = $_SESSION['extension_code'];
//             $order['extension_id'] = $_SESSION['extension_id'];
//         } else {
//             $order['extension_code'] = '';
//             $order['extension_id'] = 0;
//         }
        
//         /* 检查积分余额是否合法 */
//         $user_id = $_SESSION['user_id'];
//         if ($user_id > 0) {
//             $user_info = user_info($user_id);
            
//             // 查询用户有多少积分
//             $flow_points = flow_available_points($cart_id); // 该订单允许使用的积分
//             $user_points = $user_info['pay_points']; // 用户的积分总数
            
//             $order['integral'] = min($order['integral'], $user_points, $flow_points);
//             if ($order['integral'] < 0) {
//                 $order['integral'] = 0;
//             }
//         } else {
//             $order['surplus'] = 0;
//             $order['integral'] = 0;
//         }
//         RC_Loader::load_app_func('bonus','bonus');
//         /* 检查红包是否存在 */
//         if ($order['bonus_id'] > 0) {
//             $bonus = bonus_info($order['bonus_id']);
//             if (empty($bonus) || $bonus['user_id'] != $user_id || $bonus['order_id'] > 0 || $bonus['min_goods_amount'] > cart_amount(true, $flow_type, $cart_id)) {
//                 $order['bonus_id'] = 0;
//             }
//         } elseif (isset($_POST['bonus_sn'])) {
//             $bonus_sn = trim($_POST['bonus_sn']);
//             $bonus = bonus_info(0, $bonus_sn);
//             $now = RC_Time::gmtime();
//             if (empty($bonus) || $bonus['user_id'] > 0 || $bonus['order_id'] > 0 || $bonus['min_goods_amount'] > cart_amount(true, $flow_type, $cart_id) || $now > $bonus['use_end_date']) {} else {
//                 if ($user_id > 0) {
// 					$db_user_bonus = RC_Loader::load_app_model('user_bonus_model','bonus');
// 					$db_user_bonus->where(array('bonus_id' => $bonus['bonus_id']))->update(array('user_id' => $user_id));
//      			}
//                 $order['bonus_id'] = $bonus['bonus_id'];
//                 $order['bonus_sn'] = $bonus_sn;
//             }
//         }
        
//         /* 订单中的商品 */
//         $cart_goods = cart_goods($flow_type, $cart_id);
//         if (empty($cart_goods)) {
//             EM_Api::outPut(10002);
//         }
        
//         /* 检查商品总额是否达到最低限购金额 */
//         if ($flow_type == CART_GENERAL_GOODS && cart_amount(true, CART_GENERAL_GOODS, $cart_id) < ecjia::config('min_goods_amount')) {
//             EM_Api::outPut(10003);
//         }
        
//         /* 收货人信息 */
//         foreach ($consignee as $key => $value) {
//             $order[$key] = addslashes($value);
//         }
        
//         /* 判断是不是实体商品 */
//         foreach ($cart_goods as $val) {
//             /* 统计实体商品的个数 */
//             if ($val['is_real']) {
//                 $is_real_good = 1;
//             }
//         }
//         if (isset($is_real_good)) {
//         	$shipping_method = RC_Loader::load_app_class('shipping_method', 'shipping');
//         	$data = $shipping_method->shipping_info($order['shipping_id']);
//             if (empty($data['shipping_id'])) {
//                 EM_Api::outPut(10001);
//             }
//         }
//         /* 订单中的总额 */
//         $total = order_fee($order, $cart_goods, $consignee, $cart_id);
//         $order['bonus'] = $total['bonus'];
//         $order['goods_amount'] = $total['goods_price'];
//         $order['discount'] = $total['discount'];
//         $order['surplus'] = $total['surplus'];
//         $order['tax'] = $total['tax'];
        
//         // 购物车中的商品能享受红包支付的总额
//         $discount_amout = compute_discount_amount($cart_id);
//         // 红包和积分最多能支付的金额为商品总额
//         $temp_amout = $order['goods_amount'] - $discount_amout;
//         if ($temp_amout <= 0) {
//             $order['bonus_id'] = 0;
//         }
        
//         /* 配送方式 */
//         if ($order['shipping_id'] > 0) {
//             $shipping_method = RC_Loader::load_app_class('shipping_method', 'shipping');
//             $shipping = $shipping_method->shipping_info($order['shipping_id']);
//             $order['shipping_name'] = addslashes($shipping['shipping_name']);
//         }
//         $order['shipping_fee'] = $total['shipping_fee'];
//         $order['insure_fee'] = $total['shipping_insure'];
        
//         $payment_method = RC_Loader::load_app_class('payment_method','payment');
//         /* 支付方式 */
//         if ($order['pay_id'] > 0) {
        	
//             $payment = $payment_method->payment_info_by_id($order['pay_id']);
//             $order['pay_name'] = addslashes($payment['pay_name']);
//         	//如果是货到付款，状态设置为已确认。
//  			if($payment['pay_code'] == 'pay_cod') { $order['order_status'] = 1; }
//         }
//         $order['pay_fee'] = $total['pay_fee'];
//         $order['cod_fee'] = $total['cod_fee'];

//         $order['pack_fee'] = $total['pack_fee'];

//         $order['card_fee'] = $total['card_fee'];
        
//         $order['order_amount'] = number_format($total['amount'], 2, '.', '');
        
// //         /* 如果全部使用余额支付，检查余额是否足够 */
// //         if (($payment['pay_code'] == 'pay_balance' ) && $order['order_amount'] > 0) {
// //         	// 余额支付里如果输入了一个金额
// //             if ($order['surplus'] > 0) {
// //                 $order['order_amount'] = $order['order_amount'] + $order['surplus'];
// //                 $order['surplus'] = 0;
// //             }
// //             if ($order['order_amount'] > ($user_info['user_money'] + $user_info['credit_line'])) {
// //                 EM_Api::outPut(10003);
// //             } else {
// //                 $order['surplus'] = $order['order_amount'];
// //                 $order['order_amount'] = 0;
// //             }
// //         }
        
//         /* 如果订单金额为0（使用余额或积分或红包支付），修改订单状态为已确认、已付款 */
//         if ($order['order_amount'] <= 0) {
//             $order['order_status'] = OS_CONFIRMED;
//             $order['confirm_time'] = RC_Time::gmtime();
//             $order['pay_status'] = PS_PAYED;
//             $order['pay_time'] = RC_Time::gmtime();
//             $order['order_amount'] = 0;
//         }
        
//         $order['integral_money'] = $total['integral_money'];
//         $order['integral'] = $total['integral'];
        
//         if ($order['extension_code'] == 'exchange_goods') {
//             $order['integral_money'] = 0;
//             $order['integral'] = $total['exchange_integral'];
//         }
        
//         $order['from_ad'] = ! empty($_SESSION['from_ad']) ? $_SESSION['from_ad'] : '0';
//         $order['referer'] = 'mobile'; // !empty($_SESSION['referer']) ? addslashes($_SESSION['referer']) : '';
        
//         /* 记录扩展信息 */
//         if ($flow_type != CART_GENERAL_GOODS) {
//             $order['extension_code'] = $_SESSION['extension_code'];
//             $order['extension_id'] = $_SESSION['extension_id'];
//         }
        
// //         $affiliate = unserialize(ecjia::config('affiliate'));
// //         if (isset($affiliate['on']) && $affiliate['on'] == 1 && $affiliate['config']['separate_by'] == 1) {
// //             // 推荐订单分成
// //             $parent_id = get_affiliate();
// //             if ($user_id == $parent_id) {
// //                 $parent_id = 0;
// //             }
// //         } elseif (isset($affiliate['on']) && $affiliate['on'] == 1 && $affiliate['config']['separate_by'] == 0) {
// //             // 推荐注册分成
// //             $parent_id = 0;
// //         } else {
// //             // 分成功能关闭
// //             $parent_id = 0;
// //         }
//         $parent_id = 0;
//         $order['parent_id'] = $parent_id;
        
//         /* 插入订单表 */
//         $order['order_sn'] = get_order_sn(); // 获取新订单号
//         $db_order_info = RC_Loader::load_app_model('order_info_model','orders');
//         $new_order_id = $db_order_info->insert($order);
        
//         $order['order_id'] = $new_order_id;
        
//         /* 插入订单商品 */
// 		$db_order_goods = RC_Loader::load_app_model('order_goods_model','orders');
//         $db_goods_activity = RC_Loader::load_app_model('goods_activity_model','goods');
        
        
//         $field = 'ru_id, goods_id, goods_name, goods_sn, product_id, goods_number, market_price,goods_price, goods_attr, is_real, extension_code, parent_id, is_gift, goods_attr_id';
//         $cart_w = array('rec_type' => $flow_type);
//         if (!empty($cart_id)) {
//         	$cart_w = array_merge($cart_w, array('rec_id' => $cart_id));
//         }
//         if ($_SESSION['user_id']) {
//         	$cart_w = array_merge($cart_w, array('user_id' =>$_SESSION['user_id']));
// 			$data_row = $db_cart->field($field)->where($cart_w)->select();
//         } else {
//         	$cart_w = array_merge($cart_w, array('session_id' =>SESS_ID));
//         	$data_row = $db_cart->field($field)->where($cart_w)->select();
//         }
        
//         if (!empty($data_row)) {
//         	$area_id = $consignee['province'];
//         	//多店铺开启库存管理以及地区后才会去判断
//         	if ( $area_id > 0 ) {
//         		$warehouse_db = RC_Loader::load_app_model('warehouse_model', 'warehouse');
//         		$warehouse = $warehouse_db->where(array('regionId' => $area_id))->find();
//         		$warehouse_id = $warehouse['parent_id'];
//         	}
//         	foreach ($data_row as $row) {
//         		$arr = array(
//         				'order_id' => $new_order_id,
//         				'goods_id' => $row['goods_id'],
//         				'goods_name' => $row['goods_name'],
//         				'goods_sn' => $row['goods_sn'],
//         				'product_id' => $row['product_id'],
//         				'goods_number' => $row['goods_number'],
//         				'market_price' => $row['market_price'],
//         				'goods_price' => $row['goods_price'],
//         				'goods_attr' => $row['goods_attr'],
//         				'is_real' => $row['is_real'],
//         				'extension_code' => $row['extension_code'],
//         				'parent_id' => $row['parent_id'],
//         				'is_gift' => $row['is_gift'],
//         				'goods_attr_id' => $row['goods_attr_id'],
//         				'ru_id'		=> $row['ru_id'],
//         				'area_id'	=> $area_id,
//         				'warehouse_id'	=> $warehouse_id,
//         		);
//         		$db_order_goods->insert($arr);
//         	}
//         }
//         /* 修改拍卖活动状态 */
//         if ($order['extension_code'] == 'auction') {
// 			$db_goods_activity->where(array('act_id' => $order['extension_id']))->update(array('is_finished' => 2));
//         }
        
//         /* 处理积分、红包 */
// 		if ($order['user_id'] > 0 && $order['integral'] > 0) {
//         	$options = array(
//         			'user_id'=>$order['user_id'],
//         			'pay_points'=> $order['integral'] * (- 1),
//         			'change_desc'=>sprintf(RC_Lang::lang('pay_order'), $order['order_sn'])
//         	);
//         	$result = RC_Api::api('user', 'account_change_log',$options);
//         	if (is_ecjia_error($result)) {
//         		EM_Api::outPut(8);
//         	}
//         }
//         if ($order['bonus_id'] > 0 && $temp_amout > 0) {
//             use_bonus($order['bonus_id'], $new_order_id);
//         }
        
//         /* 如果使用库存，且下订单时减库存，则减少库存 */
//         if (ecjia::config('use_storage') == '1' && ecjia::config('stock_dec_time') == SDT_PLACE) {
//         	/* 库存不足删除已生成的订单（并发处理） will.chen*/
//             $result = change_order_goods_storage($order['order_id'], true, SDT_PLACE);
//             if (is_ecjia_error($result)) {
//             	$db_order_info->where(array('order_id' => $order['order_id']))->delete();
//             	$db_order_goods->where(array('order_id' => $order['order_id']))->delete();
//             	EM_Api::outPut(10008);
//             }
//         }
        
//         /* 给商家发邮件 */
// 		/* 增加是否给客服发送邮件选项 */
// 		if (ecjia::config('send_service_email') && ecjia::config('service_email') != '') {
// 			$tpl_name = 'remind_of_new_orders';
//             $tpl   = RC_Api::api('mail', 'mail_template', $tpl_name);
            
//             ecjia::$view_object->assign('order', $order);
//             ecjia::$view_object->assign('goods_list', $cart_goods);
//             ecjia::$view_object->assign('shop_name', ecjia::config('shop_name'));
//             ecjia::$view_object->assign('send_date', RC_Time::local_date(ecjia::config('time_format'), $order['add_time']));
            
//             $content = ecjia::$controller->fetch_string($tpl['template_content']);
//             RC_Mail::send_mail(ecjia::config('shop_name'), ecjia::config('service_email'), $tpl['template_subject'], $content, $tpl['is_html']);
//         }

//     	$result = ecjia_app::validate_application('sms');
// 	    if (!is_ecjia_error($result)) {
// 	        /* 如果需要，发短信 */
// 			if (ecjia::config('sms_order_placed')== '1' && ecjia::config('sms_shop_mobile') != '') {
// 				//发送短信
// 				$tpl_name = 'order_placed_sms';
// 				$tpl   = RC_Api::api('sms', 'sms_template', $tpl_name);
	       	
// 		       	ecjia::$view_object->assign('consignee', $order['consignee']);
// 		       	ecjia::$view_object->assign('mobile', $order['mobile']);
// 		       	ecjia::$view_object->assign('order', $order);
// 		       	$content = ecjia::$controller->fetch_string($tpl['template_content']);
// 		       	$msg = $order['pay_status'] == PS_UNPAYED ? $content : $content.__('已付款');
// 		       	$options = array(
// 		       			'mobile' 		=> ecjia::config('sms_shop_mobile'),
// 		       			'msg'			=> $msg,
// 		       			'template_id' 	=> $tpl['template_id'],
// 	       		);
// 	       		$response = RC_Api::api('sms', 'sms_send', $options);
// 			}
			
// 			if ($payment['pay_code'] == 'pay_cod') {
// 				/* 收货验证短信  */
// // 				if (ecjia::config('sms_receipt_verification') == '1' && ecjia::config('sms_shop_mobile') != '') {
// // 					$db_term_meta = RC_Loader::load_model('term_meta_model');
// // 					$meta_where = array(
// // 							'object_type'	=> 'ecjia.order',
// // 							'object_group'	=> 'order',
// // 							'meta_key'		=> 'receipt_verification',
// // 					);
// // 					$max_code = $db_term_meta->where($meta_where)->max('meta_value');
// // 					$max_code = $max_code ? ceil($max_code/10000) : 1000000;
// // 					$code = $max_code . str_pad(mt_rand(0, 9999), 4, '0', STR_PAD_LEFT);
					
// // // 					$code = rand(100000, 999999);
// // 					$tpl_name = 'sms_receipt_verification';
// // 					$tpl   = RC_Api::api('sms', 'sms_template', $tpl_name);
// // 					if (!empty($tpl)) {
// // 						ecjia::$view_object->assign('order_sn', $order['order_sn']);
// // 						ecjia::$view_object->assign('user_name', $order['consignee']);
// // 						ecjia::$view_object->assign('code', $code);
						
// // 						$content = ecjia::$controller->fetch_string($tpl['template_content']);
						
// // 						$options = array(
// // 								'mobile' 		=> $order['mobile'],
// // 								'msg'			=> $content,
// // 								'template_id' 	=> $tpl['template_id'],
// // 						);
// // 						$response = RC_Api::api('sms', 'sms_send', $options);
// // 						$meta_data = array(
// // 								'object_type'	=> 'ecjia.order',
// // 								'object_group'	=> 'order',
// // 								'object_id'		=> $order['order_id'],
// // 								'meta_key'		=> 'receipt_verification',
// // 								'meta_value'	=> $code,
// // 						);
// // 						$db_term_meta->insert($meta_data);
// // 					}
// // 				}
// 			}
// 	    }
	    
//         /* 如果订单金额为0 处理虚拟卡 */
//         if ($order['order_amount'] <= 0) {
//         	$cart_w = array('is_real' => 0, 'extension_code' => 'virtual_card', 'rec_type' => $flow_type);
//         	if (!empty($cart_id)) {
//         		$cart_w = array_merge($cart_w, array('rec_id' => $cart_id));
//         	}
// 			if ($_SESSION['user_id']) {
// 				$cart_w = array_merge($cart_w, array('user_id' => $_SESSION['user_id']));
//             	$res = $db_cart->field('goods_id, goods_name, goods_number AS num')->where($cart_w)->select();
//             } else {
//             	$cart_w = array_merge($cart_w, array('session_id' => SESS_ID));
//             	$res = $db_cart->field('goods_id, goods_name, goods_number AS num')->where($cart_w)->select();
//             }
//             $virtual_goods = array();
//             foreach ($res as $row) {
//                 $virtual_goods['virtual_card'][] = array(
//                     'goods_id' => $row['goods_id'],
//                     'goods_name' => $row['goods_name'],
//                     'num' => $row['num']
//                 );
//             }
            
//             if ($virtual_goods and $flow_type != CART_GROUP_BUY_GOODS) {
//                 /* 虚拟卡发货 */
//                 if (virtual_goods_ship($virtual_goods, $msg, $order['order_sn'], true)) {
//                     /* 如果没有实体商品，修改发货状态，送积分和红包 */
//                     $count = $db_order_goods->where(array('order_id' => $order['order_id'] , 'is_real' => 1))->count();
//                		if ($count <= 0) {
//                     /* 修改订单状态 */
//                         update_order($order['order_id'], array(
//                             'shipping_status' => SS_SHIPPED,
//                             'shipping_time' => RC_Time::gmtime()
//                         ));
                        
//                         /* 如果订单用户不为空，计算积分，并发给用户；发红包 */
//                         if ($order['user_id'] > 0) {
//                             /* 取得用户信息 */
//                             $user = user_info($order['user_id']);
//                             /* 计算并发放积分 */
//                             $integral = integral_to_give($order);
//                             $options = array(
//                             		'user_id' =>$order['user_id'],
//                             		'rank_points' => intval($integral['rank_points']),
//                             		'pay_points' => intval($integral['custom_points']),
//                             		'change_desc' =>sprintf(RC_Lang::lang('order_gift_integral'), $order['order_sn'])
//                             );
//                             $result = RC_Api::api('user', 'account_change_log',$options);
//                             if (is_ecjia_error($result)) {
//                             	EM_Api::outPut(8);
//                             }
//                             /* 发放红包 */
//                             send_order_bonus($order['order_id']);
//                         }
//                     }
//                 }
//             }
//         }
        
//         /* 清空购物车 */
//         clear_cart($flow_type, $cart_id);

//         /* 插入支付日志 */
//         $order['log_id'] = $payment_method->insert_pay_log($new_order_id, $order['order_amount'], PAY_ORDER);
        
//         $payment_info = $payment_method->payment_info_by_id($order['pay_id']);
   
//         if (! empty($order['shipping_name'])) {
//             $order['shipping_name'] = trim(stripcslashes($order['shipping_name']));
//         }
        
//         /* 订单信息 */
//         unset($_SESSION['flow_consignee']); // 清除session中保存的收货人信息
//         unset($_SESSION['flow_order']);
//         unset($_SESSION['direct_shopping']);
        
//         $subject = $cart_goods[0]['goods_name'] . '等' . count($cart_goods) . '种商品';
//         $out = array(
//             'order_sn' => $order['order_sn'],
//             'order_id' => $order['order_id'],
//             'order_info' => array(
//                 'pay_code' => $payment_info['pay_code'],
//                 'order_amount' => $order['order_amount'],
//                 'order_id' => $order['order_id'],
//                 'subject' => $subject,
//                 'desc' => $subject,
//                 'order_sn' => $order['order_sn']
//             )
//         );
        
//         //订单分子订单 start
//         $order_id = $order['order_id'];
//         $row = get_main_order_info($order_id);
//         $order_info = get_main_order_info($order_id, 1);
        
//         $ru_id = explode(",", $order_info['all_ruId']['ru_id']);

//         if(count($ru_id) > 1){
//         	get_insert_order_goods_single($order_info, $row, $order_id);
//         } else {
//         	if ($ru_id['0'] > 0) {
//         		if (ecjia::config('push_order_placed') == '1' && ecjia::config('push_order_placed_apps', ecjia::CONFIG_EXISTS)) {
//         			$admin_user_db = RC_Loader::load_model('admin_user_model');
//         			$admin_user = $admin_user_db->where(array('ru_id' => $ru_id['0']))->find();
//         			if (!empty($admin_user)) {
//         				$tpl_name = 'order_placed_sms';
//         				$tpl   = RC_Api::api('push', 'push_template', $tpl_name);
//         				if (!empty($tpl)) {
// 							ecjia::$view_object->assign('order', $order);
//         					$content = ecjia::$controller->fetch_string($tpl['template_content']);
//         					$msg = $content;
//         					$options = array(
//         							'admin_id'		=> $admin_user['user_id'],
//         							'msg'			=> $msg,
//         							'template_id' 	=> $tpl['template_id'],
//         					);
//         					$response = RC_Api::api('push', 'push_send', $options);
//         				}
//         			}
//         		}
//         	}
//         }
        
//         EM_Api::outPut($out);
	
    }
}

// end