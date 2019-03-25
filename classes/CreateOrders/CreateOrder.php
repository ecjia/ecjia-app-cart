<?php
/**
 * Created by PhpStorm.
 * User: royalwang
 * Date: 2019-03-25
 * Time: 11:56
 */

namespace Ecjia\App\Cart\CreateOrders;


use Ecjia\App\Cart\CartFlow\Cart;
use Royalcms\Component\Pipeline\Pipeline;

class CreateOrder
{

    /**
     * @var Cart
     */
    protected $cart;

    /**
     * @var GeneralOrder
     */
    protected $order;

    protected $middlewares = [
        'Ecjia\App\Cart\Middleware\BeforeMiddleware',
        'Ecjia\App\Cart\Middleware\AfterMiddleware',
    ];

    public function __construct(Cart $cart, GeneralOrder $order)
    {
        $this->cart = $cart;
        $this->order = $order;
    }



    public function pipeline()
    {
//        dd($this);
        $order = (new Pipeline(royalcms()))
            ->send($this->order)
            ->through($this->middlewares)
            ->then(function ($poster) {
            return $poster;
        });

        dd($order);
    }

}