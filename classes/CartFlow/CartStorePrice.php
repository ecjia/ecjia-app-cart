<?php
/**
 * Created by PhpStorm.
 * User: royalwang
 * Date: 2019-02-20
 * Time: 18:22
 */

namespace Ecjia\App\Cart\CartFlow;


class CartStorePrice
{

    protected $prices = [];

    protected $store_id;

    public function __construct($store_id)
    {
        $this->store_id = $store_id;
    }

    /**
     * 添加价格
     * @param CartPrice $price
     */
    public function addPrice(CartPrice $price)
    {
        $this->prices[] = $price;
    }

    /**
     * 计算价格
     */
    public function computeTotalPrice()
    {
//        dd($this->prices);
        $price = collect($this->prices)->sum(function($item) {
            $total = $item->computeTotalPrice();
            return $total['goods_price'];
        });

        $goods_quantity = collect($this->prices)->sum(function($item) {
            $total = $item->computeTotalPrice();
            return $total['goods_number'];
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