<?php
defined('IN_ECJIA') or exit('No permission resources.');
/**
 * 
 * @author will.chen
 *
 */
class cart_cart_list_api extends Component_Event_Api {
	
	
    /**
     * @param  
     *
     * @return array
     */
	public function call(&$options) {	
		
		if ((!isset($options['location']) || empty($options['location'])) && (!isset($options['cart_id']) || empty($options['cart_id'])) && $options['flow_type'] == CART_GENERAL_GOODS)
		{
			return new ecjia_error('location_error', '请选择有效的收货地址！');
		}
		
		return $this->get_cart_goods($options['cart_id'], $options['flow_type'], $options['location']);
	}
	
	
	/**
	 * 获得购物车中的商品
	 *
	 * @access  public
	 * @return  array
	 */
	private function get_cart_goods($cart_id = array(), $flow_type = CART_GENERAL_GOODS, $location = array()) {
// 		RC_Loader::load_app_func('common','goods');
		$dbview_cart 	= RC_Loader::load_app_model('cart_viewmodel', 'cart');
		$db_goods_attr 	= RC_Loader::load_app_model('goods_attr_model','goods');
		$db_goods 		= RC_Loader::load_app_model('goods_model','goods');
	
		/* 初始化 */
		$goods_list = array();
		$total = array(
				'goods_price'  => 0, // 本店售价合计（有格式）
				'market_price' => 0, // 市场售价合计（有格式）
				'saving'       => 0, // 节省金额（有格式）
				'save_rate'    => 0, // 节省百分比
				'goods_amount' => 0, // 本店售价合计（无格式）
		);
		
		/* 循环、统计 */
		$cart_where = array('rec_type' => $flow_type);
		
		/* 根据经纬度查询附近店铺*/
		if (is_array($location) && isset($location['latitude']) && isset($location['longitude'])) {
			$geohash = RC_Loader::load_app_class('geohash', 'shipping');
			$where_geohash = $geohash->encode($location['latitude'] , $location['longitude']);
			$where_geohash = substr($where_geohash, 0, 5);
		
			$msi_dbview = RC_Loader::load_app_model('merchants_shop_information_viewmodel', 'seller');
			$ru_id_info = $msi_dbview->join(array('merchants_shop_information', 'seller_shopinfo'))
									 ->field(array('msi.user_id', 'msi.shopNameSuffix', 'msi.shoprz_brandName'))
									 ->where(array(
												'geohash'		=> array('like' => "%$where_geohash%"),
												'ssi.status'	=> 1,
												'msi.merchants_audit' => 1,))
									 ->select();
		
			if (!empty($ru_id_info)) {
// 				$ru_id = array(0);
				$ru_id = array();
				foreach ($ru_id_info as $val) {
					$ru_id[] = $val['user_id'];
					$seller_info[$val['user_id']]['seller_id'] = $val['user_id'];
					$seller_info[$val['user_id']]['seller_name'] = (!empty($val['shoprz_brandName']) && !empty($val['shopNameSuffix'])) ? $val['shoprz_brandName'].$val['shopNameSuffix'] : '';
		
				}
				$merchants_shop_information_db = RC_Loader::load_app_model('merchants_shop_information_model', 'seller');
				$merchants_shop_information_db->where(array('user_id' => $ru_id))->select();
				$cart_where['c.ru_id'] = $ru_id;
			} 
// 			else {
// 				$cart_where['c.ru_id'] = 0;
// 			}
		}

		/* 选择购买 */
		if (!empty($cart_id)) {
			$cart_where = array_merge($cart_where, array('rec_id' => $cart_id));
		}
		if ($_SESSION['user_id']) {
			$cart_where = array_merge($cart_where, array('c.user_id' => $_SESSION['user_id']));
		} else {
			$cart_where = array_merge($cart_where, array('session_id' => SESS_ID));
		}
// 		$data = $db_cart->field('*, IF(parent_id, parent_id, goods_id)|pid')->where($cart_where)->order(array('pid'=>'asc', 'parent_id'=>'asc'))->select();
		
		$dbview_cart->view = array(
				'goods' => array(
						'type' 	=> Component_Model_View::TYPE_LEFT_JOIN,
						'alias' => 'g',
						'on' 	=> 'c.goods_id = g.goods_id'
				),
				'merchants_shop_information' => array(
						'type' 	=> Component_Model_View::TYPE_LEFT_JOIN,
						'alias' => 'msi',
						'on' 	=> 'msi.user_id = c.ru_id'
				),
		);
		
		$field = 'c.*, IF(c.parent_id, c.parent_id, c.goods_id) AS pid, goods_thumb, goods_img, original_img, CONCAT(shoprz_brandName,shopNameSuffix) as seller_name';
		$data = $dbview_cart->join(array('goods', 'merchants_shop_information'))
							->field($field)
							->where($cart_where)
							->order(array('ru_id' => 'asc', 'pid' => 'asc', 'parent_id' => 'asc'))
							->select();
		
		/* 用于统计购物车中实体商品和虚拟商品的个数 */
		$virtual_goods_count = 0;
		$real_goods_count    = 0;
	
		if (!empty($data)) {
			foreach ($data as $row) {
				$total['goods_price']  += $row['goods_price'] * $row['goods_number'];
				$total['market_price'] += $row['market_price'] * $row['goods_number'];
				$row['subtotal']     	= $row['goods_price'] * $row['goods_number'];
				$row['formatted_subtotal']     	= price_format($row['goods_price'] * $row['goods_number'], false);
				/* 返回未格式化价格*/
				$row['goods_price']		= $row['goods_price'];
				$row['market_price']	= $row['market_price'];
				
				$row['formatted_goods_price']  	= price_format($row['goods_price'], false);
				$row['formatted_market_price'] 	= price_format($row['market_price'], false);
	
				/* 统计实体商品和虚拟商品的个数 */
				if ($row['is_real']) {
					$real_goods_count++;
				} else {
					$virtual_goods_count++;
				}
	
				/* 查询规格 */
				if (trim($row['goods_attr']) != '') {
					$row['goods_attr'] = addslashes($row['goods_attr']);
					$attr_list = $db_goods_attr->field('attr_value')->in(array('goods_attr_id' =>$row['goods_attr_id']))->select();
					foreach ($attr_list AS $attr) {
						$row['goods_name'] .= ' [' . $attr[attr_value] . '] ';
					}
				}
				/* 增加是否在购物车里显示商品图 */
// 				if ((ecjia::config('show_goods_in_cart') == "2" || ecjia::config('show_goods_in_cart') == "3") &&
// 				$row['extension_code'] != 'package_buy') {
// 					$goods_thumb 		= $db_goods->where(array('goods_id' => $row['goods_id']))->get_field('goods_thumb');
// 					$row['goods_thumb'] = !empty($goods_thumb) ? RC_Upload::upload_url($goods_thumb) : '';
// 				}
				if ($row['extension_code'] == 'package_buy') {
// 					$row['package_goods_list'] = get_package_goods($row['goods_id']);
				}
				$goods_list[] = $row;
			}
		}
		$total['goods_amount'] = $total['goods_price'];
		$total['saving']       = price_format($total['market_price'] - $total['goods_price'], false);
		if ($total['market_price'] > 0) {
			$total['save_rate'] = $total['market_price'] ? round(($total['market_price'] - $total['goods_price']) *
					100 / $total['market_price']).'%' : 0;
		}
		$total['goods_price']  			= price_format($total['goods_price'], false);
		$total['market_price'] 			= price_format($total['market_price'], false);
		$total['real_goods_count']    	= $real_goods_count;
		$total['virtual_goods_count'] 	= $virtual_goods_count;
	
		return array('goods_list' => $goods_list, 'total' => $total);

	}
	
}

// end