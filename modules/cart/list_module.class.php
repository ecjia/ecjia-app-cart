<?php
defined('IN_ECJIA') or exit('No permission resources.');
/**
 * 购物车列表
 * @author royalwang
 *
 */
class list_module extends api_front implements api_interface {
    public function handleRequest(\Royalcms\Component\HttpKernel\Request $request) {

    	$this->authSession();
    	
    	if ($_SESSION['user_id'] <= 0) {
    		return new ecjia_error(100, 'Invalid session');
    	}
    	RC_Loader::load_app_func('cart', 'cart');
    	//recalculate_price(); //后续方法重新计算
		$location = $this->requestData('location', array());

		$seller_id = $this->requestData('seller_id', 0);

		//TODO:目前强制坐标
// 	    $location = array(
// 	        'latitude'	=> '31.235450744628906',
// 	        'longitude' => '121.41641998291016',
// 	    );

		if (isset($location['latitude']) && !empty($location['latitude']) && isset($location['longitude']) && !empty($location['longitude'])) {
			$geohash = RC_Loader::load_app_class('geohash', 'store');
			$geohash_code = $geohash->encode($location['latitude'] , $location['longitude']);
			$geohash_code = substr($geohash_code, 0, 5);
			$store_id_group = RC_Api::api('store', 'neighbors_store_id', array('geohash' => $geohash_code));
			if (!empty($seller_id) && !in_array($seller_id, $store_id_group)) {
				return new ecjia_error('location_beyond', '店铺距离过远！');
			} elseif (!empty($seller_id)) {
				$store_id_group = array($seller_id);
			}
		} else {
			return new ecjia_error('location_error', '请定位您当前所在地址！');
		}

		$cart_result = RC_Api::api('cart', 'cart_list', array('store_group' => $store_id_group, 'flow_type' => CART_GENERAL_GOODS));
		
		return formated_cart_list($cart_result);
// 		//购物车猜你喜欢  api2.4功能
// 		$options = array(
// 				'intro'		=> 'hot',
// 				'sort'		=> array('sort_order' => 'asc', 'goods_id' => 'desc'),
// 				'page'		=> 1,
// 				'size'		=> 8,
// 				'location'	=> $location,
// 		);

// 		$result = RC_Api::api('goods', 'goods_list', $options);

// 		$cart_goods['related_goods'] = array();
// 		if (!empty($result['list'])) {
// 			$mobilebuy_db = RC_Model::model('goods/goods_activity_model');
// 			/* 手机专享*/
// 			$result_mobilebuy = ecjia_app::validate_application('mobilebuy');
// 			$is_active = ecjia_app::is_active('ecjia.mobilebuy');
// 			foreach ($result['list'] as $val) {
// 				/* 判断是否有促销价格*/
// 				$price = ($val['unformatted_shop_price'] > $val['unformatted_promote_price'] && $val['unformatted_promote_price'] > 0) ? $val['unformatted_promote_price'] : $val['unformatted_shop_price'];
// 				$activity_type = ($val['unformatted_shop_price'] > $val['unformatted_promote_price'] && $val['unformatted_promote_price'] > 0) ? 'PROMOTE_GOODS' : 'GENERAL_GOODS';
// 				/* 计算节约价格*/
// 				$saving_price = ($val['unformatted_shop_price'] > $val['unformatted_promote_price'] && $val['unformatted_promote_price'] > 0) ? $val['unformatted_shop_price'] - $val['unformatted_promote_price'] : (($val['unformatted_market_price'] > 0 && $val['unformatted_market_price'] > $val['unformatted_shop_price']) ? $val['unformatted_market_price'] - $val['unformatted_shop_price'] : 0);

// 				$mobilebuy_price = $object_id = 0;
// 				if (!is_ecjia_error($result_mobilebuy) && $is_active) {
// 					$mobilebuy = $mobilebuy_db->find(array(
// 							'goods_id'	 => $val['goods_id'],
// 							'start_time' => array('elt' => RC_Time::gmtime()),
// 							'end_time'	 => array('egt' => RC_Time::gmtime()),
// 							'act_type'	 => GAT_MOBILE_BUY,
// 					));
// 					if (!empty($mobilebuy)) {
// 						$ext_info = unserialize($mobilebuy['ext_info']);
// 						$mobilebuy_price = $ext_info['price'];
// 						if ($mobilebuy_price < $price) {
// 							$val['promote_price'] = price_format($mobilebuy_price);
// 							$object_id		= $mobilebuy['act_id'];
// 							$activity_type	= 'MOBILEBUY_GOODS';
// 							$saving_price = ($val['unformatted_shop_price'] - $mobilebuy_price) > 0 ? $val['unformatted_shop_price'] - $mobilebuy_price : 0;
// 						}
// 					}
// 				}

// 				$cart_goods['related_goods'][] = array(
// 						'goods_id'		=> $val['goods_id'],
// 						'id'			=> $val['goods_id'],
// 						'name'			=> $val['name'],
// 						'market_price'	=> $val['market_price'],
// 						'shop_price'	=> $val['shop_price'],
// 						'promote_price'	=> $val['promote_price'],
// 						'img' => array(
// 								'thumb'	=> $val['goods_img'],
// 								'url'	=> $val['original_img'],
// 								'small'	=> $val['goods_thumb']
// 						),
// 						'activity_type' => $activity_type,
// 						'object_id'		=> $object_id,
// 						'saving_price'	=>	$saving_price,
// 						'formatted_saving_price' => $saving_price > 0 ? '已省'.$saving_price.'元' : '',
// 						'seller_id'		=> $val['store_id'],
// 						'seller_name'	=> $val['store_name'],
// 				);
// 			}
// 		}

// 		return $cart_goods;
	}
}

// end
