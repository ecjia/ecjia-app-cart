<?php
//
//    ______         ______           __         __         ______
//   /\  ___\       /\  ___\         /\_\       /\_\       /\  __ \
//   \/\  __\       \/\ \____        \/\_\      \/\_\      \/\ \_\ \
//    \/\_____\      \/\_____\     /\_\/\_\      \/\_\      \/\_\ \_\
//     \/_____/       \/_____/     \/__\/_/       \/_/       \/_/ /_/
//
//   上海商创网络科技有限公司
//
//  ---------------------------------------------------------------------------------
//
//   一、协议的许可和权利
//
//    1. 您可以在完全遵守本协议的基础上，将本软件应用于商业用途；
//    2. 您可以在协议规定的约束和限制范围内修改本产品源代码或界面风格以适应您的要求；
//    3. 您拥有使用本产品中的全部内容资料、商品信息及其他信息的所有权，并独立承担与其内容相关的
//       法律义务；
//    4. 获得商业授权之后，您可以将本软件应用于商业用途，自授权时刻起，在技术支持期限内拥有通过
//       指定的方式获得指定范围内的技术支持服务；
//
//   二、协议的约束和限制
//
//    1. 未获商业授权之前，禁止将本软件用于商业用途（包括但不限于企业法人经营的产品、经营性产品
//       以及以盈利为目的或实现盈利产品）；
//    2. 未获商业授权之前，禁止在本产品的整体或在任何部分基础上发展任何派生版本、修改版本或第三
//       方版本用于重新开发；
//    3. 如果您未能遵守本协议的条款，您的授权将被终止，所被许可的权利将被收回并承担相应法律责任；
//
//   三、有限担保和免责声明
//
//    1. 本软件及所附带的文件是作为不提供任何明确的或隐含的赔偿或担保的形式提供的；
//    2. 用户出于自愿而使用本软件，您必须了解使用本软件的风险，在尚未获得商业授权之前，我们不承
//       诺提供任何形式的技术支持、使用担保，也不承担任何因使用本软件而产生问题的相关责任；
//    3. 上海商创网络科技有限公司不对使用本产品构建的商城中的内容信息承担责任，但在不侵犯用户隐
//       私信息的前提下，保留以任何方式获取用户信息及商品信息的权利；
//
//   有关本产品最终用户授权协议、商业授权与技术服务的详细内容，均由上海商创网络科技有限公司独家
//   提供。上海商创网络科技有限公司拥有在不事先通知的情况下，修改授权协议的权力，修改后的协议对
//   改变之日起的新授权用户生效。电子文本形式的授权协议如同双方书面签署的协议一样，具有完全的和
//   等同的法律效力。您一旦开始修改、安装或使用本产品，即被视为完全理解并接受本协议的各项条款，
//   在享有上述条款授予的权力的同时，受到相关的约束和限制。协议许可范围以外的行为，将直接违反本
//   授权协议并构成侵权，我们有权随时终止授权，责令停止损害，并保留追究相关责任的权力。
//
//  ---------------------------------------------------------------------------------
//
defined('IN_ECJIA') or exit('No permission resources.');

/**
 * 到家商城购物车类
 * @author 
 */
class cart_bbc {
    
