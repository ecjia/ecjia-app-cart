<?php
defined('IN_ECJIA') or exit('No permission resources.');
/**
 * 添加到购物车
 * @author royalwang
 *
 */
class create_module extends api_front implements api_interface {
    public function handleRequest(\Royalcms\Component\HttpKernel\Request $request) {

    	$this->authSession();

	    $goods_id		= $this->requestData('goods_id', 0);
	    $goods_number	= $this->requestData('number', 1);
	    $location		= $this->requestData('location', array());
	    //TODO:目前强制坐标
// 		$location = array(
// 				'latitude'	=> '31.235450744628906',
// 				'longitude' => '121.41641998291016',
// 		);
	    $goods_spec		= $this->requestData('spec', array());
	    $rec_type		= $this->requestData('rec_type', 0);


// 	    $result = RC_Api::api('cart', 'cart_manage', array('goods_id' => $goods_id, 'goods_number' => $goods_number, 'goods_spec' => $goods_spec, 'rec_type' => $rec_type, 'location' => $location));

	    RC_Loader::load_app_func('cart', 'cart');
	    if ($rec_type == CART_GROUP_BUY_GOODS) {
	        //TODO:1 团购
	    	$object_id = $this->requestData('object_id');
	    	if ($object_id <= 0) {
	    		return new ecjia_error(101, '参数错误');
	    	}
	    	$result = addto_cart_groupbuy($object_id, $goods_number, $goods_spec);
	    	unset($_SESSION['cart_id']);
	    } elseif ($rec_type == CART_EXCHANGE_GOODS) {
	    	//TODO:积分兑换处理
	    	$options = array('goods_id' => $goods_id);
	    	$result = RC_Api::api('cart', 'exchange_buy', $options);
	    	if (is_ecjia_error($result)) {
	    		return $result;
	    	}
	    } else {
	    	unset($_SESSION['flow_type']);
	    	if (!$goods_id) {
	    		return new ecjia_error('not_found_goods', '请选择您所需要购买的商品！');
	    	}
	    	$store_id_group = array();
	    	/* 根据经纬度查询附近店铺id*/
	    	if (isset($location['latitude']) && !empty($location['latitude']) && isset($location['longitude']) && !empty($location['longitude'])) {
	    		$geohash = RC_Loader::load_app_class('geohash', 'store');
	    		$geohash_code = $geohash->encode($location['latitude'] , $location['longitude']);
	    		$geohash_code = substr($geohash_code, 0, 5);
	    		$store_id_group = RC_Api::api('store', 'neighbors_store_id', array('geohash' => $geohash_code));
	    	} else {
	    		return new ecjia_error('location_error', '请选择有效的收货地址！');
	    	}
	    	
	    	$result = RC_Api::api('cart', 'cart_manage', array('goods_id' => $goods_id, 'goods_number' => $goods_number, 'goods_spec' => $goods_spec, 'rec_type' => $rec_type, 'store_group' => $store_id_group));
// 	    	$result = addto_cart($goods_id, $goods_number, $goods_spec, 0, $warehouse_id, $area_id);
	    }

	    // 更新：添加到购物车
	    if (!is_ecjia_error($result)){
			/* 循环、统计 */
			$cart_dbview = RC_Model::model('cart/cart_viewmodel');
			$db_goods_attr = RC_Model::model('goods/goods_attr_model');
			RC_Loader::load_app_func('common', 'goods');

			$field = 'c.*, goods_thumb, goods_img, original_img, s.merchants_name as store_name';
			$row = $cart_dbview->join(array('goods', 'store_franchisee'))
							->field($field)
							->where(array('c.user_id' => $_SESSION['user_id'] , 'rec_type' => CART_GENERAL_GOODS, 'rec_id' => $result))
							->find();
			
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
						$goods_attrs[] = array('name' => $a[0], 'value' => $a[1]);
					}
				}
			}

//  		TODO:暂无该功能
// 			if ($row['extension_code'] == 'package_buy') {
// 				$row['package_goods_list'] = get_package_goods($row['goods_id']);
// 			}

			$goods_list = array(
					'rec_id'		=> $row['rec_id'],
					'seller_id'		=> $row['store_id'],
					'seller_name'	=> empty($row['store_name']) ? ecjia::config('shop_name') : $row['store_name'],
					'store_id'		=> $row['store_id'],
					'store_name'	=> empty($row['store_name']) ? ecjia::config('shop_name') : $row['store_name'],
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
							'thumb'	=> !empty($row['goods_img']) ? RC_Upload::upload_url($row['goods_img']) : RC_Uri::admin_url('statics/images/nopic.png'),
							'url'	=> !empty($row['original_img']) ? RC_Upload::upload_url($row['original_img']) : RC_Uri::admin_url('statics/images/nopic.png'),
							'small'	=> !empty($row['goods_thumb']) ? RC_Upload::upload_url($row['goods_thumb']) : RC_Uri::admin_url('statics/images/nopic.png'),
					),
			);
	        return $goods_list;
	    } else {
	    	return $result;
	    }

	}
}

// end
