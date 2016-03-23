<?php
defined('IN_ECJIA') or exit('No permission resources.');

/**
 * 获得用户的可用积分
 *
 * @access  private
 * @return  integral
 */
// function flow_available_points()
// {
// 	$dbview = RC_Loader::load_app_model('cart_good_member_viewmodel','cart');
// //     $sql = "SELECT SUM(g.integral * c.goods_number) ".
// //             "FROM " . $GLOBALS['ecs']->table('cart') . " AS c, " . $GLOBALS['ecs']->table('goods') . " AS g " .
// //             "WHERE c.session_id = '" . SESS_ID . "' AND c.goods_id = g.goods_id AND c.is_gift = 0 AND g.integral > 0 " .
// //             "AND c.rec_type = '" . CART_GENERAL_GOODS . "'";

// //     $val = intval($GLOBALS['db']->getOne($sql));

// 	$dbview->view =array(
// 			'goods' => array(
// 						'type'  =>Component_Model_View::TYPE_LEFT_JOIN,
// 						'alias' => 'g',
// // 						'field' => "SUM(g.integral * c.goods_number)",
// 						'on'   => 'c.goods_id = g.goods_id'
// 				),
// 	);
	
// 	$val = $dbview->where('c.session_id = "' . SESS_ID . '"  AND c.is_gift = 0 AND g.integral > 0 AND c.rec_type = ' . CART_GENERAL_GOODS . '')->sum('g.integral * c.goods_number');

//     return integral_of_value($val);
// }

/**
 * 更新购物车中的商品数量
 *
 * @access  public
 * @param   array   $arr
 * @return  void
 */
// function flow_update_cart($arr)
// {
// 	$db_cart = RC_Loader::load_app_model('cart_model','cart');
// 	$db_cartview = RC_Loader::load_app_model('cart_cart_viewmodel','cart');
// 	$dbview = RC_Loader::load_app_model('goods_auto_viewmodel','goods');
// 	$db_products = RC_Loader::load_app_model('products_model','goods');
//     /* 处理 */
//     foreach ($arr AS $key => $val)
//     {
//         $val = intval(make_semiangle($val));
//         if ($val <= 0 || !is_numeric($key))
//         {
//             continue;
//         }

//         //查询：
// //         $sql = "SELECT `goods_id`, `goods_attr_id`, `product_id`, `extension_code` FROM" .$GLOBALS['ecs']->table('cart').
// //                " WHERE rec_id='$key' AND session_id='" . SESS_ID . "'";
// //         $goods = $GLOBALS['db']->getRow($sql);

//         $goods = $db_cart->field('`goods_id`, `goods_attr_id`, `product_id`, `extension_code`')->find('rec_id = '.$key.' AND session_id = ' . SESS_ID . '');
        
// //         $sql = "SELECT g.goods_name, g.goods_number ".
// //                 "FROM " .$GLOBALS['ecs']->table('goods'). " AS g, ".
// //                     $GLOBALS['ecs']->table('cart'). " AS c ".
// //                 "WHERE g.goods_id = c.goods_id AND c.rec_id = '$key'";
// //         $row = $GLOBALS['db']->getRow($sql);

//         $dbview->view =array(
//         		'cart' => array(
//         				'type' =>Component_Model_View::TYPE_LEFT_JOIN,
//         				'alias'=> 'c',
//         				'field' => 'g.*,a.starttime,a.endtime',
//         				'on' => 'g.goods_id = c.goods_id '
//         		)
//         );

//         $row = $dbview->find('c.rec_id = '.$key.'');
        
//         //查询：系统启用了库存，检查输入的商品数量是否有效
//         if (intval($GLOBALS['_CFG']['use_storage']) > 0 && $goods['extension_code'] != 'package_buy')
//         {
//             if ($row['goods_number'] < $val)
//             {
//                 show_message(sprintf(RC_Lang::lang('stock_insufficiency'), $row['goods_name'],
//                 $row['goods_number'], $row['goods_number']));
//                 exit;
//             }
//             /* 是货品 */
//             $goods['product_id'] = trim($goods['product_id']);
//             if (!empty($goods['product_id']))
//             {
// //                 $sql = "SELECT product_number FROM " .$GLOBALS['ecs']->table('products'). " WHERE goods_id = '" . $goods['goods_id'] . "' AND product_id = '" . $goods['product_id'] . "'";
// //                 $product_number = $GLOBALS['db']->getOne($sql);
                
