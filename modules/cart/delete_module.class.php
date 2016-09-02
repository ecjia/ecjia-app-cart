<?php
defined('IN_ECJIA') or exit('No permission resources.');
/**
 * 从购物车中删除一商品
 * @author royalwang
 *
 */
class delete_module extends api_front implements api_interface {
    public function handleRequest(\Royalcms\Component\HttpKernel\Request $request) {
    		
    	$this->authSession();
	    $location = $this->requestData('location',array());
	    RC_Loader::load_app_class('cart', 'cart', false);
		
	    $rec_id = $this->requestData('rec_id');
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