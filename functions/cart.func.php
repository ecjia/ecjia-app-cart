<?php
defined('IN_ECJIA') or exit('No permission resources.');
/**
 * 获得购物车中的商品
 *
 * @access  public
 * @return  array
 */
function EM_get_cart_goods() {
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
	$db_cart = RC_Loader::load_app_model('cart_model', 'cart');
	$db_goods_attr = RC_Loader::load_app_model('goods_attr_model', 'goods');
	$db_goods = RC_Loader::load_app_model('goods_model', 'goods');
	RC_Loader::load_app_func('common', 'goods');
	if ($_SESSION['user_id']) {
		$data = $db_cart->field('*, IF(parent_id, parent_id, goods_id) AS pid')->where(array('user_id' => $_SESSION['user_id'] , 'rec_type' =>  CART_GENERAL_GOODS))->order(array('pid' => 'asc', 'parent_id' => 'asc'))->select();
	} else {
		$data = $db_cart->field('*, IF(parent_id, parent_id, goods_id) AS pid')->where(array('session_id' => SESS_ID , 'rec_type' =>  CART_GENERAL_GOODS))->order(array('pid' => 'asc', 'parent_id' => 'asc'))->select();
	}

	/* 用于统计购物车中实体商品和虚拟商品的个数 */
    $virtual_goods_count = 0;
    $real_goods_count    = 0;

	foreach ($data as $row) {
        $total['goods_price']  += $row['goods_price'] * $row['goods_number'];
        $total['market_price'] += $row['market_price'] * $row['goods_number'];
        
        $total['saving'] += $row['market_price'] > $row['goods_price'] ? ($row['market_price'] - $row['goods_price']) : 0;
        
        
        $row['subtotal']     = $row['goods_price'] * $row['goods_number'];
        $row['formated_subtotal']     = price_format($row['goods_price'] * $row['goods_number'], false);
        $row['goods_price']  = $total['goods_price'] > 0 ? price_format($row['goods_price'], false) : __('免费');
        $row['market_price'] = price_format($row['market_price'], false);

        /* 统计实体商品和虚拟商品的个数 */
        if ($row['is_real']) {
            $real_goods_count++;
        } else {
            $virtual_goods_count++;
        }

		/* 查询规格 */
        if (trim($row['goods_attr']) != '' && $row['group_id'] == '') {//兼容官网套餐问题增加条件group_id
			$attr_list = $db_goods_attr->field('attr_value')->in(array('goods_attr_id' => $row['goods_attr_id']))->select();
            foreach ($attr_list AS $attr) {
                $row['goods_name'] .= ' [' . $attr['attr_value'] . '] ';
            }
        }
        /* 增加是否在购物车里显示商品图 */
        if ((ecjia::config('show_goods_in_cart') == "2" || ecjia::config('show_goods_in_cart') == "3") && $row['extension_code'] != 'package_buy') {
			$goods_img = $db_goods->field('goods_thumb,goods_img,original_img')->find(array('goods_id' => $row['goods_id']));
            
			$row['goods_thumb'] = get_image_path($row['goods_id'], $goods_img['goods_thumb'], true);
            $row['goods_img'] = get_image_path($row['goods_id'], $goods_img['goods_img'], true);
            $row['original_img'] = get_image_path($row['goods_id'], $goods_img['original_img'], true);
        }
        if ($row['extension_code'] == 'package_buy') {
            $row['package_goods_list'] = get_package_goods($row['goods_id']);
        }
        $goods_list[] = $row;
    }
    $total['goods_amount'] = $total['goods_price'];
    $total['saving'] = price_format($total['saving'], false);
    if ($total['market_price'] > 0) {
        $total['save_rate'] = $total['market_price'] ? round(($total['market_price'] - $total['goods_price']) * 100 / $total['market_price']).'%' : 0;
    }
    $total['goods_price']  = price_format($total['goods_price'], false);
    $total['market_price'] = price_format($total['market_price'], false);
    $total['real_goods_count']    = $real_goods_count;
    $total['virtual_goods_count'] = $virtual_goods_count;

    return array('goods_list' => $goods_list, 'total' => $total);
    
    /* 循环、统计 */
//     $sql = "SELECT *, IF(parent_id, parent_id, goods_id) AS pid " .
//         " FROM " . $GLOBALS['ecs']->table('cart') . " " .
//         " WHERE session_id = '" . SESS_ID . "' AND rec_type = '" . CART_GENERAL_GOODS . "'" .
//         " ORDER BY pid, parent_id";
//     $res = $GLOBALS['db']->query($sql);    
//     while (($row = $GLOBALS['db']->fetchRow($res)) != false)
 /* 查询规格 */
    //             $sql = "SELECT attr_value FROM " . $GLOBALS['ecs']->table('goods_attr') . " WHERE goods_attr_id " .
//                 db_create_in($row['goods_attr']);
//             $attr_list = $GLOBALS['db']->getCol($sql);
        /* 增加是否在购物车里显示商品图 */
//             $goods_thumb = $GLOBALS['db']->getOne("SELECT `goods_thumb` FROM " . $GLOBALS['ecs']->table('goods') . " WHERE `goods_id`='{$row['goods_id']}'");
//             $goods_img = $GLOBALS['db']->getOne("SELECT `goods_img` FROM " . $GLOBALS['ecs']->table('goods') . " WHERE `goods_id`='{$row['goods_id']}'");
//             $original_img = $GLOBALS['db']->getOne("SELECT `original_img` FROM " . $GLOBALS['ecs']->table('goods') . " WHERE `goods_id`='{$row['goods_id']}'");

//            $goods_thumb = $db_goods->where(array('goods_id' => $row['goods_id']))->get_field('goods_thumb');
//            $goods_img = $db_goods->where(array('goods_id' => $row['goods_id']))->get_field('goods_img');
//            $original_img = $db_goods->where(array('goods_id' => $row['goods_id']))->get_field('original_img');  

}

/**
 * 更新购物车中的商品数量
 *
 * @access  public
 * @param   array   $arr
 * @return  void
 */
function flow_update_cart($arr) {
	$db_cart = RC_Loader::load_app_model('cart_model', 'cart');
	$db_cart_view = RC_Loader::load_app_model('cart_cart_viewmodel', 'cart');
	$db_products = RC_Loader::load_app_model('products_model', 'goods');
	$dbview = RC_Loader::load_app_model('goods_cart_viewmodel', 'goods');   
	RC_Loader::load_app_func('order', 'orders');
	RC_Loader::load_app_func('common', 'goods');
    /* 处理 */
    foreach ($arr AS $key => $val) {
		$val = intval(make_semiangle($val));
        if ($val <= 0 || !is_numeric($key)) {
            continue;
        }

        //查询：     
        if ($_SESSION['user_id']) {
        	$goods = $db_cart->field('goods_id,goods_attr_id,product_id,extension_code')->find(array('rec_id' => $key , 'user_id' => $_SESSION['user_id']));
        } else {
        	$goods = $db_cart->field('goods_id,goods_attr_id,product_id,extension_code')->find(array('rec_id' => $key , 'session_id' => SESS_ID));
        }

        $row   = $dbview->join('cart')->find(array('c.rec_id' => $key));
        //查询：系统启用了库存，检查输入的商品数量是否有效
        if (intval(ecjia::config('use_storage')) > 0 && $goods['extension_code'] != 'package_buy') {
            if ($row['goods_number'] < $val) {
                return new ecjia_error('low_stocks', __('库存不足'));
            }
            /* 是货品 */
            $goods['product_id'] = trim($goods['product_id']);
            if (!empty($goods['product_id'])) {
				$product_number = $db_products->where(array('goods_id' => $goods['goods_id'] , 'product_id' => $goods['product_id']))->get_field('product_number');
                if ($product_number < $val) {
                    return new ecjia_error('low_stocks', __('库存不足'));
                }
            }
        }  elseif (intval(ecjia::config('use_storage')) > 0 && $goods['extension_code'] == 'package_buy') {
        	if (judge_package_stock($goods['goods_id'], $val)) {
                return new ecjia_error('low_stocks', __('库存不足'));
            }
        }

        /* 查询：检查该项是否为基本件 以及是否存在配件 */
        /* 此处配件是指添加商品时附加的并且是设置了优惠价格的配件 此类配件都有parent_id goods_number为1 */
		if ($_SESSION['user_id']) {
			$offers_accessories_res = $db_cart_view->join('cart')->where(array('a.rec_id' => $key , 'a.user_id' => $_SESSION['user_id'] , 'a.extension_code' => array('neq' => 'package_buy') , 'b.user_id' => $_SESSION['user_id'] ))->select();
		} else {
			$offers_accessories_res = $db_cart_view->join('cart')->where(array('a.rec_id' => $key , 'a.session_id' => SESS_ID , 'a.extension_code' => array('neq' => 'package_buy') , 'b.session_id' => SESS_ID ))->select();
		}
        

        //订货数量大于0
        if ($val > 0) {
            /* 判断是否为超出数量的优惠价格的配件 删除*/
            $row_num = 1;
			if (!empty($offers_accessories_res)) {
                foreach ($offers_accessories_res as $offers_accessories_row) {
                    if ($row_num > $val) {
						if ($_SESSION['user_id']) {
							$db_cart->where(array('user_id' => $_SESSION['user_id'] , 'rec_id' => $offers_accessories_row['rec_id']))->delete();
						} else {
							$db_cart->where(array('session_id' => SESS_ID , 'rec_id' => $offers_accessories_row['rec_id']))->delete();
						}
                    }
                	$row_num ++;
                }
            }
            
            /* 处理超值礼包 */
            if ($goods['extension_code'] == 'package_buy') {
                //更新购物车中的商品数量
				if ($_SESSION['user_id']) {
					$db_cart->where(array('rec_id' => $key , 'user_id' => $_SESSION['user_id'] ))->update(array('goods_number' => $val));
				} else {
					$db_cart->where(array('rec_id' => $key , 'session_id' => SESS_ID ))->update(array('goods_number' => $val));
				}
            }  else {
            	/* 处理普通商品或非优惠的配件 */
                $attr_id    = empty($goods['goods_attr_id']) ? array() : explode(',', $goods['goods_attr_id']);
                $goods_price = get_final_price($goods['goods_id'], $val, true, $attr_id);
                
                //更新购物车中的商品数量
				if ($_SESSION['user_id']) {
					$db_cart->where(array('rec_id' => $key , 'user_id' => $_SESSION['user_id'] ))->update(array('goods_number' => $val , 'goods_price' => $goods_price));
				} else {
					$db_cart->where(array('rec_id' => $key , 'session_id' => SESS_ID ))->update(array('goods_number' => $val , 'goods_price' => $goods_price));
				}
            }
        } else {
        	//订货数量等于0
            /* 如果是基本件并且有优惠价格的配件则删除优惠价格的配件 */
            if (!empty($offers_accessories_res)) {
                foreach ($offers_accessories_res as $offers_accessories_row) {
					if ($_SESSION['user_id']) {
                		$db_cart->where(array('user_id' => $_SESSION['user_id'] , 'rec_id' => $offers_accessories_row['rec_id']))->delete();
                	} else {
                		$db_cart->where(array('session_id' => SESS_ID , 'rec_id' => $offers_accessories_row['rec_id']))->delete();
                	}
                }
            }

			if ($_SESSION['user_id']) {
				$db_cart->where(array('rec_id' => $key , 'user_id' => $_SESSION['user_id'] ))->delete();
			} else {
				$db_cart->where(array('rec_id' => $key , 'session_id' => SESS_ID ))->delete();
			}
        }
    }

    /* 删除所有赠品 */
	if ($_SESSION['user_id']) {
		$db_cart->where(array('user_id' => $_SESSION['user_id'] , 'is_gift' => array('neq' => 0)))->delete();
	} else {
		$db_cart->where(array('session_id' => SESS_ID , 'is_gift' => array('neq' => 0)))->delete();
	}
	
        //查询：
//         $sql = "SELECT `goods_id`, `goods_attr_id`, `product_id`, `extension_code` FROM" .$GLOBALS['ecs']->table('cart').
//         " WHERE rec_id='$key' AND session_id='" . SESS_ID . "'";
//         $goods = $GLOBALS['db']->getRow($sql);

//         $sql = "SELECT g.goods_name, g.goods_number ".
//             "FROM " .$GLOBALS['ecs']->table('goods'). " AS g, ".
//             $GLOBALS['ecs']->table('cart'). " AS c ".
//             "WHERE g.goods_id = c.goods_id AND c.rec_id = '$key'";
//         $row = $GLOBALS['db']->getRow($sql);

//                 $sql = "SELECT product_number FROM " .$GLOBALS['ecs']->table('products'). " WHERE goods_id = '" . $goods['goods_id'] . "' AND product_id = '" . $goods['product_id'] . "'";
//                 $product_number = $GLOBALS['db']->getOne($sql);	
        /* 查询：检查该项是否为基本件 以及是否存在配件 */
        /* 此处配件是指添加商品时附加的并且是设置了优惠价格的配件 此类配件都有parent_id goods_number为1 */
//         $sql = "SELECT b.goods_number, b.rec_id
//                 FROM " .$GLOBALS['ecs']->table('cart') . " a, " .$GLOBALS['ecs']->table('cart') . " b
//                 WHERE a.rec_id = '$key'
//                 AND a.session_id = '" . SESS_ID . "'
//                 AND a.extension_code <> 'package_buy'
//                 AND b.parent_id = a.goods_id
//                 AND b.session_id = '" . SESS_ID . "'";

//         $offers_accessories_res = $GLOBALS['db']->query($sql);
/* 判断是否为超出数量的优惠价格的配件 删除*/
//             while (($offers_accessories_row = $GLOBALS['db']->fetchRow($offers_accessories_res)) != false)
//                         $sql = "DELETE FROM " . $GLOBALS['ecs']->table('cart') .
//                         " WHERE session_id = '" . SESS_ID . "' " .
//                         "AND rec_id = '" . $offers_accessories_row['rec_id'] ."' LIMIT 1";
//                         $GLOBALS['db']->query($sql);
//更新购物车中的商品数量
//                 $sql = "UPDATE " .$GLOBALS['ecs']->table('cart').
//                 " SET goods_number = '$val' WHERE rec_id='$key' AND session_id='" . SESS_ID . "'";	

//                 $sql = "UPDATE " .$GLOBALS['ecs']->table('cart').
//                 " SET goods_number = '$val', goods_price = '$goods_price' WHERE rec_id='$key' AND session_id='" . SESS_ID . "'";
        	//订货数量等于0
            /* 如果是基本件并且有优惠价格的配件则删除优惠价格的配件 */
//             while (($offers_accessories_row = $GLOBALS['db']->fetchRow($offers_accessories_res)) != false)	
                    //                 $sql = "DELETE FROM " . $GLOBALS['ecs']->table('cart') .
                    //                 " WHERE session_id = '" . SESS_ID . "' " .
                    //                 "AND rec_id = '" . $offers_accessories_row['rec_id'] ."' LIMIT 1";
                    //                 $GLOBALS['db']->query($sql);	
//             $sql = "DELETE FROM " .$GLOBALS['ecs']->table('cart').
//             " WHERE rec_id='$key' AND session_id='" .SESS_ID. "'";
//         $GLOBALS['db']->query($sql);
    /* 删除所有赠品 */
//     $sql = "DELETE FROM " . $GLOBALS['ecs']->table('cart') . " WHERE session_id = '" .SESS_ID. "' AND is_gift <> 0";
//     $GLOBALS['db']->query($sql);	

}


