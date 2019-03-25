<?php
/**
 * Created by PhpStorm.
 * User: royalwang
 * Date: 2019-03-25
 * Time: 13:32
 */

namespace Ecjia\App\Cart\CreateOrders\OrderParts;


class OrderCartPart
{

    protected $card_id;

    public function __construct($card_id)
    {
        $this->card_id = $card_id;
    }

	

}