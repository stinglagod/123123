<?php

namespace rent\entities\Shop\Order\Item;


use rent\entities\Shop\Order\Item\ItemBlock;

class BlockData
{


    public $id;
    public $name;

    public function __construct($id=null, $name=null)
    {
        $this->id = $id?:$this->generateId();
        $this->name = $name?:ItemBlock::DEFAULT_NAME;
    }

    private function generateId():int
    {
        $id=random_int(0,4294967295);
        if (OrderItem::find()->where(['block_id'=>$id])->exists()) {
            return $this->generateId();
        } else {
            return $id;
        }
    }
}