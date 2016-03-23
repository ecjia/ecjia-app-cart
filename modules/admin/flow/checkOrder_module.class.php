<?php
defined('IN_ECJIA') or exit('No permission resources.');
/**
 * ## 添加购物流		返回参数与前台checkorder保持一致
 * @author luchongchong
 *
 */
class checkOrder_module implements ecjia_interface {
	public function run(ecjia_api & $api) {
		
		$ecjia = RC_Loader::load_app_class('api_admin', 'api');
		$ecjia->authadminSession();
		
		RC_Loader::load_app_func('global','cart');
		RC_Loader::load_app_func('cart','cart');
		RC_Loader::load_app_func('order','orders');
		RC_Loader::load_app_func('bonus','bonus');
		$db_cart = RC_Loader::load_app_model('cart_model', 'cart');
		define('SESS_ID', RC_Session::session()->get_session_id());
		
		
// 		define('SESS_ID', '686f82a243574d025f3ccea05d07c299');
		
		//从移动端接收数据
		$addgoods		= _POST('addgoods');	//添加
		$updategoods	= _POST('updategoods');	//编辑
		$deletegoods	= _POST('deletegoods');	//删除
		$user			= _POST('user');		//选择用户
		
		//选择用户
		if (!empty($user)) {
			$user_id = (empty($user['user_id']) || !isset($user['user_id'])) ? 0 : $user['user_id'];
			if ($user_id > 0) {
// 				RC_Session::set('user_id', $user_id);
				$_SESSION['temp_user_id'] = $user_id;
				$_SESSION['user_id'] = $user_id;
				$db_cart->where(array('session_id' => SESS_ID))->update(array('user_id' => $user_id));
			}
		}
		
		/* 取得购物类型 */
		$flow_type = isset($_SESSION['flow_type']) ? intval($_SESSION['flow_type']) : CART_GENERAL_GOODS;
		
		/* 判断是否是会员 */
		$consignee = array();
		if ($_SESSION['user_id']) {
			$db_user_model = RC_Loader::load_app_model('users_model','user');
			$user_info = $db_user_model->field('user_name, mobile_phone, email')
									->where(array('user_id'=>$_SESSION['user_id']))
									->find();
			$consignee = array(
					'consignee'		=> $user_info['user_name'],
					'mobile'		=> $user_info['mobile_phone'],
					'tel'			=> $user_info['mobile_phone'],
					'email'			=> $user_info['email'],
			);
		} else {//匿名用户
			$consignee = array(
					'consignee'	=> '匿名用户',
					'mobile'	=> '',
					'tel'		=> '',
					'email'		=> '',
			);	
		}
		
		
		/* 获取商家或平台的地址 作为收货地址 */
		$region = RC_Loader::load_app_model('region_model','shipping');
		if ($_SESSION['ru_id'] > 0){
			$msi_dbview = RC_Loader::load_app_model('merchants_shop_information_viewmodel', 'seller');
			$where = array();
			$where['ssi.status'] = 1;
			$where['msi.merchants_audit'] = 1;
			$where['msi.user_id'] = $_SESSION['ru_id'];
			$info = $msi_dbview->join(array('category', 'seller_shopinfo'))
						->field('ssi.*')
						->where($where)
						->find();
			$region_info = array(
					'country'			=> $info['country'],
					'province'			=> $info['province'],
					'city'				=> $info['city'],
// 					'country_name'		=> $region->where(array('region_id'=>$info['country']))->get_field('region_name'),
// 					'province_name'		=> $region->where(array('region_id'=>$info['province']))->get_field('region_name'),
// 					'city_name'			=> $region->where(array('region_id'=>$info['city']))->get_field('region_name'),
					'address'			=> $info['shop_address'],
			);
			$consignee = array_merge($consignee, $region_info);
		} else {
			$region_info = array(
					'country'			=> ecjia::config('shop_country'),
					'province'			=> ecjia::config('shop_province'),
					'city'				=> ecjia::config('shop_city'),
// 					'country_name'		=> $region->where(array('region_id' => ecjia::config('shop_country')))->get_field('region_name'),
// 					'province_name'		=> $region->where(array('region_id' => ecjia::config('shop_province')))->get_field('region_name'),
// 					'city_name'			=> $region->where(array('region_id' => ecjia::config('shop_city')))->get_field('region_name'),
					'address'			=> ecjia::config('shop_address'),
			);
			$consignee = array_merge($consignee, $region_info);
		}
		
		if (!empty($addgoods)) {
			$warehouse_db = RC_Loader::load_app_model('warehouse_model', 'warehouse');
			$warehouse = $warehouse_db->where(array('regionId' => $region_info['province']))->find();
			$area_id = $warehouse['region_id'];
			$warehouse_id = $warehouse['parent_id'];
			
			
			$products_db = RC_Loader::load_app_model('products_model', 'goods');
			$goods_db = RC_Loader::load_app_model('goods_model', 'goods');
			$goods_spec = array();
			
			$products_goods = $products_db->where(array('product_sn' => $addgoods['goods_sn']))->find();
			if (!empty($products_goods)) {
				$goods_spec = explode('|', $products_goods['goods_attr']);
				$where = array('goods_id' => $products_goods['goods_id'], 'is_delete' => 0, 'is_on_sale' => 1, 'is_alone_sale' => 1);
				if (ecjia::config('review_goods')) {
					$where['review_status'] = array('gt' => 2);
				}
				$goods = $goods_db->where($where)->find();
			} else {
				$where = array('goods_sn' => $addgoods['goods_sn'], 'is_delete' => 0, 'is_on_sale' => 1, 'is_alone_sale' => 1);
				if (ecjia::config('review_goods')) {
					$where['review_status'] = array('gt' => 2);
				}
				$goods = $goods_db->where($where)->find();
			}
			if (empty($goods)) {
				return new ecjia_error('addgoods_error', '该商品不存在或已下架');
			}
// 			$result = add_cart($addgoods);		//添加购物车商品
			addto_cart($goods['goods_id'], $addgoods['number'], $goods_spec, 0, $warehouse_id, $area_id);
				
		}
		if (!empty($updategoods)) {
			$result = updatecart($updategoods);//编辑购物车商品
				
		}
		if (!empty($deletegoods)) {
			$result = deletecart($deletegoods);//删除购物车商品
		}
		
		/* 对商品信息赋值 */
		$cart_goods = cart_goods($flow_type); // 取得商品列表，计算合计
		
		/* 对是否允许修改购物车赋值 */
// 		if ($flow_type != CART_GENERAL_GOODS || ecjia::config('one_step_buy') == '1') {
// 			$allow_edit_cart = 0 ;
// 		} else {
// 			$allow_edit_cart = 1 ;
// 		}
		
		/* 取得订单信息*/
		$order = flow_order_info();
		/* 计算折扣 */
		if ($flow_type != CART_EXCHANGE_GOODS && $flow_type != CART_GROUP_BUY_GOODS) {
			$discount = compute_discount();
			$favour_name = empty($discount['name']) ? '' : join(',', $discount['name']);
		}
		/* 计算订单的费用 */
		
		$total = cashdesk_order_fee($order, $cart_goods, $consignee);
	
		/* 取得配送列表 */
// 		$region            = array($consignee['province'], $consignee['city']);//取得省市 
// 		$shipping_method   = RC_Loader::load_app_class('shipping_method', 'shipping');
// 		$shipping_list     = $shipping_method->available_shipping_list($region);
	
		$cart_weight_price = cart_weight_price($flow_type);
		$insure_disabled   = true;
		$cod_disabled      = true;
		
		$shipping_list = array();
		
//		TODO://暂不考虑配送方式		
		/* 查看购物车中是否全为免运费商品，若是则把运费赋为零 */
// 		$db_cart = RC_Loader::load_app_model('cart_model', 'cart');
// 		if ($_SESSION['user_id']) {
// 			$shipping_count = $db_cart->where(array('user_id' => $_SESSION['user_id'] , 'extension_code' => array('neq' => 'package_buy') , 'is_shipping' => 0))->count();
// 		} else {
// 			$shipping_count = $db_cart->where(array('session_id' => SESS_ID , 'extension_code' => array('neq' => 'package_buy') , 'is_shipping' => 0))->count();
// 		}
		
// 		$ck = array();
// 		foreach ($shipping_list AS $key => $val) {
// 			if (isset($ck[$val['shipping_id']])) {
// 				unset($shipping_list[$key]);
// 				continue;
// 			}
// 			$ck[$val['shipping_id']] = $val['shipping_id'];
		
// 			$shipping_cfg = unserialize_config($val['configure']);
// 			$shipping_fee = ($shipping_count == 0 AND $cart_weight_price['free_shipping'] == 1) ? 0 : $shipping_method->shipping_fee($val['shipping_code'], unserialize($val['configure']),
// 					$cart_weight_price['weight'], $cart_weight_price['amount'], $cart_weight_price['number']);
				
// 			$shipping_list[$key]['format_shipping_fee'] = price_format($shipping_fee, false);
// 			$shipping_list[$key]['shipping_fee']        = $shipping_fee;
// 			$shipping_list[$key]['free_money']          = price_format($shipping_cfg['free_money'], false);
// 			$shipping_list[$key]['insure_formated']     = strpos($val['insure'], '%') === false ?
// 			price_format($val['insure'], false) : $val['insure'];
		
// 			/* 当前的配送方式是否支持保价 */
// 			if ($val['shipping_id'] == $order['shipping_id']) {
// 				$insure_disabled = ($val['insure'] == 0);
// 				$cod_disabled    = ($val['support_cod'] == 0);
// 			}
// 		}
		
		/* 取得支付列表 */
		$cod_fee    = 0;
		if ($order['shipping_id'] == 0) {
			$cod        = true;
			$cod_fee    = 0;
		} else {
//			TODO://暂不考虑配送方式
// 			$shipping = $shipping_method->shipping_info($order['shipping_id']);
// 			$cod = $shipping['support_cod'];
// 			if ($cod){
// 				/* 如果是团购，且保证金大于0，不能使用货到付款 */
// 				if ($flow_type == CART_GROUP_BUY_GOODS) {
// 					$group_buy_id = $_SESSION['extension_id'];
// 					if ($group_buy_id <= 0) {
// 						EM_Api::outPut(10006);
// 					}
// 					$group_buy = group_buy_info($group_buy_id);
// 					if (empty($group_buy)) {
// 						EM_Api::outPut(101);
// 					}
// 					if ($group_buy['deposit'] > 0) {
// 						$cod = false;
// 						$cod_fee = 0;
// 						/* 赋值保证金 */
// 						$gb_deposit = $group_buy['deposit'];
// 					}
// 				}
// 				if ($cod) {
// 					$shipping_area_info = $shipping_method->shipping_area_info($order['shipping_id'], $region);
// 					$cod_fee = $shipping_area_info['pay_fee'];
// 				}
// 			}
		}
		
		$payment_method = RC_Loader::load_app_class('payment_method', 'payment');
	
		// 给货到付款的手续费加<span id>，以便改变配送的时候动态显示
		$payment_list = $payment_method->available_payment_list(1, $cod_fee);
		$user_info = user_info($_SESSION['user_id']);
		
		/* 保存 session */
		$_SESSION['flow_order'] = $order;
		
		$out = array();
		$out['goods_list']		= $cart_goods;		//商品
		$out['consignee']		= $consignee;		//收货地址
		$out['shipping_list']	= $shipping_list;	//快递信息
		$out['payment_list']	= $payment_list;
		/* 如果使用积分，取得用户可用积分及本订单最多可以使用的积分 */
		if ((ecjia::config('use_integral', ecjia::CONFIG_CHECK) || ecjia::config('use_integral') == '1')
				&& $_SESSION['user_id'] > 0 && $user_info['pay_points'] > 0 
				&& ($flow_type != CART_GROUP_BUY_GOODS && $flow_type != CART_EXCHANGE_GOODS)) {
			// 能使用积分
			$allow_use_integral = 1;
			$order_max_integral = flow_available_points();
		} else {
			$allow_use_integral = 0;
			$order_max_integral = 0;
		}
		
		$out['allow_use_integral'] = $allow_use_integral;//积分 是否使用积分
		$out['order_max_integral'] = $order_max_integral;//订单最大可使用积分
		/* 如果使用红包，取得用户可以使用的红包及用户选择的红包 */
		$allow_use_bonus = 0;
		if ((ecjia::config('use_bonus', ecjia::CONFIG_CHECK) || ecjia::config('use_bonus') == '1')
				&& ($flow_type != CART_GROUP_BUY_GOODS && $flow_type != CART_EXCHANGE_GOODS)){
			// 取得用户可用红包
			$user_bonus = user_bonus($_SESSION['user_id'], $total['goods_price']);
			if (!empty($user_bonus)) {
				foreach ($user_bonus AS $key => $val) {
					$user_bonus[$key]['bonus_money_formated'] = price_format($val['type_money'], false);
				}
				$bonus_list = $user_bonus;
			}
			// 能使用红包
			$allow_use_bonus = 1;
		}
		$out['allow_use_bonus'] = $allow_use_bonus;//是否使用红包
		$out['bonus'] = $bonus_list;//红包
		$out['allow_can_invoice'] = ecjia::config('can_invoice');//能否开发票
				
		/* 如果能开发票，取得发票内容列表 */
		$inv_content_list = $inv_type_list = array();
		if ((ecjia::config('can_invoice', ecjia::CONFIG_CHECK) || ecjia::config('can_invoice') == '1')
				&&ecjia::config('invoice_content',ecjia::CONFIG_CHECK)
				&& $flow_type != CART_EXCHANGE_GOODS) {
			$inv_content_list = explode("\n", str_replace("\r", '', ecjia::config('invoice_content')));
			
			$invoice_type = ecjia::config('invoice_type');
			/* by  will.chen 2015/05/18 */
			foreach ($invoice_type['type'] as $key => $type) {
				if (!empty($type)) {
					$inv_type_list[$type] = array(
						'label'			=> $type . ' [' . floatval($invoice_type['rate'][$key]) . '%]',
						'label_type'	=> $type,
						'rate'			=> floatval($invoice_type['rate'][$key])
					);
				}
			}
		}
				
		$out['inv_content_list'] = $inv_content_list;//发票内容项
		$out['inv_type_list'] = $inv_type_list;//发票类型及税率
		$out['your_integral'] = $user_info['pay_points'];//用户可用积分
		
// 		$out['your_discount'] = $your_discount;//用户享受折扣说明
		$out['discount'] = number_format($discount['discount'], 2, '.', '');//用户享受折扣数
		$out['discount_formated'] = $total['discount_formated'];
					
		if (!empty($out['consignee'])) {
			$out['consignee']['id'] = $out['consignee']['address_id'];
			unset($out['consignee']['address_id']);
			unset($out['consignee']['user_id']);
			unset($out['consignee']['address_id']);
			$ids = array($out['consignee']["country"], $out['consignee']["province"], $out['consignee']["city"], $out['consignee']["district"]);
			
			$ids = array_filter($ids);
			$db_region = RC_Loader::load_app_model('region_model','shipping');
			$data = $db_region->in(array('region_id' => implode(',', $ids)))->select();
			$a_out = array();
			foreach ($data as $key => $val) {
				$a_out[$val['region_id']] = $val['region_name'];
			}
		
			$out['consignee']["country_name"] = isset($a_out[$out['consignee']["country"]]) ? $a_out[$out['consignee']["country"]] : '';
			$out['consignee']["province_name"] = isset($a_out[$out['consignee']["province"]]) ? $a_out[$out['consignee']["province"]] : '';
			$out['consignee']["city_name"] = isset($a_out[$out['consignee']["city"]]) ? $a_out[$out['consignee']["city"]] : '';
			$out['consignee']["district_name"] = isset($a_out[$out['consignee']["district"]]) ? $a_out[$out['consignee']["district"]] : '';
		
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
						'id'	=> $i,
						'value'	=> $value['label'],
						'label_value' => $value['label_type'],
						'rate'	=> $value['rate']);
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
				
		if (!empty($out['payment_list'])) {

			foreach ($out['payment_list'] as $key => $value) {
				unset($out['payment_list'][$key]['pay_config']);
				unset($out['payment_list'][$key]['pay_desc']);
				$out['payment_list'][$key]['pay_name'] = strip_tags($value['pay_name']);
				// cod 货到付款，alipay支付宝，bank银行转账
				if (in_array($value['pay_code'], array('post', 'balance'))) {
					unset($out['payment_list'][$key]);
				}
				// $out['shipping_list'][$key]['configure'] = unserialize($value['configure']);
			}
			$out['payment_list'] = array_values($out['payment_list']);
		}
					
		if (!empty($out['goods_list'])) {
			foreach ($out['goods_list'] as $key => $value) {
				if (!empty($value['goods_attr'])) {
					$goods_attr = explode("\n", $value['goods_attr']);
					$goods_attr = array_filter($goods_attr);
					$out['goods_list'][$key]['goods_attr'] = array();
					foreach ($goods_attr as  $v) {
						$a = explode(':',$v);
						if (!empty($a[0]) && !empty($a[1])) {
							$out['goods_list'][$key]['goods_attr'][] = array('name'=>$a[0], 'value'=>$a[1]);
						}
					}
				}
			}
		}
		EM_API::outPut($out);
	}
		
}

