<?php
defined('IN_ECJIA') or exit('No permission resources.');
/**
 * 添加到购物车
 * @author royalwang
 *
 */
class create_module implements ecjia_interface {
	
	public function run(ecjia_api & $api) {
	    EM_Api::authSession();

	    $goods_id = _POST('goods_id', 0);
	    $goods_number = _POST('number', 0);
	    $area_id = _POST('area_id', 0);
	    
	    $goods_spec = _POST('spec', array());
	    $rec_type = _POST('rec_type');
	    if (!$goods_number || !$goods_id) {
	        EM_Api::outPut(101);
	    }
	    
	    if ( $area_id > 0 ) {
	    	$db_region = RC_Loader::load_app_model('region_model', 'shipping');
	    	$region_result = $db_region->where(array('region_id' => $area_id))->find();
	    	 
	    	if ($region_result['region_type'] > 2) {//定位为区县
	    		$region_result = $db_region->where(array('region_id' => $region_result['parent_id']))->find();
	    		$area_id = $region_result['parent_id'];
	    	} elseif ($region_result['region_type'] == 2) { //定位为市
	    		$area_id = $region_result['parent_id'];
	    	} else { //定位为省
	    		$area_id = $region_result['region_id'];
	    	}
	    	$warehouse_db = RC_Loader::load_app_model('warehouse_model', 'warehouse');
	    	$warehouse = $warehouse_db->where(array('regionId' => $area_id))->find();
	    	$area_id = $warehouse['region_id'];
	    	$warehouse_id = $warehouse['parent_id'];
	    }
	    
	    RC_Loader::load_app_func('cart','cart');
	    if ($rec_type == 'GROUPBUY_GOODS') {
	    	$object_id = _POST('object_id');
	    	if ($object_id <= 0) {
	    		EM_Api::outPut(101);
	    	}
	    	$result = addto_cart_groupbuy($object_id, $goods_number, $goods_spec, 0, $warehouse_id, $area_id);
	    	unset($_SESSION['cart_id']);
	    } else {
	    	$result = addto_cart($goods_id, $goods_number, $goods_spec, 0, $warehouse_id, $area_id);
	    }
	    
	    
	    // 更新：添加到购物车
	    if (!is_ecjia_error($result)){
			/* 循环、统计 */
			$cart_dbview = RC_Loader::load_app_model('cart_viewmodel', 'seller');
			$db_goods_attr = RC_Loader::load_app_model('goods_attr_model', 'goods');
			RC_Loader::load_app_func('common', 'goods');
			
			$field = 'c.*, IF(c.parent_id, c.parent_id, c.goods_id) AS pid, goods_thumb, goods_img, original_img, CONCAT(shoprz_brandName,shopNameSuffix) as seller_name';
			$data = $cart_dbview->join(array('goods', 'merchants_shop_information'))
							->field($field)
							->where(array('c.user_id' => $_SESSION['user_id'] , 'rec_type' => CART_GENERAL_GOODS, 'rec_id' => $result))
							->select();
			
			
			$goods_list = array();
			if (!empty($data)) {
				foreach ($data as $row) {
					$total['goods_price']  += $row['goods_price'] * $row['goods_number'];
					$total['market_price'] += $row['market_price'] * $row['goods_number'];
					
					
					$row['subtotal']     = price_format($row['goods_price'] * $row['goods_number'], false);
					$row['formated_goods_price']  = price_format($row['goods_price'], false);
					$row['formated_market_price'] = price_format($row['market_price'], false);
				
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
		
	//	 			TODO:暂无该功能
	// 				if ($row['extension_code'] == 'package_buy') {
	// 					$row['package_goods_list'] = get_package_goods($row['goods_id']);
	// 				}
							
					$goods_list = array(
							'rec_id'		=> $row['rec_id'],
							'seller_id'		=> $row['ru_id'],
							'seller_name'	=> empty($row['seller_name']) ? ecjia::config('shop_name') : $row['seller_name'],
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
				}
			}
	        EM_Api::outPut($goods_list);
	    } else {
	    	EM_Api::outPut($result);
	    }
	    
	}
}

// end