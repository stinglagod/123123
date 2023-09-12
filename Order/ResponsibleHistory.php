<?php

namespace rent\entities\Shop\Order;

class ResponsibleHistory
{

    public $responsible_id;
    public $responsible_name;
    public $created_at;

    public function __construct($user_id,$name, $created_at)
    {
        $this->responsible_id = $user_id;
        $this->responsible_name = $name;
        $this->created_at = $created_at;
    }
}