//             	$product_number = $db_products->field('product_number')->find('goods_id = ' . $goods['goods_id'] . ' AND product_id = ' . $goods['product_id'] . '');
//             	$product_number = $product_number['product_number'];
//                 if ($product_number < $val)
//                 {
//                     show_message(sprintf(RC_Lang::lang('stock_insufficiency'), $row['goods_name'],
//                     $product_number['product_number'], $product_number['product_number']));
//                     exit;
//                 }
//             }
//         }
//         elseif (intval($GLOBALS['_CFG']['use_storage']) > 0 && $goods['extension_code'] == 'package_buy')
//         {
//             if (judge_package_stock($goods['goods_id'], $val))
//             {
//                 show_message(RC_Lang::lang('package_stock_insufficiency'));
//                 exit;
//             }
//         }

//         /* 查询：检查该项是否为基本件 以及是否存在配件 */
//         /* 此处配件是指添加商品时附加的并且是设置了优惠价格的配件 此类配件都有parent_id goods_number为1 */

// //         $sql = "SELECT b.goods_number, b.rec_id
// //                 FROM " .$GLOBALS['ecs']->table('cart') . " a, " .$GLOBALS['ecs']->table('cart') . " b
// //                 WHERE a.rec_id = '$key'
// //                 AND a.session_id = '" . SESS_ID . "'
// //                 AND a.extension_code <> 'package_buy'
// //                 AND b.parent_id = a.goods_id
// //                 AND b.session_id = '" . SESS_ID . "'";

// //         $offers_accessories_res = $GLOBALS['db']->query($sql);

//         $offers_accessories_res = $db_cartview->where("a.rec_id = '$key' AND a.session_id = '" . SESS_ID . "' AND a.extension_code <> 'package_buy' AND b.session_id = '" . SESS_ID . "' ")->select();
//       //订货数量大于0
//         if ($val > 0)
//         {
//             /* 判断是否为超出数量的优惠价格的配件 删除*/
//             $row_num = 1;
// //             while ($offers_accessories_row = $GLOBALS['db']->fetchRow($offers_accessories_res))
//             foreach ($offers_accessories_res as $offers_accessories_row)
//             {
//                 if ($row_num > $val)
//                 {
// //                     $sql = "DELETE FROM " . $GLOBALS['ecs']->table('cart') .
// //                             " WHERE session_id = '" . SESS_ID . "' " .
// //                             "AND rec_id = '" . $offers_accessories_row['rec_id'] ."' LIMIT 1";
// //                     $GLOBALS['db']->query($sql);

//                 	$db_cart->where('session_id = "' . SESS_ID . '" AND rec_id = ' . $offers_accessories_row['rec_id'] .'')->delete();
//                 }

//                 $row_num ++;
//             }

//             /* 处理超值礼包 */
//             if ($goods['extension_code'] == 'package_buy')
//             {
//                 //更新购物车中的商品数量
// //                 $sql = "UPDATE " .$GLOBALS['ecs']->table('cart').
// //                         " SET goods_number = '$val' WHERE rec_id='$key' AND session_id='" . SESS_ID . "'";

//             	$data = array(
//             			'goods_number' => $val
//             	);
//             	$db_cart->where('rec_id = '.$key.' AND session_id = "' . SESS_ID . '" ')->update($data);
            	
//             }
//             /* 处理普通商品或非优惠的配件 */
//             else
//             {
//                 $attr_id    = empty($goods['goods_attr_id']) ? array() : explode(',', $goods['goods_attr_id']);
//                 $goods_price = get_final_price($goods['goods_id'], $val, true, $attr_id);

//                 //更新购物车中的商品数量
// //                 $sql = "UPDATE " .$GLOBALS['ecs']->table('cart').
// //                         " SET goods_number = '$val', goods_price = '$goods_price' WHERE rec_id='$key' AND session_id='" . SESS_ID . "'";

//                 $data = array(
//                 		'goods_number' => $val,
//                 		'goods_price'  => $goods_price
//                 );
//                 $db_cart->where('rec_id = '.$key.' AND session_id = "' . SESS_ID . '"')->update($data);
//             }
//         }
//         //订货数量等于0
//         else
//         {
//             /* 如果是基本件并且有优惠价格的配件则删除优惠价格的配件 */
// //             while ($offers_accessories_row = $GLOBALS['db']->fetchRow($offers_accessories_res))
//         	foreach ($offers_accessories_res as $offers_accessories_row)
//         	{
// //                 $sql = "DELETE FROM " . $GLOBALS['ecs']->table('cart') .
// //                         " WHERE session_id = '" . SESS_ID . "' " .
// //                         "AND rec_id = '" . $offers_accessories_row['rec_id'] ."' LIMIT 1";
// //                 $GLOBALS['db']->query($sql);

//             	$db_cart->where('session_id = "' . SESS_ID . '" AND rec_id = ' . $offers_accessories_row['rec_id'] .'')->delete();
//             }

