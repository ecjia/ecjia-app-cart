<?php
/**
 * Created by PhpStorm.
 * User: royalwang
 * Date: 2019-02-19
 * Time: 19:02
 */

namespace Ecjia\App\Cart\CartFlow;


use Ecjia\App\Cart\Models\CartModel;

class CartStore
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
    }


}