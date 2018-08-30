<?php

namespace Ecjia\App\Cart;

use Royalcms\Component\App\AppParentServiceProvider;

class CartServiceProvider extends  AppParentServiceProvider
{
    
    public function boot()
    {
        $this->package('ecjia/app-cart', null, dirname(__DIR__));
    }
    
    public function register()
    {
        
    }
    
    
    
}