<?php

namespace rent\entities\Shop\Order;

class DeliveryData
{
    public $address;

    public function __construct($address)
    {
        $this->address = $address;
    }
}