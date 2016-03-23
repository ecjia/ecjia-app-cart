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
		RC_Loader::load_app_func('cart','cart');
		
	    $rec_id = _POST('rec_id');
	    $rec_id = explode(',', $rec_id);
	    
	    if (is_array($rec_id)) {
	    	foreach ($rec_id as $val) {
	    		flow_drop_cart_goods($val);
	    	}
	    } else {
	    	flow_drop_cart_goods($rec_id);
	    }
	    
	    $cart_goods = EM_get_cart_goods();
	    
	    EM_Api::outPut($cart_goods['total']);
	}
}

// end