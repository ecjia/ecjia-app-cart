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
		RC_Loader::load_app_func('cart', 'cart');
		recalculate_price();
		$_SESSION['flow_type'] = CART_GENERAL_GOODS;
		/* 初始化 */
		$total = array(
				'goods_price'  => 0, // 本店售价合计（有格式）
				'market_price' => 0, // 市场售价合计（有格式）
				'saving'       => 0, // 节省金额（有格式）
				'save_rate'    => 0, // 节省百分比
				'goods_amount' => 0, // 本店售价合计（无格式）
		);
		
		/* 循环、统计 */
		$cart_dbview = RC_Loader::load_app_model('cart_viewmodel', 'seller');
		$db_goods_attr = RC_Loader::load_app_model('goods_attr_model', 'goods');
		RC_Loader::load_app_func('common', 'goods');
		
		$field = 'c.*, IF(c.parent_id, c.parent_id, c.goods_id) AS pid, goods_thumb, goods_img, original_img, CONCAT(shoprz_brandName,shopNameSuffix) as seller_name';
		$data = $cart_dbview->join(array('goods', 'merchants_shop_information'))
						->field($field)
						->where(array('c.user_id' => $_SESSION['user_id'] , 'rec_type' => CART_GENERAL_GOODS))
						->order(array('ru_id' => 'asc', 'pid' => 'asc', 'parent_id' => 'asc'))
						->select();
		
		/* 用于统计购物车中实体商品和虚拟商品的个数 */
		$virtual_goods_count = 0;
		$real_goods_count    = 0;
		$cart_list = array();
		
		foreach ($data as $row) {
			$total['goods_price']  += $row['goods_price'] * $row['goods_number'];
			$total['market_price'] += $row['market_price'] * $row['goods_number'];
			
			
			$row['subtotal']     = price_format($row['goods_price'] * $row['goods_number'], false);
			$row['formated_goods_price']  = price_format($row['goods_price'], false);
			$row['formated_market_price'] = price_format($row['market_price'], false);
		
			/* 统计实体商品和虚拟商品的个数 */
			if ($row['is_real']) {
				$real_goods_count++;
			} else {
				$virtual_goods_count++;
			}
		
			$goods_attrs = array();
			/* 查询规格 */
			if (trim($row['goods_attr']) != '') {
				$attr_list = $db_goods_attr->field('attr_value')->in(array('goods_attr_id' => $row['goods_attr_id']))->select();
				foreach ($attr_list AS $attr) {
					$row['goods_name'] .= ' [' . $attr['attr_value'] . '] ';
				}
				
				$goods_attr = explode("\n", $row['goods_attr']);
				$goods_attr = array_filter($goods_attr);
				foreach ($goods_attr as  $v) {
					$a = explode(':',$v);
					if (!empty($a[0]) && !empty($a[1])) {
						$goods_attrs[] = array('name'=>$a[0], 'value'=>$a[1]);
					}
				}
			}

// 			TODO:暂无该功能
// 			if ($row['extension_code'] == 'package_buy') {
// 				$row['package_goods_list'] = get_package_goods($row['goods_id']);
// 			}
			
// 			$row['goods_thumb'] = get_image_path($row['goods_id'], $row['goods_thumb'], true);
// 			$row['goods_img'] = get_image_path($row['goods_id'], $row['goods_img'], true);
// 			$row['original_img'] = get_image_path($row['goods_id'], $row['original_img'], true);
			
			$goods_list = array(
					'rec_id'		=> $row['rec_id'],
					'seller_id'		=> $row['ru_id'],
					'goods_id'		=> $row['goods_id'],
					'goods_sn'		=> $row['goods_sn'],
					'goods_name'	=> $row['goods_name'],
					'goods_price'	=> $row['goods_price'],
					'market_price'	=> $row['market_price'],
					'formated_goods_price'	=> $row['formated_goods_price'],
					'formated_market_price'	=> $row['formated_market_price'],
					'goods_number'	=> $row['goods_number'],
					'attr'			=> $row['goods_attr'],
					'goods_attr'	=> $goods_attrs,
					'goods_attr_id'	=> $row['goods_attr_id'],
					'subtotal'		=> $row['subtotal'],
					'img' => array(
							'thumb'	=> get_image_path($row['goods_id'], $row['goods_img'], true),
							'url'	=> get_image_path($row['goods_id'], $row['original_img'], true),
							'small'	=> get_image_path($row['goods_id'], $row['goods_thumb'], true)
					),
			);

			$cart_list[$row['ru_id']]['seller_id'] = $row['ru_id'];
			$cart_list[$row['ru_id']]['seller_name'] = empty($row['seller_name']) ? ecjia::config('shop_name') : $row['seller_name'];
			$cart_list[$row['ru_id']]['id'] = $row['ru_id'];  //多商铺1.2废弃
			$cart_list[$row['ru_id']]['name'] = empty($row['seller_name']) ? ecjia::config('shop_name') : $row['seller_name']; //多商铺1.2废弃
			$cart_list[$row['ru_id']]['goods_list'][] = $goods_list;
		
		}
		$total['goods_amount'] = $total['goods_price'];
		$total['saving'] = price_format($total['market_price'] - $total['goods_price'], false);
		if ($total['market_price'] > 0) {
			$total['save_rate'] = $total['market_price'] ? round(($total['market_price'] - $total['goods_price']) * 100 / $total['market_price']).'%' : 0;
		}
		$total['goods_price']  = price_format($total['goods_price'], false);
		$total['market_price'] = price_format($total['market_price'], false);
		$total['real_goods_count']    = $real_goods_count;
		$total['virtual_goods_count'] = $virtual_goods_count;
		$cart_list = array_merge($cart_list);
		$cart_goods = array('cart_list' => $cart_list, 'total' => $total);
		
		
