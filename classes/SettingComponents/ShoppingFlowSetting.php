<?php
/**
 * Created by PhpStorm.
 * User: royalwang
 * Date: 2019-03-28
 * Time: 12:05
 */

namespace Ecjia\App\Cart\SettingComponents;


use Ecjia\App\Setting\ComponentAbstract;

class ShoppingFlowSetting extends ComponentAbstract
{

    /**
     * 代号标识
     * @var string
     */
    protected $code = 'goods';

    /**
     * 名称
     * @var string
     */
    protected $name = '购物流程';

    /**
     * 描述
     * @var string
     */
    protected $description = '';


    public function handle()
    {
        $data = [
            ['code' => 'use_integral', 'value' => '1', 'options' => ['type' => 'select', 'store_range' => '1,0']],
            ['code' => 'use_bonus', 'value' => '1', 'options' => ['type' => 'select', 'store_range' => '1,0']],
            ['code' => 'use_surplus', 'value' => '1', 'options' => ['type' => 'select', 'store_range' => '1,0']],
            ['code' => 'use_how_oos', 'value' => '1', 'options' => ['type' => 'select', 'store_range' => '1,0']],

        ];

        return $data;
    }


    public function getConfigs()
    {
        $config = [
            [
                'cfg_code' => 'use_integral',
                'cfg_name' => __('是否使用积分', 'goods'),
                'cfg_desc' => '',
                'cfg_range' => array(
                    '0' => __('不使用', 'goods'),
                    '1' => __('使用', 'goods'),
                ),
            ],

            [
                'cfg_code' => 'use_bonus',
                'cfg_name' => __('是否使用红包', 'goods'),
                'cfg_desc' => '',
                'cfg_range' => array(
                    '0' => __('不使用', 'goods'),
                    '1' => __('使用', 'goods'),
                ),
            ],

            [
                'cfg_code' => 'use_surplus',
                'cfg_name' => __('是否使用余额', 'goods'),
                'cfg_desc' => '',
                'cfg_range' => array(
                    '0' => __('不使用', 'goods'),
                    '1' => __('使用', 'goods'),
                ),
            ],

            [
                'cfg_code' => 'use_how_oos',
                'cfg_name' => __('是否使用缺货处理', 'goods'),
                'cfg_desc' => __('使用缺货处理时前台订单确认页面允许用户选择缺货时处理方法。', 'goods'),
                'cfg_range' => array(
                    '0' => __('不使用', 'goods'),
                    '1' => __('使用', 'goods'),
                ),
            ],


        ];

        return $config;
    }

}