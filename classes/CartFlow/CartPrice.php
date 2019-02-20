<?php
/**
 * Created by PhpStorm.
 * User: royalwang
 * Date: 2019-02-19
 * Time: 18:59
 */

namespace Ecjia\App\Cart\CartFlow;


use Ecjia\App\Cart\Models\CartModel;

class CartPrice
{

    /**
     * @var CartModel
     */
    protected $model;

    public function __construct(CartModel $model)
    {
        $this->model = $model;

        /**
         * $this->model->goods 这是购物车商品的数据模型
         */
        
        /**
         * $this->model->goods 这是购物车店铺数据模型 
         */
    }

    /**
     * 多店铺购物车总计
     */
    public function multipleStoreCartPrice()
    {
    	 
    }

	/**
	 * 订单总费用
	 */
    public function totalOrderFee()
    {
    	
    }
}