<?php
defined('IN_ECJIA') or exit('No permission resources.');
/**
 * 购物车更新商品数目
 * @author royalwang
 *
 */
class update_module implements ecjia_interface {
	
	public function run(ecjia_api & $api) {
		EM_Api::authSession();
	    RC_Loader::load_app_func('cart','cart');
		
		$rec_id = _POST('rec_id', 0);
		$new_number = _POST('new_number', 0);
		if ($new_number < 1 || !$rec_id) {
			EM_Api::outPut(101);
		}
		$goods_number = array($rec_id => $new_number);

		$result = flow_update_cart($goods_number);
		if (is_ecjia_error($result)) {
			EM_Api::outPut(10008);
		}
		$cart_goods = EM_get_cart_goods();
		EM_Api::outPut($cart_goods['total']);
	}
}

// end