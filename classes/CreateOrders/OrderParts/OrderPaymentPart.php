<?php
/**
 * Created by PhpStorm.
 * User: royalwang
 * Date: 2019-03-25
 * Time: 13:32
 */

namespace Ecjia\App\Cart\CreateOrders\OrderParts;


class OrderPaymentPart
{

    protected $pay_id;

    public function __construct($pay_id)
    {
        $this->pay_id = $pay_id;
    }

	

}