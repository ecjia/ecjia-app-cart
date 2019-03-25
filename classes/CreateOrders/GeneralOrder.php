<?php
/**
 * Created by PhpStorm.
 * User: royalwang
 * Date: 2019-03-25
 * Time: 13:25
 */

namespace Ecjia\App\Cart\CreateOrders;


class GeneralOrder
{

    protected $user_part;

    protected $payment_part;

    protected $shipping_part;

    protected $card_part;

    protected $integral_part;

    protected $bonus_part;

    protected $invoince_part;

    protected $address_part;

    protected $store_part;


    public function __construct()
    {

    }

    /**
     * @return mixed
     */
    public function getUserPart()
    {
        return $this->user_part;
    }

    /**
     * @param mixed $user_part
     * @return GeneralOrder
     */
    public function setUserPart($user_part)
    {
        $this->user_part = $user_part;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getPaymentPart()
    {
        return $this->payment_part;
    }

    /**
     * @param mixed $payment_part
     * @return GeneralOrder
     */
    public function setPaymentPart($payment_part)
    {
        $this->payment_part = $payment_part;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getShippingPart()
    {
        return $this->shipping_part;
    }

    /**
     * @param mixed $shipping_part
     * @return GeneralOrder
     */
    public function setShippingPart($shipping_part)
    {
        $this->shipping_part = $shipping_part;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getCardPart()
    {
        return $this->card_part;
    }

    /**
     * @param mixed $card_part
     * @return GeneralOrder
     */
    public function setCardPart($card_part)
    {
        $this->card_part = $card_part;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getIntegralPart()
    {
        return $this->integral_part;
    }

    /**
     * @param mixed $integral_part
     * @return GeneralOrder
     */
    public function setIntegralPart($integral_part)
    {
        $this->integral_part = $integral_part;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getBonusPart()
    {
        return $this->bonus_part;
    }

    /**
     * @param mixed $bonus_part
     * @return GeneralOrder
     */
    public function setBonusPart($bonus_part)
    {
        $this->bonus_part = $bonus_part;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getInvoincePart()
    {
        return $this->invoince_part;
    }

    /**
     * @param mixed $invoince_part
     * @return GeneralOrder
     */
    public function setInvoincePart($invoince_part)
    {
        $this->invoince_part = $invoince_part;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getAddressPart()
    {
        return $this->address_part;
    }

    /**
     * @param mixed $address_part
     * @return GeneralOrder
     */
    public function setAddressPart($address_part)
    {
        $this->address_part = $address_part;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getStorePart()
    {
        return $this->store_part;
    }

    /**
     * @param mixed $store_part
     * @return GeneralOrder
     */
    public function setStorePart($store_part)
    {
        $this->store_part = $store_part;
        return $this;
    }






}