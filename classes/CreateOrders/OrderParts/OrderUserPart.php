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

	public function userInfo()
	{
		$user_info = [];
		$user_info = \RC_Api::api('user', 'user_info', ['user_id' => $this->user_id]);
		if (is_ecjia_error($user_info)) {
			$user_info = [];
		}
		
		return $user_info;
	}

}