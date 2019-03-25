<?php
/**
 * Created by PhpStorm.
 * User: royalwang
 * Date: 2019-03-25
 * Time: 11:56
 */

namespace Ecjia\App\Cart\CreateOrders;


use Royalcms\Component\Pipeline\Pipeline;

class GeneralOrder
{

    protected $cart;

    public function __construct($cart)
    {
        $this->cart = $cart;

    }


    public function xx()
    {
        $pipes = [
            function ($poster, $callback) {
                $poster += 1;
                return $callback($poster);
            },
            function ($poster, $callback) {
                $result = $callback($poster);

                return $result - 1;
            },
            function ($poster, $callback) {
                $poster += 2;

                return $callback($poster);
            }
        ];

        echo (new Pipeline())->send(0)->through($pipes)->then(function ($poster) {
            return $poster;
        });


    }

}