// //             $sql = "DELETE FROM " .$GLOBALS['ecs']->table('cart').
// //                 " WHERE rec_id='$key' AND session_id='" .SESS_ID. "'";

//             $db_cart->where('rec_id = '.$key.' and session_id = "' . SESS_ID . '"')->delete();
//         }

// //         $GLOBALS['db']->query($sql);
//     }

//     /* 删除所有赠品 */
// //     $sql = "DELETE FROM " . $GLOBALS['ecs']->table('cart') . " WHERE session_id = '" .SESS_ID. "' AND is_gift <> 0";
// //     $GLOBALS['db']->query($sql);

//        $db_cart->where('session_id = "' . SESS_ID . '" AND is_gift <> 0')->delete();
// }

// /**
//  * 检查订单中商品库存
//  *
//  * @access  public
//  * @param   array   $arr
//  *
//  * @return  void
//  */
// function flow_cart_stock($arr)
// {
// 	$db_cart = RC_Loader::load_app_model('cart_model','cart');
// // 	$db_cartview = RC_Loader::load_app_model('cart_good_member_viewmodel','flow');
// 	$dbview = RC_Loader::load_app_model('goods_auto_viewmodel','goods');
// 	$db_products = RC_Loader::load_app_model('products_model','goods');
	
// 	foreach ($arr AS $key => $val)
//     {
//         $val = intval(make_semiangle($val));
//         if ($val <= 0 || !is_numeric($key))
//         {
//             continue;
//         }

// //         $sql = "SELECT `goods_id`, `goods_attr_id`, `extension_code` FROM" .$GLOBALS['ecs']->table('cart').
// //                " WHERE rec_id='$key' AND session_id='" . SESS_ID . "'";
// //         $goods = $GLOBALS['db']->getRow($sql);

//         $goods = $db_cart->field('`goods_id`, `goods_attr_id`, `extension_code`')->find('rec_id = '.$key.' AND session_id = "' . SESS_ID . '"');

// //         $sql = "SELECT g.goods_name, g.goods_number, c.product_id ".
// //                 "FROM " .$GLOBALS['ecs']->table('goods'). " AS g, ".
// //                     $GLOBALS['ecs']->table('cart'). " AS c ".
// //                 "WHERE g.goods_id = c.goods_id AND c.rec_id = '$key'";
// //         $row = $GLOBALS['db']->getRow($sql);

//         $dbview->view =array(
//         		'cart' => array(
//         				'type' =>Component_Model_View::TYPE_LEFT_JOIN,
//         				'alias'=> 'c',
//         				'field' => 'g.goods_name, g.goods_number, c.product_id',
//         				'on' => 'g.goods_id = c.goods_id '
//         		)
//         );
        
//         $row = $dbview->find('c.rec_id = '.$key.'');

//         //系统启用了库存，检查输入的商品数量是否有效
//         if (intval(ecjia::config('use_storage')) > 0 && $goods['extension_code'] != 'package_buy')
//         {
//             if ($row['goods_number'] < $val)
//             {
//                 show_message(sprintf(RC_Lang::lang('stock_insufficiency'), $row['goods_name'],
//                 $row['goods_number'], $row['goods_number']));
//                 exit;
//             }

//             /* 是货品 */
//             $row['product_id'] = trim($row['product_id']);
//             if (!empty($row['product_id']))
//             {
// //                 $sql = "SELECT product_number FROM " .$GLOBALS['ecs']->table('products'). " WHERE goods_id = '" . $goods['goods_id'] . "' AND product_id = '" . $row['product_id'] . "'";
// //                 $product_number = $GLOBALS['db']->getOne($sql);

//             	$product_number = $db_products->field('product_number')->find('goods_id = ' . $goods['goods_id'] . ' AND product_id = ' . $row['product_id'] . '');
//             	$product_number = $product_number['product_number'];
            	
//                 if ($product_number < $val)
//                 {
//                     show_message(sprintf(RC_Lang::lang('stock_insufficiency'), $row['goods_name'],
//                     $row['goods_number'], $row['goods_number']));
//                     exit;
//                 }
//             }
//         }
//         elseif (intval(ecjia::config('use_storage')) > 0 && $goods['extension_code'] == 'package_buy')
//         {
//             if (judge_package_stock($goods['goods_id'], $val))
//             {
//                 show_message(RC_Lang::lang('package_stock_insufficiency'));
//                 exit;
//             }
//         }
//     }

// }

/**
 * 删除购物车中的商品
 *
 * @access  public
 * @param   integer $id
 * @return  void
 */
