<?php
/**
 * Created by PhpStorm.
 * User: royalwang
 * Date: 2019-03-25
 * Time: 13:32
 */

namespace Ecjia\App\Cart\CreateOrders\OrderParts;


class OrderIntegralPart
{

    protected $integral;
    
    protected $user_id;
    

    public function __construct($integral, $user_id)
    {
        $this->integral = $integral;
        $this->user_id = $user_id;
    }

	/**
	 * 获取订单可用积分
	 */
    public function order_allow_integral()
    {
    	if ($this->user_id > 0) {
    		$user_info = \RC_Api::api('user', 'user_info', array('user_id' => $this->user_id));
    		
    		$user_points = $user_info['pay_points']; // 用户的积分总数
    	
    		$allow_use_integral = min($this->integral, $user_points); //使用积分不可超过用户剩余积分
    		
    	} else {
    		$allow_use_integral = 0;
    	}
    	
    	return $allow_use_integral;
    }

}