<?php
/**
 * Created by PhpStorm.
 * User: royalwang
 * Date: 2019-03-25
 * Time: 13:32
 */

namespace Ecjia\App\Cart\CreateOrders\OrderParts;


class OrderAddressPart
{

    protected $address_id;

    public function __construct($address_id)
    {
        $this->address_id = $address_id;
    }
	
    
	public function consigneeInfo()
	{
		
	}

}