// 		if (!empty($cart_goods['goods_list'])) {
// 		    foreach ($cart_goods['goods_list'] as $key => $value) {
// 		        unset($cart_goods['goods_list'][$key]['user_id']);
// 		        unset($cart_goods['goods_list'][$key]['session_id']);
// 		        $cart_goods['goods_list'][$key]['img'] = array(
// 		            'thumb'=>API_DATA("PHOTO", $value['goods_img']),
// 		            'url' => API_DATA("PHOTO", $value['original_img']),
// 		            'small' => API_DATA("PHOTO", $value['goods_thumb'])
// 		        );
		        
// 		        if (isset($cart_goods['goods_list'][$key]['product_id'])) {
// 		            unset($cart_goods['goods_list'][$key]['product_id']);
// 		        }
// 		        unset($cart_goods['goods_list'][$key]['goods_thumb']);
		
// 		        if (!empty($value['goods_attr'])) {
// 		            $goods_attr = explode("\n", $value['goods_attr']);
// 		            $goods_attr = array_filter($goods_attr);
// 		            $cart_goods['goods_list'][$key]['goods_attr'] = array();
// 		            foreach ($goods_attr as  $v) {
// 		                $a = explode(':',$v);
// 		                if (!empty($a[0]) && !empty($a[1])) {
// 		                    $cart_goods['goods_list'][$key]['goods_attr'][] = array('name'=>$a[0], 'value'=>$a[1]);
// 		                }
// 		            }
// 		        }
// 		    }
// 		}
		
		//购物车猜你喜欢  api2.4功能
		$db = RC_Loader::load_app_model('goods_model', 'goods');
		$field = "goods_id, goods_name, promote_price, shop_price, market_price, goods_thumb, goods_img, original_img, promote_start_date, promote_end_date";
		$where = array(
				'is_hot' => 1,
				'is_on_sale' => 1,
				'is_alone_sale' => 1,
				'is_delete' => 0,
		);
		if (ecjia::config('review_goods')) {
			$where['review_status'] = array('gt' => 2);
		}
		$rows = $db->field($field)->where($where)
								->order(array('click_count' => 'desc'))
								->limit(8)
								->select();
		
		if (!empty($rows) && is_array($rows)) {
			RC_Loader::load_app_func('common', 'goods');
			RC_Loader::load_app_func('goods', 'goods');
			$list = array();
			foreach ($rows as $key => $v) {
				if ($v['promote_price'] > 0) {
					$promote_price = bargain_price($v['promote_price'], $v['promote_start_date'], $v['promote_end_date']);
				} else {
					$promote_price = '0';
				}
				$list[] = array(
						'goods_id'        => $v['goods_id'],
						'name'            => $v['goods_name'],
						'promote_price' => ($promote_price > 0) ? price_format($promote_price, false) : '',
						'shop_price'    => price_format($v['shop_price'], false),
						'market_price'    => price_format($v['market_price'], false),
						'img' => array(
								'small' => get_image_path($v['goods_id'], $v['goods_thumb'], true),
								'url' => get_image_path($v['goods_id'], $v['original_img'], true),
								'thumb' => get_image_path($v['goods_id'], $v['goods_img'], true)
						)
				);
			}
			$cart_goods['related_goods'] = $list;
		} else {
			$cart_goods['related_goods'] = array();
		}
		
		EM_Api::outPut($cart_goods);
	}
}

// end