<?php

/**
 * 购物流类
 * @author will.chen
 *
 */
class cart {
    
	/**
	 * 取得收货人信息
	 * @param   int	 $user_id	用户编号
	 * @return  array
	 */
	public static function get_consignee($user_id) {
		if (isset($_SESSION['flow_consignee'])) {
			/* 如果存在session，则直接返回session中的收货人信息 */
			return $_SESSION['flow_consignee'];
		} else {
			/* 如果不存在，则取得用户的默认收货人信息 */
			$arr = array();
			if ($user_id > 0) {
				/* 取默认地址 */
				$arr = RC_Model::model('user/user_address_user_viewmodel')->join('users')->find(array('u.user_id' => $user_id));
			}
			return $arr;
		}
	}
	
	/**
	 * 检查收货人信息是否完整
	 * @param   array   $consignee  收货人信息
	 * @param   int	 $flow_type  购物流程类型
	 * @return  bool	true 完整 false 不完整
	 */
	public static function check_consignee_info($consignee, $flow_type) {
		if (self::exist_real_goods(0, $flow_type)) {
			/* 如果存在实体商品 */
			$res = !empty($consignee['consignee']) &&
			!empty($consignee['country']) &&
			!empty($consignee['tel']);
	
			if ($res) {
				if (empty($consignee['province'])) {
					/* 没有设置省份，检查当前国家下面有没有设置省份 */
					$pro = RC_Model::model('shipping/region_model')->get_regions(1, $consignee['country']);
					$res = empty($pro);
				} elseif (empty($consignee['city'])) {
					/* 没有设置城市，检查当前省下面有没有城市 */
					$city = RC_Model::model('shipping/region_model')->get_regions(2, $consignee['province']);
					$res = empty($city);
				} elseif (empty($consignee['district'])) {
					$dist = RC_Model::model('shipping/region_model')->get_regions(3, $consignee['city']);
					$res = empty($dist);
				}
			}
			return $res;
		} else {
			/* 如果不存在实体商品 */
			return !empty($consignee['consignee']) &&
			!empty($consignee['tel']);
		}
	}
	
	/**
	 * 查询购物车（订单id为0）或订单中是否有实体商品
	 * @param   int	 $order_id   订单id
	 * @param   int	 $flow_type  购物流程类型
	 * @return  bool
	 */
	public static function exist_real_goods($order_id = 0, $flow_type = CART_GENERAL_GOODS) {
		if ($order_id <= 0) {
			$where = array('user_id' => $_SESSION['user_id'] , 'is_real' => 1 , 'rec_type' => $flow_type);
// 			if (defined('SESS_ID')) {
// 				$where['session_id'] = SESS_ID;
// 			}
			$count = RC_Model::model('cart/cart_model')->where($where)->count();
		} else {
			$count = RC_Model::model('orders/order_goods_model')->where(array('order_id' => $order_id , 'is_real' => 1))->count();
		}
		return $count > 0;
	}
	
	/**
	 * 检查订单中商品库存
	 *
	 * @access  public
	 * @param   array   $arr
	 *
	 * @return  void
	 */
	public static function flow_cart_stock($arr) {
		foreach ($arr AS $key => $val) {
			$val = intval(make_semiangle($val));
			if ($val <= 0 || !is_numeric($key)) {
				continue;
			}
			$cart_where = array('rec_id' => $key , 'user_id' => $_SESSION['user_id']);
			if (defined($name)) {
				$cart_where['session_id'] = SESS_ID;
			}
			$goods = RC_Model::model('cart/cart_model')->field('goods_id, goods_attr_id, extension_code, product_id')->find($cart_where);
			
// 			$db_cart = RC_Loader::load_app_model('cart_model', 'cart');
// 			$db_products = RC_Loader::load_app_model('products_model', 'goods');
// 			$dbview = RC_Loader::load_app_model('goods_cart_viewmodel', 'goods');
			
	
			$row   = RC_Model::model('goods/goods_cart_viewmodel')->field('c.product_id')->join('cart')->find(array('c.rec_id' => $key));
			//系统启用了库存，检查输入的商品数量是否有效
			if (intval(ecjia::config('use_storage')) > 0 && $goods['extension_code'] != 'package_buy') {
				if ($row['goods_number'] < $val) {
					return new ecjia_error('low_stocks', __('库存不足'));
				}
				/* 是货品 */
				$row['product_id'] = trim($row['product_id']);
				if (!empty($row['product_id'])) {
					$product_number = RC_Model::model('goods/products_model')->where(array('goods_id' => $goods['goods_id'] , 'product_id' => $goods['product_id']))->get_field('product_number');
					if ($product_number < $val) {
						return new ecjia_error('low_stocks', __('库存不足'));
					}
				}
			} elseif (intval(ecjia::config('use_storage')) > 0 && $goods['extension_code'] == 'package_buy') {
				if (self::judge_package_stock($goods['goods_id'], $val)) {
					return new ecjia_error('low_stocks', __('库存不足'));
				}
			}
		}
	}
	
