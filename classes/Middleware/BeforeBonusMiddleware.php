<?php
/**
 * Created by PhpStorm.
 * User: royalwang
 * Date: 2019-03-26
 * Time: 18:15
 */

namespace Ecjia\App\Cart\Middleware;

use Closure;
use Ecjia\App\Cart\CreateOrders\OrderParts\OrderBonusPart;

/**
 * Class BeforeBonusMiddleware
 * @package Ecjia\App\Cart\Middleware
 */
class BeforeBonusMiddleware
{

    /**
     * @param \Ecjia\App\Cart\CreateOrders\CreateOrder $request
     * @param Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {


        if ($request['bonus']) {
//            dd($request);
//            dd($request['bonus']);
        }

//        if ($request->getOrder()->getBonusPart()) {


//            $cart = $request->getCart()->getGoodsCollection();

//            $bonus = $request->getOrder()->getBonusPart()->check_bonus($cart['user_id']);

//            dd();

//            $request->getOrder()->setBonusPart($bonus);
//            $bonus_part = new OrderBonusPart($cart['user_id']);

//            return new \ecjia_error('xx', 'xx', $request);

//        }



        return $next($request);
    }

}