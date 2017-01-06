<?php
defined('IN_ECJIA') or exit('No permission resources.');

/**
 * 购物流检查订单
 * @author royalwang
 */
class checkOrder_module extends api_front implements api_interface {
    public function handleRequest(\Royalcms\Component\HttpKernel\Request $request) {

    	$this->authSession();
    	if ($_SESSION['user_id'] <= 0) {
    		return new ecjia_error(100, 'Invalid session');
    	}

    	$address_id = $this->requestData('address_id', 0);
		$rec_id		= $this->requestData('rec_id');

		if (empty($address_id) || empty($rec_id)) {
		    return new ecjia_error( 'invalid_parameter', RC_Lang::get ('system::system.invalid_parameter'));
		}
		$cart_id = array();
		if (!empty($rec_id)) {
			$cart_id = explode(',', $rec_id);
		}
		RC_Loader::load_app_class('cart', 'cart', false);

		/* 取得购物类型 */
		$flow_type = isset($_SESSION['flow_type']) ? intval($_SESSION['flow_type']) : CART_GENERAL_GOODS;

		/* 团购标志 */
		if ($flow_type == CART_GROUP_BUY_GOODS) {
			$is_group_buy = 1;
		} elseif ($flow_type == CART_EXCHANGE_GOODS) {
			/* 积分兑换商品 */
			$is_exchange_goods = 1;
		} else {
			//正常购物流程  清空其他购物流程情况
			$_SESSION['flow_order']['extension_code'] = '';
		}

		/* 获取用户收货地址*/
		if ($address_id > 0) {
			$consignee = RC_Model::model('user/user_address_model')->find(array('address_id' => $address_id, 'user_id' => $_SESSION['user_id']));
			$_SESSION['address_id'] = $address_id;
		} else {
			if (isset($_SESSION['address_id'])) {
				$consignee = RC_Model::model('user/user_address_model')->find(array('address_id' => $_SESSION['address_id'], 'user_id' => $_SESSION['user_id']));
			} else {
				$consignee = cart::get_consignee($_SESSION['user_id']);
			}
		}

		/* 检查收货人信息是否完整 */
		if (!cart::check_consignee_info($consignee, $flow_type)) {
			/* 如果不完整则转向到收货人信息填写界面 */
			return new ecjia_error('pls_fill_in_consinee_info_', '请完善收货人信息！');
		}

		$store_id_group = array();
		/* 根据经纬度查询附近店铺id*/
		if (!empty($consignee['latitude']) && !empty($consignee['longitude'])) {
			$geohash         = RC_Loader::load_app_class('geohash', 'store');
			$geohash_code    = $geohash->encode($consignee['latitude'] , $consignee['longitude']);
			$geohash_code    = substr($geohash_code, 0, 5);
			$store_id_group  = RC_Api::api('store', 'neighbors_store_id', array('geohash' => $geohash_code));
		}

		/* 检查购物车中是否有商品 */
		$get_cart_goods = RC_Api::api('cart', 'cart_list', array('cart_id' => $cart_id, 'flow_type' => $flow_type, 'store_group' => $store_id_group));

		if(is_ecjia_error($get_cart_goods)) {
			return $get_cart_goods;
		}
		if (count($get_cart_goods['goods_list']) == 0) {
			return new ecjia_error('not_found_cart_goods', '购物车中还没有商品');
		}

		if (count($get_cart_goods['goods_list']) != count($cart_id)) {
			return new ecjia_error('delivery_beyond_error', '有部分商品不在送货范围内！');
		}

		/* 对是否允许修改购物车赋值 */
		if ($flow_type != CART_GENERAL_GOODS || ecjia::config('one_step_buy') == '1') {
			$allow_edit_cart = 0 ;
		} else {
			$allow_edit_cart = 1 ;
		}

		/* 取得订单信息*/
		$order = cart::flow_order_info();
		$store_group = array();
		$cart_goods  = array();
		foreach ($get_cart_goods['goods_list'] as $row) {
			$store_group[] = $row['store_id'];
			if (!empty($row['goods_attr'])) {
				$goods_attr = explode("\n", $row['goods_attr']);
				$goods_attr = array_filter($goods_attr);
				$goods_attr_gourp = array();
// 				$out['goods_list'][$key]['goods_attr'] = array();
				foreach ($goods_attr as  $v) {
					$a = explode(':',$v);
					if (!empty($a[0]) && !empty($a[1])) {
						$goods_attr_gourp[] = array('name' => $a[0], 'value' => $a[1]);
					}
				}
			}

			$cart_goods[] = array(
				'seller_id'		=> intval($row['store_id']),
				'seller_name'	=> $row['store_name'],
				'store_id'		=> intval($row['store_id']),
				'store_name'	=> $row['store_name'],
				'rec_id'		=> intval($row['rec_id']),
				'goods_id'		=> intval($row['goods_id']),
				'goods_sn'		=> $row['goods_sn'],
				'goods_name'	=> $row['goods_name'],
				'goods_price'	=> $row['goods_price'],
				'market_price'	=> $row['market_price'],
				'formated_goods_price'	=> $row['formatted_goods_price'],
				'formated_market_price' => $row['formatted_market_price'],
				'goods_number'	=> intval($row['goods_number']),
				'subtotal'		=> $row['subtotal'],
				'goods_attr_id' => $row['goods_attr_id'],
				'attr'			=> $row['goods_attr'],
				'is_real'		=> $row['is_real'],
				'goods_attr'	=> $goods_attr_gourp,
				'img' => array(
					'thumb'	=> RC_Upload::upload_url($row['goods_img']),
					'url'	=> RC_Upload::upload_url($row['original_img']),
					'small'	=> RC_Upload::upload_url($row['goods_img']),
				)
			);
		}

		$store_group = array_unique($store_group);
		if (count($store_group) > 1) {
			return new ecjia_error('pls_single_shop_for_settlement', '请单个店铺进行结算!');
		} else {
			$order['store_id'] = $store_group[0];
		}

		/* 计算折扣 */
		if ($flow_type != CART_EXCHANGE_GOODS && $flow_type != CART_GROUP_BUY_GOODS) {
			$discount = cart::compute_discount($cart_id);
			$favour_name = empty($discount['name']) ? '' : join(',', $discount['name']);
		}
		/* 计算订单的费用 */
		$total = cart::order_fee($order, $cart_goods, $consignee, $cart_id);
		/* 取得配送列表 */
		$region            = array($consignee['country'], $consignee['province'], $consignee['city'], $consignee['district']);

		$shipping_method   = RC_Loader::load_app_class('shipping_method', 'shipping');
		$shipping_list     = $shipping_method->available_shipping_list($region, $order['store_id']);

		$cart_weight_price = cart::cart_weight_price($flow_type, $cart_id);
		$insure_disabled   = true;
		$cod_disabled      = true;

		$shipping_count_where = array('extension_code' => array('neq' => 'package_buy') , 'is_shipping' => 0);
		if (!empty($cart_id)) {
			$shipping_count_where = array_merge($shipping_count_where, array('rec_id' => $cart_id));
		}

		$db_cart = RC_Model::model('cart/cart_model');
		// 查看购物车中是否全为免运费商品，若是则把运费赋为零
		if ($_SESSION['user_id']) {
			$shipping_count_where = array_merge($shipping_count_where, array('user_id' => $_SESSION['user_id']));
			$shipping_count       = $db_cart->where($shipping_count_where)->count();
		} else {
			$shipping_count_where = array_merge($shipping_count_where, array('session_id' => SESS_ID));
			$shipping_count       = $db_cart->where($shipping_count_where)->count();
		}


		$ck = array();
		foreach ($shipping_list AS $key => $val) {
			if (isset($ck[$val['shipping_id']])) {
				unset($shipping_list[$key]);
				continue;
			}
			$ck[$val['shipping_id']] = $val['shipping_id'];

			$shipping_cfg = $shipping_method->unserialize_config($val['configure']);

			$shipping_list[$key]['free_money']          = price_format($shipping_cfg['free_money'], false);
			$shipping_fee = ($shipping_count == 0 AND $cart_weight_price['free_shipping'] == 1) ? 0 : $shipping_method->shipping_fee($val['shipping_code'], unserialize($val['configure']),
					$cart_weight_price['weight'], $cart_weight_price['amount'], $cart_weight_price['number']);


			$shipping_list[$key]['format_shipping_fee'] = price_format($shipping_fee, false);
			$shipping_list[$key]['shipping_fee']        = $shipping_fee;
			$shipping_list[$key]['free_money']          = price_format($shipping_cfg['free_money'], false);
			$shipping_list[$key]['insure_formated']     = strpos($val['insure'], '%') === false ? price_format($val['insure'], false) : $val['insure'];

			/* 当前的配送方式是否支持保价 */
			if ($val['shipping_id'] == $order['shipping_id']) {
				$insure_disabled = ($val['insure'] == 0);
				$cod_disabled    = ($val['support_cod'] == 0);
			}

			/* o2o*/
			if ($val['shipping_code'] == 'ship_o2o_express') {
				/* 获取最后可送的时间（当前时间+需提前下单时间）*/
				$time = RC_Time::local_date('H:i', RC_Time::gmtime() + $shipping_cfg['last_order_time'] * 60);

				if (empty($shipping_cfg['ship_time'])) {
					unset($shipping_list[$key]);
					continue;
				}
				$shipping_list[$key]['shipping_date'] = array();
				$ship_date = 0;

				while ($shipping_cfg['ship_days']) {
					foreach ($shipping_cfg['ship_time'] as $k => $v) {
						
						if ($v['end'] > $time || $ship_date > 0) {
							$shipping_list[$key]['shipping_date'][$ship_date]['date'] = RC_Time::local_date('Y-m-d', RC_Time::local_strtotime('+'.$ship_date.' day'));
							$shipping_list[$key]['shipping_date'][$ship_date]['time'][] = array(
								'start_time' 	=> $v['start'],
								'end_time'		=> $v['end'],
							);
						}
					}

					$ship_date ++;

					if (count($shipping_list[$key]['shipping_date']) >= $shipping_cfg['ship_days']) {
						break;
					}
				}
				$shipping_list[$key]['shipping_date'] = array_merge($shipping_list[$key]['shipping_date']);

			}
		}
		$shipping_list = array_values($shipping_list);

		/* 取得支付列表 */
		$cod_fee    = 0;
		if ($order['shipping_id'] == 0) {
			$cod        = true;
			$cod_fee    = 0;
		} else {
			$shipping = $shipping_method->shipping_info($order['shipping_id']);
			$cod      = $shipping['support_cod'];
			if ($cod){
 				/* 如果是团购，且保证金大于0，不能使用货到付款 */
 				if ($flow_type == CART_GROUP_BUY_GOODS) {
 					$group_buy_id = $_SESSION['extension_id'];
 					if ($group_buy_id <= 0) {
 						return new ecjia_error('groupbuy_not_support_cod', '如果是团购，且保证金大于0，不能使用货到付款');
 					}
 					RC_Loader::load_app_func('admin_goods', 'goods');
 					$group_buy = group_buy_info($group_buy_id);
 					if (empty($group_buy)) {
 						return new ecjia_error( 'invalid_parameter', RC_Lang::get ('system::system.invalid_parameter' ));
 					}
 					if ($group_buy['deposit'] > 0) {
 						$cod = false;
 						$cod_fee = 0;
 						/* 赋值保证金 */
						$gb_deposit = $group_buy['deposit'];
 					}
 				}
				if ($cod) {
					$shipping_area_info = $shipping_method->shipping_area_info($order['shipping_id'], $region, $order['store_id']);
					$cod_fee            = $shipping_area_info['pay_fee'];
				}
			}
		}

		$payment_method = RC_Loader::load_app_class('payment_method', 'payment');

		// 给货到付款的手续费加<span id>，以便改变配送的时候动态显示
		$store_info = RC_DB::table('store_franchisee')->where('store_id', $order['store_id'])->first();
		if ($store_info['manage_mode'] == 'self') {
			$payment_list = $payment_method->available_payment_list(1, $cod_fee);
		} else {
			$payment_list = $payment_method->available_payment_list(false, $cod_fee);
		}


		$user_info = RC_Api::api('user', 'user_info', array('user_id' => $_SESSION['user_id']));
		/* 保存 session */
		$_SESSION['flow_order'] = $order;

		$out = array();
		$out['goods_list']		= $cart_goods;//商品
		$out['consignee']		= $consignee;//收货地址
		$out['shipping_list']	= $shipping_list;//快递信息
		$out['payment_list']	= $payment_list;

		/* 如果使用积分，取得用户可用积分及本订单最多可以使用的积分 */
		if ((ecjia::config('use_integral', ecjia::CONFIG_EXISTS) || ecjia::config('use_integral') == '1')
				&& $_SESSION['user_id'] > 0
				&& $user_info['pay_points'] > 0
				&& ($flow_type != CART_GROUP_BUY_GOODS && $flow_type != CART_EXCHANGE_GOODS))
		{
			// 能使用积分
			$allow_use_integral = 1;
			$order_max_integral = cart::flow_available_points($cart_id);
		} else {
			$allow_use_integral = 0;
			$order_max_integral = 0;
		}
		$out['allow_use_integral'] = $allow_use_integral;//积分 是否使用积分
		$out['order_max_integral'] = $order_max_integral;//订单最大可使用积分
			/* 如果使用红包，取得用户可以使用的红包及用户选择的红包 */
		if ((ecjia::config('use_bonus', ecjia::CONFIG_EXISTS) || ecjia::config('use_bonus') == '1')
				&& ($flow_type != CART_GROUP_BUY_GOODS && $flow_type != CART_EXCHANGE_GOODS))
		{
			// 取得用户可用红包
			$db_user_bonus_view	= RC_Model::model('bonus/user_bonus_type_viewmodel');
            $db_user_bonus_view->view = array(
                'bonus_type' => array(
    		   		'type' 	 => Component_Model_View::TYPE_LEFT_JOIN,
    			 	'alias'	 => 'bt',
    			 	'on'   	 => 'ub.bonus_type_id = bt.type_id'
    			),
                'store_franchisee' => array(
                    'type' 	=> Component_Model_View::TYPE_LEFT_JOIN,
                    'alias'	=> 'sf',
                    'on'   	=> 'sf.store_id = bt.store_id'
                )
            );
			$user_bonus = $db_user_bonus_view->join('bonus_type,store_franchisee')
				->field('bt.type_id, bt.type_name, bt.send_type, bt.type_money, ub.bonus_id, bt.use_start_date, bt.use_end_date, min_goods_amount,bt.store_id,merchants_name')
				->where(array(
					'bt.use_start_date'   => array('elt' => RC_Time::gmtime()),
					'bt.use_end_date'     => array('egt' => RC_Time::gmtime()),
					'ub.user_id'          => array('neq' => 0),
					'ub.user_id'          => $_SESSION['user_id'],
					'ub.order_id'         => 0,
					'bt.min_goods_amount' => array('lt' => $total['goods_price']),
				))
        		->in(array('bt.store_id'  => array($order['store_id'], '0')))
				->select();

			$user_bonus_list = array();
			if (!empty($user_bonus)) {
				foreach ($user_bonus AS $key => $val) {
// 					/*app2.13 判断优惠券是否可用*/
// 					if ($val['send_type'] == SEND_COUPON) {
// 						$check_use_coupon = RC_Model::Model('bonus/bonus_type_viewmodel')->check_use_coupon($val['type_id'], $goods_id_group);
// 						if (!$check_use_coupon) {
// 							continue;
// 						}
// 					}
					/*app 2.8新增字段处理*/
					$user_bonus_list[$key]['bonus_id']                 = $val['bonus_id'];
					$user_bonus_list[$key]['bonus_name']               = $val['type_name'];
					$user_bonus_list[$key]['bonus_amount']             = $val['type_money'];
					$user_bonus_list[$key]['formatted_bonus_amount']   = price_format($val['type_money']);
					$user_bonus_list[$key]['request_amount']           = $val['min_goods_amount'];
					$user_bonus_list[$key]['formatted_request_amount'] = price_format($val['min_goods_amount']);
					$user_bonus_list[$key]['bonus_status']             = 0;
					$user_bonus_list[$key]['formatted_bonus_status']   = __('未使用');
					$user_bonus_list[$key]['start_date']               = $val['use_start_date'];
					$user_bonus_list[$key]['end_date']                 = $val['use_end_date'];
					$user_bonus_list[$key]['formatted_start_date']     = RC_Time::local_date(ecjia::config('date_format'), $val['use_start_date']);
					$user_bonus_list[$key]['formatted_end_date']       = RC_Time::local_date(ecjia::config('date_format'), $val['use_end_date']);
					$user_bonus_list[$key]['seller_id']                = $val['store_id'];
					$user_bonus_list[$key]['seller_name']              = $val['merchants_name'];
				}
				$bonus_list = array_merge($user_bonus_list);
			}
			// 能使用红包
			$allow_use_bonus = 1;
		} else {
			$allow_use_bonus = 0;
		}
		$out['allow_use_bonus']		= $allow_use_bonus;//是否使用红包
		$out['bonus']				= $bonus_list;//红包
		$out['allow_can_invoice']	= ecjia::config('can_invoice');//能否开发票
		/* 如果能开发票，取得发票内容列表 */
		if ((ecjia::config('can_invoice', ecjia::CONFIG_EXISTS) || ecjia::config('can_invoice') == '1')
				&& ecjia::config('invoice_content',ecjia::CONFIG_EXISTS)
				 && $flow_type != CART_EXCHANGE_GOODS)
		{
			$inv_content_list = explode("\n", str_replace("\r", '', ecjia::config('invoice_content')));
			$inv_type_list = array();
			$invoice_type  = ecjia::config('invoice_type');
			foreach ($invoice_type['type'] as $key => $type) {
				if (!empty($type)) {
					$inv_type_list[$type] = array(
						'label'      => $type . ' [' . floatval($invoice_type['rate'][$key]) . '%]',
						'label_type' => $type,
						'rate'       => floatval($invoice_type['rate'][$key])
					);
				}
			}
		}
		$out['inv_content_list']	= empty($inv_content_list) ? null : $inv_content_list;//发票内容项
		$out['inv_type_list']		= $inv_type_list;//发票类型及税率
		$out['your_integral']		= $user_info['pay_points'];//用户可用积分
// 		$out['your_discount']		= $your_discount;//用户享受折扣说明
		$out['discount']			= number_format($discount['discount'], 2, '.', '');//用户享受折扣数
		$out['discount_formated']	= $total['discount_formated'];

		if (!empty($out['consignee'])) {
			$out['consignee']['id'] = $out['consignee']['address_id'];
			unset($out['consignee']['address_id']);
			unset($out['consignee']['user_id']);
			unset($out['consignee']['address_id']);
			$ids = array($out['consignee']['country'], $out['consignee']['province'], $out['consignee']['city'], $out['consignee']['district']);
			$ids = array_filter($ids);

			$db_region = RC_Model::model('shipping/region_model');
			$data      = $db_region->in(array('region_id' => implode(',', $ids)))->select();

			$a_out = array();
			foreach ($data as $key => $val) {
				$a_out[$val['region_id']] = $val['region_name'];
			}

			$out['consignee']['country_name']	= isset($a_out[$out['consignee']['country']]) ? $a_out[$out['consignee']['country']] : '';
			$out['consignee']['province_name']	= isset($a_out[$out['consignee']['province']]) ? $a_out[$out['consignee']['province']] : '';
			$out['consignee']['city_name']		= isset($a_out[$out['consignee']['city']]) ? $a_out[$out['consignee']['city']] : '';
			$out['consignee']['district_name']	= isset($a_out[$out['consignee']['district']]) ? $a_out[$out['consignee']['district']] : '';

		}
		if (!empty($out['inv_content_list'])) {
			$temp = array();
			foreach ($out['inv_content_list'] as $key => $value) {
				$temp[] = array('id'=>$key, 'value'=>$value);
			}
			$out['inv_content_list'] = $temp;
		}
		if (!empty($out['inv_type_list'])) {
			$temp = array();
			$i = 1;
			foreach ($out['inv_type_list'] as $key => $value) {
				$temp[] = array(
					'id'	       => $i,
					'value'	       => $value['label'],
					'label_value'  => $value['label_type'],
					'rate'	       => $value['rate']);
				$i++;
			}
			$out['inv_type_list'] = $temp;
		}

		//去掉系统使用的字段
		if (!empty($out['shipping_list'])) {
			foreach ($out['shipping_list'] as $key => $value) {
				unset($out['shipping_list'][$key]['configure']);
				unset($out['shipping_list'][$key]['shipping_desc']);
			}
		}
		
		$device		 = $this->device;
		$device_code = $device['code'];
		if (!empty($out['payment_list'])) {
			foreach ($out['payment_list'] as $key => $value) {
				if ($device_code != '8001') {
					if ($value['pay_code'] == 'pay_koolyun' || $value['pay_code'] == 'pay_cash') {
						unset($out['payment_list'][$key]);
						continue;
					}
				}
				unset($out['payment_list'][$key]['pay_config']);
				unset($out['payment_list'][$key]['pay_desc']);
				$out['payment_list'][$key]['pay_name'] = strip_tags($value['pay_name']);
				// cod 货到付款，alipay支付宝，bank银行转账
				if (in_array($value['pay_code'], array('post', 'balance'))) {
					unset($out['payment_list'][$key]);
				}
			}
			$out['payment_list'] = array_values($out['payment_list']);
		}
		return $out;
	}
}

// end