/**
 * 删除购物车中的商品
 *
 * @access  public
 * @param   integer $id
 * @return  void
 */
function flow_drop_cart_goods($id) {
    $db_cart = RC_Loader::load_app_model('cart_model', 'cart');
    $dbview  = RC_Loader::load_app_model('cart_group_goods_goods_viewmodel', 'cart');
    
    /* 取得商品id */
	$row = $db_cart->find(array('rec_id' => $id));
    if ($row) {
        //如果是超值礼包
        if ($row['extension_code'] == 'package_buy') {
			if ($_SESSION['user_id']) {
				$db_cart->where(array('user_id' => $_SESSION['user_id'] , 'rec_id' => $id))->delete();
			} else {
				$db_cart->where(array('session_id' => SESS_ID , 'rec_id' => $id))->delete();
			}
        } elseif ($row['parent_id'] == 0 && $row['is_gift'] == 0) {
        	//如果是普通商品，同时删除所有赠品及其配件
            /* 检查购物车中该普通商品的不可单独销售的配件并删除 */
			$data = $dbview->join(array('group_goods','goods'))->field('c.rec_id')->where(array('gg.parent_id' => $row['goods_id'] , 'c.parent_id' => $row['goods_id'] , 'c.extension_code' => array('neq' => 'package_buy') , 'g.is_alone_sale' => 0))->select();
            
            $_del_str = $id . ',';
            if (!empty($data)) {
                foreach ($data as $id_alone_sale_goods) {
                    $_del_str .= $id_alone_sale_goods['rec_id'] . ',';
                }
            }
            
            $_del_str = trim($_del_str, ',');

			if ($_SESSION['user_id']) {
				$db_cart->where("user_id = '" . $_SESSION['user_id'] . "' and (rec_id IN ($_del_str) OR parent_id = '$row[goods_id]' OR is_gift <> 0)")->delete();
			} else {
				$db_cart->where("session_id = '" . SESS_ID . "' and (rec_id IN ($_del_str) OR parent_id = '$row[goods_id]' OR is_gift <> 0)")->delete();
			}
        } else {
        	//如果不是普通商品，只删除该商品即可
			if ($_SESSION['user_id']) {
				$db_cart->where(array('user_id' => $_SESSION['user_id'] , 'rec_id' => $id))->delete();
			} else {
				$db_cart->where(array('session_id' => SESS_ID , 'rec_id' => $id))->delete();
			}
        }

    }

    flow_clear_cart_alone();
    
    /* 取得商品id */
//     $sql = "SELECT * FROM " .$GLOBALS['ecs']->table('cart'). " WHERE rec_id = '$id'";
//     $row = $GLOBALS['db']->getRow($sql);
//如果是超值礼包
    //             $sql = "DELETE FROM " . $GLOBALS['ecs']->table('cart') .
//             " WHERE session_id = '" . SESS_ID . "' " .
//             "AND rec_id = '$id' LIMIT 1";
        	//如果是普通商品，同时删除所有赠品及其配件
            /* 检查购物车中该普通商品的不可单独销售的配件并删除 */
//             $sql = "SELECT c.rec_id
//                     FROM " . $GLOBALS['ecs']->table('cart') . " AS c, " . $GLOBALS['ecs']->table('group_goods') . " AS gg, " . $GLOBALS['ecs']->table('goods'). " AS g
//                     WHERE gg.parent_id = '" . $row['goods_id'] . "'
//                     AND c.goods_id = gg.goods_id
//                     AND c.parent_id = '" . $row['goods_id'] . "'
//                     AND c.extension_code <> 'package_buy'
//                     AND gg.goods_id = g.goods_id
//                     AND g.is_alone_sale = 0";
//             $res = $GLOBALS['db']->query($sql);

//             $sql = "DELETE FROM " . $GLOBALS['ecs']->table('cart') .
//             " WHERE session_id = '" . SESS_ID . "' " .
//             "AND (rec_id IN ($_del_str) OR parent_id = '$row[goods_id]' OR is_gift <> 0)";
//             $sql = "DELETE FROM " . $GLOBALS['ecs']->table('cart') .
//             " WHERE session_id = '" . SESS_ID . "' " .
//             "AND rec_id = '$id' LIMIT 1";
//         $GLOBALS['db']->query($sql);
}

/**
 * 删除购物车中不能单独销售的商品
 *
 * @access  public
 * @return  void
 */
