<?php
/**
 * Created by PhpStorm.
 * User: royalwang
 * Date: 2019-03-25
 * Time: 13:32
 */

namespace Ecjia\App\Cart\CreateOrders\OrderParts;


class OrderInvoincePart
{

    protected $inv_title_type;
    
    protected $inv_payee;
    
    protected $inv_tax_no;
    
    protected $inv_type;
    
    protected $inv_content;

    public function __construct($inv_title_type, $inv_payee, $inv_tax_no, $inv_type, $inv_content)
    {
        $this->inv_type = $inv_type;
        
        $this->inv_content = $inv_content;
    }

	

}