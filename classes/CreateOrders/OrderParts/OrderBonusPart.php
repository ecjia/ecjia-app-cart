<?php
/**
 * Created by PhpStorm.
 * User: royalwang
 * Date: 2019-03-25
 * Time: 13:32
 */

namespace Ecjia\App\Cart\CreateOrders\OrderParts;


class OrderBonusPart
{

    protected $bonus_id;

    public function __construct($bonus_id)
    {
        $this->bonus_id = $bonus_id;
    }

	/**
	 * 红包信息
	 */
    public function getBonusInfo()
    {
    	$bonus = \Ecjia\App\Bonus\UserAvaliableBonus::bonusInfo($this->bonus_id);
    	return $bonus;
    }
	
}