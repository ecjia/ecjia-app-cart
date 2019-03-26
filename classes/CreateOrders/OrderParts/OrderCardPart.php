<?php
/**
 * Created by PhpStorm.
 * User: royalwang
 * Date: 2019-03-25
 * Time: 13:32
 */

namespace Ecjia\App\Cart\CreateOrders\OrderParts;


class OrderCardPart
{

    protected $card_id;

    public function __construct($card_id)
    {
        $this->card_id = $card_id;
    }

	/**
	 * 贺卡信息
	 */
    public function cardInfo()
    {
    	$card_info = [];
    	return $card_info;
    }
    
    /**
     * 贺卡费用
     * @return number
     */
    public function total_card_fee()
    {
    	$card_fee = 0;
    	return $card_fee;
    }

}