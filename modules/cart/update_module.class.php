<?php
defined('IN_ECJIA') or exit('No permission resources.');
/**
 * 购物车更新商品数目
 * @author royalwang
 *
 */
class update_module extends api_front implements api_interface {
    public function handleRequest(\Royalcms\Component\HttpKernel\Request $request) {
    		
    	$this->authSession();
		$location = $this->requestData('location',array());
		//TODO:目前强制坐标
// 		$location = array(
// 		    'latitude'	=> '31.235450744628906',
// 		    'longitude' => '121.41641998291016',
// 		);
		RC_Loader::load_app_class('cart', 'cart', false);
		
		$rec_id = $this->requestData('rec_id', 0);
		$new_number = $this->requestData('new_number', 0);
		
		if ($new_number < 1 || $rec_id < 1) {
			return new ecjia_error(101, '参数错误');
		}
		$goods_number = array($rec_id => $new_number);

		$result = cart::flow_update_cart($goods_number);
		if (is_ecjia_error($result)) {
			return new ecjia_error(10008, '库存不足');
		}
		
		$cart_goods = RC_Api::api('cart', 'cart_list', array('location' => $location));
	    return $cart_goods['total'];
	}
}

// end