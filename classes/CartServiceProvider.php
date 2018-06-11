<?php

namespace Ecjia\App\Cart;

use Royalcms\Component\App\AppServiceProvider;

class CartServiceProvider extends  AppServiceProvider
{
    
    public function boot()
    {
        $this->package('ecjia/app-cart');
    }
    
    public function register()
    {
        
    }
    
    
    
}