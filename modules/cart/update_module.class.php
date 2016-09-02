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
		RC_Loader::load_app_class('cart', 'cart', false);
// 	    RC_Loader::load_app_func('cart','cart');
		
		$rec_id = $this->requestData('rec_id', 0);
		$new_number = $this->requestData('new_number', 0);
		if ($new_number < 1 || !$rec_id) {
			EM_Api::outPut(101);
		}
		$goods_number = array($rec_id => $new_number);

		$result = cart::flow_update_cart($goods_number);
		if (is_ecjia_error($result)) {
			EM_Api::outPut(10008);
		}
// 		$cart_goods = EM_get_cart_goods();
		$cart_goods = RC_Api::api('cart', 'cart_list', array('location' => $location));
	    return $cart_goods['total'];
	}
}

// end