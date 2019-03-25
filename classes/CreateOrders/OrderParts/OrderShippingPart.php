<?php
/**
 * Created by PhpStorm.
 * User: royalwang
 * Date: 2019-03-25
 * Time: 13:32
 */

namespace Ecjia\App\Cart\CreateOrders\OrderParts;


class OrderShippingPart
{

    protected $shipping_id;

    public function __construct(array $shipping_id)
    {
        $this->shipping_id = $shipping_id;
    }

	

}