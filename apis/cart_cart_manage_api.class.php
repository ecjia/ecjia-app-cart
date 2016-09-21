<?php
defined('IN_ECJIA') or exit('No permission resources.');
/**
 * 
 * @author will.chen
 *
 */
class cart_cart_manage_api extends Component_Event_Api {
	
	
    /**
     * @param  
     *
     * @return array
     */
	public function call(&$options) {	
		
		if (!isset($options['location']) || !isset($options['location']['latitude']) || !isset($options['location']['latitude']))
		{
			return new ecjia_error('location_error', '请选择有效的收货地址！');
		}
		
		if (!isset($options['goods_id']) || empty($options['goods_id'])) {
			return new ecjia_error('not_found_goods', '请选择您所需要的商品！');
		}
		
		return $this->addto_cart($options['goods_id'], $options['goods_number'], $options['goods_spec'], $options['parent_id'], $options['location']);
	}
	
	
	/**
	 * 添加商品到购物车
	 *
	 * @access  public
	 * @param   integer $goods_id   商品编号
	 * @param   integer $num        商品数量
	 * @param   array   $spec       规格值对应的id数组
	 * @param   integer $parent     基本件
	 * @return  boolean
	 */
	private function addto_cart($goods_id, $num = 1, $spec = array(), $parent = 0, $location = array()) {
		$_parent_id 	= $parent;
		
		$dbview = RC_DB::table('goods as g')->leftJoin('member_price as mp', RC_DB::raw('g.goods_id'), '=', RC_DB::raw('mp.goods_id'));
		$db_cart = RC_DB::table('cart');
		
		RC_Loader::load_app_class('goods_info', 'goods', false);
		
		$field = 'g.goods_name, g.goods_sn, g.is_on_sale, g.is_real,g.market_price, g.shop_price AS org_price, g.promote_price, g.promote_start_date,g.promote_end_date, g.goods_weight, g.integral, g.extension_code,g.goods_number, g.is_alone_sale, g.is_shipping,IFNULL(mp.user_price, g.shop_price * '.$_SESSION['discount'].') AS shop_price, g.seller_id';
		//$goods = $dbview->field($field)->join(array('member_price'))->find(array('g.goods_id' => $goods_id , 'g.is_delete' => 0));
		$goods = $dbview->selectRaw($field)
		->where(RC_DB::raw('g.goods_id'), $goods_id)
		->where(RC_DB::raw('g.is_delete'), 0)->first();

		if (empty($goods)) {
			return new ecjia_error('no_goods', __('对不起，该商品不存在！'));
		}
		
		/* 是否正在销售 */
		if ($goods['is_on_sale'] == 0) {
			return new ecjia_error('goods_out_of_stock', __('对不起，该商品已下架！'));
		}
		
		/* 如果是作为配件添加到购物车的，需要先检查购物车里面是否已经有基本件 */
		if ($parent > 0) {
			//$parent_w = array('goods_id' => $parent , 'user_id' => $_SESSION['user_id'] , 'extension_code' => array('neq' => 'package_buy'));
			$db_cart->where('goods_id', $parent);
			$db_cart->where('user_id', $_SESSION['user_id']);
			$db_cart->where('extension_code', '<>', 'package_buy');
			if (defined('SESS_ID')) {
				//$parent_w['session_id'] = SESS_ID;
				$db_cart->where('session_id', SESS_ID);
			}
			$count = $db_cart->count();
		
			if ($count == 0) {
				return new ecjia_error('addcart_error', __('对不起，您希望将该商品做为配件购买，可是购物车中还没有该商品的基本件。'));
			}
		}
		
		/* 不是配件时检查是否允许单独销售 */
		if (empty($parent) && $goods['is_alone_sale'] == 0) {
			return new ecjia_error('not_alone_sale', __('对不起，该商品不能单独购买！'));
		}
		
		/* 如果商品有规格则取规格商品信息 配件除外 */
		
		//$prod = RC_Model::model('goods/products_model')->find(array('goods_id' => $goods_id));
		$prod = RC_DB::table('products')->where('goods_id', $goods_id)->first();
		
		if (goods_info::is_spec($spec) && !empty($prod)) {
			$product_info = goods_info::get_products_info($goods_id, $spec);
		}
		if (!isset($product_info) || empty($product_info)) {
			$product_info = array('product_number' => 0, 'product_id' => 0 , 'goods_attr'=>'');
		}
		
		/* 检查：库存 */
		if (ecjia::config('use_storage') == 1) {
			//检查：商品购买数量是否大于总库存
			if ($num > $goods['goods_number']) {
				return new ecjia_error('low_stocks', __('库存不足'));
			}
			//商品存在规格 是货品 检查该货品库存
			if (goods_info::is_spec($spec) && !empty($prod)) {
				if (!empty($spec)) {
					/* 取规格的货品库存 */
					if ($num > $product_info['product_number']) {
						return new ecjia_error('low_stocks', __('库存不足'));
					}
				}
			}
		}
		
		/* 计算商品的促销价格 */
		if (!empty($spec)) {
			$spec_price             = goods_info::spec_price($spec);
			$goods_price            = goods_info::get_final_price($goods_id, $num, true, $spec);
			$goods['market_price'] += $spec_price;
			$goods_attr             = goods_info::get_goods_attr_info($spec);
			$goods_attr_id          = join(',', $spec);
		}
	
		/* 初始化要插入购物车的基本件数据 */
		$parent = array(
				'user_id'       => $_SESSION['user_id'],
				'goods_id'      => $goods_id,
				'goods_sn'      => addslashes($goods['goods_sn']),
				'product_id'    => $product_info['product_id'],
				'goods_name'    => addslashes($goods['goods_name']),
				'market_price'  => $goods['market_price'],
				'goods_attr'    => addslashes($goods_attr),
				'goods_attr_id' => empty($goods_attr_id) ? 0 : $goods_attr_id,
				'is_real'       => $goods['is_real'],
				'extension_code'=> $goods['extension_code'],
				'is_gift'       => 0,
				'is_shipping'   => $goods['is_shipping'],
				'rec_type'      => CART_GENERAL_GOODS,
// 				'ru_id'			=> $goods['user_id'],
				'seller_id'		=> $goods['seller_id']
		);
		
		if (defined('SESS_ID')) {
			$parent['session_id'] = SESS_ID;
		}
		
		/* 如果该配件在添加为基本件的配件时，所设置的“配件价格”比原价低，即此配件在价格上提供了优惠， */
		/* 则按照该配件的优惠价格卖，但是每一个基本件只能购买一个优惠价格的“该配件”，多买的“该配件”不享受此优惠 */
		$basic_list = array();
		//$data = RC_Model::model('goods/group_goods_model')->field('parent_id, goods_price')
		//												  ->where(
		//												  		array(
		//												  			'goods_id'		=> $goods_id,
		//												  			'goods_price'	=> array('lt' => $goods_price),
		//												  			'parent_id'		=> $_parent_id,
		//												  ))
		//												 ->order(array('goods_price' => 'asc'))
		//												 ->select();
		$db_group_goods = RC_DB::table('group_goods');
		$where_gr = '';
		if (!empty($goods_id)) {
			$where_gr .= "goods_id = '$goods_id'";
		}
		if (!empty($goods_price)) {
			$where_gr .= "goods_price < '$goods_price'";
		}
		if (!empty($_parent_id)) {
			$where_gr .= "parent_id = '$_parent_id'";
		}
		$data = $db_group_goods
		->select('parent_id, goods_price')
		->whereRaw($where_gr)
		->orderBy('goods_price', 'asc')
		->select();

		if(!empty($data)) {
			foreach ($data as $row) {
				$basic_list[$row['parent_id']] = $row['goods_price'];
			}
		}
		/* 取得购物车中该商品每个基本件的数量 */
		$basic_count_list = array();
		if ($basic_list) {
			//$basic_w = array(
			//				'user_id'	=> $_SESSION['user_id'],
			//				'parent_id' => '0' , 
			//				'extension_code' =>array('neq' => 'package_buy')
			//);
			//if (defined('SESS_ID')) {
			//	$basic_w['session_id'] = SESS_ID;
			//}
				
			//$data = $db_cart->field('goods_id, SUM(goods_number)|count')
			//				->where($basic_w)
			//				->in(array('goods_id' => array_keys($basic_list)))
			//				->order(array('goods_id' => 'asc'))
			//				->select();
			$basic_w = '';
			if (defined('SESS_ID')) {
				$session_id = SESS_ID;
				$basic_w .= "and session_id='$session_id'";
			}
			
			$data = $db_cart
					->where('user_id', $_SESSION['user_id'])
					->where('parent_id', 0)
					->where('extension_code', '<>', 'package_buy'.$basic_w)
					->whereIn('goods_id', array_keys($basic_list))
					->orderBy('goods_id', 'asc')
					->get();
			
			if(!empty($data)) {
				foreach ($data as $row) {
					$basic_count_list[$row['goods_id']] = $row['count'];
				}
			}
		}
		/* 取得购物车中该商品每个基本件已有该商品配件数量，计算出每个基本件还能有几个该商品配件 */
		/* 一个基本件对应一个该商品配件 */
		if ($basic_count_list) {
			//$basic_l_w = array(
			//		'user_id'		=> $_SESSION['user_id'],
			//		'goods_id'		=> $goods_id,
			//		'extension_code' => array('neq' => 'package_buy')
			//);
			//if (defined('SESS_ID')) {
			//	$basic_l_w['session_id'] = SESS_ID;
			//}
			//$data = $db_cart->field('parent_id, SUM(goods_number)|count')
			//				->where($basic_l_w)
			//				->in(array('parent_id' => array_keys($basic_count_list)))
			//				->order(array('parent_id' => 'asc'))
			//				->select();
			$basic_l_w = '';
			if (defined('SESS_ID')) {
				$session_id = SESS_ID;
				$basic_l_w .= "and session_id='$session_id'";
			}
			$data = $db_cart
					->select('parent_id, SUM(goods_number) as count')
					->where('user_id', $_SESSION['user_id'])
					->where('goods_id', $goods_id)
					->where('extension_code', '<>', 'package_buy'.$basic_l_w)
					->whereIn('parent_id', array_keys($basic_count_list))
					->get();
			if(!empty($data)) {
				foreach ($data as $row) {
					$basic_count_list[$row['parent_id']] -= $row['count'];
				}
			}
		}
		
		/* 循环插入配件 如果是配件则用其添加数量依次为购物车中所有属于其的基本件添加足够数量的该配件 */
		if  (!empty($basic_list)) {
			foreach ($basic_list as $parent_id => $fitting_price) {
				/* 如果已全部插入，退出 */
				if ($num <= 0) {
					break;
				}
			
				/* 如果该基本件不再购物车中，执行下一个 */
				if (!isset($basic_count_list[$parent_id])) {
					continue;
				}
			
				/* 如果该基本件的配件数量已满，执行下一个基本件 */
				if ($basic_count_list[$parent_id] <= 0) {
					continue;
				}
			
				/* 作为该基本件的配件插入 */
				$parent['goods_price']  = max($fitting_price, 0) + $spec_price; //允许该配件优惠价格为0
				$parent['goods_number'] = min($num, $basic_count_list[$parent_id]);
				$parent['parent_id']    = $parent_id;
			
				/* 添加 */
				$db_cart->insert($parent);
				/* 改变数量 */
				$num -= $parent['goods_number'];
			}
		}
		
		/* 如果数量不为0，作为基本件插入 */
		$user_id=$_SESSION['user_id'];
		if ($num > 0) {
			/* 检查该商品是否已经存在在购物车中 */
			//$cart_w = array(
			//		'user_id'	=> $_SESSION['user_id'],
			//		'goods_id'	=> $goods_id,
			//		'parent_id'	=> 0,
// 					'goods_attr' => get_goods_attr_info($spec)
			//		'goods_attr' => $goods_attr,
			//		'extension_code' => array('neq' => 'package_buy'),
			//		'rec_type'	=> CART_GENERAL_GOODS
			//);
			//if (defined('SESS_ID')) {
			//	$cart_w['session_id'] = SESS_ID;
			//}			
			//$row = $db_cart->field('rec_id, goods_number')
			//			   ->find($cart_w);
			if (!empty($goods_id)) {
				$cart_w = '';
				$rec_type = CART_GENERAL_GOODS;
				$cart_w = "user_id = '$user_id'"
				."and goods_id = '$goods_id'"
				."and parent_id = 0"
				." and extension_code <>'package_buy'"
				."and rec_type='$rec_type'";
			}
			
			if (!empty($goods_attr)) {
				$cart_w .= "and goods_attr='$goods_attr'";
			}
			if (defined('SESS_ID')) {
				$session_id = SESS_ID;
				$cart_w .= "and session_id='$session_id'";
			}
		
			$row = $db_cart
				   ->select(RC_DB::Raw('rec_id, goods_number'))
				   ->whereRaw($cart_w)
				   ->first();
			if($row) {
				//如果购物车已经有此物品，则更新
				$num += $row['goods_number'];
				if(goods_info::is_spec($spec) && !empty($prod) ) {
					$goods_storage = $product_info['product_number'];
				} else {
					$goods_storage = $goods['goods_number'];
				}
				if (ecjia::config('use_storage') == 0 || $num <= $goods_storage) {
					$goods_price = goods_info::get_final_price($goods_id, $num, true, $spec);
					$data =  array(
							'goods_number' => $num,
							'goods_price'  => $goods_price
					);
					
					//$db_cart->where($cart_w)->update($data);
					$db_cart
					   ->whereRaw($cart_w)
					   ->update($data);
				} else {
					return new ecjia_error('low_stocks', __('库存不足'));
				}
				$cart_id = $row['rec_id'];
			} else {
				//购物车没有此物品，则插入
				$goods_price = goods_info::get_final_price($goods_id, $num, true, $spec);
				$parent['goods_price']  = empty($goods_price) ? 0.00 : max($goods_price, 0);
				$parent['goods_number'] = $num;
				$parent['parent_id']    = 0;
				$cart_id = $db_cart->insert($parent);
			}
		}
		
		/* 把赠品删除 */
		//$delete_w = array('user_id' => $_SESSION['user_id'] , 'is_gift' => array('neq' => 0));
		//if (defined('SESS_ID')) {
		//	$delete_w['session_id'] = SESS_ID;
		//}
		//$db_cart->where($delete_w)->delete();
		$delete_w = '';
		$delete_w = "user_id = '$user_id'"."and is_gift <> 0";
		if (defined('SESS_ID')) {
			$session_id = SESS_ID;
			$delete_w .= " and session_id='$session_id'";
		}
		
		$db_cart->whereRaw($delete_w)->delete();
		return $cart_id;
		
		
		
		
		
		
// 		$dbview 		= RC_Loader::load_app_model('sys_goods_member_viewmodel', 'goods');
// 		$db_cart 		= RC_Loader::load_app_model('cart_model', 'cart');
// 		$db_products 	= RC_Loader::load_app_model('products_model', 'goods');
// 		$db_group 		= RC_Loader::load_app_model('group_goods_model', 'goods');
		
// 		RC_Loader::load_app_func('order', 'orders');
// 		RC_Loader::load_app_func('goods', 'goods');
// 		RC_Loader::load_app_func('common', 'goods');
// 		/* 取得商品信息 */
// 		$dbview->view = array(
// 				'member_price' => array(
// 						'type'  => Component_Model_View::TYPE_LEFT_JOIN,
// 						'alias' => 'mp',
// 						'field' => "",
// 						'on'   	=> "mp.goods_id = g.goods_id AND mp.user_rank = '$_SESSION[user_rank]'"
// 				)
// 		);
// 		$goods = $dbview->find(array('g.goods_id' => $goods_id , 'g.is_delete' => 0));
// 		if (empty($goods)) {
// 			return new ecjia_error('no_goods', __('对不起，指定的商品不存在！'));
// 		}
// 		/* 是否正在销售 */
// 		if ($goods['is_on_sale'] == 0) {
// 			return new ecjia_error('addcart_error', __('购买失败'));
// 		}
	
// 		/* 如果是作为配件添加到购物车的，需要先检查购物车里面是否已经有基本件 */
// 		if ($parent > 0) {
// 			if ($_SESSION['user_id']) {
// 				$count = $db_cart->where(array('goods_id' => $parent , 'user_id' => $_SESSION['user_id'] , 'extension_code' => array('neq' => 'package_buy')))->count();
// 			} else {
// 				$count = $db_cart->where(array('goods_id' => $parent , 'session_id' => SESS_ID , 'extension_code' => array('neq' => 'package_buy')))->count();
// 			}
			 
// 			if ($count == 0) {
// 				return new ecjia_error('addcart_error', __('对不起，您希望将该商品做为配件购买，可是购物车中还没有该商品的基本件。'));
// 			}
// 		}
	
// 		/* 不是配件时检查是否允许单独销售 */
// 		if (empty($parent) && $goods['is_alone_sale'] == 0) {
// 			return new ecjia_error('addcart_error', __('购买失败'));
// 		}
	
// 		/* 如果商品有规格则取规格商品信息 配件除外 */
// 		$prod = $db_products->find(array('goods_id' => $goods_id));
// 		if (is_spec($spec) && !empty($prod)) {
// 			$product_info = get_products_info($goods_id, $spec);
// 		}
// 		if (empty($product_info)) {
// 			$product_info = array('product_number' => '', 'product_id' => 0 , 'goods_attr'=>'');
// 		}
	
// 		/* 检查：库存 */
// 		if (ecjia::config('use_storage') == 1) {
// 			//检查：商品购买数量是否大于总库存
// 			if ($num > $goods['goods_number']) {
// 				return new ecjia_error('low_stocks', __('库存不足'));
// 			}
// 			//商品存在规格 是货品 检查该货品库存
// 			if (is_spec($spec) && !empty($prod)) {
// 				if (!empty($spec)) {
// 					/* 取规格的货品库存 */
// 					if ($num > $product_info['product_number']) {
// 						return new ecjia_error('low_stocks', __('库存不足'));
// 					}
// 				}
// 			}
// 		}
	
// 		/* 计算商品的促销价格 */
// 		$spec_price             = spec_price($spec);
// 		$goods_price            = get_final_price($goods_id, $num, true, $spec);
// 		$goods['market_price'] += $spec_price;
// 		$goods_attr             = get_goods_attr_info($spec);
// 		$goods_attr_id          = join(',', $spec);
	
// 		/* 初始化要插入购物车的基本件数据 */
// 		$parent = array(
// 				'user_id'       => $_SESSION['user_id'],
// 				'session_id'    => SESS_ID,
// 				'goods_id'      => $goods_id,
// 				'goods_sn'      => addslashes($goods['goods_sn']),
// 				'product_id'    => $product_info['product_id'],
// 				'goods_name'    => addslashes($goods['goods_name']),
// 				'market_price'  => $goods['market_price'],
// 				'goods_attr'    => addslashes($goods_attr),
// 				'goods_attr_id' => $goods_attr_id,
// 				'is_real'       => $goods['is_real'],
// 				'extension_code'=> $goods['extension_code'],
// 				'is_gift'       => 0,
// 				'is_shipping'   => $goods['is_shipping'],
// 				'rec_type'      => CART_GENERAL_GOODS,
				 
// 		);
	
// 		/* 如果该配件在添加为基本件的配件时，所设置的“配件价格”比原价低，即此配件在价格上提供了优惠， */
// 		/* 则按照该配件的优惠价格卖，但是每一个基本件只能购买一个优惠价格的“该配件”，多买的“该配件”不享受此优惠 */
// 		$basic_list = array();
// 		$data = $db_group->field('parent_id, goods_price')->where('goods_id = '.$goods_id.' AND goods_price < "'.$goods_price.'" AND parent_id = '.$_parent_id.'')->order('goods_price asc')->select();
	
// 		if(!empty($data)) {
// 			foreach ($data as $row) {
// 				$basic_list[$row['parent_id']] = $row['goods_price'];
// 			}
// 		}
// 		/* 取得购物车中该商品每个基本件的数量 */
// 		$basic_count_list = array();
// 		if ($basic_list) {
// 			if ($_SESSION['user_id']) {
// 				$data = $db_cart->field('goods_id, SUM(goods_number)|count')->where(array('user_id'=>$_SESSION['user_id'],'parent_id' => '0' , extension_code =>array('neq'=>"package_buy")))->in(array('goods_id'=>array_keys($basic_list)))->order('goods_id asc')->select();
// 			} else {
// 				$data = $db_cart->field('goods_id, SUM(goods_number)|count')->where(array('session_id'=>SESS_ID,'parent_id' => '0' , extension_code =>array('neq'=>"package_buy")))->in(array('goods_id'=>array_keys($basic_list)))->order('goods_id asc')->select();
// 			}
// 			if(!empty($data)) {
// 				foreach ($data as $row) {
// 					$basic_count_list[$row['goods_id']] = $row['count'];
// 				}
// 			}
// 		}
// 		/* 取得购物车中该商品每个基本件已有该商品配件数量，计算出每个基本件还能有几个该商品配件 */
// 		/* 一个基本件对应一个该商品配件 */
// 		if ($basic_count_list) {
// 			if ($_SESSION['user_id']) {
// 				$data = $db_cart->field('parent_id, SUM(goods_number)|count')->where(array('user_id' => $_SESSION['user_id'],'goods_id'=>$goods_id,extension_code =>array('neq'=>"package_buy")))->in(array('parent_id'=>array_keys($basic_count_list)))->order('parent_id asc')->select();
// 			} else {
// 				$data = $db_cart->field('parent_id, SUM(goods_number)|count')->where(array('session_id' => SESS_ID,'goods_id'=>$goods_id,extension_code =>array('neq'=>"package_buy")))->in(array('parent_id'=>array_keys($basic_count_list)))->order('parent_id asc')->select();
// 			}
			 
// 			if(!empty($data)) {
// 				foreach ($data as $row) {
// 					$basic_count_list[$row['parent_id']] -= $row['count'];
// 				}
// 			}
// 		}
	
// 		/* 循环插入配件 如果是配件则用其添加数量依次为购物车中所有属于其的基本件添加足够数量的该配件 */
// 		foreach ($basic_list as $parent_id => $fitting_price) {
// 			/* 如果已全部插入，退出 */
// 			if ($num <= 0) {
// 				break;
// 			}
	
// 			/* 如果该基本件不再购物车中，执行下一个 */
// 			if (!isset($basic_count_list[$parent_id])) {
// 				continue;
// 			}
	
// 			/* 如果该基本件的配件数量已满，执行下一个基本件 */
// 			if ($basic_count_list[$parent_id] <= 0) {
// 				continue;
// 			}
	
// 			/* 作为该基本件的配件插入 */
// 			$parent['goods_price']  = max($fitting_price, 0) + $spec_price; //允许该配件优惠价格为0
// 			$parent['goods_number'] = min($num, $basic_count_list[$parent_id]);
// 			$parent['parent_id']    = $parent_id;
	
// 			/* 添加 */
// 			$db_cart->insert($parent);
// 			/* 改变数量 */
// 			$num -= $parent['goods_number'];
// 		}
	
// 		/* 如果数量不为0，作为基本件插入 */
// 		if ($num > 0) {
// 			/* 检查该商品是否已经存在在购物车中 */
// 			if ($_SESSION['user_id']) {
// 				$row = $db_cart->field('rec_id, goods_number')->find('user_id = "' .$_SESSION['user_id']. '" AND goods_id = '.$goods_id.' AND parent_id = 0 AND goods_attr = "' .get_goods_attr_info($spec).'" AND extension_code <> "package_buy" AND rec_type = "'.CART_GENERAL_GOODS.'" ');
// 			} else {
// 				$row = $db_cart->field('rec_id, goods_number')->find('session_id = "' .SESS_ID. '" AND goods_id = '.$goods_id.' AND parent_id = 0 AND goods_attr = "' .get_goods_attr_info($spec).'" AND extension_code <> "package_buy" AND rec_type = "'.CART_GENERAL_GOODS.'" ');
// 			}
			 
// 			if($row) {
// 				//如果购物车已经有此物品，则更新
// 				$num += $row['goods_number'];
// 				if(is_spec($spec) && !empty($prod) ) {
// 					$goods_storage=$product_info['product_number'];
// 				} else {
// 					$goods_storage=$goods['goods_number'];
// 				}
// 				if (ecjia::config('use_storage') == 0 || $num <= $goods_storage) {
// 					$goods_price = get_final_price($goods_id, $num, true, $spec);
// 					$data =  array(
// 							'goods_number' => $num,
// 							'goods_price'  => $goods_price
// 					);
// 					if ($_SESSION['user_id']) {
// 						$db_cart->where('user_id = "' .$_SESSION['user_id']. '" AND goods_id = '.$goods_id.' AND parent_id = 0 AND goods_attr = "' .get_goods_attr_info($spec).'" AND extension_code <> "package_buy" AND rec_type = "'.CART_GENERAL_GOODS.'" ')->update($data);
// 					} else {
// 						$db_cart->where('session_id = "' .SESS_ID. '" AND goods_id = '.$goods_id.' AND parent_id = 0 AND goods_attr = "' .get_goods_attr_info($spec).'" AND extension_code <> "package_buy" AND rec_type = "'.CART_GENERAL_GOODS.'" ')->update($data);
// 					}
// 				} else {
// 					return new ecjia_error('low_stocks', __('库存不足'));
// 				}
// 				$cart_id = $row['rec_id'];
// 			} else {
// 				//购物车没有此物品，则插入
// 				$goods_price = get_final_price($goods_id, $num, true, $spec);
// 				$parent['goods_price']  = max($goods_price, 0);
// 				$parent['goods_number'] = $num;
// 				$parent['parent_id']    = 0;
// 				$cart_id = $db_cart->insert($parent);
// 			}
// 		}
	
// 		/* 把赠品删除 */
// 		if ($_SESSION['user_id']) {
// 			$db_cart->where(array('user_id' => $_SESSION['user_id'] , 'is_gift' => array('neq' => 0)))->delete();
// 		} else {
// 			$db_cart->where(array('session_id' => SESS_ID , 'is_gift' => array('neq' => 0)))->delete();
// 		}
	
// 		return $cart_id;
	}
	
}

// end