/**
 * 添加商品到购物车
 *
 * @access  public
 * @param   integer $goods_id   商品编号
 * @param   integer $num        商品数量
 * @param   array   $spec       规格值对应的id数组
 * @param   integer $parent     基本件
 * @return  boolean
 */
function add_cart($goods_id, $num = 1, $spec = array(), $parent = 0, $warehouse_id = 0, $area_id = 0) {
	$dbview 		= RC_Loader::load_app_model('sys_goods_member_viewmodel', 'goods');
	$db_cart 		= RC_Loader::load_app_model('cart_model', 'cart');
	$db_products 	= RC_Loader::load_app_model('products_model', 'goods');
	$db_group 		= RC_Loader::load_app_model('group_goods_model', 'goods');
	$_parent_id 	= $parent;
	RC_Loader::load_app_func('order', 'orders');
	RC_Loader::load_app_func('goods', 'goods');
	RC_Loader::load_app_func('common', 'goods');

	$field = "wg.w_id, g.goods_name, g.goods_sn, g.is_on_sale, g.is_real, g.user_id as ru_id, g.model_inventory, g.model_attr, ".
			"wg.warehouse_price, wg.warehouse_promote_price, wg.region_number as wg_number, wag.region_price, wag.region_promote_price, wag.region_number as wag_number, g.model_price, g.model_attr, ".
			"g.market_price, IF(g.model_price < 1, g.shop_price, IF(g.model_price < 2, wg.warehouse_price, wag.region_price)) AS org_price, ".
			"IF(g.model_price < 1, g.promote_price, IF(g.model_price < 2, wg.warehouse_promote_price, wag.region_promote_price)) as promote_price, ".
			" g.promote_start_date, g.promote_end_date, g.goods_weight, g.integral, g.extension_code, g.goods_number, g.is_alone_sale, g.is_shipping, ".
			"IFNULL(mp.user_price, IF(g.model_price < 1, g.shop_price, IF(g.model_price < 2, wg.warehouse_price, wag.region_price)) * '$_SESSION[discount]') AS shop_price ";
	/* 取得商品信息 */
	$dbview->view = array(
			'warehouse_goods' => array(
					'type'  => Component_Model_View::TYPE_LEFT_JOIN,
					'alias' => 'wg',
					'on'   	=> "g.goods_id = wg.goods_id and wg.region_id = '$warehouse_id'"
			),
			'warehouse_area_goods' => array(
					'type'  => Component_Model_View::TYPE_LEFT_JOIN,
					'alias' => 'wag',
					'on'   	=> "g.goods_id = wag.goods_id and wag.region_id = '$area_id'"
			),
			'member_price' => array(
					'type'  => Component_Model_View::TYPE_LEFT_JOIN,
					'alias' => 'mp',
					'on'   	=> "mp.goods_id = g.goods_id AND mp.user_rank = '$_SESSION[user_rank]'"
			)
	);

	$where = array(
			'g.goods_id' => $goods_id,
			'g.is_delete' => 0,
	);
	if(ecjia::config('review_goods') == 1){
		$where['g.review_status'] = array('gt' => 2);
	}


	$goods = $dbview->field($field)->join(array('warehouse_goods', 'warehouse_area_goods', 'member_price'))->find($where);


	if (empty($goods)) {
		return new ecjia_error('no_goods', __('对不起，指定的商品不存在！'));
	}
	/* 是否正在销售 */
	if ($goods['is_on_sale'] == 0) {
		return new ecjia_error('addcart_error', __('购买失败'));
	}


	/* 如果是作为配件添加到购物车的，需要先检查购物车里面是否已经有基本件 */
	if ($parent > 0) {
		if ($_SESSION['user_id']) {
			$count = $db_cart->where(array('goods_id' => $parent , 'user_id' => $_SESSION['user_id'] , 'extension_code' => array('neq' => 'package_buy')))->count();
		} else {
			$count = $db_cart->where(array('goods_id' => $parent , 'session_id' => SESS_ID , 'extension_code' => array('neq' => 'package_buy')))->count();
		}
		 
		if ($count == 0) {
			return new ecjia_error('addcart_error', __('对不起，您希望将该商品做为配件购买，可是购物车中还没有该商品的基本件。'));
		}
	}

	/* 不是配件时检查是否允许单独销售 */
	if (empty($parent) && $goods['is_alone_sale'] == 0) {
		return new ecjia_error('addcart_error', __('购买失败'));
	}

	//     $sql = "SELECT * FROM " .$GLOBALS['ecs']->table($table_products). " WHERE goods_id = '$goods_id'" .$type_files. " LIMIT 0, 1";
	//     $prod = $GLOBALS['db']->getRow($sql);
	/* 如果商品有规格则取规格商品信息 配件除外 */
	if ($goods['model_attr'] == 1) {
		$db = RC_Loader::load_app_model('products_warehouse_model', 'warehouse');
		$prod = $db->where(array('goods_id' => $goods_id, 'warehouse_id' => $warehouse_id))->find();
		//     	$table_products = "products_warehouse";
		//     	$type_files = " and warehouse_id = '$warehouse_id'";
	} elseif($goods['model_attr'] == 2) {
		$db = RC_Loader::load_app_model('products_area_model', 'warehouse');
		$prod = $db->where(array('goods_id' => $goods_id, 'area_id' => $area_id))->find();
		//     	$table_products = "products_area";
		//     	$type_files = " and area_id = '$area_id'";
	} else {
		//     	$table_products = "products";
		//     	$type_files = "";
		$prod = $db_products->find(array('goods_id' => $goods_id));
	}

	if (is_spec($spec) && !empty($prod)) {
		$product_info = get_products_info($goods_id, $spec, $warehouse_id, $area_id);
	}
	if (empty($product_info)) {
		$product_info = array('product_number' => '', 'product_id' => 0 , 'goods_attr'=>'');
	}

	if ($goods['model_inventory'] == 1) {
		$goods['goods_number'] = $goods['wg_number'];
	} elseif($goods['model_inventory'] == 2) {
		$goods['goods_number'] = $goods['wag_number'];
	}

	/* 检查：库存 */
	if (ecjia::config('use_storage') == 1) {
		//检查：商品购买数量是否大于总库存
		if ($num > $goods['goods_number']) {
			return new ecjia_error('low_stocks', __('库存不足'));
		}
		//商品存在规格 是货品 检查该货品库存
		if (is_spec($spec) && !empty($prod)) {
			if (!empty($spec)) {
				/* 取规格的货品库存 */
				if ($num > $product_info['product_number']) {
					return new ecjia_error('low_stocks', __('库存不足'));
				}
			}
		}
	}

	/* 计算商品的促销价格 */
	$warehouse_area['warehouse_id'] = $warehouse_id;
	$warehouse_area['area_id'] = $area_id;

	$spec_price             = spec_price($spec, $goods_id, $warehouse_area);
	$goods_price            = get_final_price($goods_id, $num, true, $spec, $warehouse_id, $area_id);
	$goods['market_price'] += $spec_price;
	$goods_attr             = get_goods_attr_info($spec, 'pice', $warehouse_id, $area_id);
	$goods_attr_id          = join(',', $spec);

	/* 初始化要插入购物车的基本件数据 */
	$parent = array(
			'user_id'       => $_SESSION['user_id'],
			'session_id'    => SESS_ID,
			'goods_id'      => $goods_id,
			'goods_sn'      => addslashes($goods['goods_sn']),
			'product_id'    => $product_info['product_id'],
			'goods_name'    => addslashes($goods['goods_name']),
			'market_price'  => $goods['market_price'],
			'goods_attr'    => addslashes($goods_attr),
			'goods_attr_id' => $goods_attr_id,
			'is_real'       => $goods['is_real'],
			'extension_code'=> $goods['extension_code'],
			'is_gift'       => 0,
			'is_shipping'   => $goods['is_shipping'],
			'rec_type'      => CART_GENERAL_GOODS,
			'ru_id'			=> $goods['user_id'],
			'model_attr'  	=> $goods['model_attr'], //属性方式
			'warehouse_id'  => $warehouse_id,  //仓库
			'area_id'  		=> $area_id, // 仓库地区
			'ru_id'			=> $goods['ru_id'],
	);

	/* 如果该配件在添加为基本件的配件时，所设置的“配件价格”比原价低，即此配件在价格上提供了优惠， */
	/* 则按照该配件的优惠价格卖，但是每一个基本件只能购买一个优惠价格的“该配件”，多买的“该配件”不享受此优惠 */
	$basic_list = array();
	$data = $db_group->field('parent_id, goods_price')->where('goods_id = '.$goods_id.' AND goods_price < "'.$goods_price.'" AND parent_id = '.$_parent_id.'')->order('goods_price asc')->select();

	if(!empty($data)) {
		foreach ($data as $row) {
			$basic_list[$row['parent_id']] = $row['goods_price'];
		}
	}
	/* 取得购物车中该商品每个基本件的数量 */
	$basic_count_list = array();
	if ($basic_list) {
		if ($_SESSION['user_id']) {
			$data = $db_cart->field('goods_id, SUM(goods_number)|count')->where(array('user_id'=>$_SESSION['user_id'],'parent_id' => '0' , extension_code =>array('neq'=>"package_buy")))->in(array('goods_id'=>array_keys($basic_list)))->order('goods_id asc')->select();
		} else {
			$data = $db_cart->field('goods_id, SUM(goods_number)|count')->where(array('session_id'=>SESS_ID,'parent_id' => '0' , extension_code =>array('neq'=>"package_buy")))->in(array('goods_id'=>array_keys($basic_list)))->order('goods_id asc')->select();
		}
		if(!empty($data)) {
			foreach ($data as $row) {
				$basic_count_list[$row['goods_id']] = $row['count'];
			}
		}
	}
	/* 取得购物车中该商品每个基本件已有该商品配件数量，计算出每个基本件还能有几个该商品配件 */
	/* 一个基本件对应一个该商品配件 */
	if ($basic_count_list) {
		if ($_SESSION['user_id']) {
			$data = $db_cart->field('parent_id, SUM(goods_number)|count')->where(array('user_id' => $_SESSION['user_id'],'goods_id'=>$goods_id,extension_code =>array('neq'=>"package_buy")))->in(array('parent_id'=>array_keys($basic_count_list)))->order('parent_id asc')->select();
		} else {
			$data = $db_cart->field('parent_id, SUM(goods_number)|count')->where(array('session_id' => SESS_ID,'goods_id'=>$goods_id,extension_code =>array('neq'=>"package_buy")))->in(array('parent_id'=>array_keys($basic_count_list)))->order('parent_id asc')->select();
		}
		 
		if(!empty($data)) {
			foreach ($data as $row) {
				$basic_count_list[$row['parent_id']] -= $row['count'];
			}
		}
	}

	/* 循环插入配件 如果是配件则用其添加数量依次为购物车中所有属于其的基本件添加足够数量的该配件 */
	foreach ($basic_list as $parent_id => $fitting_price) {
		/* 如果已全部插入，退出 */
		if ($num <= 0) {
			break;
		}

		/* 如果该基本件不再购物车中，执行下一个 */
		if (!isset($basic_count_list[$parent_id])) {
			continue;
		}

		/* 如果该基本件的配件数量已满，执行下一个基本件 */
		if ($basic_count_list[$parent_id] <= 0) {
			continue;
		}

		/* 作为该基本件的配件插入 */
		$parent['goods_price']  = max($fitting_price, 0) + $spec_price; //允许该配件优惠价格为0
		$parent['goods_number'] = min($num, $basic_count_list[$parent_id]);
		$parent['parent_id']    = $parent_id;

		/* 添加 */
		$db_cart->insert($parent);
		/* 改变数量 */
		$num -= $parent['goods_number'];
	}

	/* 如果数量不为0，作为基本件插入 */
	if ($num > 0) {
		/* 检查该商品是否已经存在在购物车中 */
		if ($_SESSION['user_id']) {
			$row = $db_cart->field('rec_id, goods_number')->find('user_id = "' .$_SESSION['user_id']. '" AND goods_id = '.$goods_id.' AND parent_id = 0 AND goods_attr = "' .get_goods_attr_info($spec).'" AND extension_code <> "package_buy" AND rec_type = "'.CART_GENERAL_GOODS.'" ');
		} else {
			$row = $db_cart->field('rec_id, goods_number')->find('session_id = "' .SESS_ID. '" AND goods_id = '.$goods_id.' AND parent_id = 0 AND goods_attr = "' .get_goods_attr_info($spec).'" AND extension_code <> "package_buy" AND rec_type = "'.CART_GENERAL_GOODS.'" ');
		}
		 
		if($row) {
			//如果购物车已经有此物品，则更新
			$num += $row['goods_number'];
			if(is_spec($spec) && !empty($prod) ) {
				$goods_storage=$product_info['product_number'];
			} else {
				$goods_storage=$goods['goods_number'];
			}
			if (ecjia::config('use_storage') == 0 || $num <= $goods_storage) {
				$goods_price = get_final_price($goods_id, $num, true, $spec, $warehouse_id, $area_id);
				$data =  array(
						'goods_number' => $num,
						'goods_price'  => $goods_price,
						'area_id'	   => $area_id,
				);
				if ($_SESSION['user_id']) {
					$db_cart->where('user_id = "' .$_SESSION['user_id']. '" AND goods_id = '.$goods_id.' AND parent_id = 0 AND goods_attr = "' .get_goods_attr_info($spec).'" AND extension_code <> "package_buy" AND rec_type = "'.CART_GENERAL_GOODS.'" AND warehouse_id = "'.$warehouse_id.'"')->update($data);
				} else {
					$db_cart->where('session_id = "' .SESS_ID. '" AND goods_id = '.$goods_id.' AND parent_id = 0 AND goods_attr = "' .get_goods_attr_info($spec).'" AND extension_code <> "package_buy" AND rec_type = "'.CART_GENERAL_GOODS.'"  AND warehouse_id = "'.$warehouse_id.'"')->update($data);
				}
			} else {
				return new ecjia_error('low_stocks', __('库存不足'));
			}

			$cart_id = $row['rec_id'];
		} else {
			//购物车没有此物品，则插入
			$goods_price = get_final_price($goods_id, $num, true, $spec ,$warehouse_id, $area_id);
			$parent['goods_price']  = max($goods_price, 0);
			$parent['goods_number'] = $num;
			$parent['parent_id']    = 0;
			$cart_id = $db_cart->insert($parent);
		}
	}

	/* 把赠品删除 */
	if ($_SESSION['user_id']) {
		$db_cart->where(array('user_id' => $_SESSION['user_id'] , 'is_gift' => array('neq' => 0)))->delete();
	} else {
		$db_cart->where(array('session_id' => SESS_ID , 'is_gift' => array('neq' => 0)))->delete();
	}

	return $cart_id;
}