// function flow_drop_cart_goods($id)
// {
// 	$db_cart = RC_Loader::load_app_model('cart_model','cart');
// 	$db_cartview = RC_Loader::load_app_model('cart_good_member_viewmodel','cart');

	
//     /* 取得商品id */
// //     $sql = "SELECT * FROM " .$GLOBALS['ecs']->table('cart'). " WHERE rec_id = '$id'";
// //     $row = $GLOBALS['db']->getRow($sql);

// 	$row = $db_cart->find('rec_id = '.$id.'');
//     if ($row)
//     {
//         //如果是超值礼包
//         if ($row['extension_code'] == 'package_buy')
//         {
// //             $sql = "DELETE FROM " . $GLOBALS['ecs']->table('cart') .
// //                     " WHERE session_id = '" . SESS_ID . "' " .
// //                     "AND rec_id = '$id' LIMIT 1";

//         	$db_cart->where('session_id = "' . SESS_ID . '"')->delete();
//         }

//         //如果是普通商品，同时删除所有赠品及其配件
//         elseif ($row['parent_id'] == 0 && $row['is_gift'] == 0)
//         {
//             /* 检查购物车中该普通商品的不可单独销售的配件并删除 */
// //             $sql = "SELECT c.rec_id
// //                     FROM " . $GLOBALS['ecs']->table('cart') . " AS c, " . $GLOBALS['ecs']->table('group_goods') . " AS gg, " . $GLOBALS['ecs']->table('goods'). " AS g
// //                     WHERE gg.parent_id = '" . $row['goods_id'] . "'
// //                     AND c.goods_id = gg.goods_id
// //                     AND c.parent_id = '" . $row['goods_id'] . "'
// //                     AND c.extension_code <> 'package_buy'
// //                     AND gg.goods_id = g.goods_id
// //                     AND g.is_alone_sale = 0";
// //             $res = $GLOBALS['db']->query($sql);

//         	$db_cartview->view =array(
//         			'group_goods' => array(
//         					'type'  =>Component_Model_View::TYPE_LEFT_JOIN,
//         					'alias' => 'gg',
//         					'field' => 'c.rec_id',
//         					'on'   => 'c.goods_id = gg.goods_id'
//         			),
//         			'goods' => array(
//         					'type'  =>Component_Model_View::TYPE_LEFT_JOIN,
//         					'alias' => 'g',
//         					'on'   => 'gg.goods_id = g.goods_id'
//         			)     		
//         	);
            
//             $data = $db_cartview->where("gg.parent_id = $row[goods_id] AND c.parent_id = '" . $row['goods_id'] . "' AND c.extension_code <> 'package_buy' AND g.is_alone_sale = 0 ")->select();
        	
//             $_del_str = $id . ',';
// //             while ($id_alone_sale_goods = $GLOBALS['db']->fetchRow($res))
//             if(!empty($data))
//             {
// 	            foreach ($data as $id_alone_sale_goods)
// 	            {
// 	                $_del_str .= $id_alone_sale_goods['rec_id'] . ',';
// 	            }
//             }
//             $_del_str = trim($_del_str, ',');

// //             $sql = "DELETE FROM " . $GLOBALS['ecs']->table('cart') .
// //                     " WHERE session_id = '" . SESS_ID . "' " .
// //                     "AND (rec_id IN ($_del_str) OR parent_id = '$row[goods_id]' OR is_gift <> 0)";

//             $db_cart->where('session_id = "' . SESS_ID . '" and rec_id IN ('.$_del_str.') OR parent_id = '.$row[goods_id].' OR is_gift <> 0')->delete();
//         }

//         //如果不是普通商品，只删除该商品即可
//         else
//         {
// //             $sql = "DELETE FROM " . $GLOBALS['ecs']->table('cart') .
// //                     " WHERE session_id = '" . SESS_ID . "' " .
// //                     "AND rec_id = '$id' LIMIT 1";

//         	$db_cart->where('session_id = "' . SESS_ID . '" AND rec_id = '.$id.' ')->delete();
        	
//         }

// //         $GLOBALS['db']->query($sql);
//     }

//     flow_clear_cart_alone();
// }

/**
 * 删除购物车中不能单独销售的商品
 *
 * @access  public
 * @return  void
 */
// function flow_clear_cart_alone()
// {
// 	$db_cart = RC_Loader::load_app_model('cart_model','cart');
// 	$db_cartview = RC_Loader::load_app_model('cart_good_member_viewmodel','cart');
	
