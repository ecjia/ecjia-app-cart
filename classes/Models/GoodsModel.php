<?php
/**
 * Created by PhpStorm.
 * User: royalwang
 * Date: 2019-02-19
 * Time: 18:16
 */

namespace Ecjia\App\Cart\Models;

use Royalcms\Component\Database\Eloquent\Model;

class GoodsModel extends Model
{

    protected $table = 'goods';

    protected $primaryKey = 'goods_id';

    /**
     * 可以被批量赋值的属性。
     *
     * @var array
     */
    protected $fillable = [
        'store_id',
        'cat_id',
        'cat_level1_id',
        'cat_level2_id',
    ];

    /**
     * 该模型是否被自动维护时间戳
     *
     * @var bool
     */
    public $timestamps = false;


    public function cart()
    {
        return $this->belongsTo('Ecjia\App\Cart\Models\CartModel', 'goods_id', 'goods_id');
    }

}