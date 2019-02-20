<?php
/**
 * Created by PhpStorm.
 * User: royalwang
 * Date: 2019-02-20
 * Time: 18:50
 */

namespace Ecjia\App\Cart\CartFlow;


class MultiCartPrice
{

    protected $prices = [];

    public function __construct()
    {

    }

    /**
     * 添加价格
     * @param array $price
     */
    public function addPrice(array $price)
    {
        $this->prices[] = $price;
    }

    /**
     * 购物车总计
     */
    public function computeTotalPrice()
    {
//        dd($this->prices);
        $price = collect($this->prices)->sum(function($item) {
            return $item['goods_amount'];
        });

        $goods_quantity = collect($this->prices)->sum(function($item) {
            return $item['real_goods_count'];
        });


        $total['goods_amount'] = $price;
        $total['saving']       = ecjia_price_format($total['market_price'] - $total['goods_price'], false);
        if ($total['market_price'] > 0) {
            $total['save_rate'] = $total['market_price'] ? round(($total['market_price'] - $total['goods_price']) * 100 / $total['market_price']).'%' : 0;
        }
        $total['unformatted_goods_price']  	= sprintf("%.2f", $total['goods_price']);
        $total['goods_price']  				= ecjia_price_format($total['goods_price'], false);
        $total['unformatted_market_price'] 	= sprintf("%.2f", $total['market_price']);
        $total['market_price'] 				= ecjia_price_format($total['market_price'], false);
        $total['real_goods_count']    		= $goods_quantity;
//        $total['virtual_goods_count'] 		= $virtual_goods_count;

//        dd($total);

        return $total;
    }




}