/** 
 * 添加商品到购物车(传入goods_sn)
 *
 * @access  public
 * @param   integer $goods_sn   商品编号
 * @param   integer $num        商品数量
 * @param   array   $spec       规格值对应的id数组
 * @param   integer $parent     基本件
 * @return  boolean
 */
function addcart($addgoods){
				
				$dbview 		= RC_Loader::load_app_model('sys_goods_member_viewmodel', 'goods');
				$db_cart 		= RC_Loader::load_app_model('cart_model', 'cart');
				$db_productsinfoview= RC_Loader::load_app_model('productsinfo_viewmodel', 'goods');
				$db_group 		= RC_Loader::load_app_model('group_goods_model', 'goods');
			//	$_parent_id 	= $parent;
				RC_Loader::load_app_func('order', 'orders');
				RC_Loader::load_app_func('goods', 'goods');
				RC_Loader::load_app_func('common', 'goods');
				/* 取得商品信息 */
				$dbview->view = array(
						'member_price' => array(
								'type'  => Component_Model_View::TYPE_LEFT_JOIN,
								'alias' => 'mp',
								'field' => "g.goods_name, g.goods_sn, g.is_on_sale, g.is_real,g.market_price, g.shop_price AS org_price, g.promote_price, g.promote_start_date,g.promote_end_date, g.goods_weight, g.integral, g.extension_code,g.goods_number, g.is_alone_sale, g.is_shipping,IFNULL(mp.user_price, g.shop_price * '$_SESSION[discount]') AS shop_price",
								'on'   	=> "mp.goods_id = g.goods_id AND mp.user_rank = '$_SESSION[user_rank]'"
						)
				);
				$db_productsinfoview->view = array(
						'goods' => array(
								'type'  => Component_Model_View::TYPE_LEFT_JOIN,
								'alias' => 'g',
								'field' => 'g.goods_name',
								'on'   	=> 'p.goods_id=g.goods_id'
						)
				);
				$product =$db_productsinfoview->join(array('goods'))->field('p.*')->find(array('product_sn'=>$addgoods['goods_sn']));

				$goods = $dbview->field('g.*')->find(array('g.goods_sn' => $addgoods['goods_sn'] , 'g.is_delete' => 0));
				if (empty($goods)&&empty($product)) {
					return new ecjia_error('no_goods', __('对不起，指定的商品(编号)不存在！'));
				}
				/* 是否正在销售 */
				if ($goods['is_on_sale'] == 0 && $product==null) {
					return new ecjia_error('addcart_error', __('购买失败'));
				}
				/* 如果是作为配件添加到购物车的，需要先检查购物车里面是否已经有基本件 */
// 				if ($parent > 0) {
// 					if ($_SESSION['user_id']) {
// 						$count = $db_cart->where(array('goods_id' => $parent , 'user_id' => $_SESSION['user_id'] , 'extension_code' => array('neq' => 'package_buy')))->count();
// 					} else {
// 						$count = $db_cart->where(array('goods_id' => $parent , 'session_id' => SESS_ID , 'extension_code' => array('neq' => 'package_buy')))->count();
// 					}
						
// 					if ($count == 0) {
// 						return new ecjia_error('addcart_error', __('对不起，您希望将该商品做为配件购买，可是购物车中还没有该商品的基本件。'));
// 					}
// 				}
				
// 				/* 不是配件时检查是否允许单独销售 */
// 				if (empty($parent) && $goods['is_alone_sale'] == 0) {
// 					return new ecjia_error('addcart_error', __('购买失败'));
// 				}
			
				/* 如果商品有规格则取规格商品信息 配件除外 */
				
				$prod['goods_attr'] =$product['goods_attr'];
				//$prod = $db_productsview->field('p.goods_attr')->find(array('product_sn' => $addgoods['goods_sn']));
				$prod['goods_attr']=explode ('|', $prod['goods_attr']);
					if (is_spec($prod['goods_attr']) && !empty($prod)) {
						$product_info = get_products_info_new($addgoods['goods_sn'], $prod['goods_attr']);//product_sn;
					}
				if (empty($product_info)) {
					$product_info = array('product_number' => '', 'product_id' => 0 , 'goods_attr'=>'');
				}
				
				/* 检查：库存 */
				if (ecjia::config('use_storage') == 1) {
					//检查：商品购买数量是否大于总库存
					if(!empty($goods)){
						if ($addgoods['number'] > $goods['goods_number']) {
							return new ecjia_error('low_stocks', __('库存不足'));
						}
						//商品存在规格 是货品 检查该货品库存
						if (is_spec($prod['goods_attr']) && !empty($prod)) {
							if (!empty($prod['goods_attr'])) {
								/* 取规格的货品库存 */
								if ($addgoods['number'] > $product_info['product_number']) {
									return new ecjia_error('low_stocks', __('库存不足'));
								}
							}
						}
					}
				
				}
				/* 计算商品的促销价格 */
				$spec_price             = spec_price($prod['goods_attr']);
				$goods_price            = get_final_price($addgoods['goods_sn'], $addgoods['number'], true, $prod['goods_attr']);//----
				$goods['market_price'] += $spec_price;
				$goods_attr             = get_goods_attr_info($prod['goods_attr']);
				$goods_attr_id          = join(',', $prod['goods_attr']);
		//	 _dump($product['goods_name'],1);
				/* 初始化要插入购物车的基本件数据 */
		//	_dump(addslashes($goods['goods_name']),1);
				$parent = array(
						'user_id'       => $_SESSION['user_id'],
						'session_id'    => SESS_ID,
			 			'goods_id'      => !empty($product)?$product['goods_id']:$goods['goods_id'],
						'goods_sn'		=> $addgoods['goods_sn'],
						'product_id'    => $product_info['product_id'],
						'goods_name'    => addslashes(empty($goods['goods_name']))?$product['goods_name']:addslashes($goods['goods_name']),
						'market_price'  => $goods['market_price'],
						'goods_attr'    => addslashes($goods_attr),
						'goods_attr_id' => $goods_attr_id,
						'is_real'       => $goods['is_real'],
						'extension_code'=> $goods['extension_code'],
						'is_gift'       => 0,
						'is_shipping'   => $goods['is_shipping'],
						'rec_type'      => CART_GENERAL_GOODS
				);
			//	_dump($parent,1);
				//大循环
				/* 如果该配件在添加为基本件的配件时，所设置的“配件价格”比原价低，即此配件在价格上提供了优惠， */
				/* 则按照该配件的优惠价格卖，但是每一个基本件只能购买一个优惠价格的“该配件”，多买的“该配件”不享受此优惠 */
				$basic_list = array();
				// 	$data = $db_group->field('parent_id, goods_price')->where('goods_id = '.$goods_id.' AND goods_price < "'.$goods_price.'" AND parent_id = '.$_parent_id.'')->order('goods_price asc')->select();
				
				// 	if(!empty($data)) {
				// 		foreach ($data as $row) {
				// 			$basic_list[$row['parent_id']] = $row['goods_price'];
				// 		}
				// 	}
				/* 取得购物车中该商品每个基本件的数量 */
				
				$basic_count_list = array();
				if ($basic_list) {
					if ($_SESSION['user_id']) {
						$data = $db_cart->field('goods_id, SUM(goods_number)|count')->where(array('user_id'=>$_SESSION['user_id'],'parent_id' => '0' , extension_code =>array('neq'=>"package_buy")))->in(array('goods_id'=>array_keys($basic_list)))->order('goods_id asc')->select();
					} else {
						$data = $db_cart->field('goods_id, SUM(goods_number)|count')->where(array('session_id'=>SESS_ID,'parent_id' => '0' , extension_code =>array('neq'=>"package_buy")))->in(array('goods_id'=>array_keys($basic_list)))->order('goods_id asc')->select();
					}
					if(!empty($data)) {
						foreach ($data as $row) {
							$basic_count_list[$row['goods_id']] = $row['count'];
						}
					}
				}
				//_dump($basic_count_list,1);
				/* 取得购物车中该商品每个基本件已有该商品配件数量，计算出每个基本件还能有几个该商品配件 */
				/* 一个基本件对应一个该商品配件 */
				if ($basic_count_list) {
					if ($_SESSION['user_id']) {
						$data = $db_cart->field('parent_id, SUM(goods_number)|count')->where(array('user_id' => $_SESSION['user_id'],'goods_sn'=>$addgoods['goods_sn'],extension_code =>array('neq'=>"package_buy")))->in(array('parent_id'=>array_keys($basic_count_list)))->order('parent_id asc')->select();
					} else {
						$data = $db_cart->field('parent_id, SUM(goods_number)|count')->where(array('session_id' => SESS_ID,'goods_sn'=>$addgoods['goods_sn'],extension_code =>array('neq'=>"package_buy")))->in(array('parent_id'=>array_keys($basic_count_list)))->order('parent_id asc')->select();
					}
						
					if(!empty($data)) {
						foreach ($data as $row) {
							$basic_count_list[$row['parent_id']] -= $row['count'];
						}
					}
				}
				/* 循环插入配件 如果是配件则用其添加数量依次为购物车中所有属于其的基本件添加足够数量的该配件 */
				foreach ($basic_list as $parent_id => $fitting_price) {
					/* 如果已全部插入，退出 */
					if ($addgoods['number'] <= 0) {
						break;
					}
				
					/* 如果该基本件不再购物车中，执行下一个 */
					if (!isset($basic_count_list[$parent_id])) {
						continue;
					}
				
					/* 如果该基本件的配件数量已满，执行下一个基本件 */
					if ($basic_count_list[$parent_id] <= 0) {
						continue;
					}
				
					/* 作为该基本件的配件插入 */
					$parent['goods_price']  = max($fitting_price, 0) + $spec_price; //允许该配件优惠价格为0
					$parent['goods_number'] = min($addgoods['number'], $basic_count_list[$parent_id]);
					$parent['parent_id']    = $parent_id;
					
					/* 添加 */
					$db_cart->insert($parent);
					/* 改变数量 */
					$addgoods['number'] -= $parent['goods_number'];
				}
				/* 如果数量不为0，作为基本件插入 */
				if ($addgoods['number'] > 0) {
					/* 检查该商品是否已经存在在购物车中 */
					if ($_SESSION['user_id']) {
					//	_dump(1,1);
						//_dump(get_goods_attr_info($addgoods['number']),1);
						//$row = $db_cart->field('goods_number')->find('user_id = "' .$_SESSION['user_id']. '" AND goods_sn = '.$addgoods['goods_sn'].' AND parent_id = 0 AND goods_attr = "' .get_goods_attr_info($addgoods['number']).'" AND extension_code <> "package_buy" AND rec_type = "'.CART_GENERAL_GOODS.'" ');
					//	_dump($addgoods['goods_sn'],1);
						$row= $db_cart->field('goods_number')->where(array('user_id'=>$_SESSION['user_id'],'goods_sn'=>$addgoods['goods_sn']))->find();
					} else {
						//$row = $db_cart->field('goods_number')->find('session_id = "' .SESS_ID. '" AND goods_sn = '.$addgoods['goods_sn'].' AND parent_id = 0 AND goods_attr = "' .get_goods_attr_info($addgoods['number']).'" AND extension_code <> "package_buy" AND rec_type = "'.CART_GENERAL_GOODS.'" ');
						$row= $db_cart->field('goods_number')->where(array('user_id'=>SESS_ID,'goods_sn'=>$addgoods['goods_sn']))->find();
					}
				  //   _dump($row,1);
					if($row) {
						//如果购物车已经有此物品，则更新
						$addgoods['number'] += $row['goods_number'];
						//foreach ($prod['goods_attr'] as $k => $v){
							if(is_spec($prod['goods_attr']) && !empty($prod) ) {
								$goods_storage=$product_info['product_number'];
							} else {
								$goods_storage=$goods['goods_number'];
							}
						//}
						if (ecjia::config('use_storage') == 0 || $addgoods['number'] <= $goods_storage) {
							$goods_price = get_final_price($addgoods['goods_sn'], $addgoods['number'], true, $prod['goods_attr']);
							$data =  array(
									'goods_number' => $addgoods['number'],
									'goods_price'  => $goods_price
							);
							if ($_SESSION['user_id']) {
								//$db_cart->where('user_id = "' .$_SESSION['user_id']. '" AND goods_sn = '.$addgoods['goods_sn'].' AND parent_id = 0 AND goods_attr = "' .get_goods_attr_info($addgoods['number']).'" AND extension_code <> "package_buy" AND rec_type = "'.CART_GENERAL_GOODS.'" ')->update($data);
								$row= $db_cart->field('goods_number')->where(array('user_id'=>$_SESSION['user_id'],'goods_sn'=>$addgoods['goods_sn']))->update($data);
							} else {
								//$db_cart->where('session_id = "' .SESS_ID. '" AND goods_sn = '.$addgoods['goods_sn'].' AND parent_id = 0 AND goods_attr = "' .get_goods_attr_info($addgoods['number']).'" AND extension_code <> "package_buy" AND rec_type = "'.CART_GENERAL_GOODS.'" ')->update($data);
								$row= $db_cart->field('goods_number')->where(array('user_id'=>SESS_ID,'goods_sn'=>$addgoods['goods_sn']))->update($data);
							}
						} else {
							return new ecjia_error('low_stocks', __('库存不足'));
						}
					} else {
						//购物车没有此物品，则插入
					//	_dump($parent,1);
						$goods_price = get_final_price($addgoods['goods_sn'], $addgoods['number'], true, $prod['goods_attr']);//goods_id改成$product_sn-----
						$parent['goods_price']  = max($goods_price, 0);
						$parent['goods_number'] = $addgoods['number'];
						$parent['parent_id']    = 0;
						$db_cart->insert($parent);
					}
				}
				
				/* 把赠品删除 */
				if ($_SESSION['user_id']) {
					$db_cart->where(array('user_id' => $_SESSION['user_id'] , 'is_gift' => array('neq' => 0)))->delete();
				} else {
					$db_cart->where(array('session_id' => SESS_ID , 'is_gift' => array('neq' => 0)))->delete();
				}
				//	_dump(1,1);
				return true;
			
}
/**
 * 取指定规格的货品信息
 *
 * @access public
 * @param string $goods_id
 * @param array $spec_goods_attr_id
 * @return array
 */