	/**
	 * 到家商城购物车列表格式化处理
	 * @param array $cart_result
	 */
    public static function formated_bbc_cart_list($cart_result = array(), $user_rank = 0, $user_id = 0) 
    {
    	$cart_goods = array('cart_list' => array(), 'total' => $cart_result['total'], 'cart_store_ids' => array());
    	
        if (!empty($cart_result['goods_list'])) {
	       	 foreach ($cart_result['goods_list'] as $row) {
	            if (!isset($cart_goods['cart_list'][$row['store_id']])) {
	                $cart_goods['cart_list'][$row['store_id']] = array(
	                    'store_id'		=> intval($row['store_id']),
	                    'store_name'	=> $row['store_name'],
	                    'manage_mode'   => $row['manage_mode'],
	                    'is_disabled'   => 0,
	                    'disabled_label'=> "",
	                	'store_checked_rec_id' => array()
	                );
	            }
	            $goods_attrs = [];
	            /* 查询规格 */
	            if (trim($row['goods_attr']) != '') {
	                $goods_attr = explode("\n", $row['goods_attr']);
	                $goods_attr = array_filter($goods_attr);
	                foreach ($goods_attr as $v) {
	                    $a = explode(':', $v);
	                    if (!empty($a[0]) && !empty($a[1])) {
	                        $goods_attrs[] = array('name' => $a[0], 'value' => $a[1]);
	                    }
	                }
	            }
	    
	            //goods_list
	            $cart_goods['cart_list'][$row['store_id']]['goods_list'][] = array(
	                'rec_id'	            => intval($row['rec_id']),
	                'goods_id'	            => intval($row['goods_id']),
	                'goods_sn'	            => $row['goods_sn'],
	                'goods_name'	        => rc_stripslashes($row['goods_name']),
	                'goods_price'	        => $row['goods_price'],
	                'market_price'	        => $row['market_price'],
	                'formated_goods_price'	=> $row['formatted_goods_price'],
	                'formated_market_price' => $row['formatted_market_price'],
	                'goods_number'	        => intval($row['goods_number']),
	                'subtotal'		        => $row['subtotal'],
	                'attr'			        => $row['goods_attr'],
	                'goods_attr_id'         => $row['goods_attr_id'],
	                'goods_attr'	        => $goods_attrs,
	                'is_checked'	        => $row['is_checked'],
	                'is_disabled'           => $row['is_disabled'],
	                'disabled_label'        => $row['disabled_label'],
					'img' 					=> array(
													'thumb'	=> empty($row['goods_img']) ? '' : RC_Upload::upload_url($row['goods_img']),
								                    'url'	=> empty($row['original_img']) ? '' : RC_Upload::upload_url($row['original_img']),
								                    'small'	=> empty($row['goods_thumb']) ? '' : RC_Upload::upload_url($row['goods_thumb']),
								                )
	            );
	            //选中的某一店铺购物车id
	            if ($row['is_checked'] == 1) {
	            	$cart_goods['cart_list'][$row['store_id']]['store_checked_rec_id'][] = $row['rec_id'];
	            }
	        }
    	}
    	
    	$cart_goods['cart_list'] = array_merge($cart_goods['cart_list']);
    	
    	//店铺优惠活动
    	$total_discount = 0;
    	foreach ($cart_goods['cart_list'] as &$seller) {
    		/*获取店铺选中购物车所满足的优惠活动*/
    		$store_discount_result = [];
    		if ($seller['store_checked_rec_id']) {
    			$store_discount_result = self::bbc_cart_store_discount(array('store_id' => $seller['store_id'], 'user_id' => $user_id,'user_rank' => $user_rank, 'rec_id' => $seller['store_checked_rec_id']));
    			/* 用于统计购物车中实体商品和虚拟商品的个数 */
    			$virtual_goods_count = 0;
    			$real_goods_count    = 0;
    			//店铺小计
    			$total = array(
    					'goods_price'  => 0, // 本店售价合计（有格式）
    					'market_price' => 0, // 市场售价合计（有格式）
    					'saving'       => 0, // 节省金额（有格式）
    					'save_rate'    => 0, // 节省百分比
    					'goods_amount' => 0, // 本店售价合计（无格式）
    					'goods_number' => 0, // 商品总件数
    					'discount'     => 0
    			);
    			foreach ($seller['goods_list'] as $goods) {
    				if ($goods['is_checked'] == 1) {
    					$total['goods_price']  += $goods['goods_price'] * $goods['goods_number'];
    					$total['market_price'] += $goods['market_price'] * $goods['goods_number'];
    				}
    				$total['goods_number'] += $goods['goods_number'];
    			}
    			//判断优惠超过商品总价时
    			if ($store_discount_result['store_cart_discount'] > $total['goods_price']) {
    				$store_discount_result['store_cart_discount'] = $total['goods_price'];
    			}
    			
    			$total['goods_amount'] = $total['goods_price']; //此处商品金额小计为已减去优惠金额的
    			$total['saving']       = price_format($total['market_price'] - $total['goods_price'], false);
    			if ($total['market_price'] > 0) {
    				$total['save_rate'] = $total['market_price'] ? round(($total['market_price'] - $total['goods_price']) *
    						100 / $total['market_price']).'%' : 0;
    			}
    			
    			$total['unformatted_goods_price']     = sprintf("%.2f", $total['goods_price']);
    			$total['goods_price']  			      = price_format($total['goods_price'], false);
    			$total['unformatted_market_price']    = sprintf("%.2f", $total['market_price']);
    			$total['market_price'] 				  = price_format($total['market_price'], false);
    			$total['real_goods_count']    		  = $real_goods_count;
    			$total['virtual_goods_count'] 		  = $virtual_goods_count;
    			
    			$total['discount']			= $store_discount_result['store_cart_discount'];//用户享受折扣数
    			$total['discount_formated']	= ecjia_price_format($total['discount'], false);
    			
    			$seller['total'] = $total;
    			
    			if (!empty($store_discount_result['store_cart_discount'])) {
    				$total_discount += $store_discount_result['store_cart_discount'];
    			}
    			$seller['favourable_activity'] = $store_discount_result['store_fav_activity'];
    			unset($seller['store_checked_rec_id']);
    			$cart_store_ids[] = $seller['store_id'];
    		}
    	}
    	$cart_goods['total']['discount'] = sprintf("%.2f", $total_discount);
    	$cart_goods['total']['formated_discount'] = ecjia_price_format($total_discount, false);
    	$cart_goods['cart_store_ids'] = $cart_store_ids;
    	
    	return $cart_goods;
    }
    
    
    /**
     * 选中的店铺购物车id满足的优惠活动，返回最优活动
     */
    public static function bbc_cart_store_discount($options)
    {
    	//店铺优惠活动
    	$now = RC_Time::gmtime();
    	$user_rank = ',' . $options['user_rank'] . ',';
    	$db	  = RC_DB::table('favourable_activity');
    	$favourable_list = $db->where('store_id', $options['store_id'])
    						  ->where('start_time', '<=', $now)
    						  ->where('end_time', '>=', $now)
    						  ->whereRaw('CONCAT(",", user_rank, ",") LIKE "%' . $user_rank . '%"')
    						  ->whereIn('act_type', array(FAT_DISCOUNT, FAT_PRICE))
    						  ->get();
    	
    	if (empty($favourable_list)) {
    		return array();
    	}
    	/* 查询购物车商品 */
    	$field = "c.rec_id, c.goods_id, c.goods_price * c.goods_number AS subtotal, g.store_id";
    	$dbview = RC_DB::table('cart as c')->leftJoin('goods as g', RC_DB::raw('c.goods_id'), '=', RC_DB::raw('g.goods_id'));
    	
    	$dbview->where(RC_DB::raw('c.parent_id'), 0)->where(RC_DB::raw('c.is_gift'), 0)->where(RC_DB::raw('c.rec_type'), CART_GENERAL_GOODS)->where(RC_DB::raw('g.is_on_sale'),1)->where(RC_DB::raw('g.is_delete'), 0);
    	
    	$goods_list = $dbview->where('user_id', $options['user_id'])
    					->whereIn('rec_id', $options['rec_id'])
    					->select(RC_DB::raw($field))
    					->get();
    	
    	if (empty($goods_list)) {
    		return array();
    	}
    	
    	/* 店铺购物车选中的rec_id*/
    	$rec_id = $options['rec_id'];
    	
    	$favourable_group = array();
    	
    	if (!empty($favourable_list)) {
    		foreach ($favourable_list as $key => $favourable) {
    			/* 初始化折扣 */
    			$cart_discount = 0;  /* 店铺购物车选中优惠折扣信息,最优惠的*/
    			$total_amount = 0;
    			
    			$is_favourable	= false;
    			$favourable_group[$key] = array(
    					'activity_id'	=> $favourable['act_id'],
    					'activity_name'	=> $favourable['act_name'],
    					'min_amount'	=> $favourable['min_amount'],
    					'max_amount'	=> $favourable['max_amount'],
    					'fav_discount'	=> $favourable['act_type_ext'],
    					'act_type'		=> $favourable['act_type'],
    					'type'			=> $favourable['act_type'] == '1' ? 'price_reduction' : 'price_discount',
    					'type_label'	=> $favourable['act_type'] == '1' ? __('满减') : __('满折'),
    					'can_discount'	=> 0,
    			);
    			if ($favourable['act_range'] == FAR_ALL) {
    				foreach ($goods_list as $goods) {
    					//判断店铺和平台活动
    					if($favourable['store_id'] == $goods['store_id'] || $favourable['store_id'] == 0){
    						$favourable_group[$key]['rec_id'][] = $goods['rec_id'];
    						$amount_sort[$key] = $favourable['min_amount'];
    						/* 计算费用 */
    						$total_amount += $goods['subtotal'];
    					}
    				}
    				if (!isset($favourable_group[$key]['rec_id'])) {
    					unset($favourable_group[$key]);
    				}
    				/* 判断活动，及金额满足条件（超过最大值剔除）*/
    				if (!empty($favourable_group[$key]) && ($total_amount <= $favourable['max_amount'] || $favourable['max_amount'] == 0)) {
    					/* 如果未选择商品*/
    					if ($total_amount == 0) {
    						if ($favourable['act_type'] == '1') {
    							$favourable_group[$key]['label_discount'] = '购满'.$favourable['min_amount'].',可减'.$favourable['act_type_ext'];
    						} else {
    							$favourable_group[$key]['label_discount'] = '购满'.$favourable['min_amount'].',可打'. $favourable['act_type_ext']/10 .'折';
    						}
    					} elseif ($total_amount < $favourable['min_amount']) {
    						if ($favourable['act_type'] == '1') {
    							$favourable_group[$key]['label_discount'] = '购满'.$favourable['min_amount'].'可减'.$favourable['act_type_ext'].'，还差'.($favourable['min_amount']-$total_amount);
    						} else {
    							$favourable_group[$key]['label_discount'] = '购满'.$favourable['min_amount'].',可打'. $favourable['act_type_ext']/10 .'折';
    						}
    					} else {
    						if ($favourable['act_type'] == '1') {
    							$favourable_group[$key]['label_discount'] = '购满'.$favourable['min_amount'].',可减'.$favourable['act_type_ext'];
    							$cart_discount += $favourable['act_type_ext'];
    							$cart_discount_temp[$key] = $favourable['act_type_ext'];
    							$favourable_group[$key]['can_discount'] = sprintf("%.2f", $cart_discount);
    						} else {
    							$discount = $total_amount - ($total_amount*$favourable['act_type_ext']/100);
    							$favourable_group[$key]['label_discount'] = '已购满'.$total_amount.',已减'. $discount;
    							$cart_discount += $total_amount - ($total_amount*$favourable['act_type_ext']/100);
    							$favourable_group[$key]['can_discount'] = sprintf("%.2f", $cart_discount);
    							$cart_discount_temp[$key] = $cart_discount;
    						}
    					}
    				} else {
    					unset($favourable_group[$key]);
    				}
    			} elseif ($favourable['act_range'] == FAR_GOODS) {
    				foreach ($goods_list as $goods) {
    					if ($favourable['store_id'] == $goods['store_id'] || $favourable['store_id'] == 0) {
    						if (strpos(',' . $favourable['act_range_ext'] . ',', ',' . $goods['goods_id'] . ',') !== false) {
    							$favourable_group[$key]['rec_id'][] = $goods['rec_id'];
    							$amount_sort[$key] = $favourable['min_amount'];
    							$total_amount += $goods['subtotal'];
    						}
    					}
    				}
    				if (!isset($favourable_group[$key]['rec_id'])) {
    					unset($favourable_group[$key]);
    				}
    				/* 判断活动，及金额满足条件（超过最大值剔除）*/
    				if (!empty($favourable_group[$key]) && ($total_amount <= $favourable['max_amount'] || $favourable['max_amount'] == 0)) {
    					/* 如果未选择商品*/
    					if ($total_amount == 0) {
    						if ($favourable['act_type'] == '1') {
    							$favourable_group[$key]['label_discount'] = '购满'.$favourable['min_amount'].',可减'.$favourable['act_type_ext'];
    						} else {
    							$favourable_group[$key]['label_discount'] = '购满'.$favourable['min_amount'].',可打'. $favourable['act_type_ext']/10 .'折';
    						}
    					} elseif ($total_amount < $favourable['min_amount']) {
    						if ($favourable['act_type'] == '1') {
    							$favourable_group[$key]['label_discount'] = '已购满'.$favourable['min_amount'].'可减'.$favourable['act_type_ext'].'，还差'.($favourable['min_amount']-$total_amount);
    						} else {
    							$favourable_group[$key]['label_discount'] = '购满'.$favourable['min_amount'].',可打'. $favourable['act_type_ext']/10 .'折';
    						}
    					} else {
    						if ($favourable['act_type'] == '1') {
    							$favourable_group[$key]['label_discount'] = '购满'.$favourable['min_amount'].',可减'.$favourable['act_type_ext'];
    							$cart_discount += $favourable['act_type_ext'];
    							$favourable_group[$key]['can_discount'] = sprintf("%.2f", $cart_discount);
    							$cart_discount_temp[$key] = $favourable['act_type_ext'];
    						} else {
    							$discount = $total_amount - ($total_amount*$favourable['act_type_ext']/100);
    							$favourable_group[$key]['label_discount'] = '已购满'.$total_amount.',已减'. $discount;
    							$cart_discount += $total_amount - ($total_amount*$favourable['act_type_ext']/100);
    							$favourable_group[$key]['can_discount'] = sprintf("%.2f", $cart_discount);
    							$cart_discount_temp[$key] = $cart_discount;
    						}
    					}
    				} else {
    					unset($favourable_group[$key]);
    				}
    			} else {
    				continue;
    			}
    		}
    		$cart_discount = max($cart_discount_temp);
    		//优惠金额不能超过订单本身
    		if ($total_amount && $discount > $total_amount) {
    			$cart_discount = $total_amount;
    		}
    		 
    		if (!empty($amount_sort) && !empty($favourable_group)) {
    			array_multisort($amount_sort, SORT_ASC, $favourable_group);
    		}
    		 
    		//获取最优惠的活动信息
    		$best_fav_key = array_search(max($cart_discount_temp),$cart_discount_temp);
    		
    		return array('store_fav_activity' => $favourable_group[$best_fav_key], 'store_cart_discount' => $cart_discount);
    	}
    }
    