//     /* 查询：购物车中所有不可以单独销售的配件 */
// //     $sql = "SELECT c.rec_id, gg.parent_id
// //             FROM " . $GLOBALS['ecs']->table('cart') . " AS c
// //                 LEFT JOIN " . $GLOBALS['ecs']->table('group_goods') . " AS gg ON c.goods_id = gg.goods_id
// //                 LEFT JOIN" . $GLOBALS['ecs']->table('goods') . " AS g ON c.goods_id = g.goods_id
// //             WHERE c.session_id = '" . SESS_ID . "'
// //             AND c.extension_code <> 'package_buy'
// //             AND gg.parent_id > 0
// //             AND g.is_alone_sale = 0";
// //     $res = $GLOBALS['db']->query($sql);

// 	$db_cartview->view =array(
// 			'group_goods' => array(
//         					'type'  =>Component_Model_View::TYPE_LEFT_JOIN,
//         					'alias' => 'gg',
//         					'field' => 'c.rec_id, gg.parent_id',
//         					'on'   => 'c.goods_id = gg.goods_id'
//         			),
//         			'goods' => array(
//         					'type'  =>Component_Model_View::TYPE_LEFT_JOIN,
//         					'alias' => 'g',
//         					'on'   => 'c.goods_id = g.goods_id'
//         			)    
// 	);
	
// 	$data = $db_cartview->where('c.session_id = "' . SESS_ID . '" AND c.extension_code <> "package_buy" AND gg.parent_id > 0 AND g.is_alone_sale = 0 ')->select(); 
	
//     $rec_id = array();
// //     while ($row = $GLOBALS['db']->fetchRow($res))
//     if(!empty($data))
//     {
// 	    foreach ($data as $row)
// 	    {
// 	        $rec_id[$row['rec_id']][] = $row['parent_id'];
// 	    }
//     }
//     if (empty($rec_id))
//     {
//         return;
//     }

//     /* 查询：购物车中所有商品 */
// //     $sql = "SELECT DISTINCT goods_id
// //             FROM " . $GLOBALS['ecs']->table('cart') . "
// //             WHERE session_id = '" . SESS_ID . "'
// //             AND extension_code <> 'package_buy'";
// //     $res = $GLOBALS['db']->query($sql);
 
//      $res  =  $db_cart->field('DISTINCT goods_id')->where('session_id = "' . SESS_ID . '" AND extension_code <> "package_buy" ')->select(); 
//     $cart_good = array();
// //     while ($row = $GLOBALS['db']->fetchRow($res))
//     if(!empty($res))
//     {
// 	    foreach ($res as $row)
// 	    {
// 	        $cart_good[] = $row['goods_id'];
// 	    }
//     }
//     if (empty($cart_good))
//     {
//         return;
//     }

//     /* 如果购物车中不可以单独销售配件的基本件不存在则删除该配件 */
//     $del_rec_id = '';
//     foreach ($rec_id as $key => $value)
//     {
//         foreach ($value as $v)
//         {
//             if (in_array($v, $cart_good))
//             {
//                 continue 2;
//             }
//         }

//         $del_rec_id = $key . ',';
//     }
//     $del_rec_id = trim($del_rec_id, ',');

//     if ($del_rec_id == '')
//     {
//         return;
//     }

//     /* 删除 */
// //     $sql = "DELETE FROM " . $GLOBALS['ecs']->table('cart') ."
// //             WHERE session_id = '" . SESS_ID . "'
// //             AND rec_id IN ($del_rec_id)";
// //     $GLOBALS['db']->query($sql);

//     $db_cart->where('session_id = "' . SESS_ID . '"')->in(array('rec_id' => $del_rec_id))->delete();
// }

/**
 * 比较优惠活动的函数，用于排序（把可用的排在前面）
 * @param   array   $a      优惠活动a
 * @param   array   $b      优惠活动b
 * @return  int     相等返回0，小于返回-1，大于返回1
 */
function cmp_favourable($a, $b)
{
    if ($a['available'] == $b['available'])
    {
        if ($a['sort_order'] == $b['sort_order'])
        {
            return 0;
        }
        else
        {
            return $a['sort_order'] < $b['sort_order'] ? -1 : 1;
        }
    }
    else
    {
        return $a['available'] ? -1 : 1;
    }
}

/**
 * 取得某用户等级当前时间可以享受的优惠活动
 * @param   int     $user_rank      用户等级id，0表示非会员
 * @return  array
 */
