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

    public function __construct($inv_title_type = '', $inv_payee = '', $inv_tax_no = '', $inv_type ='', $inv_content ='')
    {
    	
    	$this->inv_title_type 	= $inv_title_type;
    	
    	$this->inv_payee 		= $inv_payee;
    	
    	$this->inv_tax_no 		= $inv_tax_no;
    	
        $this->inv_type 		= $inv_type;
        
        $this->inv_content 		= $inv_content;
        
    }

	/**
	 * 发票信息
	 */
    public function invoinceInfo()
    {
    	$inv_payee_final = '';
    	
    	$inv_payee_final = $this->get_inv_payee_final();
    	if (is_ecjia_error($inv_payee_final)) {
    		return $inv_payee_final;
    	}
    	$invoinceInfo = [
    		'inv_title_type' 	=> $this->inv_title_type,
    		'inv_payee'		 	=> $this->inv_payee,  //客户端传的原始值
    		'inv_payee_final'	=> $inv_payee_final, //数据库最终存放的值
    		'inv_tax_no'		=> $this->inv_tax_no,
    		'inv_type'			=> $this->inv_type,
    		'inv_content'		=> $this->inv_content
    	];
    	return $invoinceInfo;
    }
    
    
    /**
     * 发票抬头处理
     */
    protected function get_inv_payee_final()
    {
    	$inv_title_type = $this->inv_title_type;
    	$inv_payee_last = '';
    	if (!empty($inv_title_type)) {
    		if ($inv_title_type == 'personal') {
    			$inv_payee_last = empty($this->inv_payee) ?  __('个人', 'cart') : trim($this->inv_payee);
    			
    		} elseif($inv_title_type == 'enterprise') {
    			//发票纳税人识别码
    			$inv_tax_no = empty($this->inv_tax_no) ? '' : trim($this->inv_tax_no);
    			$inv_payee = empty($this->inv_payee) ? '' : trim($this->inv_payee);
    			
    			if (empty($inv_tax_no) || empty($inv_payee)) {
    				return new \ecjia_error('invoice_error', __('发票抬头和识别码都不能为空！', 'cart'));
    			}
    			//如果有传发票识别码，发票识别码存储在inv_payee（发票抬头）字段中；格式为发票抬头 + ,发票纳税人识别码；如：（企业,789654321456987124）。
    			$inv_payee_last = $inv_payee.','.$inv_tax_no;
    		}
    	}
    	return $inv_payee_last;
    }
    

}