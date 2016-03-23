<?php
defined('IN_ECJIA') or exit('No permission resources.');
/**
 * 添加优惠活动到购物车
 * @author royalwang
 *
 */
class create_module implements ecjia_interface {
	
	public function run(ecjia_api & $api) {
	    EM_Api::authSession();
		RC_Loader::load_app_func('favourable', 'favourable');
		RC_Loader::load_app_func('global', 'cart');
		/* 取得优惠活动信息 */
	    $favourable_id = _POST('favourable_id',0);
	    $goods_id = _POST('goods_id', array());
	    $favourable = favourable_info($favourable_id);
	    if (empty($favourable)) {
	    	$result = new ecjia_error('favourable_not_exist', __('您要加入购物车的优惠活动不存在！'));
	    }
	
	    /* 判断用户能否享受该优惠 */
	    if (!favourable_available($favourable)) {
	    	$result = new ecjia_error('favourable_not_available', __('您不能享受该优惠！'));
	    }
	
	    /* 检查购物车中是否已有该优惠 */
	    $cart_favourable = cart_favourable();
	    if (favourable_used($favourable, $cart_favourable)) {
	    	$result = new ecjia_error('favourable_used', __('该优惠活动已加入购物车了！'));
	    }
	
	    /* 赠品（特惠品）优惠 */
	    if ($favourable['act_type'] == FAT_GOODS) {
	        /* 检查是否选择了赠品 */
	        if (empty($goods_id)) {
	        	$result = new ecjia_error('pls_select_gift', __('请选择赠品（特惠品）！'));
	        }
	
	        /* 检查是否已在购物车 */
	        $db = RC_Loader::load_app_model('cart_model','cart');
	        $where = array(
	        	'rec_type' => CART_GENERAL_GOODS ,
	        	'is_gift' => $favourable_id , 
	        	'goods_id'. db_create_in($goods_id)
	        );
	        $where = $_SESSION['user_id'] >0 ? array_merge($where, array('user_id' => $_SESSION['user_id'])): array_merge($where, array(session_id => SESS_ID));
	        
			$gift_name = $db->where($where)->get_field('goods_name');
	        if (!empty($gift_name)) {
	        	$result = new ecjia_error('gift_in_cart', sprintf(__('您选择的赠品（特惠品）已经在购物车中了：%s'), join(',', $gift_name)));
	        }
	
	        /* 检查数量是否超过上限 */
	        $count = isset($cart_favourable[$favourable_id]) ? $cart_favourable[$favourable_id] : 0;
	        if ($favourable['act_type_ext'] > 0 && $count + count($goods_id) > $favourable['act_type_ext']) {
	        	$result = new ecjia_error('gift_count_exceed', __('您选择的赠品（特惠品）数量超过上限了 ！'));
	        }
			if (is_ecjia_error($result)) {
				EM_Api::outPut(14);
			}
	        /* 添加赠品到购物车 */
	        foreach ($favourable['gift'] as $gift) {
	            if (in_array($gift['id'], $goods_id)) {
	                add_gift_to_cart($favourable_id, $gift['id'], $gift['price']);
	            }
	        }
	    } elseif ($favourable['act_type'] == FAT_DISCOUNT) {
	        add_favourable_to_cart($favourable_id, $favourable['act_name'], cart_favourable_amount($favourable) * (100 - $favourable['act_type_ext']) / 100);
	    } elseif ($favourable['act_type'] == FAT_PRICE) {
	        add_favourable_to_cart($favourable_id, $favourable['act_name'], $favourable['act_type_ext']);
	    }
	    EM_Api::outPut(array());
	    // 	        show_message($_LANG['favourable_not_exist']);
	    // 	        show_message($_LANG['favourable_not_available']);
	    // 	        show_message($_LANG['favourable_used']);
	    // 	            show_message($_LANG['pls_select_gift']);
	    // 	        $sql = "SELECT goods_name" .
	    // 	                " FROM " . $ecs->table('cart') .
	    // 	                " WHERE session_id = '" . SESS_ID . "'" .
	    // 	                " AND rec_type = '" . CART_GENERAL_GOODS . "'" .
	    // 	                " AND is_gift = '$act_id'" .
	    // 	                " AND goods_id " . db_create_in($_POST['gift']);
	    // 	        $gift_name = $db->getCol($sql);
	    // 	            show_message(sprintf($_LANG['gift_in_cart'], join(',', $gift_name)));
	    // 	            show_message($_LANG['gift_count_exceed']);
	}
}

// end