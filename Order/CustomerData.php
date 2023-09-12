<?php

namespace rent\entities\Shop\Order;

class CustomerData
{
    public $phone;
    public $name;
    public $email;

    public function __construct($phone, $name, $email)
    {
        $this->phone = $phone;
        $this->name = $name;
        $this->email = $email;
    }
}