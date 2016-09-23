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
		$dbview_cart = RC_DB::table('cart as c')
					   ->leftJoin('goods as g', RC_DB::raw('c.goods_id'), '=', RC_DB::raw('g.goods_id'))
					   ->leftJoin('store_franchisee as s', RC_DB::raw('s.store_id'), '=', RC_DB::raw('c.store_id'));
		$db_goods_attr = RC_DB::table('goods_attr');
		$db_goods 	   = RC_DB::table('goods');
		
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
		$dbview_cart->where(RC_DB::raw('c.rec_type'), '=', $flow_type);
		/* 根据经纬度查询附近店铺*/
		if (is_array($location) && isset($location['latitude']) && isset($location['longitude'])) {
			$geohash = RC_Loader::load_app_class('geohash', 'store');
			$geohash_code = $geohash->encode($location['latitude'] , $location['longitude']);
			$geohash_code = substr($geohash_code, 0, 5);
			$dbview_cart->where(RC_DB::raw('s.geohash'), 'like', '%'.$geohash_code.'%');
		}

		/* 选择购买 */
		if (!empty($cart_id)) {
			$dbview_cart->whereIn(RC_DB::raw('c.rec_id'), $cart_id);
		}
		if ($_SESSION['user_id']) {
			$dbview_cart->where(RC_DB::raw('c.user_id'), '=', $_SESSION['user_id']);
		} else {
			$dbview_cart->where(RC_DB::raw('c.session_id'), '=', SESS_ID);
		}
				
		//$dbview_cart->view = array(
		//		'goods' => array(
		//				'type' 	=> Component_Model_View::TYPE_LEFT_JOIN,
		//				'alias' => 'g',
		//				'on' 	=> 'c.goods_id = g.goods_id'
		//		),
		//		'seller_shopinfo' => array(
		//				'type' 	=> Component_Model_View::TYPE_LEFT_JOIN,
		//				'alias' => 'ssi',
		//				'on' 	=> 'ssi.id = c.store_id'
		//		),
		//);
		
		//$field = 'c.*, IF(c.parent_id, c.parent_id, c.goods_id) AS pid, goods_thumb, goods_img, original_img, ssi.shop_name as seller_name';
		//$data = $dbview_cart->join(array('goods', 'seller_shopinfo'))
		//					->field($field)
		//					->where($cart_where)
		//					->order(array('store_id' => 'asc', 'pid' => 'asc', 'parent_id' => 'asc'))
		//					->select();
		
		$data = $dbview_cart
				->selectRaw("c.*,IF(c.parent_id, c.parent_id, c.goods_id) AS pid, goods_thumb, goods_img, original_img, s.merchants_name as seller_name")
				->orderBy('store_id', 'asc')
				->orderBy('pid', 'asc')
				->orderBy('parent_id', 'asc')
				->get();
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
					//$attr_list = $db_goods_attr->field('attr_value')->in(array('goods_attr_id' =>$row['goods_attr_id']))->select();
					$attr_list = $db_goods_attr->select('attr_value')->whereIn('goods_attr_id', $row['goods_attr_id'])->get();
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