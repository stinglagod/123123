<?php

namespace rent\entities\Shop\Order\Item;

class PeriodData
{
    const TYPE_MINUTE = 1;
    const TYPE_HOUR = 2;
    const TYPE_DAY = 3;
    const TYPE_WEEK = 4;
    const TYPE_MONTH = 5;

    public $type;
    public $qty;

    public function __construct($qty, $type_id=self::TYPE_DAY)
    {
        $this->qty = $qty;
        $this->type = $type_id;
    }
}