	/**
	 * 获得用户的可用积分
	 *
	 * @access  private
	 * @return  integral
	 */
	public static function flow_available_points($cart_id = array()) {
		$cart_where = array('c.user_id' => $_SESSION['user_id'], 'c.is_gift' => 0 , 'g.integral' => array('gt' => 0) , 'c.rec_type' => CART_GENERAL_GOODS);
		if (!empty($cart_id)) {
			$cart_where = array_merge($cart_where, array('rec_id' => $cart_id));
		}
// 		if (defined('SESS_ID')) {
// 			$cart_where['c.session_id'] = SESS_ID;
// 		}
		
// 		$data = $db_view->join('goods')->where($cart_where)->sum('g.integral * c.goods_number');

		$data = RC_Model::model('cart/cart_goods_viewmodel')->join('goods')->where($cart_where)->sum('g.integral * c.goods_number');
		
		$val = intval($data);
		
		return self::integral_of_value($val);
	}
	
	/**
	 * 计算指定的金额需要多少积分
	 *
	 * @access  public
	 * @param   integer $value  金额
	 * @return  void
	 */
	public static function integral_of_value($value) {
		$scale = floatval(ecjia::config('integral_scale'));
		return $scale > 0 ? round($value / $scale * 100) : 0;
	}
	
	/**
	 * 计算积分的价值（能抵多少钱）
	 * @param   int	 $integral   积分
	 * @return  float   积分价值
	 */
	public static function value_of_integral($integral) {
		$scale = floatval(ecjia::config('integral_scale'));
		return $scale > 0 ? round(($integral / 100) * $scale, 2) : 0;
	}
	
	/**
	 * 取得购物车总金额
	 * @params  boolean $include_gift   是否包括赠品
	 * @param   int     $type           类型：默认普通商品
	 * @return  float   购物车总金额
	 */
	public static function cart_amount($include_gift = true, $type = CART_GENERAL_GOODS, $cart_id = array()) {
		$where = array('rec_type' => $type, 'user_id' => $_SESSION['user_id']);
// 		if (defined('SESS_ID')) {
// 			$where['session_id'] = SESS_ID;
// 		}
		
		if (!empty($cart_id)) {
			$where['rec_id'] = $cart_id;
		}
	
		if (!$include_gift) {
			$where['is_gift']	= 0;
			$where['goods_id']	= array('gt' => 0);
		}
	
		$data = RC_Model::model('cart/cart_model')->where($where)->sum('goods_price * goods_number');
		return $data;
	}
	