function em_favourable_list($user_rank)
{
	RC_Loader::load_app_func('common','goods');
	$db_favourable_activity = RC_Loader::load_app_model('favourable_activity_model','favourable');
	$db_goods = RC_Loader::load_app_model('goods_model','goods');
    /* 购物车中已有的优惠活动及数量 */
    $used_list = cart_favourable();

    /* 当前用户可享受的优惠活动 */
    $favourable_list = array();
    $user_rank = ',' . $user_rank . ',';
    $now = RC_Time::gmtime();
	
    $where = array(
    	"CONCAT(',', user_rank, ',') LIKE '%" . $user_rank . "%'",
    	'start_time' => array('elt' => $now),
    	'end_time' => array('egt' => $now),
    	'act_type' => FAT_GOODS
    );
    
	$data = $db_favourable_activity->where($where)->order('sort_order asc')->select();
    RC_Lang::load('cart/shopping_flow');
    foreach ($data as $favourable) {
        $favourable['formated_start_time'] = RC_Time::local_date(ecjia::config('time_format'), $favourable['start_time']);
        $favourable['formated_end_time']   = RC_Time::local_date(ecjia::config('time_format'), $favourable['end_time']);
        $favourable['formated_min_amount'] = price_format($favourable['min_amount'], false);
        $favourable['formated_max_amount'] = price_format($favourable['max_amount'], false);
        $favourable['gift']       = unserialize($favourable['gift']);

        foreach ($favourable['gift'] as $key => $value) {
            $favourable['gift'][$key]['formated_price'] = price_format($value['price'], false);

            $is_sale = $db_goods->where('is_on_sale = 1 AND goods_id = '.$value['id'].'')->count();            
            if(!$is_sale) {
                unset($favourable['gift'][$key]);
            }
        }
		
        $favourable['act_range_desc'] = act_range_desc($favourable);
        $favourable['act_type_desc'] = sprintf(RC_Lang::lang('fat_ext/'.$favourable['act_type']), $favourable['act_type_ext']);

        /* 是否能享受 */
        $favourable['available'] = favourable_available($favourable);
        if ($favourable['available']) {
            /* 是否尚未享受 */
            $favourable['available'] = !favourable_used($favourable, $used_list);
        }

        $favourable_list[] = $favourable;
    }

    return $favourable_list;
    
    //     $sql = "SELECT * " .
    //             "FROM " . $GLOBALS['ecs']->table('favourable_activity') .
    //             " WHERE CONCAT(',', user_rank, ',') LIKE '%" . $user_rank . "%'" .
    //             " AND start_time <= '$now' AND end_time >= '$now'" .
    //             " AND act_type = '" . FAT_GOODS . "'" .
    //             " ORDER BY sort_order";
    //     $res = $GLOBALS['db']->query($sql);
    //     while ($favourable = $GLOBALS['db']->fetchRow($res))
    //             $sql = "SELECT COUNT(*) FROM " . $GLOBALS['ecs']->table('goods') . " WHERE is_on_sale = 1 AND goods_id = ".$value['id'];
    //             $is_sale = $GLOBALS['db']->getOne($sql);   
}

/**
 * 根据购物车判断是否可以享受某优惠活动
 * @param   array   $favourable     优惠活动信息
 * @return  bool
 */
function favourable_available($favourable)
{
    /* 会员等级是否符合 */
    $user_rank = $_SESSION['user_rank'];
    if (strpos(',' . $favourable['user_rank'] . ',', ',' . $user_rank . ',') === false) {
        return false;
    }

    /* 优惠范围内的商品总额 */
    $amount = cart_favourable_amount($favourable);
    /* 金额上限为0表示没有上限 */
    return $amount >= $favourable['min_amount'] &&
        ($amount <= $favourable['max_amount'] || $favourable['max_amount'] == 0);
}

/**
 * 取得优惠范围描述
 * @param   array   $favourable     优惠活动
 * @return  string
 */
function act_range_desc($favourable)
{
	$db_brand = RC_Loader::load_app_model('brand_model','goods');
	$db_category = RC_Loader::load_app_model('category_model','goods');
	$db_goods = RC_Loader::load_app_model('goods_model','goods');

    if ($favourable['act_range'] == FAR_BRAND) {
    	$brandArr = array();
    	if (!empty($favourable['act_range_ext'])) {
    		$brandName = $db_brand->field('brand_name')->in(array('brand_id'=>$favourable['act_range_ext']))->select();
    		foreach ($brandName as $row) {
    			$brandArr[] = $row['brand_name'];
    		}
    		return join(',', $brandArr);
    	}
    	return '';
    } elseif ($favourable['act_range'] == FAR_CATEGORY) {
    	$catArr = array();
    	if (!empty($favourable['act_range_ext'])) {
	    	$cat_name = $db_category->field('cat_name')->in(array('cat_id'=>$favourable['act_range_ext']))->select();
	    	foreach ($cat_name as $row) {
	    		$catArr[] = $row['cat_name'];
	    	}
	    	return join(',', $catArr);
    	}
    	return '';
    } elseif ($favourable['act_range'] == FAR_GOODS) {
    	if (!empty($favourable['act_range_ext'])) {
	        $goods_name = $db_goods->field('goods_name')->in(array('goods_id'=>$favourable['act_range_ext']))->select();
	    	foreach ($goods_name as $row) {
	    		$goodsArr[] = $row['goods_name'];
	    	}
	    	return join(',', $goodsArr);
    	}
    	return '';
    } else {
        return '';
    }
    //         $sql = "SELECT brand_name FROM " . $GLOBALS['ecs']->table('brand') .
    //                 " WHERE brand_id " . db_create_in($favourable['act_range_ext']);
    //         return join(',', $GLOBALS['db']->getCol($sql));
    //         $sql = "SELECT cat_name FROM " . $GLOBALS['ecs']->table('category') .
    //                 " WHERE cat_id " . db_create_in($favourable['act_range_ext']);
    //         return join(',', $GLOBALS['db']->getCol($sql));
    //         $sql = "SELECT goods_name FROM " . $GLOBALS['ecs']->table('goods') .
    //                 " WHERE goods_id " . db_create_in($favourable['act_range_ext']);
    //         return join(',', $GLOBALS['db']->getCol($sql));
}