function flow_clear_cart_alone() {
    /* 查询：购物车中所有不可以单独销售的配件 */
	$db_cart  = RC_Loader::load_app_model('cart_model', 'cart');
    $dbview  = RC_Loader::load_app_model('cart_group_goods_goods_viewmodel', 'cart');
    if ($_SESSION['user_id']) {
    	$data = $dbview->join(array('group_goods','goods'))->where(array('c.user_id' => $_SESSION['user_id'] , 'c.extension_code' => array('neq' => 'package_buy') , 'gg.parent_id' => array('gt' => 0) , 'g.is_alone_sale' => 0))->select();
    } else {
    	$data = $dbview->join(array('group_goods','goods'))->where(array('c.session_id' => SESS_ID , 'c.extension_code' => array('neq' => 'package_buy') , 'gg.parent_id' => array('gt' => 0) , 'g.is_alone_sale' => 0))->select();
    }
    
    $rec_id = array();
	if (!empty($data)) {
        foreach ($data as $row) {
            $rec_id[$row['rec_id']][] = $row['parent_id'];
        } 
    }
    
    if (empty($rec_id)) {
        return;
    }

    /* 查询：购物车中所有商品 */
	if ($_SESSION['user_id']) {
		$res = $db_cart->field('DISTINCT goods_id')->where(array('user_id' => $_SESSION['user_id'] , 'extension_code' => array('neq' => 'package_buy')))->select();
	} else {
		$res = $db_cart->field('DISTINCT goods_id')->where(array('session_id' => SESS_ID , 'extension_code' => array('neq' => 'package_buy')))->select();
	}
    
    $cart_good = array();
	if (!empty($res)) {
        foreach ($res as $row) {
            $cart_good[] = $row['goods_id'];
        } 
    }

    if (empty($cart_good)) {
        return;
    }

    /* 如果购物车中不可以单独销售配件的基本件不存在则删除该配件 */
    $del_rec_id = '';
    foreach ($rec_id as $key => $value) {
        foreach ($value as $v) {
            if (in_array($v, $cart_good)) {
                continue 2;
            }
        }
		$del_rec_id = $key . ',';
    }
    $del_rec_id = trim($del_rec_id, ',');

    if ($del_rec_id == '') {
        return;
    }

    /* 删除 */
    if ($_SESSION['user_id']) {
    	$db_cart->where(array('user_id' => $_SESSION['user_id']))->in(array('rec_id' => $del_rec_id))->delete();
    } else {
    	$db_cart->where(array('session_id' => SESS_ID))->in(array('rec_id' => $del_rec_id))->delete();
    }
    
//     $sql = "SELECT c.rec_id, gg.parent_id
//             FROM " . $GLOBALS['ecs']->table('cart') . " AS c
//                 LEFT JOIN " . $GLOBALS['ecs']->table('group_goods') . " AS gg ON c.goods_id = gg.goods_id
//                 LEFT JOIN" . $GLOBALS['ecs']->table('goods') . " AS g ON c.goods_id = g.goods_id
//             WHERE c.session_id = '" . SESS_ID . "'
//             AND c.extension_code <> 'package_buy'
//             AND gg.parent_id > 0
//             AND g.is_alone_sale = 0";
//     $res = $GLOBALS['db']->query($sql);
//     while (($row = $GLOBALS['db']->fetchRow($res)) != false)    
//     $sql = "SELECT DISTINCT goods_id
//             FROM " . $GLOBALS['ecs']->table('cart') . "
//             WHERE session_id = '" . SESS_ID . "'
//             AND extension_code <> 'package_buy'";
//     $res = $GLOBALS['db']->query($sql);
//     while (($row = $GLOBALS['db']->fetchRow($res)) != false)
//     $sql = "DELETE FROM " . $GLOBALS['ecs']->table('cart') ."
//             WHERE session_id = '" . SESS_ID . "'
//             AND rec_id IN ($del_rec_id)";
//     $GLOBALS['db']->query($sql);
    
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
function addto_cart($goods_id, $num = 1, $spec = array(), $parent = 0, $warehouse_id = 0, $area_id = 0) {
	$dbview 		= RC_Loader::load_app_model('sys_goods_member_viewmodel', 'goods');
	$db_cart 		= RC_Loader::load_app_model('cart_model', 'cart');
	$db_products 	= RC_Loader::load_app_model('products_model', 'goods');
	$db_group 		= RC_Loader::load_app_model('group_goods_model', 'goods');
    $_parent_id 	= $parent;
	RC_Loader::load_app_func('order', 'orders');
	RC_Loader::load_app_func('goods', 'goods');
	RC_Loader::load_app_func('common', 'goods');
	
	$field = "g.goods_id, wg.w_id, g.goods_name, g.goods_sn, g.is_on_sale, g.is_real, g.user_id as ru_id, g.model_inventory, g.model_attr, ".
			"g.is_xiangou, g.xiangou_start_date, g.xiangou_end_date, g.xiangou_num, ".
			"wg.warehouse_price, wg.warehouse_promote_price, wg.region_number as wg_number, wag.region_price, wag.region_promote_price, wag.region_number as wag_number, g.model_price, g.model_attr, ".
			"g.market_price, IF(g.model_price < 1, g.shop_price, IF(g.model_price < 2, wg.warehouse_price, wag.region_price)) AS org_price, ".
			"IF(g.model_price < 1, g.promote_price, IF(g.model_price < 2, wg.warehouse_promote_price, wag.region_promote_price)) as promote_price, ".
			" g.promote_start_date, g.promote_end_date, g.goods_weight, g.integral, g.extension_code, g.goods_number, g.is_alone_sale, g.is_shipping, ".
			"IFNULL(mp.user_price, IF(g.model_price < 1, g.shop_price, IF(g.model_price < 2, wg.warehouse_price, wag.region_price)) * '$_SESSION[discount]') AS shop_price ";
    /* 取得商品信息 */
   	$dbview->view = array(
   				'warehouse_goods' => array(
						'type'  => Component_Model_View::TYPE_LEFT_JOIN,
						'alias' => 'wg',
						'on'   	=> "g.goods_id = wg.goods_id and wg.region_id = '$warehouse_id'"
				),
	   			'warehouse_area_goods' => array(
	   					'type'  => Component_Model_View::TYPE_LEFT_JOIN,
	   					'alias' => 'wag',
	   					'on'   	=> "g.goods_id = wag.goods_id and wag.region_id = '$area_id'"
	   			),
				'member_price' => array(
						'type'  => Component_Model_View::TYPE_LEFT_JOIN,
						'alias' => 'mp',
						'on'   	=> "mp.goods_id = g.goods_id AND mp.user_rank = '$_SESSION[user_rank]'"
				)			
	); 
   	
   	$where = array(
   			'g.goods_id' => $goods_id,
   			'g.is_delete' => 0,
   	);
   	if(ecjia::config('review_goods') == 1){
   		$where['g.review_status'] = array('gt' => 2);
   	}
   	
   	
    $goods = $dbview->field($field)->join(array('warehouse_goods', 'warehouse_area_goods', 'member_price'))->find($where);
    
    
    if (empty($goods)) {
    	return new ecjia_error('no_goods', __('对不起，指定的商品不存在！'));
    }
    /* 是否正在销售 */
    if ($goods['is_on_sale'] == 0) {
    	return new ecjia_error('addcart_error', __('购买失败'));
    }
    
    
    /* 如果是作为配件添加到购物车的，需要先检查购物车里面是否已经有基本件 */
    if ($parent > 0) {
    	if ($_SESSION['user_id']) {
    		$count = $db_cart->where(array('goods_id' => $parent , 'user_id' => $_SESSION['user_id'] , 'extension_code' => array('neq' => 'package_buy')))->count();
    	} else {
    		$count = $db_cart->where(array('goods_id' => $parent , 'session_id' => SESS_ID , 'extension_code' => array('neq' => 'package_buy')))->count();
    	}
    	
        if ($count == 0) {
			return new ecjia_error('addcart_error', __('对不起，您希望将该商品做为配件购买，可是购物车中还没有该商品的基本件。'));
        }
    }

    /* 不是配件时检查是否允许单独销售 */
    if (empty($parent) && $goods['is_alone_sale'] == 0) {
		return new ecjia_error('addcart_error', __('购买失败'));
    }
    
   
    
//     $sql = "SELECT * FROM " .$GLOBALS['ecs']->table($table_products). " WHERE goods_id = '$goods_id'" .$type_files. " LIMIT 0, 1";
//     $prod = $GLOBALS['db']->getRow($sql);
    /* 如果商品有规格则取规格商品信息 配件除外 */
    if ($goods['model_attr'] == 1) {
    	$db = RC_Loader::load_app_model('products_warehouse_model', 'warehouse');
    	$prod = $db->where(array('goods_id' => $goods_id, 'warehouse_id' => $warehouse_id))->find();
//     	$table_products = "products_warehouse";
//     	$type_files = " and warehouse_id = '$warehouse_id'";
    } elseif($goods['model_attr'] == 2) {
    	$db = RC_Loader::load_app_model('products_area_model', 'warehouse');
    	$prod = $db->where(array('goods_id' => $goods_id, 'area_id' => $area_id))->find();
//     	$table_products = "products_area";
//     	$type_files = " and area_id = '$area_id'";
    } else {
//     	$table_products = "products";
//     	$type_files = "";
    	$prod = $db_products->find(array('goods_id' => $goods_id));
    }
    
    if (is_spec($spec) && !empty($prod)) {
        $product_info = get_products_info($goods_id, $spec, $warehouse_id, $area_id);
    }
    if (empty($product_info)) {
        $product_info = array('product_number' => '', 'product_id' => 0 , 'goods_attr'=>'');
    }

    if ($goods['model_inventory'] == 1) {
    	$goods['goods_number'] = $goods['wg_number'];
    } elseif($goods['model_inventory'] == 2) {
    	$goods['goods_number'] = $goods['wag_number'];
    }
    
    /* 检查：库存 */
    if (ecjia::config('use_storage') == 1) {
		//检查：商品购买数量是否大于总库存
		if ($num > $goods['goods_number']) {
			return new ecjia_error('low_stocks', __('库存不足'));
		}
		//商品存在规格 是货品 检查该货品库存
    	if (is_spec($spec) && !empty($prod)) {
    		if (!empty($spec)) {
				/* 取规格的货品库存 */
    			if ($num > $product_info['product_number']) {
    				return new ecjia_error('low_stocks', __('库存不足'));
    			}
    		}
    	}
    }
  
    /* 计算商品的促销价格 */
    $warehouse_area['warehouse_id'] = $warehouse_id;
    $warehouse_area['area_id'] = $area_id;
    
    $spec_price             = spec_price($spec, $goods_id, $warehouse_area);
    $goods_price            = get_final_price($goods_id, $num, true, $spec, $warehouse_id, $area_id);
    $goods['market_price'] += $spec_price;
    $goods_attr             = get_goods_attr_info($spec, 'pice', $warehouse_id, $area_id);
    $goods_attr_id          = join(',', $spec);
	
    /* 初始化要插入购物车的基本件数据 */
    $parent = array(
        'user_id'       => $_SESSION['user_id'],
        'session_id'    => SESS_ID,
        'goods_id'      => $goods_id,
        'goods_sn'      => addslashes($goods['goods_sn']),
        'product_id'    => $product_info['product_id'],
        'goods_name'    => addslashes($goods['goods_name']),
        'market_price'  => $goods['market_price'],
        'goods_attr'    => addslashes($goods_attr),
        'goods_attr_id' => $goods_attr_id,
        'is_real'       => $goods['is_real'],
        'extension_code'=> $goods['extension_code'],
        'is_gift'       => 0,
        'is_shipping'   => $goods['is_shipping'],
        'rec_type'      => CART_GENERAL_GOODS,
    	'ru_id'			=> $goods['user_id'],
    	'model_attr'  	=> $goods['model_attr'], //属性方式
        'warehouse_id'  => $warehouse_id,  //仓库
        'area_id'  		=> $area_id, // 仓库地区
        'ru_id'			=> $goods['ru_id'],
    );

    /* 如果该配件在添加为基本件的配件时，所设置的“配件价格”比原价低，即此配件在价格上提供了优惠， */
    /* 则按照该配件的优惠价格卖，但是每一个基本件只能购买一个优惠价格的“该配件”，多买的“该配件”不享受此优惠 */
    $basic_list = array();
    $data = $db_group->field('parent_id, goods_price')->where('goods_id = '.$goods_id.' AND goods_price < "'.$goods_price.'" AND parent_id = '.$_parent_id.'')->order('goods_price asc')->select();

    if(!empty($data)) {
	    foreach ($data as $row) {
	        $basic_list[$row['parent_id']] = $row['goods_price'];
	    }
    }
    /* 取得购物车中该商品每个基本件的数量 */
    $basic_count_list = array();
    if ($basic_list) {
    	if ($_SESSION['user_id']) {
    		$data = $db_cart->field('goods_id, SUM(goods_number)|count')->where(array('user_id'=>$_SESSION['user_id'],'parent_id' => '0' , extension_code =>array('neq'=>"package_buy")))->in(array('goods_id'=>array_keys($basic_list)))->order('goods_id asc')->select();
    	} else {
    		$data = $db_cart->field('goods_id, SUM(goods_number)|count')->where(array('session_id'=>SESS_ID,'parent_id' => '0' , extension_code =>array('neq'=>"package_buy")))->in(array('goods_id'=>array_keys($basic_list)))->order('goods_id asc')->select();
    	}
    	if(!empty($data)) {
	        foreach ($data as $row) {
	            $basic_count_list[$row['goods_id']] = $row['count'];
	        }
        }
    }
    /* 取得购物车中该商品每个基本件已有该商品配件数量，计算出每个基本件还能有几个该商品配件 */
    /* 一个基本件对应一个该商品配件 */
    if ($basic_count_list) {
    	if ($_SESSION['user_id']) {
    		$data = $db_cart->field('parent_id, SUM(goods_number)|count')->where(array('user_id' => $_SESSION['user_id'],'goods_id'=>$goods_id,extension_code =>array('neq'=>"package_buy")))->in(array('parent_id'=>array_keys($basic_count_list)))->order('parent_id asc')->select();
    	} else {
    		$data = $db_cart->field('parent_id, SUM(goods_number)|count')->where(array('session_id' => SESS_ID,'goods_id'=>$goods_id,extension_code =>array('neq'=>"package_buy")))->in(array('parent_id'=>array_keys($basic_count_list)))->order('parent_id asc')->select();
    	}
    	
        if(!empty($data)) {
	        foreach ($data as $row) {
	            $basic_count_list[$row['parent_id']] -= $row['count'];
	        }
        }
    }
	
    /* 循环插入配件 如果是配件则用其添加数量依次为购物车中所有属于其的基本件添加足够数量的该配件 */
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

    /* 如果数量不为0，作为基本件插入 */
    if ($num > 0) {
        /* 检查该商品是否已经存在在购物车中 */
    	if ($_SESSION['user_id']) {
    		$row = $db_cart->field('rec_id, goods_number')->find('user_id = "' .$_SESSION['user_id']. '" AND goods_id = '.$goods_id.' AND parent_id = 0 AND goods_attr = "' .get_goods_attr_info($spec).'" AND extension_code <> "package_buy" AND rec_type = "'.CART_GENERAL_GOODS.'" ');
    	} else {
    		$row = $db_cart->field('rec_id, goods_number')->find('session_id = "' .SESS_ID. '" AND goods_id = '.$goods_id.' AND parent_id = 0 AND goods_attr = "' .get_goods_attr_info($spec).'" AND extension_code <> "package_buy" AND rec_type = "'.CART_GENERAL_GOODS.'" ');
    	}
    	
    	/* 限购判断*/
    	if ($goods['is_xiangou'] > 0) {
    		$order_info_viewdb = RC_Loader::load_app_model('order_info_viewmodel', 'orders');
    		$order_info_viewdb->view = array(
    				'order_goods' => array(
    						'type'     => Component_Model_View::TYPE_LEFT_JOIN,
    						'alias' => 'g',
    						'on'     => 'oi.order_id = g.order_id '
    				)
    		);
    		$xiangou = array(
    				'oi.add_time >=' . $goods['xiangou_start_date'] . ' and oi.add_time <=' .$goods['xiangou_end_date'],
    				'g.goods_id'	=> $goods['goods_id'],
    				'oi.user_id'	=> $_SESSION['user_id'],
    		);
    		$xiangou_info = $order_info_viewdb->join(array('order_goods'))->field(array('sum(goods_number) as number'))->where($xiangou)->find();
    		
    		if ($xiangou_info['number'] + $row['goods_number'] >= $goods['xiangou_num']) {
    			return new ecjia_error('xiangou_error', __('该商品已限购'));
    		}
    	}
    	
        if($row) {
        	//如果购物车已经有此物品，则更新
            $num += $row['goods_number'];
            if(is_spec($spec) && !empty($prod) ) {
             	$goods_storage=$product_info['product_number'];
            } else {
                $goods_storage=$goods['goods_number'];
            }
            if (ecjia::config('use_storage') == 0 || $num <= $goods_storage) {
                $goods_price = get_final_price($goods_id, $num, true, $spec, $warehouse_id, $area_id);
                $data =  array(
                		'goods_number' => $num,
                		'goods_price'  => $goods_price,
                		'area_id'	   => $area_id,
                );
                if ($_SESSION['user_id']) {
                	$db_cart->where('user_id = "' .$_SESSION['user_id']. '" AND goods_id = '.$goods_id.' AND parent_id = 0 AND goods_attr = "' .get_goods_attr_info($spec).'" AND extension_code <> "package_buy" AND rec_type = "'.CART_GENERAL_GOODS.'" AND warehouse_id = "'.$warehouse_id.'"')->update($data);
                } else {
                	$db_cart->where('session_id = "' .SESS_ID. '" AND goods_id = '.$goods_id.' AND parent_id = 0 AND goods_attr = "' .get_goods_attr_info($spec).'" AND extension_code <> "package_buy" AND rec_type = "'.CART_GENERAL_GOODS.'"  AND warehouse_id = "'.$warehouse_id.'"')->update($data);
                }
            } else {
				return new ecjia_error('low_stocks', __('库存不足'));
            }
            
            $cart_id = $row['rec_id'];
        } else {
        	//购物车没有此物品，则插入
            $goods_price = get_final_price($goods_id, $num, true, $spec ,$warehouse_id, $area_id);
            $parent['goods_price']  = max($goods_price, 0);
            $parent['goods_number'] = $num;
            $parent['parent_id']    = 0;
			$cart_id = $db_cart->insert($parent);
        }
    }

    /* 把赠品删除 */
    if ($_SESSION['user_id']) {
    	$db_cart->where(array('user_id' => $_SESSION['user_id'] , 'is_gift' => array('neq' => 0)))->delete();
    } else {
    	$db_cart->where(array('session_id' => SESS_ID , 'is_gift' => array('neq' => 0)))->delete();
    }
    
    return $cart_id; 
//     return true;
    
// 	$GLOBALS['err']->clean();
// 	$sql = "SELECT g.goods_name, g.goods_sn, g.is_on_sale, g.is_real, ".
// 			"g.market_price, g.shop_price AS org_price, g.promote_price, g.promote_start_date, ".
// 			"g.promote_end_date, g.goods_weight, g.integral, g.extension_code, ".
// 			"g.goods_number, g.is_alone_sale, g.is_shipping,".
// 			"IFNULL(mp.user_price, g.shop_price * '$_SESSION[discount]') AS shop_price ".
// 			" FROM " .$GLOBALS['ecs']->table('goods'). " AS g ".
// 			" LEFT JOIN " . $GLOBALS['ecs']->table('member_price') . " AS mp ".
// 			"ON mp.goods_id = g.goods_id AND mp.user_rank = '$_SESSION[user_rank]' ".
// 			" WHERE g.goods_id = '$goods_id'" .
// 			" AND g.is_delete = 0";
// 	$goods = $GLOBALS['db']->getRow($sql);

// 	$sql = "SELECT COUNT(*) FROM " . $GLOBALS['ecs']->table('cart') .
// 	" WHERE goods_id='$parent' AND session_id='" . SESS_ID . "' AND extension_code <> 'package_buy'";
// 	if ($GLOBALS['db']->getOne($sql) == 0)
	
// 	$sql = "SELECT * FROM " .$GLOBALS['ecs']->table('products'). " WHERE goods_id = '$goods_id' LIMIT 0, 1";
// 	$prod = $GLOBALS['db']->getRow($sql);

// 	$sql = "SELECT parent_id, goods_price " .
// 			"FROM " . $GLOBALS['ecs']->table('group_goods') .
// 			" WHERE goods_id = '$goods_id'" .
// 			" AND goods_price < '$goods_price'" .
// 			" AND parent_id = '$_parent_id'" .
// 			" ORDER BY goods_price";
// 	$res = $GLOBALS['db']->query($sql);
// 	while ($row = $GLOBALS['db']->fetchRow($res))
	
// 	$sql = "SELECT goods_id, SUM(goods_number) AS count " .
// 			"FROM " . $GLOBALS['ecs']->table('cart') .
// 			" WHERE session_id = '" . SESS_ID . "'" .
// 			" AND parent_id = 0" .
// 			" AND extension_code <> 'package_buy' " .
// 			" AND goods_id " . db_create_in(array_keys($basic_list)) .
// 			" GROUP BY goods_id";
// 	$res = $GLOBALS['db']->query($sql);
// 	while ($row = $GLOBALS['db']->fetchRow($res))
	
// 	$sql = "SELECT parent_id, SUM(goods_number) AS count " .
// 			"FROM " . $GLOBALS['ecs']->table('cart') .
// 			" WHERE session_id = '" . SESS_ID . "'" .
// 			" AND goods_id = '$goods_id'" .
// 			" AND extension_code <> 'package_buy' " .
// 			" AND parent_id " . db_create_in(array_keys($basic_count_list)) .
// 			" GROUP BY parent_id";
// 	$res = $GLOBALS['db']->query($sql);
// 	while ($row = $GLOBALS['db']->fetchRow($res))
// 	$GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('cart'), $parent, 'INSERT');

// 	$sql = "SELECT goods_number FROM " .$GLOBALS['ecs']->table('cart').
// 	" WHERE session_id = '" .SESS_ID. "' AND goods_id = '$goods_id' ".
// 	" AND parent_id = 0 AND goods_attr = '" .get_goods_attr_info($spec). "' " .
// 	" AND extension_code <> 'package_buy' " .
// 	" AND rec_type = 'CART_GENERAL_GOODS'";	
// 	$row = $GLOBALS['db']->getRow($sql);
	
// 	$sql = "UPDATE " . $GLOBALS['ecs']->table('cart') . " SET goods_number = '$num'" .
// 	" , goods_price = '$goods_price'".
// 	" WHERE session_id = '" .SESS_ID. "' AND goods_id = '$goods_id' ".
// 	" AND parent_id = 0 AND goods_attr = '" .get_goods_attr_info($spec). "' " .
// 	" AND extension_code <> 'package_buy' " .
// 	"AND rec_type = 'CART_GENERAL_GOODS'";
// 	$GLOBALS['db']->query($sql);
// 	$GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('cart'), $parent, 'INSERT');
	
// 	$sql = "DELETE FROM " . $GLOBALS['ecs']->table('cart') . " WHERE session_id = '" . SESS_ID . "' AND is_gift <> 0";
// 	$GLOBALS['db']->query($sql);
    
/* 取得商品信息 */    
//        $GLOBALS['err']->add(RC_Lang::lang('goods_not_exists'), ERR_NOT_EXISTS);
//        return false;    

//            $GLOBALS['err']->add(RC_Lang::lang('no_basic_goods'), ERR_NO_BASIC_GOODS);
//            return false;    
//        $GLOBALS['err']->add(RC_Lang::lang('not_on_sale'), ERR_NOT_ON_SALE);
//        return false;
//        $GLOBALS['err']->add(RC_Lang::lang('cannt_alone_sale'), ERR_CANNT_ALONE_SALE);
//        return false;    
//         //商品存在规格 是货品 检查该货品库存
//         if (is_spec($spec) && !empty($prod)) {
//             if (!empty($spec)) {
//                 /* 取规格的货品库存 */
//                 if ($num > $product_info['product_number']) {
// //                    $GLOBALS['err']->add(sprintf(RC_Lang::lang('shortage'), $product_info['product_number']), ERR_OUT_OF_STOCK);
// //                    return false;
// 					  EM_Api::outPut(10008);
//                 }
//             }
//         }    
//$GLOBALS['err']->add(sprintf(RC_Lang::lang('shortage'), $goods['goods_number']), ERR_OUT_OF_STOCK);

//               	$GLOBALS['err']->add(sprintf(RC_Lang::lang('shortage'), $num), ERR_OUT_OF_STOCK);
//                return false;

    
}


/**
 * 获得用户的可用积分
 *
 * @access  private
 * @return  integral
 */
function flow_available_points($cart_id = array()) {
	$db_view = RC_Loader::load_app_model('cart_goods_viewmodel', 'cart');
	$cart_where = array('c.user_id' => $_SESSION['user_id'], 'c.is_gift' => 0 , 'g.integral' => array('gt' => '0') , 'c.rec_type' => CART_GENERAL_GOODS);
	if (!empty($cart_id)) {
		$cart_where = array_merge($cart_where, array('rec_id' => $cart_id));
	}
	if ($_SESSION['user_id']) {
		$cart_where = array_merge($cart_where, array('c.user_id' => $_SESSION['user_id']));
		$data = $db_view->join('goods')->where($cart_where)->sum('g.integral * c.goods_number');
	} else {
		$cart_where = array_merge($cart_where, array('c.session_id' => SESS_ID));
		$data = $db_view->join('goods')->where($cart_where)->sum('g.integral * c.goods_number');
	}
	$val = intval($data);
	RC_Loader::load_app_func('order','orders');
	return integral_of_value($val);
	
	//     $sql = "SELECT SUM(g.integral * c.goods_number) ".
	//         "FROM " . $GLOBALS['ecs']->table('cart') . " AS c, " . $GLOBALS['ecs']->table('goods') . " AS g " .
	//         "WHERE c.session_id = '" . SESS_ID . "' AND c.goods_id = g.goods_id AND c.is_gift = 0 AND g.integral > 0 " .
	//         "AND c.rec_type = '" . CART_GENERAL_GOODS . "'";
	
	//     $val = intval($GLOBALS['db']->getOne($sql));
	
	//     return integral_of_value($val);	
}

/**
 * 检查订单中商品库存
 *
 * @access  public
 * @param   array   $arr
 *
 * @return  void
 */
function flow_cart_stock($arr) {
	foreach ($arr AS $key => $val) {
		$val = intval(make_semiangle($val));
		if ($val <= 0 || !is_numeric($key)) {
			continue;
		}

		$db_cart = RC_Loader::load_app_model('cart_model', 'cart');
		$db_products = RC_Loader::load_app_model('products_model', 'goods');
		$dbview = RC_Loader::load_app_model('goods_cart_viewmodel', 'goods');
		if ($_SESSION['user_id']) {
			$goods = $db_cart->field('goods_id,goods_attr_id,extension_code, product_id')->find(array('rec_id' => $key , 'user_id' => $_SESSION['user_id']));
		} else {
			$goods = $db_cart->field('goods_id,goods_attr_id,extension_code, product_id')->find(array('rec_id' => $key , 'session_id' => SESS_ID));
		}

		$row   = $dbview->field('c.product_id, g.is_on_sale, g.is_delete')->join('cart')->find(array('c.rec_id' => $key));
		//系统启用了库存，检查输入的商品数量是否有效
		if (intval(ecjia::config('use_storage')) > 0 && $goods['extension_code'] != 'package_buy') {
			if ($row['is_on_sale'] == 0 || $row['is_delete'] == 1) {
				return new ecjia_error('put_on_sale', '商品['.$row['goods_name'].']下架');
			}
			
			if ($row['goods_number'] < $val) {
				return new ecjia_error('low_stocks', __('库存不足'));
			}
			/* 是货品 */
			$row['product_id'] = trim($row['product_id']);
			if (!empty($row['product_id'])) {
				$product_number = $db_products->where(array('goods_id' => $goods['goods_id'] , 'product_id' => $goods['product_id']))->get_field('product_number');
				if ($product_number < $val) {
					return new ecjia_error('low_stocks', __('库存不足'));
				}
			}
		} elseif (intval(ecjia::config('use_storage')) > 0 && $goods['extension_code'] == 'package_buy') {
			if (judge_package_stock($goods['goods_id'], $val)) {
				return new ecjia_error('low_stocks', __('库存不足'));
			}
		}
	}
	//         $sql = "SELECT `goods_id`, `goods_attr_id`, `extension_code` FROM" .$GLOBALS['ecs']->table('cart').
	//         " WHERE rec_id='$key' AND session_id='" . SESS_ID . "'";
	//         $goods = $GLOBALS['db']->getRow($sql);

	//         $sql = "SELECT g.goods_name, g.goods_number, c.product_id ".
	//             "FROM " .$GLOBALS['ecs']->table('goods'). " AS g, ".
	//             $GLOBALS['ecs']->table('cart'). " AS c ".
	//             "WHERE g.goods_id = c.goods_id AND c.rec_id = '$key'";
	//         $row = $GLOBALS['db']->getRow($sql);
	//                 $sql = "SELECT product_number FROM " .$GLOBALS['ecs']->table('products'). " WHERE goods_id = '" . $goods['goods_id'] . "' AND product_id = '" . $row['product_id'] . "'";
	//                 $product_number = $GLOBALS['db']->getOne($sql);
}

/**
 * 重新计算购物车中的商品价格：目的是当用户登录时享受会员价格，当用户退出登录时不享受会员价格
 * 如果商品有促销，价格不变
 *
 * @access public
 * @return void
 */
function recalculate_price()
{
	// 链接数据库
	$db_cart = RC_Loader::load_app_model('cart_model', 'cart');
	$dbview = RC_Loader::load_app_model('cart_good_member_viewmodel', 'cart');

	/* 取得有可能改变价格的商品：除配件和赠品之外的商品 */
	if ($_SESSION['user_id']) {
		$res = $dbview->join(array(
				'goods',
				'member_price'
		))
		->where('c.user_id = "' . $_SESSION['user_id'] . '" AND c.parent_id = 0 AND c.is_gift = 0 AND c.goods_id > 0 AND c.rec_type = "' . CART_GENERAL_GOODS . '" AND c.extension_code <> "package_buy"')
		->select();
	} else {
		$res = $dbview->join(array(
				'goods',
				'member_price'
		))
		->where('c.session_id = "' . SESS_ID . '" AND c.parent_id = 0 AND c.is_gift = 0 AND c.goods_id > 0 AND c.rec_type = "' . CART_GENERAL_GOODS . '" AND c.extension_code <> "package_buy"')
		->select();
	}

	if (! empty($res)) {
		RC_Loader::load_app_func('common','goods');
		foreach ($res as $row) {
			$attr_id = empty($row['goods_attr_id']) ? array() : explode(',', $row['goods_attr_id']);
			$goods_price = get_final_price($row['goods_id'], $row['goods_number'], true, $attr_id);
			$data = array(
					'goods_price' => $goods_price
			);
			if ($_SESSION['user_id']) {
				$db_cart->where('goods_id = ' . $row['goods_id'] . ' AND user_id = "' . $_SESSION['user_id'] . '" AND rec_id = "' . $row['rec_id'] . '"')->update($data);
			} else {
				$db_cart->where('goods_id = ' . $row['goods_id'] . ' AND session_id = "' . SESS_ID . '" AND rec_id = "' . $row['rec_id'] . '"')->update($data);
			}
		}
	}
	/* 删除赠品，重新选择 */

	if ($_SESSION['user_id']) {
		$db_cart->where('user_id = "' . $_SESSION['user_id'] . '" AND is_gift > 0')->delete();
	} else {
		$db_cart->where('session_id = "' . SESS_ID . '" AND is_gift > 0')->delete();
	}
}

/**
 * 获得购物车中商品的总重量、总价格、总数量
 *
 * @access  public
 * @param   int	 $type   类型：默认普通商品
 * @return  array
 */
function cart_weight_price($type = CART_GENERAL_GOODS, $cart_id = array()) {
	$db 			= RC_Loader::load_app_model('cart_model', 'cart');
	$dbview 		= RC_Loader::load_app_model('package_goods_viewmodel','orders');
	$db_cartview 	= RC_Loader::load_app_model('cart_good_member_viewmodel', 'cart');

	$package_row['weight'] 			= 0;
	$package_row['amount'] 			= 0;
	$package_row['number'] 			= 0;
	$packages_row['free_shipping'] 	= 1;
	if (!empty($cart_id)) {
		$where = array('rec_id' => $cart_id);
	}
	
	/* 计算超值礼包内商品的相关配送参数 */
	if ($_SESSION['user_id']) {
		$row = $db->field('goods_id, goods_number, goods_price')->where(array_merge($where, array('extension_code' => 'package_buy' , 'user_id' => $_SESSION['user_id'] )))->select();
	} else {
		$row = $db->field('goods_id, goods_number, goods_price')->where(array_merge($where, array('extension_code' => 'package_buy' , 'session_id' => SESS_ID )))->select();
	}

	if ($row) {
		$packages_row['free_shipping'] = 0;
		$free_shipping_count = 0;
		foreach ($row as $val) {
			// 如果商品全为免运费商品，设置一个标识变量
			$dbview->view = array(
					'goods' => array(
							'type'  => Component_Model_View::TYPE_LEFT_JOIN,
							'alias' => 'g',
							'on'    => 'g.goods_id = pg.goods_id ',
					)
			);

			$shipping_count = $dbview->where(array('g.is_shipping' => 0 , 'pg.package_id' => $val['goods_id']))->count();
			if ($shipping_count > 0) {
				// 循环计算每个超值礼包商品的重量和数量，注意一个礼包中可能包换若干个同一商品
				$dbview->view = array(
						'goods' => array(
								'type'  => Component_Model_View::TYPE_LEFT_JOIN,
								'alias' => 'g',
								'field' => 'SUM(g.goods_weight * pg.goods_number)|weight,SUM(pg.goods_number)|number',
								'on'    => 'g.goods_id = pg.goods_id',
						)
				);
				$goods_row = $dbview->find(array('g.is_shipping' => 0 , 'pg.package_id' => $val['goods_id']));

				$package_row['weight'] += floatval($goods_row['weight']) * $val['goods_number'];
				$package_row['amount'] += floatval($val['goods_price']) * $val['goods_number'];
				$package_row['number'] += intval($goods_row['number']) * $val['goods_number'];
			} else {
				$free_shipping_count++;
			}
		}
		$packages_row['free_shipping'] = $free_shipping_count == count($row) ? 1 : 0;
	}

	/* 获得购物车中非超值礼包商品的总重量 */
	$db_cartview->view =array(
			'goods' => array(
					'type'  => Component_Model_View::TYPE_LEFT_JOIN,
					'alias' => 'g',
					'field' => 'SUM(g.goods_weight * c.goods_number)|weight,SUM(c.goods_price * c.goods_number)|amount,SUM(c.goods_number)|number',
					'on'    => 'g.goods_id = c.goods_id'
			)
	);
	if ($_SESSION['user_id']) {
		$row = $db_cartview->find(array_merge($where, array('c.user_id' => $_SESSION['user_id'] , 'rec_type' => $type , 'g.is_shipping' => 0 , 'c.extension_code' => array('neq' => package_buy))));
	} else {
		$row = $db_cartview->find(array_merge($where, array('c.session_id' => SESS_ID , 'rec_type' => $type , 'g.is_shipping' => 0 , 'c.extension_code' => array('neq' => package_buy))));
	}

	$packages_row['weight'] = floatval($row['weight']) + $package_row['weight'];
	$packages_row['amount'] = floatval($row['amount']) + $package_row['amount'];
	$packages_row['number'] = intval($row['number']) + $package_row['number'];
	/* 格式化重量 */
	$packages_row['formated_weight'] = formated_weight($packages_row['weight']);
	return $packages_row;
	// 	$sql = 'SELECT goods_id, goods_number, goods_price FROM ' . $GLOBALS['ecs']->table('cart') . " WHERE extension_code = 'package_buy' AND session_id = '" . SESS_ID . "'";
	// 	$row = $GLOBALS['db']->getAll($sql);

	// 	$sql = 'SELECT count(*) FROM ' .
	// 			$GLOBALS['ecs']->table('package_goods') . ' AS pg, ' .
	// 			$GLOBALS['ecs']->table('goods') . ' AS g ' .
	// 			"WHERE g.goods_id = pg.goods_id AND g.is_shipping = 0 AND pg.package_id = '"  . $val['goods_id'] . "'";
	// 	$shipping_count = $GLOBALS['db']->getOne($sql);

	// 	$sql = 'SELECT SUM(g.goods_weight * pg.goods_number) AS weight, ' .
	// 			'SUM(pg.goods_number) AS number FROM ' .
	// 			$GLOBALS['ecs']->table('package_goods') . ' AS pg, ' .
	// 			$GLOBALS['ecs']->table('goods') . ' AS g ' .
	// 			"WHERE g.goods_id = pg.goods_id AND g.is_shipping = 0 AND pg.package_id = '"  . $val['goods_id'] . "'";
	// 	$goods_row = $GLOBALS['db']->getRow($sql);

	// 	$sql    = 'SELECT SUM(g.goods_weight * c.goods_number) AS weight, ' .
	// 			'SUM(c.goods_price * c.goods_number) AS amount, ' .
	// 			'SUM(c.goods_number) AS number '.
	// 			'FROM ' . $GLOBALS['ecs']->table('cart') . ' AS c '.
	// 			'LEFT JOIN ' . $GLOBALS['ecs']->table('goods') . ' AS g ON g.goods_id = c.goods_id '.
	// 			"WHERE c.session_id = '" . SESS_ID . "' " .
	// 			"AND rec_type = '$type' AND g.is_shipping = 0 AND c.extension_code != 'package_buy'";
	// 	$row = $GLOBALS['db']->getRow($sql);
}

/**
 * 取得购物车商品
 * @param   int     $type   类型：默认普通商品
 * @return  array   购物车商品数组
 */
function cart_goods($type = CART_GENERAL_GOODS, $cart_id = array()) {

// 	$db = RC_Loader::load_app_model('cart_model', 'cart');
	$db = RC_Loader::load_app_model('cart_goods_viewmodel', 'cart');
	
	$cart_where = array('rec_type' => $type);
	if (!empty($cart_id)) {
		$cart_where = array_merge($cart_where,  array('rec_id' => $cart_id));
	}
	$field = 'goods_img, original_img, goods_thumb, c.rec_id, c.user_id, c.goods_id, c.goods_name, c.goods_sn, c.goods_number, c.market_price, c.goods_price, c.goods_attr, c.is_real, c.extension_code, c.parent_id, c.is_gift, c.is_shipping, c.goods_price * c.goods_number|subtotal, goods_weight as goodsWeight, c.goods_attr_id';
	if ($_SESSION['user_id']) {
		$cart_where = array_merge($cart_where, array('c.user_id' => $_SESSION['user_id']));
		$arr = $db->field($field)->where($cart_where)->select();
	} else {
		$cart_where = array_merge($cart_where, array('session_id' => SESS_ID));
		$arr = $db->field($field)->where($cart_where)->select();
	}

	$db_goods_attr = RC_Loader::load_app_model('goods_attr_model', 'goods');
	$db_goods = RC_Loader::load_app_model('goods_model', 'goods');
	$order_info_viewdb = RC_Loader::load_app_model('order_info_viewmodel', 'orders');
	$order_info_viewdb->view = array(
			'order_goods' => array(
					'type'     => Component_Model_View::TYPE_LEFT_JOIN,
					'alias' => 'g',
					'on'     => 'oi.order_id = g.order_id '
			)
	);
	/* 格式化价格及礼包商品 */
	foreach ($arr as $key => $value) {
		$goods = $db_goods->field(array('is_xiangou', 'xiangou_start_date', 'xiangou_end_date', 'xiangou_num'))->find(array('goods_id' => $value['goods_id']));
		/* 限购判断*/
		if ($goods['is_xiangou'] > 0) {
			$xiangou = array(
					'oi.add_time >=' . $goods['xiangou_start_date'] . ' and oi.add_time <=' .$goods['xiangou_end_date'],
					'g.goods_id'	=> $value['goods_id'],
					'oi.user_id'	=> $_SESSION['user_id'],
			);
			$xiangou_info = $order_info_viewdb->join(array('order_goods'))->field(array('sum(goods_number) as number'))->where($xiangou)->find();
		
			if ($xiangou_info['number'] + $value['goods_number'] > $goods['xiangou_num']) {
				return new ecjia_error('xiangou_error', __('该商品已限购'));
			}
		}
		
		$arr[$key]['formated_market_price'] = price_format($value['market_price'], false);
		$arr[$key]['formated_goods_price']  = $value['goods_price'] > 0 ? price_format($value['goods_price'], false) : __('免费');
		$arr[$key]['formated_subtotal']     = price_format($value['subtotal'], false);
		
		/* 查询规格 */
		if (trim($value['goods_attr']) != '' && $value['group_id'] == '') {//兼容官网套餐问题增加条件group_id
			$value['goods_attr_id'] = empty($value['goods_attr_id']) ? '' : explode(',', $value['goods_attr_id']);
			$attr_list = $db_goods_attr->field('attr_value')->in(array('goods_attr_id' => $value['goods_attr_id']))->select();
			foreach ($attr_list AS $attr) {
				$arr[$key]['goods_name'] .= ' [' . $attr['attr_value'] . '] ';
			}
		}
		
		$arr[$key]['goods_attr'] = array();
		if (!empty($value['goods_attr'])) {
			$goods_attr = explode("\n", $value['goods_attr']);
			$goods_attr = array_filter($goods_attr);
			
			foreach ($goods_attr as  $v) {
				$a = explode(':',$v);
				if (!empty($a[0]) && !empty($a[1])) {
					$arr[$key]['goods_attr'][] = array('name'=>$a[0], 'value'=>$a[1]);
				}
			}
		}
		RC_Loader::load_app_func('common', 'goods');
		$arr[$key]['img'] = array(
				'thumb'	=> get_image_path($value['goods_id'], $value['goods_img'], true),
				'url'	=> get_image_path($value['goods_id'], $value['original_img'], true),
				'small' => get_image_path($value['goods_id'], $value['goods_thumb'], true),
		);
		unset($arr[$key]['goods_thumb']);
		unset($arr[$key]['goods_img']);
		unset($arr[$key]['original_img']);
		if ($value['extension_code'] == 'package_buy') {
			$arr[$key]['package_goods_list'] = get_package_goods($value['goods_id']);
		}
	}
	return $arr;

	//     $sql = "SELECT rec_id, user_id, goods_id, goods_name, goods_sn, goods_number, " .
	//             "market_price, goods_price, goods_attr, is_real, extension_code, parent_id, is_gift, is_shipping, " .
	//             "goods_price * goods_number AS subtotal " .
	//             "FROM " . $GLOBALS['ecs']->table('cart') ." WHERE session_id = '" . SESS_ID . "' " ."AND rec_type = '$type'";
	//     $arr = $GLOBALS['db']->getAll($sql);
	//	$arr = $db->field('rec_id, user_id, goods_id, goods_name, goods_sn, goods_number,market_price, goods_price, goods_attr, is_real, extension_code, parent_id, is_gift, is_shipping, goods_price * goods_number|subtotal')->
	//	where('session_id = "'. SESS_ID . '" AND rec_type = "'.$type.'"')->select();

}


/**
 * 取得购物车总金额
 * @params  boolean $include_gift   是否包括赠品
 * @param   int     $type           类型：默认普通商品
 * @return  float   购物车总金额
 */
function cart_amount($include_gift = true, $type = CART_GENERAL_GOODS, $cart_id = array()) {
	$db = RC_Loader::load_app_model('cart_model', 'cart');

	if ($_SESSION['user_id']) {
		$where['user_id'] = $_SESSION['user_id'];
	} else {
		$where['session_id'] = SESS_ID;
	}
	if (!empty($cart_id)) {
		$where['rec_id'] = $cart_id;
	}
	$where['rec_type'] = $type;

	if (!$include_gift) {
		$where['is_gift'] = 0;
		$where['goods_id']= array('gt'=>0);
	}

	$data = $db->where($where)->sum('goods_price * goods_number');
	return $data;

	//     $sql = "SELECT SUM(goods_price * goods_number) " .
	//             " FROM " . $GLOBALS['ecs']->table('cart') .
	//             " WHERE session_id = '" . SESS_ID . "' " ."AND rec_type = '$type' ";
	// 	$sql .= ' AND is_gift = 0 AND goods_id > 0';
	// 	return floatval($GLOBALS['db']->getOne($sql));


	//	$db = RC_Loader::load_app_model('cart_model','flow');
	//    $data = $db->where("session_id = '" . SESS_ID . "' AND rec_type = '$type' ".$where)->sum('goods_price * goods_number');
}

/**
 * 清空购物车
 * @param   int	 $type   类型：默认普通商品
 */
function clear_cart($type = CART_GENERAL_GOODS, $cart_id = array()) {
	//  $sql = "DELETE FROM " . $GLOBALS['ecs']->table('cart') ." WHERE session_id = '" . SESS_ID . "' AND rec_type = '$type'";
	//  $GLOBALS['db']->query($sql);

	//$db_cart = RC_Loader::load_app_model('cart_model','flow');
	$db_cart = RC_Loader::load_app_model('cart_model', 'cart');
	$cart_w = array('rec_type' => $type);
	if (!empty($cart_id)) {
		$cart_w = array_merge($cart_w, array('rec_id' => $cart_id));
	}
	if ($_SESSION['user_id']) {
		$cart_w = array_merge($cart_w, array('user_id' => $_SESSION['user_id']));
		$db_cart->where($cart_w)->delete();
	} else {
		$cart_w = array_merge($cart_w, array('session_id' => SESS_ID));
		$db_cart->where($cart_w)->delete();
	}
}

/**
 * 获得购物车中的商品
 *
 * @access  public
 * @return  array
 */
function get_cart_goods($cart_id = array()) {
	RC_Loader::load_app_func('common','goods');
	$db_cart 		= RC_Loader::load_app_model('cart_model', 'cart');
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
	$cart_where = array('rec_type' => CART_GENERAL_GOODS);
	if (!empty($cart_id)) {
		$cart_where = array_merge($cart_where, array('rec_id' => $cart_id));
	}
	if ($_SESSION['user_id']) {
		$cart_where = array_merge($cart_where, array('user_id' => $_SESSION['user_id']));
		$data = $db_cart->field('*,IF(parent_id, parent_id, goods_id)|pid')->where(array('user_id' => $_SESSION['user_id'] , 'rec_type' => CART_GENERAL_GOODS))->order(array('pid'=>'asc', 'parent_id'=>'asc'))->select();
	} else {
		$cart_where = array_merge($cart_where, array('session_id' => SESS_ID));
		$data = $db_cart->field('*,IF(parent_id, parent_id, goods_id)|pid')->where(array('session_id' => SESS_ID , 'rec_type' => CART_GENERAL_GOODS))->order(array('pid'=>'asc', 'parent_id'=>'asc'))->select();
	}


	/* 用于统计购物车中实体商品和虚拟商品的个数 */
	$virtual_goods_count = 0;
	$real_goods_count    = 0;

	if (!empty($data)) {
		foreach ($data as $row) {
			$total['goods_price']  += $row['goods_price'] * $row['goods_number'];
			$total['market_price'] += $row['market_price'] * $row['goods_number'];
			$row['subtotal']     	= price_format($row['goods_price'] * $row['goods_number'], false);
			$row['goods_price']  	= price_format($row['goods_price'], false);
			$row['market_price'] 	= price_format($row['market_price'], false);

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
			if ((ecjia::config('show_goods_in_cart') == "2" || ecjia::config('show_goods_in_cart') == "3") &&
			$row['extension_code'] != 'package_buy') {

				$goods_thumb 		= $db_goods->field('goods_thumb')->find(array('goods_id' => '{'.$row['goods_id'].'}'));
				$row['goods_thumb'] = get_image_path($row['goods_id'], $goods_thumb, true);
			}
			if ($row['extension_code'] == 'package_buy') {
				$row['package_goods_list'] = get_package_goods($row['goods_id']);
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
	// 	$sql = "SELECT *, IF(parent_id, parent_id, goods_id) AS pid " .
	// 			" FROM " . $GLOBALS['ecs']->table('cart') . " " .
	// 			" WHERE session_id = '" . SESS_ID . "' AND rec_type = '" . CART_GENERAL_GOODS . "'" ." ORDER BY pid, parent_id";
	// 	$res = $GLOBALS['db']->query($sql);
	// 	while ($row = $GLOBALS['db']->fetchRow($res))

	// 	$sql = "SELECT attr_value FROM " . $GLOBALS['ecs']->table('goods_attr') . " WHERE goods_attr_id " .db_create_in($row['goods_attr']);
	// 	$attr_list = $GLOBALS['db']->getCol($sql);

	// 	$goods_thumb = $GLOBALS['db']->getOne("SELECT `goods_thumb` FROM " . $GLOBALS['ecs']->table('goods') . " WHERE `goods_id`='{$row['goods_id']}'");

}

/**
 * 获得订单信息
 *
 * @access  private
 * @return  array
 */
function flow_order_info() {
	$order = isset($_SESSION['flow_order']) ? $_SESSION['flow_order'] : array();

	/* 初始化配送和支付方式 */
	if (!isset($order['shipping_id']) || !isset($order['pay_id'])) {
		/* 如果还没有设置配送和支付 */
		if ($_SESSION['user_id'] > 0) {
			/* 用户已经登录了，则获得上次使用的配送和支付 */
			$arr = last_shipping_and_payment();

			if (!isset($order['shipping_id'])) {
				$order['shipping_id'] = $arr['shipping_id'];
			}
			if (!isset($order['pay_id'])) {
				$order['pay_id'] = $arr['pay_id'];
			}
		} else {
			if (!isset($order['shipping_id'])) {
				$order['shipping_id'] = 0;
			}
			if (!isset($order['pay_id'])) {
				$order['pay_id'] = 0;
			}
		}
	}

	if (!isset($order['pack_id'])) {
		$order['pack_id'] = 0;  // 初始化包装
	}
	if (!isset($order['card_id'])) {
		$order['card_id'] = 0;  // 初始化贺卡
	}
	if (!isset($order['bonus'])) {
		$order['bonus'] = 0;    // 初始化红包
	}
	if (!isset($order['integral'])) {
		$order['integral'] = 0; // 初始化积分
	}
	if (!isset($order['surplus'])) {
		$order['surplus'] = 0;  // 初始化余额
	}

	/* 扩展信息 */
	if (isset($_SESSION['flow_type']) && intval($_SESSION['flow_type']) != CART_GENERAL_GOODS) {
		$order['extension_code'] 	= $_SESSION['extension_code'];
		$order['extension_id'] 		= $_SESSION['extension_id'];
	}
	return $order;
}

/**
 * 计算折扣：根据购物车和优惠活动
 * @return  float   折扣
 * 
 */
function compute_discount($type = 0, $newInfo = array(), $cart_id = array(), $user_type = 0) {
	$db 			= RC_Loader::load_app_model('favourable_activity_model', 'favourable');
	$db_cartview 	= RC_Loader::load_app_model('cart_good_member_viewmodel', 'cart');

	/* 查询优惠活动 */
	$now = RC_Time::gmtime();
	$user_rank = ',' . $_SESSION['user_rank'] . ',';

	$favourable_list = $db->where("start_time <= '$now' AND end_time >= '$now' AND CONCAT(',', user_rank, ',') LIKE '%" . $user_rank . "%'")->in(array('act_type'=>array(FAT_DISCOUNT, FAT_PRICE)))->select();
	if (!$favourable_list) {
		return 0;
	}

	if($type == 0){
		/* 查询购物车商品 */
		$db_cartview->view = array(
				'goods' => array(
						'type'  => Component_Model_View::TYPE_LEFT_JOIN,
						'alias' => 'g',
						'field' => " c.goods_id, c.goods_price * c.goods_number AS subtotal, g.cat_id, g.brand_id",
						'on'   	=> 'c.goods_id = g.goods_id'
				)
		);
		$where = empty($cart_id) ? '' : array('rec_id' => $cart_id);
		if ($_SESSION['user_id']) {
			$goods_list = $db_cartview->where(array_merge($where, array('c.user_id' => $_SESSION['user_id'] , 'c.parent_id' => 0 , 'c.is_gift' => 0 , 'rec_type' => CART_GENERAL_GOODS)))->select();
		} else {
			$goods_list = $db_cartview->where(array_merge($where, array('c.session_id' => SESS_ID , 'c.parent_id' => 0 , 'c.is_gift' => 0 , 'rec_type' => CART_GENERAL_GOODS)))->select();
		}
	}elseif($type == 2){
		$db_goods = RC_Loader::load_app_model('goods_model', 'goods');
		$goods_list = array();
		foreach($newInfo as $key=>$row){
// 			$order_goods = $GLOBALS['db']->getRow("SELECT cat_id, brand_id FROM" .$GLOBALS['ecs']->table('goods'). " WHERE goods_id = '" .$row['goods_id']. "'");
			$order_goods = $db_goods->field('cat_id, brand_id')->where(array('goods_id' => $row['goods_id']))->find();
			$goods_list[$key]['goods_id'] = $row['goods_id'];
			$goods_list[$key]['cat_id'] = $order_goods['cat_id'];
			$goods_list[$key]['brand_id'] = $order_goods['brand_id'];
			$goods_list[$key]['ru_id'] = $row['ru_id'];
			$goods_list[$key]['subtotal'] = $row['goods_price'] * $row['goods_number'];
		}
	}
	
	


	if (!$goods_list) {
		return 0;
	}

	/* 初始化折扣 */
	$discount = 0;
	$favourable_name = array();
	RC_Loader::load_app_func('category', 'goods');
	/* 循环计算每个优惠活动的折扣 */
	foreach ($favourable_list as $favourable) {
		$total_amount = 0;
		if ($favourable['act_range'] == FAR_ALL) {
			foreach ($goods_list as $goods) {
				if ($use_type == 1) {
					if($favourable['user_id'] == $goods['ru_id']){
						$total_amount += $goods['subtotal'];
					}
				} else {
					if (isset($favourable['userFav_type']) && $favourable['userFav_type'] == 1) {
						$total_amount += $goods['subtotal'];
					} else {
						if($favourable['user_id'] == $goods['ru_id']){
							$total_amount += $goods['subtotal'];
						}
					}
				}
			}
		} elseif ($favourable['act_range'] == FAR_CATEGORY) {
			/* 找出分类id的子分类id */
			$id_list = array();
			$raw_id_list = explode(',', $favourable['act_range_ext']);
			foreach ($raw_id_list as $id) {
				$id_list = array_merge($id_list, array_keys(cat_list($id, 0, false)));
			}
			$ids = join(',', array_unique($id_list));

			foreach ($goods_list as $goods) {
				if (strpos(',' . $ids . ',', ',' . $goods['cat_id'] . ',') !== false) {
					if ($use_type == 1) {
						if ($favourable['user_id'] == $goods['ru_id'] && $favourable['userFav_type'] == 0) {
							$total_amount += $goods['subtotal'];
						}
					} else {
						if (isset($favourable['userFav_type']) && $favourable['userFav_type'] == 1) {
							$total_amount += $goods['subtotal'];
						} else {
							if ($favourable['user_id'] == $goods['ru_id']) {
								$total_amount += $goods['subtotal'];
							}
						}
					}
				}
			}
		} elseif ($favourable['act_range'] == FAR_BRAND) {
			foreach ($goods_list as $goods) {
				if (strpos(',' . $favourable['act_range_ext'] . ',', ',' . $goods['brand_id'] . ',') !== false) {
					if ($use_type == 1) {
						if ($favourable['user_id'] == $goods['ru_id']) {
							$total_amount += $goods['subtotal'];
						}
					} else {
						if (isset($favourable['userFav_type']) && $favourable['userFav_type'] == 1) {
							$total_amount += $goods['subtotal'];
						} else {
							if ($favourable['user_id'] == $goods['ru_id']) {
								$total_amount += $goods['subtotal'];
							}
						}
					}
					
				}
			}
		} elseif ($favourable['act_range'] == FAR_GOODS) {
			foreach ($goods_list as $goods) {
				if (strpos(',' . $favourable['act_range_ext'] . ',', ',' . $goods['goods_id'] . ',') !== false) {
					if ($use_type == 1) {
						if ($favourable['user_id'] == $goods['ru_id']) {
							$total_amount += $goods['subtotal'];
						}
					} else {
						if (isset($favourable['userFav_type']) && $favourable['userFav_type'] == 1) {
							$total_amount += $goods['subtotal'];
						} else {
							if ($favourable['user_id'] == $goods['ru_id']) {
								$total_amount += $goods['subtotal'];
							}
						}
					}
				}
			}
		} else {
			continue;
		}

		/* 如果金额满足条件，累计折扣 */
		if ($total_amount > 0 && $total_amount >= $favourable['min_amount'] &&
		($total_amount <= $favourable['max_amount'] || $favourable['max_amount'] == 0)) {
			if ($favourable['act_type'] == FAT_DISCOUNT) {
				$discount += $total_amount * (1 - $favourable['act_type_ext'] / 100);

				$favourable_name[] = $favourable['act_name'];
			} elseif ($favourable['act_type'] == FAT_PRICE) {
				$discount += $favourable['act_type_ext'];
				$favourable_name[] = $favourable['act_name'];
			}
		}
	}
	return array('discount' => $discount, 'name' => $favourable_name);

	// 	$sql = "SELECT *" .
	// 			"FROM " . $GLOBALS['ecs']->table('favourable_activity') .
	// 			" WHERE start_time <= '$now'" .
	// 			" AND end_time >= '$now'" .
	// 			" AND CONCAT(',', user_rank, ',') LIKE '%" . $user_rank . "%'" .
	// 			" AND act_type " . db_create_in(array(FAT_DISCOUNT, FAT_PRICE));
	// 	$favourable_list = $GLOBALS['db']->getAll($sql);

	// 	$sql = "SELECT c.goods_id, c.goods_price * c.goods_number AS subtotal, g.cat_id, g.brand_id " .
	// 			"FROM " . $GLOBALS['ecs']->table('cart') . " AS c, " . $GLOBALS['ecs']->table('goods') . " AS g " .
	// 			"WHERE c.goods_id = g.goods_id " .
	// 			"AND c.session_id = '" . SESS_ID . "' " .
	// 			"AND c.parent_id = 0 " .
	// 			"AND c.is_gift = 0 " .
	// 			"AND rec_type = '" . CART_GENERAL_GOODS . "'";
	// 	$goods_list = $GLOBALS['db']->getAll($sql);
}

/**
 * 计算购物车中的商品能享受红包支付的总额
 * @return  float   享受红包支付的总额
 */
function compute_discount_amount($cart_id = array()) {
	// 	$sql = "SELECT *" .
	// 			"FROM " . $GLOBALS['ecs']->table('favourable_activity') .
	// 			" WHERE start_time <= '$now'" .
	// 			" AND end_time >= '$now'" .
	// 			" AND CONCAT(',', user_rank, ',') LIKE '%" . $user_rank . "%'" .
	// 			" AND act_type " . db_create_in(array(FAT_DISCOUNT, FAT_PRICE));
	// 	$favourable_list = $GLOBALS['db']->getAll($sql);

	// 	$sql = "SELECT c.goods_id, c.goods_price * c.goods_number AS subtotal, g.cat_id, g.brand_id " .
	// 			"FROM " . $GLOBALS['ecs']->table('cart') . " AS c, " . $GLOBALS['ecs']->table('goods') . " AS g " .
	// 			"WHERE c.goods_id = g.goods_id " .
	// 			"AND c.session_id = '" . SESS_ID . "' " ."AND c.parent_id = 0 " ."AND c.is_gift = 0 " .
	// 			"AND rec_type = '" . CART_GENERAL_GOODS . "'";
	// 	$goods_list = $GLOBALS['db']->getAll($sql);



	//	$db 			= RC_Loader::load_app_model('favourable_activity_model','favourable');
	$db 			= RC_Loader::load_app_model('favourable_activity_model', 'favourable');
	$db_cartview 	= RC_Loader::load_app_model('cart_good_member_viewmodel', 'cart');
	/* 查询优惠活动 */
	$now = RC_Time::gmtime();
	$user_rank = ',' . $_SESSION['user_rank'] . ',';

	$favourable_list = $db->where('start_time <= '.$now.' AND end_time >= '.$now.' AND CONCAT(",", user_rank, ",") LIKE "%' . $user_rank . '%" ')->in(array('act_type' => array(FAT_DISCOUNT, FAT_PRICE)))->select();
	if (!$favourable_list) {
		return 0;
	}

	/* 查询购物车商品 */
	$db_cartview->view = array(
			'goods' => array(
					'type'  => Component_Model_View::TYPE_LEFT_JOIN,
					'alias' => 'g',
					'field' => " c.goods_id, c.goods_price * c.goods_number AS subtotal, g.cat_id, g.brand_id",
					'on'    => 'c.goods_id = g.goods_id'
			)
	);
	$cart_where = array('c.parent_id' => 0 , 'c.is_gift' => 0 , 'rec_type' => CART_GENERAL_GOODS);
	if (!empty($cart_id)) {
		$cart_where = array_merge($cart_where, array('c.rec_id' => $cart_id));
	}
	if ($_SESSION['user_id']) {
		$cart_where = array_merge($cart_where, array('c.user_id' => $_SESSION['user_id']));
		$goods_list = $db_cartview->where($cart_where)->select();
	} else {
		$cart_where = array_merge($cart_where, array('c.session_id' => SESS_ID));
		$goods_list = $db_cartview->where($cart_where)->select();
	}

	if (!$goods_list) {
		return 0;
	}

	/* 初始化折扣 */
	$discount = 0;
	$favourable_name = array();

	/* 循环计算每个优惠活动的折扣 */
	foreach ($favourable_list as $favourable) {
		$total_amount = 0;
		if ($favourable['act_range'] == FAR_ALL) {
			foreach ($goods_list as $goods) {
				if($favourable['user_id'] == $goods['ru_id']){
					$total_amount += $goods['subtotal'];
				}
			}
		} elseif ($favourable['act_range'] == FAR_CATEGORY) {
			/* 找出分类id的子分类id */
			$id_list = array();
			$raw_id_list = explode(',', $favourable['act_range_ext']);
			foreach ($raw_id_list as $id) {
				$id_list = array_merge($id_list, array_keys(cat_list($id, 0, false)));
			}
			$ids = join(',', array_unique($id_list));

			foreach ($goods_list as $goods) {
				if (strpos(',' . $ids . ',', ',' . $goods['cat_id'] . ',') !== false) {
				if($favourable['user_id'] == $goods['ru_id']){
					$total_amount += $goods['subtotal'];
				}
				}
			}
		} elseif ($favourable['act_range'] == FAR_BRAND) {
			foreach ($goods_list as $goods) {
				if (strpos(',' . $favourable['act_range_ext'] . ',', ',' . $goods['brand_id'] . ',') !== false) {
					if($favourable['user_id'] == $goods['ru_id']){
						$total_amount += $goods['subtotal'];
					}
				}
			}
		} elseif ($favourable['act_range'] == FAR_GOODS) {
			foreach ($goods_list as $goods) {
				if (strpos(',' . $favourable['act_range_ext'] . ',', ',' . $goods['goods_id'] . ',') !== false) {
					if($favourable['user_id'] == $goods['ru_id']){
						$total_amount += $goods['subtotal'];
					}
				}
			}
		} else {
			continue;
		}

		if ($total_amount > 0 && $total_amount >= $favourable['min_amount'] && ($total_amount <= $favourable['max_amount'] || $favourable['max_amount'] == 0)) {
			if ($favourable['act_type'] == FAT_DISCOUNT) {
				$discount += $total_amount * (1 - $favourable['act_type_ext'] / 100);
			} elseif ($favourable['act_type'] == FAT_PRICE) {
				$discount += $favourable['act_type_ext'];
			}
		}
	}
	return $discount;
}

/**
 * 取得购物车该赠送的积分数
 * @return  int	 积分数
 */
function get_give_integral() {

	$db_cartview = RC_Loader::load_app_model('cart_good_member_viewmodel', 'cart');

	$db_cartview->view = array(
			'goods' => array(
					'type'  => Component_Model_View::TYPE_LEFT_JOIN,
					'alias' => 'g',
					'field' => "c.rec_id, c.goods_id, c.goods_attr_id, g.promote_price, g.promote_start_date, c.goods_number,g.promote_end_date, IFNULL(mp.user_price, g.shop_price * '$_SESSION[discount]') AS member_price",
					'on'    => 'g.goods_id = c.goods_id'
			),
	);
	$field = array();
	if ($_SESSION['user_id']) {
		return  intval($db_cartview->where(array('c.user_id' => $_SESSION['user_id'] , 'c.goods_id' => array('gt' => 0) ,'c.parent_id' => 0 ,'c.rec_type' => 0 , 'c.is_gift' => 0))->sum('c.goods_number * IF(g.give_integral > -1, g.give_integral, c.goods_price)'));
	} else {
		return  intval($db_cartview->where(array('c.session_id' => SESS_ID , 'c.goods_id' => array('gt' => 0) ,'c.parent_id' => 0 ,'c.rec_type' => 0 , 'c.is_gift' => 0))->sum('c.goods_number * IF(g.give_integral > -1, g.give_integral, c.goods_price)'));
	}

}

function addto_cart_groupbuy($act_id, $number = 1, $spec = array(), $parent = 0, $warehouse_id = 0, $area_id = 0)
{
	$db_cart 		= RC_Loader::load_app_model('cart_model', 'cart');
	/* 查询：取得团购活动信息 */
	RC_Loader::load_app_func('goods', 'goods');
	RC_Loader::load_app_func('order', 'orders');
	$group_buy = group_buy_info($act_id, $number);
	if (empty($group_buy)) {
		return new ecjia_error('gb_error', __('对不起，该团购活动不存在！'));
		
	}
	
	/* 查询：检查团购活动是否是进行中 */
	if ($group_buy['status'] != GBS_UNDER_WAY) {
		return new ecjia_error('gb_error_status', __('对不起，该团购活动已经结束或尚未开始，现在不能参加！'));
	}
	
	/* 查询：取得团购商品信息 */
	$goods = get_goods_info($group_buy['goods_id'], $warehouse_id, $area_id);
	if (empty($goods)) {
		return new ecjia_error('goods_error', __('对不起，团购商品不存在！'));
	}
	
	/* 查询：判断数量是否足够 */
	if (($group_buy['restrict_amount'] > 0 && $number > ($group_buy['restrict_amount'] - $group_buy['valid_goods'])) || $number > $goods['goods_number']) {
		return new ecjia_error('gb_error_goods_lacking', __('对不起，商品库存不足，请您修改数量！'));
	}

	if (!empty($spec)) {
		$product_info = get_products_info($goods['goods_id'], $spec, $warehouse_id, $area_id);
	}
	
	empty($product_info) ? $product_info = array('product_number' => 0, 'product_id' => 0) : '';
	
	/* 查询：判断指定规格的货品数量是否足够 */
	if (!empty($spec) && $number > $product_info['product_number']) {
		return new ecjia_error('gb_error_goods_lacking', __('对不起，商品库存不足，请您修改数量！'));
	}

	
	
	$goods_attr = get_goods_attr_info($spec, 'pice', $warehouse_id, $area_id);
	$goods_attr_id          = join(',', $spec);
	/* 更新：清空购物车中所有团购商品 */
	
	clear_cart(CART_GROUP_BUY_GOODS);

	
	/* 更新：加入购物车 */
	$goods_price = $group_buy['deposit'] > 0 ? $group_buy['deposit'] : $group_buy['cur_price'];
	$cart = array(
			'user_id'        => $_SESSION['user_id'],
// 			'session_id'     => SESS_ID,
			'goods_id'       => $group_buy['goods_id'],
			'product_id'     => $product_info['product_id'],
			'goods_sn'       => addslashes($goods['goods_sn']),
			'goods_name'     => addslashes($goods['goods_name']),
			'market_price'   => $goods['market_price'],
			'goods_price'    => $goods_price,
			'goods_number'   => $number,
			'goods_attr'     => addslashes($goods_attr),
// 			'goods_attr_id'  => $specs,
			'goods_attr_id'  => $goods_attr_id,
			//ecmoban模板堂 --zhuo start
// 			'ru_id'			 => $goods['user_id'],
			'seller_id'		 => $goods['seller_id'],
			'warehouse_id'   => $warehouse_id,
			'area_id'  		 => $area_id,
			//ecmoban模板堂 --zhuo end
			'is_real'        => $goods['is_real'],
			'extension_code' => addslashes($goods['extension_code']),
			'parent_id'      => 0,
			'rec_type'       => CART_GROUP_BUY_GOODS,
			'is_gift'        => 0
	);
	$db_cart->insert($cart);
	
	/* 更新：记录购物流程类型：团购 */
	$_SESSION['flow_type'] = CART_GROUP_BUY_GOODS;
	$_SESSION['extension_code'] = 'group_buy';
	$_SESSION['extension_id'] = $act_id;
}

//购物车格式 api返回
function formated_cart_list($cart_result) {
    if (is_ecjia_error($cart_result)) {
        return $cart_result;
    }
    recalculate_price();
    unset($_SESSION['flow_type']);
    $cart_goods = array('cart_list' => array(), 'total' => $cart_result['total']);
    if (!empty($cart_result['goods_list'])) {
        foreach ($cart_result['goods_list'] as $row) {
            if (!isset($cart_goods['cart_list'][$row['store_id']])) {
                $cart_goods['cart_list'][$row['store_id']] = array(
                    'seller_id'		=> intval($row['store_id']),
                    'seller_name'	=> $row['store_name'],
                    'promotions' => array(
                        'id'    => 1,
                        'title' => '全场商品促销，满100打9折',
                        'type'  => 'discount',
                    ),
                );
            }
            $goods_attrs = null;
            /* 查询规格 */
            if (trim($row['goods_attr']) != '') {
                $goods_attr = explode("\n", $row['goods_attr']);
                $goods_attr = array_filter($goods_attr);
                foreach ($goods_attr as $v) {
                    $a = explode(':', $v);
                    if (!empty($a[0]) && !empty($a[1])) {
                        $goods_attrs[] = array('name' => $a[0], 'value' => $a[1]);
                    }
                }
            }
    
            $cart_goods['cart_list'][$row['store_id']]['goods_list'][] = array(
                'rec_id'	=> intval($row['rec_id']),
                'goods_id'	=> intval($row['goods_id']),
                'goods_sn'	=> $row['goods_sn'],
                'goods_name'	=> $row['goods_name'],
                'goods_price'	=> $row['goods_price'],
                'market_price'	=> $row['market_price'],
                'formated_goods_price'	=> $row['formatted_goods_price'],
                'formated_market_price' => $row['formatted_market_price'],
                'goods_number'	=> intval($row['goods_number']),
                'subtotal'		=> $row['subtotal'],
                'goods_attr_id' => intval($row['goods_attr_id']),
                'attr'			=> $row['goods_attr'],
                'goods_attr'	=> $goods_attrs,
                'is_checked'	=> $row['is_checked'],
                'promotions' => array(
                    'id'    => 1,
                    'title' => '满9.90、19.90、29.90可换购商品',
                    'type'  => 'discount',
                ),
                'img' => array(
                    'thumb'	=> RC_Upload::upload_url($row['goods_img']),
                    'url'	=> RC_Upload::upload_url($row['original_img']),
                    'small'	=> RC_Upload::upload_url($row['goods_img']),
                )
            );
        }
    }
    $cart_goods['cart_list'] = array_merge($cart_goods['cart_list']);
    
    return $cart_goods;
}

//	TODO:以下func，api中暂未用到
///**
// * 比较优惠活动的函数，用于排序（把可用的排在前面）
// * @param   array   $a      优惠活动a
// * @param   array   $b      优惠活动b
// * @return  int     相等返回0，小于返回-1，大于返回1
// */
//function cmp_favourable($a, $b)
//{
//    if ($a['available'] == $b['available'])
//    {
//        if ($a['sort_order'] == $b['sort_order'])
//        {
//            return 0;
//        }
//        else
//        {
//            return $a['sort_order'] < $b['sort_order'] ? -1 : 1;
//        }
//    }
//    else
//    {
//        return $a['available'] ? -1 : 1;
//    }
//}

///**
// * 取得某用户等级当前时间可以享受的优惠活动
// * @param   int     $user_rank      用户等级id，0表示非会员
// * @return  array
// */
//function favourable_list($user_rank)
//{
//    $db = RC_Loader::load_app_model('favourable_activity_model' , 'favourable');
//    $db_goods = RC_Loader::load_app_model('goods_model' , 'goods');
//    /* 购物车中已有的优惠活动及数量 */
//    $used_list = cart_favourable();
//
//    /* 当前用户可享受的优惠活动 */
//    $favourable_list = array();
//    $user_rank = ',' . $user_rank . ',';
//    $now = RC_Time::gmtime();
////     $sql = "SELECT * " .
////         "FROM " . $GLOBALS['ecs']->table('favourable_activity') .
////         " WHERE CONCAT(',', user_rank, ',') LIKE '%" . $user_rank . "%'" .
////         " AND start_time <= '$now' AND end_time >= '$now'" .
////         " AND act_type = '" . FAT_GOODS . "'" .
////         " ORDER BY sort_order";
////     $res = $GLOBALS['db']->query($sql);
//
//    $data = $db->where(array("CONCAT(',', user_rank, ',')" => array('like' => "%" . $user_rank . "%") , 'start_time' => array('elt' => $now) , 'end_time' => array('egt' => $now) , 'act_type' => FAT_GOODS))->order(array('sort_order' => 'asc'))->select(); 
//    
////     while (($favourable = $GLOBALS['db']->fetchRow($res)) != false)
//    if (!empty($data)) {
//        foreach ($data as $favourable)
//        {
//            $favourable['start_time'] = RC_Time::local_date(ecjia::config('time_format'), $favourable['start_time']);
//            $favourable['end_time']   = RC_Time::local_date(ecjia::config('time_format'), $favourable['end_time']);
//            $favourable['formated_min_amount'] = price_format($favourable['min_amount'], false);
//            $favourable['formated_max_amount'] = price_format($favourable['max_amount'], false);
//            $favourable['gift']       = unserialize($favourable['gift']);
//        
//            foreach ($favourable['gift'] as $key => $value)
//            {
//                $favourable['gift'][$key]['formated_price'] = price_format($value['price'], false);
////                 $sql = "SELECT COUNT(*) FROM " . $GLOBALS['ecs']->table('goods') . " WHERE is_on_sale = 1 AND goods_id = ".$value['id'];
////                 $is_sale = $GLOBALS['db']->getOne($sql);
//
//                $is_sale = $db_goods->where(array('is_on_sale' => 1 , 'goods_id' => $value['id']))->count();
//                if(!$is_sale)
//                {
//                    unset($favourable['gift'][$key]);
//                }
//            }
//        
//            $favourable['act_range_desc'] = act_range_desc($favourable);
//            $favourable['act_type_desc'] = sprintf($GLOBALS['_LANG']['fat_ext'][$favourable['act_type']], $favourable['act_type_ext']);
//        
//            /* 是否能享受 */
//            $favourable['available'] = favourable_available($favourable);
//            if ($favourable['available'])
//            {
//                /* 是否尚未享受 */
//                $favourable['available'] = !favourable_used($favourable, $used_list);
//            }
//        
//            $favourable_list[] = $favourable;
//        }
//    }
//    
//    return $favourable_list;
//}
//
///**
// * 根据购物车判断是否可以享受某优惠活动
// * @param   array   $favourable     优惠活动信息
// * @return  bool
// */
//function favourable_available($favourable)
//{
//    /* 会员等级是否符合 */
//    $user_rank = $_SESSION['user_rank'];
//    if (strpos(',' . $favourable['user_rank'] . ',', ',' . $user_rank . ',') === false)
//    {
//        return false;
//    }
//
//    /* 优惠范围内的商品总额 */
//    $amount = cart_favourable_amount($favourable);
//
//    /* 金额上限为0表示没有上限 */
//    return $amount >= $favourable['min_amount'] &&
//    ($amount <= $favourable['max_amount'] || $favourable['max_amount'] == 0);
//}
//
///**
// * 取得优惠范围描述
// * @param   array   $favourable     优惠活动
// * @return  string
// */
//function act_range_desc($favourable)
//{
//    
//    $db_brand = RC_Loader::load_app_model('brand_model' , 'goods');
//    $db_category = RC_Loader::load_app_model('category_model' , 'goods');
//    $db_goods = RC_Loader::load_app_model('goods_model' , 'goods');
//    
//    if ($favourable['act_range'] == FAR_BRAND)
//    {
////         $sql = "SELECT brand_name FROM " . $GLOBALS['ecs']->table('brand') .
////         " WHERE brand_id " . db_create_in($favourable['act_range_ext']);
////         return join(',', $GLOBALS['db']->getCol($sql));
//           
//        $data = $db_brand->field('brand_name')->in(array('brand_id' => $favourable['act_range_ext']))->select();
//        return join(',', $data);
//        
//    }
//    elseif ($favourable['act_range'] == FAR_CATEGORY)
//    {
////         $sql = "SELECT cat_name FROM " . $GLOBALS['ecs']->table('category') .
////         " WHERE cat_id " . db_create_in($favourable['act_range_ext']);
////         return join(',', $GLOBALS['db']->getCol($sql));
//
//        $data = $db_brand->field('cat_name')->in(array('cat_id' => $favourable['act_range_ext']))->select();
//        return join(',', $data);
//    }
//    elseif ($favourable['act_range'] == FAR_GOODS)
//    {
////         $sql = "SELECT goods_name FROM " . $GLOBALS['ecs']->table('goods') .
////         " WHERE goods_id " . db_create_in($favourable['act_range_ext']);
////         return join(',', $GLOBALS['db']->getCol($sql));
//
//        $data = $db_brand->field('goods_name')->in(array('goods_id' => $favourable['act_range_ext']))->select();
//        return join(',', $data);
//    }
//    else
//    {
//        return '';
//    }
//}
//
///**
// * 取得购物车中已有的优惠活动及数量
// * @return  array
// */
//function cart_favourable()
//{
//    $db_cart = RC_Loader::load_app_model('cart_model' , 'flow');
//    
//    $list = array();
////     $sql = "SELECT is_gift, COUNT(*) AS num " .
////         "FROM " . $GLOBALS['ecs']->table('cart') .
////         " WHERE session_id = '" . SESS_ID . "'" .
////         " AND rec_type = '" . CART_GENERAL_GOODS . "'" .
////         " AND is_gift > 0" .
////         " GROUP BY is_gift";
////     $res = $GLOBALS['db']->query($sql);
//	if ($_SESSION['user_id']) {
//		$data = $db_cart->field('is_gift, COUNT(*) AS num')->where(array('user_id' => $_SESSION['user_id'] , 'rec_type' => CART_GENERAL_GOODS , 'is_gift' => array('gt' => 0)))->group('is_gift')->select();
//	} else {
//		$data = $db_cart->field('is_gift, COUNT(*) AS num')->where(array('session_id' => SESS_ID , 'rec_type' => CART_GENERAL_GOODS , 'is_gift' => array('gt' => 0)))->group('is_gift')->select();
//	}
//    
//    
////     while (($row = $GLOBALS['db']->fetchRow($res)) != false)
//    if (!empty($data)) {
//        foreach ($data as $row)
//        {
//            $list[$row['is_gift']] = $row['num'];
//        }
//    }
//    return $list;
//}
//
///**
// * 购物车中是否已经有某优惠
// * @param   array   $favourable     优惠活动
// * @param   array   $cart_favourable购物车中已有的优惠活动及数量
// */
//function favourable_used($favourable, $cart_favourable)
//{
//    if ($favourable['act_type'] == FAT_GOODS)
//    {
//        return isset($cart_favourable[$favourable['act_id']]) &&
//        $cart_favourable[$favourable['act_id']] >= $favourable['act_type_ext'] &&
//        $favourable['act_type_ext'] > 0;
//    }
//    else
//    {
//        return isset($cart_favourable[$favourable['act_id']]);
//    }
//}
//
///**
// * 添加优惠活动（赠品）到购物车
// * @param   int     $act_id     优惠活动id
// * @param   int     $id         赠品id
// * @param   float   $price      赠品价格
// */
//function add_gift_to_cart($act_id, $id, $price)
//{
////     $sql = "INSERT INTO " . $GLOBALS['ecs']->table('cart') . " (" .
////         "user_id, session_id, goods_id, goods_sn, goods_name, market_price, goods_price, ".
////         "goods_number, is_real, extension_code, parent_id, is_gift, rec_type ) ".
////         "SELECT '$_SESSION[user_id]', '" . SESS_ID . "', goods_id, goods_sn, goods_name, market_price, ".
////         "'$price', 1, is_real, extension_code, 0, '$act_id', '" . CART_GENERAL_GOODS . "' " .
////         "FROM " . $GLOBALS['ecs']->table('goods') .
////         " WHERE goods_id = '$id'";
////     $GLOBALS['db']->query($sql);
//
//    $db_cart = RC_Loader::load_app_model('cart_model' , 'flow');
//    $db_goods = RC_Loader::load_app_model('goods_model' , 'goods');
//    
//    $goods_row = $db_goods->field('goods_id, goods_sn, goods_name, market_price,is_real, extension_code,')->find(array('goods_id' => $id));
//    $data = array(
//        'user_id'        => $_SESSION['user_id'],
//        'session_id'     => SESS_ID,
//        'goods_id'       => $goods_row['user_id'],
//        'goods_sn'       => $goods_row['goods_sn'],
//        'goods_name'     => $goods_row['goods_name'],
//        'market_price'   => $goods_row['market_price'],
//        'goods_price'    => $price,
//        'goods_number'   => 1,
//        'is_real'        => $goods_row['is_real'],
//        'extension_code' => $goods_row['extension_code'],
//        'parent_id'      => 0,
//        'is_gift'        => $act_id,
//        'rec_type'       => CART_GENERAL_GOODS
//    );
//    
//    $db_cart->insert($data);
//}
//
///**
// * 添加优惠活动（非赠品）到购物车
// * @param   int     $act_id     优惠活动id
// * @param   string  $act_name   优惠活动name
// * @param   float   $amount     优惠金额
// */
//function add_favourable_to_cart($act_id, $act_name, $amount)
//{
////     $sql = "INSERT INTO " . $GLOBALS['ecs']->table('cart') . "(" .
////         "user_id, session_id, goods_id, goods_sn, goods_name, market_price, goods_price, ".
////         "goods_number, is_real, extension_code, parent_id, is_gift, rec_type ) ".
////         "VALUES('$_SESSION[user_id]', '" . SESS_ID . "', 0, '', '$act_name', 0, ".
////         "'" . (-1) * $amount . "', 1, 0, '', 0, '$act_id', '" . CART_GENERAL_GOODS . "')";
////     $GLOBALS['db']->query($sql);
//
//    $db_cart = RC_Loader::load_app_model('cart_model');
//    $data = array(
//        'user_id'        => $_SESSION['user_id'],
//        'session_id'     => SESS_ID,
//        'goods_id'       => 0,
//        'goods_sn'       => '',
//        'goods_name'     => $act_name,
//        'market_price'   => 0,
//        'goods_price'    => (-1) * $amount,
//        'goods_number'   => 1,
//        'is_real'        => 0,
//        'extension_code' => '',
//        'parent_id'      => 0,
//        'is_gift'        => $act_id,
//        'rec_type'       => CART_GENERAL_GOODS
//    );
//    
//    $db_cart->insert($data);
//}
//
///**
// * 取得购物车中某优惠活动范围内的总金额
// * @param   array   $favourable     优惠活动
// * @return  float
// */
//function cart_favourable_amount($favourable)
//{
//    $dbview = RC_Loader::load_app_model('cart_goods_viewmodel','flow');
//    
//    /* 查询优惠范围内商品总额的sql */
////     $sql = "SELECT SUM(c.goods_price * c.goods_number) " .
////         "FROM " . $GLOBALS['ecs']->table('cart') . " AS c, " . $GLOBALS['ecs']->table('goods') . " AS g " .
////         "WHERE c.goods_id = g.goods_id " .
////         "AND c.session_id = '" . SESS_ID . "' " .
////         "AND c.rec_type = '" . CART_GENERAL_GOODS . "' " .
////         "AND c.is_gift = 0 " .
////         "AND c.goods_id > 0 ";
//
//    /* 根据优惠范围修正sql */
//    if ($favourable['act_range'] == FAR_ALL)
//    {
//        // sql do not change
//    }
//    elseif ($favourable['act_range'] == FAR_CATEGORY)
//    {
//        /* 取得优惠范围分类的所有下级分类 */
//        $id_list = array();
//        $cat_list = explode(',', $favourable['act_range_ext']);
//        foreach ($cat_list as $id)
//        {
//            $id_list = array_merge($id_list, array_keys(cat_list(intval($id), 0, false)));
//        }
//
////         $sql .= "AND g.cat_id " . db_create_in($id_list);
//        $where = "AND g.cat_id " . db_create_in($id_list);
//    }
//    elseif ($favourable['act_range'] == FAR_BRAND)
//    {
//        $id_list = explode(',', $favourable['act_range_ext']);
////         $sql .= "AND g.brand_id " . db_create_in($id_list);
//        $where .= "AND g.brand_id " . db_create_in($id_list);
//    }
//    else
//    {
//        $id_list = explode(',', $favourable['act_range_ext']);
////         $sql .= "AND g.goods_id " . db_create_in($id_list);
//        $where .= "AND g.goods_id " . db_create_in($id_list);
//    }
//
//    /* 优惠范围内的商品总额 */
////     return $GLOBALS['db']->getOne($sql);
//	if ($_SESSION['user_id']) {
//		return $dbview->join('goods')->where(array('c.user_id' => $_SESSION['user_id'] , 'c.rec_type' => CART_GENERAL_GOODS , 'c.is_gift' => 0 , 'c.goods_id' => array('gt' => 0)))->sum('c.goods_price * c.goods_number');
//	} else {
//		return $dbview->join('goods')->where(array('c.session_id' => SESS_ID , 'c.rec_type' => CART_GENERAL_GOODS , 'c.is_gift' => 0 , 'c.goods_id' => array('gt' => 0)))->sum('c.goods_price * c.goods_number');
//	}
//    
//    
//}





//	TODO：在api下的order.fun.php中
// /**
//  * 获得指定的商品属性
//  * @access      public
//  * @param       array       $arr        规格、属性ID数组
//  * @param       type        $type       设置返回结果类型：pice，显示价格，默认；no，不显示价格
//  * @return      string
//  */
// function get_goods_attr_info($arr, $type = 'pice') {
// // 	$sql = "SELECT a.attr_name, ga.attr_value, ga.attr_price ".
// // 			"FROM ".$GLOBALS['ecs']->table('goods_attr')." AS ga, ".
// // 			$GLOBALS['ecs']->table('attribute')." AS a ".
// // 			"WHERE " .db_create_in($arr, 'ga.goods_attr_id')." AND a.attr_id = ga.attr_id";
// // 	$res = $GLOBALS['db']->query($sql);
// // 	while ($row = $GLOBALS['db']->fetchRow($res))
	
	
	
// 	$dbview = RC_Loader::load_app_model('goods_attr_viewmodel','goods');
//     $attr   = '';
//     if (!empty($arr)) {
//         $fmt = "%s:%s[%s] \n";
        
//        $dbview->view =array(
// 				'attribute' => array(
// 				     'type' 	=> Component_Model_View::TYPE_LEFT_JOIN,
// 					 'alias' 	=> 'a',
// 					 'field' 	=> 'a.attr_name, ga.attr_value, ga.attr_price',
// 					 'on' 		=> 'a.attr_id = ga.attr_id'
// 				)
// 		);   
//         $data = $dbview->in(array('ga.goods_attr_id'=> $arr))->select();

//         if(!empty($data)) {
// 	        foreach ($data as $row) {
// 	            $attr_price = round(floatval($row['attr_price']), 2);
// 	            $attr .= sprintf($fmt, $row['attr_name'], $row['attr_value'], $attr_price);
// 	        }
//         }
//         $attr = str_replace('[0]', '', $attr);
//     }
//     return $attr;
// }

//	TODO： 已在flow.fun.php中
///**
// * 检查订单中商品库存 
// *
// * @access  public
// * @param   array   $arr
// *
// * @return  void
// */
//function flow_cart_stock($arr)
//{
//    foreach ($arr AS $key => $val)
//    {
//        $val = intval(make_semiangle($val));
//        if ($val <= 0 || !is_numeric($key))
//        {
//            continue;
//        }
//
////         $sql = "SELECT `goods_id`, `goods_attr_id`, `extension_code` FROM" .$GLOBALS['ecs']->table('cart').
////         " WHERE rec_id='$key' AND session_id='" . SESS_ID . "'";
////         $goods = $GLOBALS['db']->getRow($sql);
//
////         $sql = "SELECT g.goods_name, g.goods_number, c.product_id ".
////             "FROM " .$GLOBALS['ecs']->table('goods'). " AS g, ".
////             $GLOBALS['ecs']->table('cart'). " AS c ".
////             "WHERE g.goods_id = c.goods_id AND c.rec_id = '$key'";
////         $row = $GLOBALS['db']->getRow($sql);
//
//        $db_cart = RC_Loader::load_app_model('cart_model');
//        $db_products = RC_Loader::load_app_model('products_model','goods');
//        $dbview = RC_Loader::load_app_model('goods_cart_viewmodel','goods');
//
//        $goods = $db_cart->field('goods_id,goods_attr_id,extension_code')->find(array('rec_id' => $key , 'session_id' => SESS_ID));
//        $row   = $dbview->join('cart')->find(array('c.rec_id' => $key));
//        //系统启用了库存，检查输入的商品数量是否有效
//        if (intval(ecjia::config('use_storage')) > 0 && $goods['extension_code'] != 'package_buy')
//        {
//            if ($row['goods_number'] < $val)
//            {
//                EM_Api::outPut(10008);
//                exit;
//            }
//
//            /* 是货品 */
//            $row['product_id'] = trim($row['product_id']);
//            if (!empty($row['product_id']))
//            {
////                 $sql = "SELECT product_number FROM " .$GLOBALS['ecs']->table('products'). " WHERE goods_id = '" . $goods['goods_id'] . "' AND product_id = '" . $row['product_id'] . "'";
////                 $product_number = $GLOBALS['db']->getOne($sql);
//                $product_number = $db_products->where(array('goods_id' => $goods['goods_id'] , 'product_id' => $goods['product_id']))->get_field('product_number');
//                if ($product_number < $val)
//                {
//                    EM_Api::outPut(10005);
//                    exit;
//                }
//            }
//        }
//        elseif (intval(ecjia::config('use_storage')) > 0 && $goods['extension_code'] == 'package_buy')
//        {
//            if (judge_package_stock($goods['goods_id'], $val))
//            {
//                EM_Api::outPut(10005);
//                exit;
//            }
//        }
//    }
//
//}


// end