<?php
/**
 * Created by PhpStorm.
 * User: royalwang
 * Date: 2019-02-19
 * Time: 18:16
 */

namespace Ecjia\App\Cart\Models;

use Royalcms\Component\Database\Eloquent\Model;

class ProductsModel extends Model
{

    protected $table = 'products';

    protected $primaryKey = 'product_id';

    /**
     * 可以被批量赋值的属性。
     *
     * @var array
     */
    protected $fillable = [
        'goods_id',
        'goods_attr',
        'product_sn',
        'product_number'
    ];

    /**
     * 该模型是否被自动维护时间戳
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * @return \Royalcms\Component\Database\Eloquent\Relations\HasMany
     * @deprecated 1.33.0
     */
    public function cart()
    {
        return $this->hasMany('Ecjia\App\Cart\Models\CartModel', 'product_id', 'product_id');
    }

    /**
     * 获取购物车
     * 一对多
     * @return \Royalcms\Component\Database\Eloquent\Relations\HasMany
     */
    public function cart_collection()
    {
        return $this->hasMany('Ecjia\App\Cart\Models\CartModel', 'product_id', 'product_id');
    }
    
}