/**
 * 取得购物车中已有的优惠活动及数量
 * @return  array
 */
function cart_favourable()
{
	$db_cart = RC_Loader::load_app_model('cart_model','cart');
    $list = array();
//     $sql = "SELECT is_gift, COUNT(*) AS num " .
//             "FROM " . $GLOBALS['ecs']->table('cart') .
//             " WHERE session_id = '" . SESS_ID . "'" .
//             " AND rec_type = '" . CART_GENERAL_GOODS . "'" .
//             " AND is_gift > 0" .
//             " GROUP BY is_gift";
//     $res = $GLOBALS['db']->query($sql);

    $data = $db_cart->field('is_gift, COUNT(*) AS num')->where('session_id = "' . SESS_ID . '" AND rec_type = ' . CART_GENERAL_GOODS . ' AND is_gift > 0')->group('is_gift asc')->select();
    
//  while ($row = $GLOBALS['db']->fetchRow($res))
    if(!empty($data))
    {
	    foreach ($data as $row)
	    {
	        $list[$row['is_gift']] = $row['num'];
	    }
    }
    return $list;
}

/**
 * 购物车中是否已经有某优惠
 * @param   array   $favourable     优惠活动
 * @param   array   $cart_favourable购物车中已有的优惠活动及数量
 */
function favourable_used($favourable, $cart_favourable)
{
    if ($favourable['act_type'] == FAT_GOODS)
    {
        return isset($cart_favourable[$favourable['act_id']]) &&
            $cart_favourable[$favourable['act_id']] >= $favourable['act_type_ext'] &&
            $favourable['act_type_ext'] > 0;
    }
    else
    {
        return isset($cart_favourable[$favourable['act_id']]);
    }
}

/**
 * 添加优惠活动（赠品）到购物车
 * @param   int     $act_id     优惠活动id
 * @param   int     $id         赠品id
 * @param   float   $price      赠品价格
 */
function add_gift_to_cart($act_id, $id, $price)
{
	$db_goods = RC_Loader::load_app_model('goods_model','goods');
	$db_cart = RC_Loader::load_app_model('cart_model','cart');
// 	$sql = "INSERT INTO " . $GLOBALS['ecs']->table('cart') . " (" .
//                 "user_id, session_id, goods_id, goods_sn, goods_name, market_price, goods_price, ".
//                 "goods_number, is_real, extension_code, parent_id, is_gift, rec_type ) ".
//             "SELECT '$_SESSION[user_id]', '" . SESS_ID . "', goods_id, goods_sn, goods_name, market_price, ".
//                 "'$price', 1, is_real, extension_code, 0, '$act_id', '" . CART_GENERAL_GOODS . "' " .
//             "FROM " . $GLOBALS['ecs']->table('goods') .
//             " WHERE goods_id = '$id'";
//     $GLOBALS['db']->query($sql);

	$row = $db_goods->field('goods_id, goods_sn, goods_name, market_price, is_real, extension_code')->find('goods_id = '.$id.'');
	
	$data = array(
			'user_id' => $_SESSION['user_id'],
			'session_id' => SESS_ID,
			'goods_id' => $row['goods_id'],
			'goods_sn' => $row['goods_sn'],
			'goods_name' => $row['goods_name'],
			'market_price' => $row['market_price'],
			'goods_price' => $price,
			'goods_number' => 1,
			'is_real' => $row['is_real'],
			'extension_code' => $row['extension_code'],
			'parent_id' => 0,
			'is_gift' => $act_id,
			'rec_type' => CART_GENERAL_GOODS,
	);

	$db_cart->insert($data);
}