    /**
     * 商家购物车划分，含配送方式
     */
    public static function store_cart_goods($cart_goods = array(), $consignee = array())
    {
    	if (!empty($cart_goods['cart_list'])) {
    		foreach ($cart_goods['cart_list'] as $key => $val) {
    			$store_shipping_list = self::store_shipping_list($val, $consignee, $val['store_id']);
    			$val['shipping'] = $store_shipping_list;
    			$val['goods_amount'] = sprintf("%.2f", $val['total']['goods_amount']);
    			unset($val['total']);
    			unset($val['favourable_activity']);
    			$store_cart_goods [] = $val;
    		}
    	}
    	return $store_cart_goods;
    }
    
    /**
     * 商家配送方式列表
     */
    public static function store_shipping_list($store_goods_list, $consignee, $store_id)
    {
    	$region = array($consignee['country'], $consignee['province'], $consignee['city'], $consignee['district'], $consignee['street']);
    	if (empty($store_goods_list)) {
    		return [];
    	}
    	$is_free_ship = 0;
    	$shipping_count = 0;
    	$cart_weight_price['weight'] 		= 0;
    	$cart_weight_price['amount'] 		= 0;
    	$cart_weight_price['number'] 		= 0;
    	
    	foreach ($store_goods_list as $key => $goods) {
    		if($goods['is_shipping'] == 1) {
    			$shipping_count ++;
    		}
    	
    		$cart_weight_price['weight'] += floatval($goods['goodsWeight']) * $goods['goods_number'];
    		$cart_weight_price['amount'] += floatval($goods['goods_price']) * $goods['goods_number'];
    		$cart_weight_price['number'] += $goods['goods_number'];
    	}
    	if($shipping_count == count($store_goods_list)) {
    		//全部包邮
    		$is_free_ship = 1;
    	}
    	
    	$shipping_list = ecjia_shipping::availableUserShippings($region, $store_id);
    	$shipping_list_new = [];
    	
    	if($shipping_list) {
    		RC_Loader::load_app_class('cart', 'cart', false);
    		foreach ($shipping_list as $key => $row) {
    			// O2O的配送费用计算传参调整 参考flow/checkOrder
    			if (in_array($row['shipping_code'], ['ship_o2o_express','ship_ecjia_express'])) {
    				//配送费
    				$shipping_fee = self::o2o_shipping_fee($cart_weight_price, $is_free_ship, $store_id, $consignee, $row);
    				//配送时间
    				$shipping_cfg = ecjia_shipping::unserializeConfig($row['configure']);
    				/* 获取最后可送的时间（当前时间+需提前下单时间）*/
    				$time = RC_Time::local_date('H:i', RC_Time::gmtime() + $shipping_cfg['last_order_time'] * 60);
    				
    				if (empty($shipping_cfg['ship_time'])) {
    					unset($shipping_list[$key]);
    					continue;
    				}
    				$shipping_list[$key]['shipping_date'] = array();
    				$ship_date = 0;
    				
    				if (empty($shipping_cfg['ship_days'])) {
    					$shipping_cfg['ship_days'] = 7;
    				}
    				
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
    				
    			} else {
    				$shipping_fee = $is_free_ship ? 0 : ecjia_shipping::fee($row['shipping_area_id'], $cart_weight_price['weight'], $cart_weight_price['amount'], $cart_weight_price['number']);
    			}
    			//上门取货 自提插件 获得提货时间
    			if($row['shipping_code'] == 'ship_cac') {
    				$shipping_list[$key]['expect_pickup_date'] = cart::get_ship_cac_date_by_store($store_id, $row['shipping_id']);
    				$shipping_list[$key]['expect_pickup_date_default'] = $shipping_list[$key]['expect_pickup_date'][0]['date'] . ' ' . $shipping_list[$key]['expect_pickup_date'][0]['time'][0]['start_time'] . '-' . $shipping_list[$key]['expect_pickup_date'][0]['time'][0]['end_time'];
    			}
    	
    			$shipping_list[$key]['shipping_fee']        = $shipping_fee;
    			$shipping_list[$key]['format_shipping_fee'] = ecjia_price_format($shipping_fee, false);
    			unset($shipping_list[$key]['shipping_desc']);
    			unset($shipping_list[$key]['configure']);
    		}
    	}
    	$shipping_list = array_values($shipping_list);
    	return $shipping_list;
    }
    
    /**
     * 商家配送及o2o配送费获取
     */
    public static function o2o_shipping_fee($cart_weight_price, $is_free_ship, $store_id, $consignee, $shipping_val)
    {
    	$store_info = RC_DB::table('store_franchisee')->where('store_id', $store_id)->where('shop_close', '0')->first();
    	$from = ['latitude' => $store_info['latitude'], 'longitude' => $store_info['longitude']];
    	$to = ['latitude' => $consignee['location']['latitude'], 'longitude' => $consignee['location']['longitude']];
    	$distance = Ecjia\App\User\Location::getDistance($from, $to);
    	$shipping_fee = $is_free_ship ? 0 : ecjia_shipping::fee($shipping_val['shipping_area_id'], $distance, $cart_weight_price['amount'], $cart_weight_price['number']);
    	 return $shipping_fee;
    }
}

// end