	/**
	 * 获得订单中的费用信息
	 *
	 * @access  public
	 * @param   array   $order
	 * @param   array   $goods
	 * @param   array   $consignee
	 * @param   bool    $is_gb_deposit  是否团购保证金（如果是，应付款金额只计算商品总额和支付费用，可以获得的积分取 $gift_integral）
	 * @return  array
	 */
	public static function order_fee($order, $goods, $consignee, $cart_id = array()) {
		
		$db 	= RC_Loader::load_app_model('cart_model', 'cart');
		$dbview = RC_Loader::load_app_model('cart_exchange_viewmodel', 'cart');
		/* 初始化订单的扩展code */
		if (!isset($order['extension_code'])) {
			$order['extension_code'] = '';
		}
		 
//     	TODO: 团购等促销活动注释后暂时给的固定参数
		$order['extension_code'] = '';
		$group_buy = '';
//     	TODO: 团购功能暂时注释
//     if ($order['extension_code'] == 'group_buy') {
//         $group_buy = group_buy_info($order['extension_id']);
//     }
		 
		$total  = array(
				'real_goods_count' => 0,
				'gift_amount'      => 0,
				'goods_price'      => 0,
				'market_price'     => 0,
				'discount'         => 0,
				'pack_fee'         => 0,
				'card_fee'         => 0,
				'shipping_fee'     => 0,
				'shipping_insure'  => 0,
				'integral_money'   => 0,
				'bonus'            => 0,
				'surplus'          => 0,
				'cod_fee'          => 0,
				'pay_fee'          => 0,
				'tax'              => 0
		   
		);
		$weight = 0;
	
		/* 商品总价 */
		foreach ($goods AS $key => $val) {
			/* 统计实体商品的个数 */
			if ($val['is_real']) {
				$total['real_goods_count']++;
			}
	
			$total['goods_price']  += $val['goods_price'] * $val['goods_number'];
			$total['market_price'] += $val['market_price'] * $val['goods_number'];
		
		}
	
		$total['saving']    = $total['market_price'] - $total['goods_price'];
		$total['save_rate'] = $total['market_price'] ? round($total['saving'] * 100 / $total['market_price']) . '%' : 0;
	
		$total['goods_price_formated']  = price_format($total['goods_price'], false);
		$total['market_price_formated'] = price_format($total['market_price'], false);
		$total['saving_formated']       = price_format($total['saving'], false);
	
		/* 折扣 */
		if ($order['extension_code'] != 'group_buy') {
			$discount = self::compute_discount($cart_id);
			$total['discount'] = $discount['discount'];
			if ($total['discount'] > $total['goods_price']) {
				$total['discount'] = $total['goods_price'];
			}
		}
		$total['discount_formated'] = price_format($total['discount'], false);
	
		/* 税额 */
		if (!empty($order['need_inv']) && $order['inv_type'] != '') {
			/* 查税率 */
			$rate = 0;
			$invoice_type = ecjia::config('invoice_type');
			foreach ($invoice_type['type'] as $key => $type) {
				if ($type == $order['inv_type']) {
					$rate_str = $invoice_type['rate'];
					$rate = floatval($rate_str[$key]) / 100;
					break;
				}
			}
			if ($rate > 0) {
				$total['tax'] = $rate * $total['goods_price'];
			}
		}
		$total['tax_formated'] = price_format($total['tax'], false);
//	TODO：暂时注释
/* 包装费用 */
//     if (!empty($order['pack_id'])) {
//         $total['pack_fee']      = pack_fee($order['pack_id'], $total['goods_price']);
//     }
//     $total['pack_fee_formated'] = price_format($total['pack_fee'], false);

//	TODO：暂时注释
//    /* 贺卡费用 */
//    if (!empty($order['card_id'])) {
//        $total['card_fee']      = card_fee($order['card_id'], $total['goods_price']);
//    }
		$total['card_fee_formated'] = price_format($total['card_fee'], false);
	
		/* 红包 */
		if (!empty($order['bonus_id'])) {
			$bonus          = RC_Api::api('bonus', 'bonus_info', array('bonus_id' => $order['bonus_id']));
			$total['bonus'] = $bonus['type_money'];
		}
		$total['bonus_formated'] = price_format($total['bonus'], false);
		/* 线下红包 */
		if (!empty($order['bonus_kill'])) {
			 
			$bonus  = RC_Api::api('bonus', 'bonus_info', array('bonus_id' => 0, 'bonus_sn' => $order['bonus_kill']));
			$total['bonus_kill'] = $order['bonus_kill'];
			$total['bonus_kill_formated'] = price_format($total['bonus_kill'], false);
		}
		
		/* 配送费用 */
		$shipping_cod_fee = NULL;
		if ($order['shipping_id'] > 0 && $total['real_goods_count'] > 0) {
			$region['country']  = $consignee['country'];
			$region['province'] = $consignee['province'];
			$region['city']     = $consignee['city'];
			$region['district'] = isset($consignee['district']) ? $consignee['district'] : '';
			 
			$shipping_method	= RC_Loader::load_app_class('shipping_method', 'shipping');
			$shipping_info 		= $shipping_method->shipping_area_info($order['shipping_id'], $region);
	
			if (!empty($shipping_info)) {
	
				if ($order['extension_code'] == 'group_buy') {
					$weight_price = self::cart_weight_price(CART_GROUP_BUY_GOODS);
				} else {
					$weight_price = self::cart_weight_price(CART_GENERAL_GOODS, $cart_id);
				}
				if (!empty($cart_id)) {
					$shipping_count_where = array('rec_id' => $cart_id);
				}
				// 查看购物车中是否全为免运费商品，若是则把运费赋为零
				if ($_SESSION['user_id']) {
					$shipping_count = $db->where(array_merge($shipping_count_where, array('user_id' => $_SESSION['user_id'] , 'extension_code' => array('neq' => 'package_buy') , 'is_shipping' => 0)))->count();
				} else {
					$shipping_count = $db->where(array_merge($shipping_count_where, array('session_id' => SESS_ID , 'extension_code' => array('neq' => 'package_buy') , 'is_shipping' => 0)))->count();
				}
				 
				$total['shipping_fee'] = ($shipping_count == 0 AND $weight_price['free_shipping'] == 1) ? 0 :  $shipping_method->shipping_fee($shipping_info['shipping_code'], $shipping_info['configure'], $weight_price['weight'], $total['goods_price'], $weight_price['number']);
				
				//ecmoban模板堂 --zhuo start
// 				if (ecjia::config('freight_model') == 0) {
// 					$total['shipping_fee'] = ($shipping_count == 0 AND $weight_price['free_shipping'] == 1) ? 0 :  $shipping_method->shipping_fee($shipping_info['shipping_code'],$shipping_info['configure'], $weight_price['weight'], $total['goods_price'], $weight_price['number']);
// 					//             	$total['shipping_fee'] = ($shipping_count == 0 AND $weight_price['free_shipping'] == 1) ?0 :  shipping_fee($shipping_info['shipping_code'],$shipping_info['configure'], $weight_price['weight'], $total['goods_price'], $weight_price['number']);
// 				} elseif (ecjia::config('freight_model') == 1) {
// 					$shipping_fee = get_goods_order_shipping_fee($goods, $region, $shipping_info['shipping_code']);
// 					$total['shipping_fee'] = ($shipping_count == 0 AND $weight_price['free_shipping'] == 1) ? 0 :  $shipping_fee['shipping_fee'];
// 					//             	$total['ru_list'] = $shipping_fee['ru_list']; //商家运费详细信息
// 				}
				 
				//ecmoban模板堂 --zhuo end
				//             $total['shipping_fee'] = ($shipping_count == 0 AND $weight_price['free_shipping'] == 1) ? 0 :  $shipping_method->shipping_fee($shipping_info['shipping_code'],$shipping_info['configure'], $weight_price['weight'], $total['goods_price'], $weight_price['number']);
				 
				if (!empty($order['need_insure']) && $shipping_info['insure'] > 0) {
					$total['shipping_insure'] = self::shipping_insure_fee($shipping_info['shipping_code'], $total['goods_price'], $shipping_info['insure']);
				} else {
					$total['shipping_insure'] = 0;
				}
	
				if ($shipping_info['support_cod']) {
					$shipping_cod_fee = $shipping_info['pay_fee'];
				}
			}
		}
	
		$total['shipping_fee_formated']    = price_format($total['shipping_fee'], false);
		$total['shipping_insure_formated'] = price_format($total['shipping_insure'], false);
	
		// 购物车中的商品能享受红包支付的总额
		$bonus_amount = self::compute_discount_amount($cart_id);
		// 红包和积分最多能支付的金额为商品总额
		$max_amount = $total['goods_price'] == 0 ? $total['goods_price'] : $total['goods_price'] - $bonus_amount;
	
		/* 计算订单总额 */
		if ($order['extension_code'] == 'group_buy' && $group_buy['deposit'] > 0) {
			$total['amount'] = $total['goods_price'];
		} else {
			$total['amount'] = $total['goods_price'] - $total['discount'] + $total['tax'] + $total['pack_fee'] + $total['card_fee'] + $total['shipping_fee'] + $total['shipping_insure'] + $total['cod_fee'];
			// 减去红包金额
			$use_bonus        = min($total['bonus'], $max_amount); // 实际减去的红包金额
			if(isset($total['bonus_kill'])) {
				$use_bonus_kill   = min($total['bonus_kill'], $max_amount);
				$total['amount'] -=  $price = number_format($total['bonus_kill'], 2, '.', ''); // 还需要支付的订单金额
			}
	
			$total['bonus']   			= $use_bonus;
			$total['bonus_formated'] 	= price_format($total['bonus'], false);
	
			$total['amount'] -= $use_bonus; // 还需要支付的订单金额
			$max_amount      -= $use_bonus; // 积分最多还能支付的金额
		}
	
		/* 余额 */
		$order['surplus'] = $order['surplus'] > 0 ? $order['surplus'] : 0;
		if ($total['amount'] > 0) {
			if (isset($order['surplus']) && $order['surplus'] > $total['amount']) {
				$order['surplus'] = $total['amount'];
				$total['amount']  = 0;
			} else {
				$total['amount'] -= floatval($order['surplus']);
			}
		} else {
			$order['surplus'] = 0;
			$total['amount']  = 0;
		}
		$total['surplus'] 			= $order['surplus'];
		$total['surplus_formated'] 	= price_format($order['surplus'], false);
	
		/* 积分 */
		$order['integral'] = $order['integral'] > 0 ? $order['integral'] : 0;
		if ($total['amount'] > 0 && $max_amount > 0 && $order['integral'] > 0) {
			$integral_money = self::value_of_integral($order['integral']);
			// 使用积分支付
			$use_integral            = min($total['amount'], $max_amount, $integral_money); // 实际使用积分支付的金额
			$total['amount']        -= $use_integral;
			$total['integral_money'] = $use_integral;
			$order['integral']       = self::integral_of_value($use_integral);
		} else {
			$total['integral_money'] = 0;
			$order['integral']       = 0;
		}
		$total['integral'] 			 = $order['integral'];
		$total['integral_formated']  = price_format($total['integral_money'], false);
	
		/* 保存订单信息 */
		$_SESSION['flow_order'] = $order;
		$se_flow_type = isset($_SESSION['flow_type']) ? $_SESSION['flow_type'] : '';
		 
		/* 支付费用 */
		if (!empty($order['pay_id']) && ($total['real_goods_count'] > 0 || $se_flow_type != CART_EXCHANGE_GOODS)) {
			$total['pay_fee']      	= self::pay_fee($order['pay_id'], $total['amount'], $shipping_cod_fee);
		}
		$total['pay_fee_formated'] 	= price_format($total['pay_fee'], false);
		$total['amount']           += $total['pay_fee']; // 订单总额累加上支付费用
		$total['amount_formated']  	= price_format($total['amount'], false);
	
		/* 取得可以得到的积分和红包 */
		if ($order['extension_code'] == 'group_buy') {
			$total['will_get_integral'] = $group_buy['gift_integral'];
		} elseif ($order['extension_code'] == 'exchange_goods') {
			$total['will_get_integral'] = 0;
		} else {
			$total['will_get_integral'] = self::get_give_integral($cart_id);
		}
// 		TODO::客户可获得赠送的红包总额，
// 		$total['will_get_bonus']        = $order['extension_code'] == 'exchange_goods' ? 0 : price_format(get_total_bonus(), false);
		$total['formated_goods_price']  = price_format($total['goods_price'], false);
		$total['formated_market_price'] = price_format($total['market_price'], false);
		$total['formated_saving']       = price_format($total['saving'], false);
	
		if ($order['extension_code'] == 'exchange_goods') {
			if ($_SESSION['user_id']) {
				$exchange_integral = $dbview->join('exchange_goods')->where(array('c.user_id' => $_SESSION['user_id'] , 'c.rec_type' => CART_EXCHANGE_GOODS , 'c.is_gift' => 0 ,'c.goods_id' => array('gt' => 0)))->group('eg.goods_id')->sum('eg.exchange_integral');
			} else {
				$exchange_integral = $dbview->join('exchange_goods')->where(array('c.session_id' => SESS_ID , 'c.rec_type' => CART_EXCHANGE_GOODS , 'c.is_gift' => 0 ,'c.goods_id' => array('gt' => 0)))->group('eg.goods_id')->sum('eg.exchange_integral');
			}
			$total['exchange_integral'] = $exchange_integral;
		}
		return $total;
	}
	
