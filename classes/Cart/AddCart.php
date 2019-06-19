<?php


namespace Ecjia\App\Cart\Cart;


use Royalcms\Component\Model\Model;

class AddCart
{
    /**
     * @var Model
     */
    protected $goods;

    public function __construct(Model $goods)
    {
        $this->goods = $goods;
    }


    public function add()
    {
        $result = $this->checkBuyGoods();

        if (is_ecjia_error($result)) {

        }

    }

    /**
     * 检查购买商品是否满足条件
     */
    public function checkBuyGoods()
    {
        //1.检查商品是否存在
        $this->checkGoodsExists();

        //2.检查商品是否已经下架
        $this->checkGoodsOffSale();

        //3.检查商品所属的店铺是否已经下线
        $this->checkGoodsForStoreOffline();

        //4.检查商品是否只能作为配件购买，不能单独销售
        $this->checkGoodsNotAloneSale();

        //5.检查商品是否有货品
        $this->checkGoodsHasProduct();

        //6.检查商品或货品的库存是否满足
        $this->checkLowStocks();


    }

    /**
     * 1.检查商品是否存在
     */
    protected function checkGoodsExists()
    {



    }


    protected function checkGoodsOffSale()
    {

    }


    protected function checkGoodsForStoreOffline()
    {

    }

    protected function checkGoodsNotAloneSale()
    {

    }

    protected function checkGoodsHasProduct()
    {

    }

    protected function checkLowStocks()
    {

    }

}