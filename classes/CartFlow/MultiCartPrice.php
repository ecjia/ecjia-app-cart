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
	
    protected $discount = [];
    
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
     * 添加优惠折扣
     * @param CartPrice $price
     */
    public function addDiscount(array $discount)
    {
    	$this->discount = $discount;
    }

    /**
     * 购物车总计
     */
    public function computeTotalPrice()
    {
        $goods_price = collect($this->prices)->sum(function($item) {
            return $item['goods_amount'];
        });

        $goods_quantity = collect($this->prices)->sum(function($item) {
            return $item['goods_number'];
        });
		
        $market_price = collect($this->prices)->sum(function($item) {
        	return $item['unformatted_market_price'];
        });
		
         $discount = collect($this->prices)->sum(function($item) {
        	return $item['discount'];
        });
        
        $total['goods_amount'] = sprintf("%.2f", $goods_price);
        $total['goods_number'] = $goods_quantity;
        
        $total['saving']    = ecjia_price_format($discount, false);
        $total['save_rate'] = $discount > 0 ? round($discount * 100 / $goods_price).'%' : 0;
        $total['unformatted_goods_price']  	= sprintf("%.2f", $goods_price);
        $total['goods_price']  				= ecjia_price_format($goods_price, false);
        $total['unformatted_market_price'] 	= sprintf("%.2f", $market_price);
        $total['market_price'] 				= ecjia_price_format($market_price, false);
        $total['real_goods_count']    		= $goods_quantity;
        $total['discount']    				= sprintf("%.2f", $discount);
        $total['formatted_discount']        = ecjia_price_format($discount, false);

        return $total;
    }




}