	/**
	 * 获得订单需要支付的支付费用
	 *
	 * @access  public
	 * @param   integer $payment_id
	 * @param   float   $order_amount
	 * @param   mix	 $cod_fee
	 * @return  float
	 */
	public static function pay_fee($payment_id, $order_amount, $cod_fee=null) {
		$payment_method = RC_Loader::load_app_class('payment_method','payment');
		$pay_fee = 0;
		$payment = $payment_method->payment_info($payment_id);
		$rate	= ($payment['is_cod'] && !is_null($cod_fee)) ? $cod_fee : $payment['pay_fee'];
	
		if (strpos($rate, '%') !== false) {
			/* 支付费用是一个比例 */
			$val		= floatval($rate) / 100;
			$pay_fee	= $val > 0 ? $order_amount * $val /(1- $val) : 0;
		} else {
			$pay_fee	= floatval($rate);
		}
		return round($pay_fee, 2);
	}
	
	/**
	 * 取得购物车该赠送的积分数
	 * @return  int	 积分数
	 */
	public static function get_give_integral($cart_id = array()) {
		$db_cartview = RC_Loader::load_app_model('cart_good_member_viewmodel', 'cart');
		$db_cartview->view = array(
				'goods' => array(
						'type'  => Component_Model_View::TYPE_LEFT_JOIN,
						'alias' => 'g',
						'field' => "c.rec_id, c.goods_id, c.goods_attr_id, g.promote_price, g.promote_start_date, c.goods_number,g.promote_end_date, IFNULL(mp.user_price, g.shop_price * '$_SESSION[discount]') AS member_price",
						'on'    => 'g.goods_id = c.goods_id'
				),
		);
		$where = array(
				'c.user_id'		=> $_SESSION['user_id'] , 
				'c.goods_id'	=> array('gt' => 0) ,
				'c.parent_id'	=> 0 ,
				'c.rec_type'	=> 0 , 
				'c.is_gift'		=> 0
		);
		if (!empty($cart_id)) {
			$where['rec_id'] = $cart_id;
		}
// 		if (defined('SESS_ID')) {
// 			$where['c.session_id'] = SESS_ID;
// 		}
			
		$integral = $db_cartview->where($where)->sum('c.goods_number * IF(g.give_integral > -1, g.give_integral, c.goods_price)');
		
		return intval($integral);
	}
	