function get_products_info_new($product_sn, $spec_goods_attr_id) {//$goods_id改成$product_sn
	//_dump($spec_goods_attr_id,1);
//	$spec_goods_attr_id=explode("|",$spec_goods_attr_id);
	//_dump($spec_goods_attr_id,1);
	$db = RC_Loader::load_app_model ('products_model','goods');
	$return_array = array ();
	if (empty ( $spec_goods_attr_id ) || ! is_array ( $spec_goods_attr_id ) || empty ( $product_sn )) {
		return $return_array;
	}
	$goods_attr_array = sort_goods_attr_id_array ( $spec_goods_attr_id );
	if (isset ( $goods_attr_array ['sort'] )) {
		$goods_attr = implode ( '|', $goods_attr_array ['sort'] );
		$return_array = $db->where(array ('product_sn' => $product_sn,'goods_attr' => $goods_attr))->find();
	}
	return $return_array;
}

//存在，更新(编辑)到购物车
function updatecart($updategoods){
		$db_carts = RC_Loader::load_app_model('cart_viewmodel','cart');
		//定义视图选项
		$db_carts->view = array(
				'goods' => array(
						'type'  => Component_Model_View::TYPE_LEFT_JOIN,
						'alias' => 'g',
						'on'    => 'c.goods_id = g.goods_id'
				),
		);

		$data	= array(
				'goods_number'=>	$updategoods['number']
		);
		$count = $db_carts->where(array('rec_id'=>$updategoods['rec_id']))->update($data);
		if($count>0){
			return true;
		}
}
//删除购物车商品(购物车可以批量删除)
function deletecart($deletegoods){
	$db_cart = RC_Loader::load_app_model('cart_model', 'cart');
	$rec_id = explode(',', $deletegoods['rec_id']);
	$db_cart->in(array('rec_id'=> $rec_id, 'session_id' => SESS_ID))->delete();
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
function cashdesk_order_fee($order, $goods, $consignee) {
	// 	$sql = 'SELECT count(*) FROM ' . $GLOBALS['ecs']->table('cart') . " WHERE  `session_id` = '" . SESS_ID. "' AND `extension_code` != 'package_buy' AND `is_shipping` = 0";
	// 	$shipping_count = $GLOBALS['db']->getOne($sql);

	RC_Loader::load_app_func('common','goods');
	RC_Loader::load_app_func('cart','cart');
	$db 	= RC_Loader::load_app_model('cart_model', 'cart');
	$dbview = RC_Loader::load_app_model('cart_exchange_viewmodel', 'cart');
	/* 初始化订单的扩展code */
	if (!isset($order['extension_code'])) {
		$order['extension_code'] = '';
	}

	//     TODO: 团购等促销活动注释后暂时给的固定参数
	$order['extension_code'] = '';
	$group_buy ='';
	//     TODO: 团购功能暂时注释
	//     if ($order['extension_code'] == 'group_buy') {
	//         $group_buy = group_buy_info($order['extension_id']);
	//     }

	$total  = array('real_goods_count' => 0,
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
		$area_id = $consignee['province'];
		//多店铺开启库存管理以及地区后才会去判断
		if ( $area_id > 0 ) {
			//         	$db_goods = RC_Loader::load_app_model('goods_model', 'goods');
			//         	$goods_model_inventory = $db_goods->where(array('goods_id' => $val['goods_id']))->get_field('model_inventory');
			//         	if ($goods_model_inventory > 0 ) {
			$warehouse_db = RC_Loader::load_app_model('warehouse_model', 'warehouse');
			$warehouse = $warehouse_db->where(array('regionId' => $area_id))->find();
			//         		$area = $warehouse['region_id'];
			$warehouse_id = $warehouse['parent_id'];
			$goods[$key]['warehouse_id'] = $warehouse_id;
			$goods[$key]['area_id'] = $area_id;
			//         	}
		}
	}

	$total['saving']    = $total['market_price'] - $total['goods_price'];
	$total['save_rate'] = $total['market_price'] ? round($total['saving'] * 100 / $total['market_price']) . '%' : 0;

	$total['goods_price_formated']  = price_format($total['goods_price'], false);
	$total['market_price_formated'] = price_format($total['market_price'], false);
	$total['saving_formated']       = price_format($total['saving'], false);

	/* 折扣 */
	if ($order['extension_code'] != 'group_buy') {
		RC_Loader::load_app_func('cart','cart');
		$discount = compute_discount();
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
		$invoice_type=ecjia::config('invoice_type');
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

	RC_Loader::load_app_func('bonus','bonus');
	/* 红包 */
	if (!empty($order['bonus_id'])) {
		$bonus          = bonus_info($order['bonus_id']);
		$total['bonus'] = $bonus['type_money'];
	}
	$total['bonus_formated'] = price_format($total['bonus'], false);
	/* 线下红包 */
	if (!empty($order['bonus_kill'])) {

		$bonus  = bonus_info(0,$order['bonus_kill']);
		$total['bonus_kill'] = $order['bonus_kill'];
		$total['bonus_kill_formated'] = price_format($total['bonus_kill'], false);
	}

// 	TODO:暂时不考虑配送费用
// 	/* 配送费用 */
// 	$shipping_cod_fee = NULL;
// 	if ($order['shipping_id'] > 0 && $total['real_goods_count'] > 0) {
// 		$region['country']  = $consignee['country'];
// 		$region['province'] = $consignee['province'];
// 		$region['city']     = $consignee['city'];
// 		$region['district'] = $consignee['district'];

// 		$shipping_method = RC_Loader::load_app_class('shipping_method', 'shipping');
// 		$shipping_info 		= $shipping_method->shipping_area_info($order['shipping_id'], $region);

// 		if (!empty($shipping_info)) {
			 
// 			if ($order['extension_code'] == 'group_buy') {
// 				$weight_price = cart_weight_price(CART_GROUP_BUY_GOODS);
// 			} else {
// 				$weight_price = cart_weight_price();
// 			}

// 			// 查看购物车中是否全为免运费商品，若是则把运费赋为零
// 			if ($_SESSION['user_id']) {
// 				$shipping_count = $db->where(array('user_id' => $_SESSION['user_id'] , 'extension_code' => array('neq' => 'package_buy') , 'is_shipping' => 0))->count();
// 			} else {
// 				$shipping_count = $db->where(array('session_id' => SESS_ID , 'extension_code' => array('neq' => 'package_buy') , 'is_shipping' => 0))->count();
// 			}

// 			//ecmoban模板堂 --zhuo start
// 			if (ecjia::config('freight_model') == 0) {
// 				$total['shipping_fee'] = ($shipping_count == 0 AND $weight_price['free_shipping'] == 1) ? 0 :  $shipping_method->shipping_fee($shipping_info['shipping_code'],$shipping_info['configure'], $weight_price['weight'], $total['goods_price'], $weight_price['number']);
// 				//             	$total['shipping_fee'] = ($shipping_count == 0 AND $weight_price['free_shipping'] == 1) ?0 :  shipping_fee($shipping_info['shipping_code'],$shipping_info['configure'], $weight_price['weight'], $total['goods_price'], $weight_price['number']);
// 			} elseif (ecjia::config('freight_model') == 1) {
// 				$shipping_fee = get_goods_order_shipping_fee($goods, $region, $shipping_info['shipping_code']);
// 				$total['shipping_fee'] = ($shipping_count == 0 AND $weight_price['free_shipping'] == 1) ? 0 :  $shipping_fee['shipping_fee'];
// 				//             	$total['ru_list'] = $shipping_fee['ru_list']; //商家运费详细信息
// 			}

// 			//ecmoban模板堂 --zhuo end
// 			//             $total['shipping_fee'] = ($shipping_count == 0 AND $weight_price['free_shipping'] == 1) ? 0 :  $shipping_method->shipping_fee($shipping_info['shipping_code'],$shipping_info['configure'], $weight_price['weight'], $total['goods_price'], $weight_price['number']);

// 			if (!empty($order['need_insure']) && $shipping_info['insure'] > 0) {
// 				$total['shipping_insure'] = shipping_insure_fee($shipping_info['shipping_code'],$total['goods_price'], $shipping_info['insure']);
// 			} else {
// 				$total['shipping_insure'] = 0;
// 			}

// 			if ($shipping_info['support_cod']) {
// 				$shipping_cod_fee = $shipping_info['pay_fee'];
// 			}
// 		}
// 	}
	$total['shipping_fee'] = 0;
	$total['shipping_insure'] = 0;
	$total['shipping_fee_formated']    = price_format($total['shipping_fee'], false);
	$total['shipping_insure_formated'] = price_format($total['shipping_insure'], false);

	// 购物车中的商品能享受红包支付的总额
	$bonus_amount = compute_discount_amount();
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
		$integral_money = value_of_integral($order['integral']);
		// 使用积分支付
		$use_integral            = min($total['amount'], $max_amount, $integral_money); // 实际使用积分支付的金额
		$total['amount']        -= $use_integral;
		$total['integral_money'] = $use_integral;
		$order['integral']       = integral_of_value($use_integral);
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
		$total['pay_fee']      	= pay_fee($order['pay_id'], $total['amount'], $shipping_cod_fee);
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
		$total['will_get_integral'] = get_give_integral($goods);
	}

	$total['will_get_bonus']        = $order['extension_code'] == 'exchange_goods' ? 0 : price_format(get_total_bonus(), false);
	$total['formated_goods_price']  = price_format($total['goods_price'], false);
	$total['formated_market_price'] = price_format($total['market_price'], false);
	$total['formated_saving']       = price_format($total['saving'], false);

	if ($order['extension_code'] == 'exchange_goods') {
		//         $sql = 'SELECT SUM(eg.exchange_integral) '.
		//                'FROM ' . $GLOBALS['ecs']->table('cart') . ' AS c,' . $GLOBALS['ecs']->table('exchange_goods') . 'AS eg '.
		//                "WHERE c.goods_id = eg.goods_id AND c.session_id= '" . SESS_ID . "' " .
		//                "  AND c.rec_type = '" . CART_EXCHANGE_GOODS . "' " .
		//                '  AND c.is_gift = 0 AND c.goods_id > 0 ' .
		//                'GROUP BY eg.goods_id';
		//         $exchange_integral = $GLOBALS['db']->getOne($sql);
		if ($_SESSION['user_id']) {
			$exchange_integral = $dbview->join('exchange_goods')->where(array('c.user_id' => $_SESSION['user_id'] , 'c.rec_type' => CART_EXCHANGE_GOODS , 'c.is_gift' => 0 ,'c.goods_id' => array('gt' => 0)))->group('eg.goods_id')->sum('eg.exchange_integral');
		} else {
			$exchange_integral = $dbview->join('exchange_goods')->where(array('c.session_id' => SESS_ID , 'c.rec_type' => CART_EXCHANGE_GOODS , 'c.is_gift' => 0 ,'c.goods_id' => array('gt' => 0)))->group('eg.goods_id')->sum('eg.exchange_integral');
		}
		$total['exchange_integral'] = $exchange_integral;
	}
	return $total;
}
