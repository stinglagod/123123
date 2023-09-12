<?php

namespace rent\entities\Shop\Order;

use rent\entities\behaviors\ClientBehavior;
use rent\entities\Client\Site;
use rent\entities\Shop\Product\Product;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

/**
 * @property integer $id
 * @property integer $dateTime
 * @property integer $order_id
 * @property integer $sum
 * @property integer $site_id
 * @property integer $payment_id
 * @property integer $client_id
 *
 * @property Order $order
 * @property Payment $payment
 *
 * @method ActiveQuery find(bool $all)
**/

class BalanceCash extends ActiveRecord
{

    public static function create(int $dateTime, float $sum): self
    {
        $balance = new static();
        $balance->dateTime=$dateTime;
        $balance->sum=$sum;
        return $balance;
    }
###########################################
    public function getOrder(): ActiveQuery
    {
        return $this->hasOne(Order::class, ['id' => 'order_id']);
    }
    public function getPayment(): ActiveQuery
    {
        return $this->hasOne(Payment::class, ['id' => 'payment_id']);
    }
##############################################
    public static function tableName(): string
    {
        return '{{%shop_balance_cash}}';
    }

    public function behaviors(): array
    {
        return [
            ClientBehavior::class,
        ];
    }

    public function beforeSave($insert): bool
    {
        $this->order_id = $this->payment?$this->payment->order_id:null;
        return parent::beforeSave($insert);
    }
}