<?php
defined('IN_ECJIA') or exit('No permission resources.');
/**
 * 从购物车中删除一商品
 * @author royalwang
 *
 */
class delete_module implements ecjia_interface {
	
	public function run(ecjia_api & $api) {

	    EM_Api::authSession();
	    $location = _POST('location');
	    RC_Loader::load_app_class('cart', 'cart', false);
		
	    $rec_id = _POST('rec_id');
	    $rec_id = explode(',', $rec_id);
	    
	    if (is_array($rec_id)) {
	    	foreach ($rec_id as $val) {
	    		cart::flow_drop_cart_goods($val);
	    	}
	    } else {
	    	cart::flow_drop_cart_goods($val);
	    }
	    
// 	    $cart_goods = EM_get_cart_goods();
	   	$cart_goods = RC_Api::api('cart', 'cart_list', array('location' => $location));
	    return $cart_goods['total'];
	    
	    
	}
}

// end