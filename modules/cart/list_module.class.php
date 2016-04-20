<?php
defined('IN_ECJIA') or exit('No permission resources.');
/**
 * 购物车列表
 * @author royalwang
 *
 */
class list_module implements ecjia_interface {
	
	public function run(ecjia_api & $api) {
		
		EM_Api::authSession();
		$location = _POST('location');

		$cart_result = RC_Api::api('cart', 'cart_list', array('location' => $location));
		if (is_ecjia_error($result)) {
			return $result;
		}

		$cart_goods = array('cart_list' => array(), 'total' => $cart_result['total']);
		if (!empty($cart_result['goods_list'])) {
			foreach ($cart_result['goods_list'] as $row) {
				if (!isset($cart_goods['cart_list'][$row['ru_id']])) {
					$cart_goods['cart_list'][$row['ru_id']] = array(
							'seller_id'		=> intval($row['ru_id']),
							'seller_name'	=> $row['seller_name'],
					);
				}
				$goods_attrs = null;
				/* 查询规格 */
				if (trim($row['goods_attr']) != '') {
					$goods_attr = explode("\n", $row['goods_attr']);
					$goods_attr = array_filter($goods_attr);
					foreach ($goods_attr as  $v) {
						$a = explode(':',$v);
						if (!empty($a[0]) && !empty($a[1])) {
							$goods_attrs[] = array('name' => $a[0], 'value' => $a[1]);
						}
					}
				}
				
				$cart_goods['cart_list'][$row['ru_id']]['goods_list'][] = array(
						'rec_id'	=> intval($row['rec_id']),
						'goods_id'	=> intval($row['goods_id']),
						'goods_sn'	=> $row['goods_sn'],
						'goods_name'	=> $row['goods_name'],
						'goods_price'	=> $row['unformatted_goods_price'],
						'market_price'	=> $row['unformatted_market_price'],
						'formated_goods_price'	=> $row['goods_price'],
						'formated_market_price' => $row['market_price'],
						'goods_number'	=> intval($row['goods_number']),
						'subtotal'		=> $row['subtotal'],
						'goods_attr_id' => $row['goods_attr_id'],
						'attr'			=> $row['goods_attr'],
						'goods_attr'	=> $goods_attrs,
						'img' => array(
							'thumb'	=> RC_Upload::upload_url($row['goods_img']),
							'url'	=> RC_Upload::upload_url($row['original_img']),
							'small'	=> RC_Upload::upload_url($row['goods_img']),
						)
				);
			}
		}
		$cart_goods['cart_list'] = array_merge($cart_goods['cart_list']);
				
		//购物车猜你喜欢  api2.4功能
		$options = array(
				'intro'		=> 'hot',
				'sort'		=> array('sort_order' => 'asc', 'goods_id' => 'desc'),
				'page'		=> 1,
				'size'		=> 8,
				'location'	=> $location,
		);
		$result = RC_Api::api('goods', 'goods_list', $options);
		
	
		$cart_goods['related_goods'] = array();
		if (!empty($result['list'])) {
			$mobilebuy_db = RC_Loader::load_app_model('goods_activity_model', 'goods');
			/* 手机专享*/
			$result_mobilebuy = ecjia_app::validate_application('mobilebuy');
			$is_active = ecjia_app::is_active('ecjia.mobilebuy');
			foreach ($result['list'] as $val) {
				/* 判断是否有促销价格*/
				$price = ($val['unformatted_shop_price'] > $val['unformatted_promote_price'] && $val['unformatted_promote_price'] > 0) ? $val['unformatted_promote_price'] : $val['unformatted_shop_price'];
				$activity_type = ($val['unformatted_shop_price'] > $val['unformatted_promote_price'] && $val['unformatted_promote_price'] > 0) ? 'PROMOTE_GOODS' : 'GENERAL_GOODS';
				/* 计算节约价格*/
				$saving_price = ($val['unformatted_shop_price'] > $val['unformatted_promote_price'] && $val['unformatted_promote_price'] > 0) ? $val['unformatted_shop_price'] - $val['unformatted_promote_price'] : (($val['unformatted_market_price'] > 0 && $val['unformatted_market_price'] > $val['unformatted_shop_price']) ? $val['unformatted_market_price'] - $val['unformatted_shop_price'] : 0);
				 
				$mobilebuy_price = $object_id = 0;
				if (!is_ecjia_error($result_mobilebuy) && $is_active) {
					$mobilebuy = $mobilebuy_db->find(array(
							'goods_id'	 => $val['goods_id'],
							'start_time' => array('elt' => RC_Time::gmtime()),
							'end_time'	 => array('egt' => RC_Time::gmtime()),
							'act_type'	 => GAT_MOBILE_BUY,
					));
					if (!empty($mobilebuy)) {
						$ext_info = unserialize($mobilebuy['ext_info']);
						$mobilebuy_price = $ext_info['price'];
						if ($mobilebuy_price < $price) {
							$val['promote_price'] = price_format($mobilebuy_price);
							$object_id		= $mobilebuy['act_id'];
							$activity_type	= 'MOBILEBUY_GOODS';
							$saving_price = ($val['unformatted_shop_price'] - $mobilebuy_price) > 0 ? $val['unformatted_shop_price'] - $mobilebuy_price : 0;
						}
					}
				}
				 
				$cart_goods['related_goods'][] = array(
						'goods_id'		=> $val['goods_id'],
						'id'			=> $val['goods_id'],
						'name'			=> $val['name'],
						'market_price'	=> $val['market_price'],
						'shop_price'	=> $val['shop_price'],
						'promote_price'	=> $val['promote_price'],
						'img' => array(
								'thumb'	=> $val['goods_img'],
								'url'	=> $val['original_img'],
								'small'	=> $val['goods_thumb']
						),
						'activity_type' => $activity_type,
						'object_id'		=> $object_id,
						'saving_price'	=>	$saving_price,
						'formatted_saving_price' => $saving_price > 0 ? '已省'.$saving_price.'元' : '',
						'seller_id'		=> $val['seller_id'],
						'seller_name'	=> $val['seller_name'],
				);
			}
		}
		
		return $cart_goods;
		
		
		
		
// 		RC_Loader::load_app_func('cart', 'cart');
// 		recalculate_price();
// 		$_SESSION['flow_type'] = CART_GENERAL_GOODS;
// 		/* 初始化 */
// 		$total = array(
// 				'goods_price'  => 0, // 本店售价合计（有格式）
// 				'market_price' => 0, // 市场售价合计（有格式）
// 				'saving'       => 0, // 节省金额（有格式）
// 				'save_rate'    => 0, // 节省百分比
// 				'goods_amount' => 0, // 本店售价合计（无格式）
// 		);
		
// 		/* 循环、统计 */
// 		$cart_dbview = RC_Loader::load_app_model('cart_viewmodel', 'seller');
// 		$db_goods_attr = RC_Loader::load_app_model('goods_attr_model', 'goods');
// 		RC_Loader::load_app_func('common', 'goods');
		
// 		$field = 'c.*, IF(c.parent_id, c.parent_id, c.goods_id) AS pid, goods_thumb, goods_img, original_img, CONCAT(shoprz_brandName,shopNameSuffix) as seller_name';
// 		$data = $cart_dbview->join(array('goods', 'merchants_shop_information'))
// 		->field($field)
// 		->where(array('c.user_id' => $_SESSION['user_id'] , 'rec_type' => CART_GENERAL_GOODS))
// 		->order(array('ru_id' => 'asc', 'pid' => 'asc', 'parent_id' => 'asc'))
// 		->select();
		
// 		/* 用于统计购物车中实体商品和虚拟商品的个数 */
// 		$virtual_goods_count = 0;
// 		$real_goods_count    = 0;
// 		$cart_list = array();
		
// 		foreach ($data as $row) {
// 			$total['goods_price']  += $row['goods_price'] * $row['goods_number'];
// 			$total['market_price'] += $row['market_price'] * $row['goods_number'];
				
				
// 			$row['subtotal']     = price_format($row['goods_price'] * $row['goods_number'], false);
// 			$row['formated_goods_price']  = price_format($row['goods_price'], false);
// 			$row['formated_market_price'] = price_format($row['market_price'], false);
		
// 			/* 统计实体商品和虚拟商品的个数 */
// 			if ($row['is_real']) {
// 				$real_goods_count++;
// 			} else {
// 				$virtual_goods_count++;
// 			}
		
// 			$goods_attrs = array();
// 			/* 查询规格 */
// 			if (trim($row['goods_attr']) != '') {
// 				$attr_list = $db_goods_attr->field('attr_value')->in(array('goods_attr_id' => $row['goods_attr_id']))->select();
// 				foreach ($attr_list AS $attr) {
// 					$row['goods_name'] .= ' [' . $attr['attr_value'] . '] ';
// 				}
		
// 				$goods_attr = explode("\n", $row['goods_attr']);
// 				$goods_attr = array_filter($goods_attr);
// 				foreach ($goods_attr as  $v) {
// 					$a = explode(':',$v);
// 					if (!empty($a[0]) && !empty($a[1])) {
// 						$goods_attrs[] = array('name'=>$a[0], 'value'=>$a[1]);
// 					}
// 				}
// 			}
				
// 			$goods_list = array(
// 					'rec_id'		=> $row['rec_id'],
// 					'seller_id'		=> $row['ru_id'],
// 					'goods_id'		=> $row['goods_id'],
// 					'goods_sn'		=> $row['goods_sn'],
// 					'goods_name'	=> $row['goods_name'],
// 					'goods_price'	=> $row['goods_price'],
// 					'market_price'	=> $row['market_price'],
// 					'formated_goods_price'	=> $row['formated_goods_price'],
// 					'formated_market_price'	=> $row['formated_market_price'],
// 					'goods_number'	=> $row['goods_number'],
// 					'attr'			=> $row['goods_attr'],
// 					'goods_attr'	=> $goods_attrs,
// 					'goods_attr_id'	=> $row['goods_attr_id'],
// 					'subtotal'		=> $row['subtotal'],
// 					'img' => array(
// 							'thumb'	=> get_image_path($row['goods_id'], $row['goods_img'], true),
// 							'url'	=> get_image_path($row['goods_id'], $row['original_img'], true),
// 							'small'	=> get_image_path($row['goods_id'], $row['goods_thumb'], true)
// 					),
// 			);
		
// 			$cart_list[$row['ru_id']]['seller_id'] = $row['ru_id'];
// 			$cart_list[$row['ru_id']]['seller_name'] = empty($row['seller_name']) ? ecjia::config('shop_name') : $row['seller_name'];
// 			$cart_list[$row['ru_id']]['id'] = $row['ru_id'];  //多商铺1.2废弃
// 			$cart_list[$row['ru_id']]['name'] = empty($row['seller_name']) ? ecjia::config('shop_name') : $row['seller_name']; //多商铺1.2废弃
// 			$cart_list[$row['ru_id']]['goods_list'][] = $goods_list;
		
// 		}
// 		$total['goods_amount'] = $total['goods_price'];
// 		$total['saving'] = price_format($total['market_price'] - $total['goods_price'], false);
// 		if ($total['market_price'] > 0) {
// 			$total['save_rate'] = $total['market_price'] ? round(($total['market_price'] - $total['goods_price']) * 100 / $total['market_price']).'%' : 0;
// 		}
// 		$total['goods_price']  = price_format($total['goods_price'], false);
// 		$total['market_price'] = price_format($total['market_price'], false);
// 		$total['real_goods_count']    = $real_goods_count;
// 		$total['virtual_goods_count'] = $virtual_goods_count;
// 		$cart_list = array_merge($cart_list);
// 		$cart_goods = array('cart_list' => $cart_list, 'total' => $total);
	}
}

// end