	/**
	 * 获得购物车中商品的总重量、总价格、总数量
	 *
	 * @access  public
	 * @param   int	 $type   类型：默认普通商品
	 * @return  array
	 */
	public static function cart_weight_price($type = CART_GENERAL_GOODS, $cart_id = array()) {
		$db 			= RC_Loader::load_app_model('cart_model', 'cart');
		$dbview 		= RC_Loader::load_app_model('package_goods_viewmodel','orders');
		$db_cartview 	= RC_Loader::load_app_model('cart_good_member_viewmodel', 'cart');
	
		$package_row['weight'] 			= 0;
		$package_row['amount'] 			= 0;
		$package_row['number'] 			= 0;
		$packages_row['free_shipping'] 	= 1;
		$where = array('extension_code' => 'package_buy' , 'user_id' => $_SESSION['user_id'] );
		if (!empty($cart_id)) {
			$where['rec_id'] = $cart_id;
		}
	
// 		if (defined('SESS_ID')) {
// 			$where['session_id'] = SESS_ID;
// 		}
		
		/* 计算超值礼包内商品的相关配送参数 */
		$row = $db->field('goods_id, goods_number, goods_price')->where($where)->select();
	
		if ($row) {
			$packages_row['free_shipping'] = 0;
			$free_shipping_count = 0;
			foreach ($row as $val) {
				// 如果商品全为免运费商品，设置一个标识变量
				$dbview->view = array(
						'goods' => array(
								'type'  => Component_Model_View::TYPE_LEFT_JOIN,
								'alias' => 'g',
								'on'    => 'g.goods_id = pg.goods_id ',
						)
				);
	
				$shipping_count = $dbview->where(array('g.is_shipping' => 0 , 'pg.package_id' => $val['goods_id']))->count();
				if ($shipping_count > 0) {
					// 循环计算每个超值礼包商品的重量和数量，注意一个礼包中可能包换若干个同一商品
					$dbview->view = array(
							'goods' => array(
									'type'  => Component_Model_View::TYPE_LEFT_JOIN,
									'alias' => 'g',
									'field' => 'SUM(g.goods_weight * pg.goods_number)|weight,SUM(pg.goods_number)|number',
									'on'    => 'g.goods_id = pg.goods_id',
							)
					);
					$goods_row = $dbview->find(array('g.is_shipping' => 0 , 'pg.package_id' => $val['goods_id']));
	
					$package_row['weight'] += floatval($goods_row['weight']) * $val['goods_number'];
					$package_row['amount'] += floatval($val['goods_price']) * $val['goods_number'];
					$package_row['number'] += intval($goods_row['number']) * $val['goods_number'];
				} else {
					$free_shipping_count++;
				}
			}
			$packages_row['free_shipping'] = $free_shipping_count == count($row) ? 1 : 0;
		}
	
		/* 获得购物车中非超值礼包商品的总重量 */
		$db_cartview->view =array(
				'goods' => array(
						'type'  => Component_Model_View::TYPE_LEFT_JOIN,
						'alias' => 'g',
						'field' => 'SUM(g.goods_weight * c.goods_number)|weight,SUM(c.goods_price * c.goods_number)|amount,SUM(c.goods_number)|number',
						'on'    => 'g.goods_id = c.goods_id'
				)
		);
		$where = array(
				'c.user_id'		=> $_SESSION['user_id'] , 
				'rec_type'		=> $type , 
				'g.is_shipping' => 0 , 
				'c.extension_code' => array('neq' => 'package_buy')
				
		);
// 		if (defined('SESS_ID')) {
// 			$where['session_id'] = SESS_ID;
// 		}
		$row = $db_cartview->find($where);
	
		$packages_row['weight'] = floatval($row['weight']) + $package_row['weight'];
		$packages_row['amount'] = floatval($row['amount']) + $package_row['amount'];
		$packages_row['number'] = intval($row['number']) + $package_row['number'];
		/* 格式化重量 */
		$packages_row['formated_weight'] = self::formated_weight($packages_row['weight']);
		return $packages_row;
	}
	
