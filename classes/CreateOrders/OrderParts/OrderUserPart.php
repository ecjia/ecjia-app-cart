<?php
/**
 * Created by PhpStorm.
 * User: royalwang
 * Date: 2019-03-25
 * Time: 13:32
 */

namespace Ecjia\App\Cart\CreateOrders\OrderParts;


class OrderUserPart
{

    protected $user_id;

    public function __construct($user_id)
    {
        $this->user_id = $user_id;
    }



}