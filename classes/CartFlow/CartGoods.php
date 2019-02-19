<?php
/**
 * Created by PhpStorm.
 * User: royalwang
 * Date: 2019-02-19
 * Time: 18:30
 */

namespace Ecjia\App\Cart\CartFlow;


use Ecjia\App\Cart\Models\CartModel;
use ecjia_error;

class CartGoods
{

    /**
     * @var CartModel
     */
    protected $model;

    protected $output = [];

    public function __construct(CartModel $model)
    {
        $this->model = $model;

        /**
         * $this->model->goods 这是购物车商品的数据模型
         */
    }


    public function formattedHandleData()
    {
        //初始状态
        $this->output['is_disabled'] = 0;
        $this->output['disabled_label'] = '';

        //判断库存
        $result = $this->checkGoodsStockNumber();
        if (is_ecjia_error($result)) {
            $this->output['is_disabled'] = 1;
            $this->output['disabled_label'] = $result->get_error_message();
        }

        //判断上架状态
        $result = $this->checkOnSaleStatus();
        if (is_ecjia_error($result)) {
            $this->output['is_disabled'] = 1;
            $this->output['disabled_label'] = $result->get_error_message();
        }


        //不可用状态，取消选中
        $this->checkSeletedStatus();

        //增加购物车选中状态判断



        /* 返回未格式化价格*/
        $this->output['goods_price'] = $this->model->goods_price;
        $this->output['market_price'] = $this->model->market_price;
        $this->output['formatted_goods_price'] = ecjia_price_format($this->model->market_price, false);
        $this->output['formatted_market_price'] = ecjia_price_format($this->model->market_price, false);

        /* 统计实体商品和虚拟商品的个数 */


        /* 查询规格 */


        return $this->output;
    }

    /**
     * 检查商品库存数量
     */
    protected function checkGoodsStockNumber()
    {

        return new ecjia_error('inventory_shortage', __('库存不足', 'cart'));
    }

    /**
     * 检测上架状态
     */
    protected function checkOnSaleStatus()
    {

        return new ecjia_error('goods_onsale_error', __('商品已下架', 'cart'));
    }

    /**
     * 检查商品选中状态
     */
    protected function checkSeletedStatus()
    {
        if ($this->model->is_disabled === 1) {
            $this->output['is_checked'] = 0;
//            RC_Loader::load_app_class('cart', 'cart', false);
//            cart::flow_check_cart_goods(array('id' => $row['rec_id'], 'is_checked' => 0));

        }

    }

}