	/**
	 * 格式化重量：小于1千克用克表示，否则用千克表示
	 *
	 * @param float $weight
	 *        	重量
	 * @return string 格式化后的重量
	 */
	public static function formated_weight($weight)
	{
		$weight = round(floatval($weight), 3);
		if ($weight > 0) {
			if ($weight < 1) {
				/* 小于1千克，用克表示 */
				return intval($weight * 1000) . RC_Lang::lang('gram');
			} else {
				/* 大于1千克，用千克表示 */
				return $weight . RC_Lang::lang('kilogram');
			}
		} else {
			return 0;
		}
	}
	
	/**
	 * 计算折扣：根据购物车和优惠活动
	 * @return  float   折扣
	 */
	public static function compute_discount($cart_id = array()) {
		$db 			= RC_Loader::load_app_model('favourable_activity_model', 'favourable');
		$db_cartview 	= RC_Loader::load_app_model('cart_good_member_viewmodel', 'cart');
	
		/* 查询优惠活动 */
		$now = RC_Time::gmtime();
		$user_rank = ',' . $_SESSION['user_rank'] . ',';
	
		$favourable_list = $db->where("start_time <= '$now' AND end_time >= '$now' AND CONCAT(',', user_rank, ',') LIKE '%" . $user_rank . "%'")->in(array('act_type'=>array(FAT_DISCOUNT, FAT_PRICE)))->select();
		if (!$favourable_list) {
			return 0;
		}
	
		/* 查询购物车商品 */
		$db_cartview->view = array(
				'goods' => array(
						'type'  => Component_Model_View::TYPE_LEFT_JOIN,
						'alias' => 'g',
						'field' => "c.goods_id, c.goods_price * c.goods_number AS subtotal, g.cat_id, g.brand_id",
						'on'   	=> 'c.goods_id = g.goods_id'
				)
		);
		
		$where = array(
				'c.user_id'		=> $_SESSION['user_id'],
				'c.parent_id'	=> 0,
				'c.is_gift'		=> 0,
				'rec_type' 		=> CART_GENERAL_GOODS
		);
		
// 		if (defined('SESS_ID')) {
// 			$where['c.session_id'] = SESS_ID;
// 		}
		
		$goods_list = $db_cartview->where($where)->select();
	
		if (!$goods_list) {
			return 0;
		}
	
		/* 初始化折扣 */
		$discount = 0;
		$favourable_name = array();
		RC_Loader::load_app_class('goods_category', 'goods', false);
		/* 循环计算每个优惠活动的折扣 */
		foreach ($favourable_list as $favourable) {
			$total_amount = 0;
			if ($favourable['act_range'] == FAR_ALL) {
				foreach ($goods_list as $goods) {
					$total_amount += $goods['subtotal'];
				}
			} elseif ($favourable['act_range'] == FAR_CATEGORY) {
				/* 找出分类id的子分类id */
				$id_list = array();
				$raw_id_list = explode(',', $favourable['act_range_ext']);
				foreach ($raw_id_list as $id) {
					$id_list = array_merge($id_list, array_keys(goods_category::cat_list($id, 0, false)));
				}
				$ids = join(',', array_unique($id_list));
	
				foreach ($goods_list as $goods) {
					if (strpos(',' . $ids . ',', ',' . $goods['cat_id'] . ',') !== false) {
						$total_amount += $goods['subtotal'];
					}
				}
			} elseif ($favourable['act_range'] == FAR_BRAND) {
				foreach ($goods_list as $goods) {
					if (strpos(',' . $favourable['act_range_ext'] . ',', ',' . $goods['brand_id'] . ',') !== false) {
						$total_amount += $goods['subtotal'];
					}
				}
			} elseif ($favourable['act_range'] == FAR_GOODS) {
				foreach ($goods_list as $goods) {
					if (strpos(',' . $favourable['act_range_ext'] . ',', ',' . $goods['goods_id'] . ',') !== false) {
						$total_amount += $goods['subtotal'];
					}
				}
			} else {
				continue;
			}
	
			/* 如果金额满足条件，累计折扣 */
			if ($total_amount > 0 && $total_amount >= $favourable['min_amount'] &&
			($total_amount <= $favourable['max_amount'] || $favourable['max_amount'] == 0)) {
				if ($favourable['act_type'] == FAT_DISCOUNT) {
					$discount += $total_amount * (1 - $favourable['act_type_ext'] / 100);
	
					$favourable_name[] = $favourable['act_name'];
				} elseif ($favourable['act_type'] == FAT_PRICE) {
					$discount += $favourable['act_type_ext'];
					$favourable_name[] = $favourable['act_name'];
				}
			}
		}
		return array('discount' => $discount, 'name' => $favourable_name);
	}
	