/**
 * 添加优惠活动（非赠品）到购物车
 * @param   int     $act_id     优惠活动id
 * @param   string  $act_name   优惠活动name
 * @param   float   $amount     优惠金额
 */
function add_favourable_to_cart($act_id, $act_name, $amount)
{
	$db_cart = RC_Loader::load_app_model('cart_model','cart');
//     $sql = "INSERT INTO " . $GLOBALS['ecs']->table('cart') . "(" .
//                 "user_id, session_id, goods_id, goods_sn, goods_name, market_price, goods_price, ".
//                 "goods_number, is_real, extension_code, parent_id, is_gift, rec_type ) ".
//             "VALUES('$_SESSION[user_id]', '" . SESS_ID . "', 0, '', '$act_name', 0, ".
//                 "'" . (-1) * $amount . "', 1, 0, '', 0, '$act_id', '" . CART_GENERAL_GOODS . "')";
//     $GLOBALS['db']->query($sql);

	$data = array(
			'user_id' => $_SESSION['user_id'],
			'session_id' => SESS_ID,
			'goods_id' => 0,
			'goods_sn' => '',
			'goods_name' => $act_name,
			'market_price' => 0,
			'goods_price' => (-1) * $amount,
			'goods_number' => 1,
			'is_real' => 0,
			'extension_code' => '',
			'parent_id' => 0,
			'is_gift' => $act_id,
			'rec_type' => CART_GENERAL_GOODS
	);
	$db_cart->insert($data);	
}

/**
 * 取得购物车中某优惠活动范围内的总金额
 * @param   array   $favourable     优惠活动
 * @return  float
 */
function cart_favourable_amount($favourable)
{
	$db_cartview = RC_Loader::load_app_model('cart_good_member_viewmodel','cart');
    /* 查询优惠范围内商品总额的sql */
	$db_cartview->view =array(
    		'goods' => array(
    				'type'  =>Component_Model_View::TYPE_LEFT_JOIN,
    				'alias' => 'g',
    				'on'   => 'c.goods_id = g.goods_id'
    		)
    );
    $where = array(
    		'c.rec_type' => CART_GENERAL_GOODS,
    		'c.is_gift' => 0,
    		'c.goods_id' => array('gt' => 0),
    );
    if ($_SESSION['user_id']) {
    	$where = array_merge($where,array('c.user_id' => $_SESSION['user_id']));
    } else {
    	$where = array_merge(array('c.session_id' => SESS_ID));
    }
	$sum = 'c.goods_price * c.goods_number';
	RC_Loader::load_app_func('common', 'goods');
	RC_Loader::load_app_func('category', 'goods');
	
    /* 根据优惠范围修正sql */
    if ($favourable['act_range'] == FAR_ALL) {
        // sql do not change
    } elseif ($favourable['act_range'] == FAR_CATEGORY) {
        /* 取得优惠范围分类的所有下级分类 */
        $id_list = array();
        $cat_list = explode(',', $favourable['act_range_ext']);
        foreach ($cat_list as $id) {
            $id_list = array_merge($id_list, array_keys(cat_list(intval($id), 0, false)));
        }
        $where = array_merge($where,array('g.cat_id'.db_create_in($id_list)));
	} elseif ($favourable['act_range'] == FAR_BRAND) {
        $id_list = explode(',', $favourable['act_range_ext']);
        $where = array_merge($where,array('g.brand_id'.db_create_in($id_list)));
		$query = $db_cartview->where($where)->in(array('g.brand_id' => $id_list))->sum($sum);
    } else {
        $id_list = explode(',', $favourable['act_range_ext']);
        $where = array_merge($where,array('g.goods_id'.db_create_in($id_list)));
	}
    $id_list = explode(',', $favourable['act_range_ext']);
    
    /* 优惠范围内的商品总额 */
	$row = $db_cartview->where($where)->sum($sum);
  	return $row;
	
	//     $sql = "SELECT SUM(c.goods_price * c.goods_number) " .
	//             "FROM " . $GLOBALS['ecs']->table('cart') . " AS c, " . $GLOBALS['ecs']->table('goods') . " AS g " .
	//             "WHERE c.goods_id = g.goods_id " .
	//             "AND c.session_id = '" . SESS_ID . "' " .
	//             "AND c.rec_type = '" . CART_GENERAL_GOODS . "' " .
	//             "AND c.is_gift = 0 " .
	//             "AND c.goods_id > 0 ";
	//         $sql .= "AND g.cat_id " . db_create_in($id_list);
	//         $sql .= "AND g.brand_id " . db_create_in($id_list);
	//         $sql .= "AND g.goods_id " . db_create_in($id_list);
	//     return $GLOBALS['db']->getOne($sql);
}
// end