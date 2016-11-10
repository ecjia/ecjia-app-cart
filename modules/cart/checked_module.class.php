<?php
defined('IN_ECJIA') or exit('No permission resources.');
/**
 * 购物车更新选中状态
 * @author royalwang
 * $is_checked 0未选中，1选中
 * http://wiki.shangchina.com/index.php?title=Cart/checked(o2o)
 */
class checked_module extends api_front implements api_interface {
    public function handleRequest(\Royalcms\Component\HttpKernel\Request $request) {
    		
    	$this->authSession();
    	if ($_SESSION['user_id'] <= 0) {
    		return new ecjia_error(100, 'Invalid session');
    	}
		$location = $this->requestData('location',array());
		//TODO:目前强制坐标
// 		$location = array(
// 		    'latitude'	=> '31.235450744628906',
// 		    'longitude' => '121.41641998291016',
// 		);
		RC_Loader::load_app_class('cart', 'cart', false);
		
		$rec_id = $this->requestData('rec_id', 0);
		$rec_id = explode(',', $rec_id);
		$is_checked  = $this->requestData('is_checked', 1);
		
		if (!in_array($is_checked, array(0,1)) || empty($rec_id)) {
			return new ecjia_error(101, '参数错误');
		}
		
		$result = cart::flow_check_cart_goods(array('id' => $rec_id, 'is_checked' => $is_checked));
		
		$cart_goods = RC_Api::api('cart', 'cart_list', array('location' => $location));
	    return $cart_goods['total'];
	}
}

// end