	/**
	 * 计算购物车中的商品能享受红包支付的总额
	 * @return  float   享受红包支付的总额
	 */
	public static function compute_discount_amount($cart_id = array()) {
		$db 			= RC_Loader::load_app_model('favourable_activity_model', 'favourable');
		$db_cartview 	= RC_Loader::load_app_model('cart_good_member_viewmodel', 'cart');
		/* 查询优惠活动 */
		$now = RC_Time::gmtime();
		$user_rank = ',' . $_SESSION['user_rank'] . ',';
	
		$favourable_list = $db->where('start_time <= '.$now.' AND end_time >= '.$now.' AND CONCAT(",", user_rank, ",") LIKE "%' . $user_rank . '%" ')->in(array('act_type' => array(FAT_DISCOUNT, FAT_PRICE)))->select();
		if (!$favourable_list) {
			return 0;
		}
	
		/* 查询购物车商品 */
		$db_cartview->view = array(
				'goods' => array(
						'type'  => Component_Model_View::TYPE_LEFT_JOIN,
						'alias' => 'g',
						'field' => "c.goods_id, c.goods_price * c.goods_number AS subtotal, g.cat_id, g.brand_id",
						'on'    => 'c.goods_id = g.goods_id'
				)
		);
		$cart_where = array('c.parent_id' => 0 , 'c.is_gift' => 0 , 'rec_type' => CART_GENERAL_GOODS);
		if (!empty($cart_id)) {
			$cart_where = array_merge($cart_where, array('c.rec_id' => $cart_id));
		}
		if ($_SESSION['user_id']) {
			$cart_where = array_merge($cart_where, array('c.user_id' => $_SESSION['user_id']));
	
		} else {
			$cart_where = array_merge($cart_where, array('c.session_id' => SESS_ID));
		}
		$goods_list = $db_cartview->where($cart_where)->select();

	
		if (!$goods_list) {
			return 0;
		}
	
		/* 初始化折扣 */
		$discount = 0;
		$favourable_name = array();
	
		/* 循环计算每个优惠活动的折扣 */
		foreach ($favourable_list as $favourable) {
			$total_amount = 0;
			if ($favourable['act_range'] == FAR_ALL) {
				foreach ($goods_list as $goods) {
					$total_amount += $goods['subtotal'];
				}
			} elseif ($favourable['act_range'] == FAR_CATEGORY) {
				/* 找出分类id的子分类id */
				$id_list = array();
				$raw_id_list = explode(',', $favourable['act_range_ext']);
				foreach ($raw_id_list as $id) {
					$id_list = array_merge($id_list, array_keys(cat_list($id, 0, false)));
				}
				$ids = join(',', array_unique($id_list));
	
				foreach ($goods_list as $goods) {
					if (strpos(',' . $ids . ',', ',' . $goods['cat_id'] . ',') !== false) {
						$total_amount += $goods['subtotal'];
					}
				}
			} elseif ($favourable['act_range'] == FAR_BRAND) {
				foreach ($goods_list as $goods) {
					if (strpos(',' . $favourable['act_range_ext'] . ',', ',' . $goods['brand_id'] . ',') !== false) {
						$total_amount += $goods['subtotal'];
					}
				}
			} elseif ($favourable['act_range'] == FAR_GOODS) {
				foreach ($goods_list as $goods) {
					if (strpos(',' . $favourable['act_range_ext'] . ',', ',' . $goods['goods_id'] . ',') !== false) {
						$total_amount += $goods['subtotal'];
					}
				}
			} else {
				continue;
			}
	
			if ($total_amount > 0 && $total_amount >= $favourable['min_amount'] && ($total_amount <= $favourable['max_amount'] || $favourable['max_amount'] == 0)) {
				if ($favourable['act_type'] == FAT_DISCOUNT) {
					$discount += $total_amount * (1 - $favourable['act_type_ext'] / 100);
				} elseif ($favourable['act_type'] == FAT_PRICE) {
					$discount += $favourable['act_type_ext'];
				}
			}
		}
		return $discount;
	}
	
	
	/**
	 * 检查礼包内商品的库存
	 * @return  boolen
	 */
	public static function judge_package_stock($package_id, $package_num = 1) {
		$db_package_goods 	= RC_Loader::load_app_model('package_goods_model', 'goods');
		$db_products_view 	= RC_Loader::load_app_model('products_viewmodel', 'goods');
		$db_goods_view 		= RC_Loader::load_app_model('goods_auto_viewmodel', 'goods');
	
		$row = $db_package_goods->field('goods_id, product_id, goods_number')->where(array('package_id' => $package_id))->select();
		if (empty($row)) {
			return true;
		}
	
		/* 分离货品与商品 */
		$goods = array('product_ids' => '', 'goods_ids' => '');
		foreach ($row as $value) {
			if ($value['product_id'] > 0) {
				$goods['product_ids'] .= ',' . $value['product_id'];
				continue;
			}
			$goods['goods_ids'] .= ',' . $value['goods_id'];
		}
	
		/* 检查货品库存 */
		if ($goods['product_ids'] != '') {
			$row = $db_products_view->join('package_goods')->where(array('pg.package_id' => $package_id , 'pg.goods_number' * $package_num => array('gt' => 'p.product_number')))->in(array('p.product_id' => trim($goods['product_ids'], ',')))->select();
			if (!empty($row)) {
				return true;
			}
		}
	
		/* 检查商品库存 */
		if ($goods['goods_ids'] != '') {
			$db_goods_view->view = array(
					'package_goods' => array(
							'type' 	=> Component_Model_View::TYPE_LEFT_JOIN,
							'alias'	=> 'pg',
							'field' => 'g.goods_id',
							'on' 	=> 'pg.goods_id = g.goods_id'
					)
			);
			$row = $db_goods_view->where(array('pg.goods_number' * $package_num => array('gt' => 'g.goods_number')  , 'pg.package_id' => $package_id))->in(array('pg.goods_id' => trim($goods['goods_ids'] , ',')))->select();
			if (!empty($row)) {
				return true;
			}
		}
		return false;
	}
	
	/**
	 * 清空购物车
	 * @param   int	 $type   类型：默认普通商品
	 */
	public static function clear_cart($type = CART_GENERAL_GOODS, $cart_id = array()) {
		$db_cart = RC_Loader::load_app_model('cart_model', 'cart');
		
		$cart_w = array(
				'user_id'	=> $_SESSION['user_id'],
				'rec_type'	=> $type,
		);
		if (!empty($cart_id)) {
			$cart_w['rec_id'] = $cart_id;
		}
		
// 		if (defined('SESS_ID')) {
// 			$cart_w['session_id'] = SESS_ID;
// 		}
		
		$db_cart->where($cart_w)->delete();
	}
	
	/**
	 * 得到新订单号
	 * @return  string
	 */
	public static function get_order_sn() {
		/* 选择一个随机的方案 */
		mt_srand((double) microtime() * 1000000);
		return date('Ymd') . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
	}
}

// end