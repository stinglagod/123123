<?php

namespace rent\entities\Shop\Order\Item;

use lhs\Yii2SaveRelationsBehavior\SaveRelationsBehavior;
use rent\cart\CartItem;
use rent\entities\behaviors\ClientBehavior;
use rent\entities\Shop\Order\Order;
use rent\entities\Shop\Product\Movement\Movement;
use rent\entities\Shop\Service;
use rent\entities\Shop\Order\Status;
use rent\entities\Shop\Product\Product;
use rent\helpers\MovementTypeHelper;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property int $order_id
 * @property int $product_id
 * @property string $name
 * @property double $price
 * @property int $qty
 * @property int $period_qty
 * @property int $period_id
 * @property int $block_id
 * @property string $block_name
 * @property int $type_id
 * @property int $parent_id
 * @property int $service_id
 * @property string $note
 * @property int $current_status
 * @property int $sort
 * @property float $cost
 * @property int $is_montage
 * @property int $updated_at
 * @property integer $client_id
 *
 * @property BlockData $blockData
 * @property PeriodData $periodData
 * @property Order $order
 * @property Product $product
 * @property OrderItem $parent
 * @property OrderItem $block
 * @property Service $service
 * @property OrderItem[] $children
 * @property OrderItem[] $collects
 * @property Movement[] $movements
 *
 * @method ActiveQuery find(bool $all)
 */
class OrderItem extends ActiveRecord
{
    const TYPE_BLOCK = 1;             //блок
    const TYPE_RENT = 2;              //аренда
    const TYPE_SALE = 3;              //продажа
    const TYPE_COLLECT = 4;           //коллекция
    const TYPE_SERVICE = 5;           //услуга
    const TYPE_CUSTOM = 6;            //произвольная позиция

    public $blockData;
    public $periodData;

    public static function create(CartItem $cartItem)
    {
        $item = new static();
        if ($cartItem->product) {
            $item->product_id = $cartItem->product->id;
        }
        $item->name = $cartItem->name;
        $item->qty = $cartItem->qty;
        $item->price = $cartItem->price;
//        $item->period_qty=$cartItem->periodData->qty;
//        $item->period_id=$cartItem->periodData->type;
        $item->type_id = $cartItem->type_id;
        if ($cartItem->parent) {
            $item->parent_id = $cartItem->parent->id;
        }
        $item->current_status = $cartItem->createCustomer ? Status::NEW_BY_CUSTOMER : Status::NEW;

        $item->periodData = $cartItem->periodData;

        return $item;
    }
###Block
    public static function createBlock($name): self
    {
        $item = new static();
        $item->name = $name;
        $item->type_id = self::TYPE_BLOCK;
        return $item;
    }

    public function editBlock($name): void
    {
        $this->name = $name;
    }

    public function isBlockIdEqualTo($id): bool
    {
        return $this->block_id == $id;
    }
###Service
    public static function createService(Service $service): self
    {
        $item = new static();
        $item->name = $service->name;
        $item->price = $service->defaultCost;
        $item->type_id = self::TYPE_SERVICE;
        $item->service_id = $service->id;
        $item->qty=1;
        return $item;
    }
###Status
    public function isNew(): bool
    {
        return Status::isNew($this->current_status);
    }
    public function isCompleted():bool
    {
        if ($this->isNew()) return true;

        $pull=0;        //к нам
        $push=0;        //от нас
        foreach ($this->movements as $movement) {
            if (MovementTypeHelper::isPull($movement->type_id)){
                $pull+=$movement->qty;
            } else if (MovementTypeHelper::isPush($movement->type_id)){
                $push+=$movement->qty;
            }
        }
        if ($this->type_id==self::TYPE_RENT) {
            return ($this->qty==$pull) and ($this->qty==$push);
        } else {
            return ($this->qty==$push);
        }
    }
    public function isIssued($full=false):bool
    {
        if ($full) {
            return $this->current_status==Status::ISSUE;
        } else {
            return (($this->current_status==Status::ISSUE) or ($this->current_status==Status::PART_ISSUE));
        }
    }
    public function isReturned($full=false): ?bool
    {
        if ($this->type_id==self::TYPE_RENT) {
            if ($full) {
                return $this->current_status==Status::RETURN;
            } else {
                return (($this->current_status==Status::RETURN) or ($this->current_status==Status::PART_RETURN));
            }
        }
        return null;
    }
    public function isReserved(): bool
    {
        return $this->current_status==Status::ESTIMATE;
    }
    public function updateStatus(): void
    {
        $status=Status::NEW;
        $pull=0;        //к нам
        $push=0;        //от нас
        foreach ($this->movements as $movement) {
            if (MovementTypeHelper::isPull($movement->type_id)){
                $pull+=$movement->qty;
            } else if (MovementTypeHelper::isPush($movement->type_id)){
                $push+=$movement->qty;
            }
        }

        if (($this->qty>$push)and($push!=0)) {
            $status=Status::PART_ISSUE;
        } else if ($this->qty==$push) {
            $status=Status::ISSUE;
        }

        if (($this->qty>$pull)and($pull!=0)) {
            $status=Status::PART_RETURN;
        } else if ($this->qty==$pull) {
            $status=Status::RETURN;
        }

        $this->current_status=$status;
    }
###Operation
    public function addOperation($operation_id=null,$typeMovement_id=null,int $qty=null):void
    {
        if ((empty($operation_id))and (empty($typeMovement_id))) {
            throw new \RuntimeException('Empty is $operation_id or $typeMovement_id');
        }
        $typeMovement_id=$typeMovement_id?:$this->getTypeMovementFromOperation($operation_id);
        $qty=$qty?:$this->qty;
        $this->canOperation($typeMovement_id,$qty);

//        $depend_id=null;
//        if ($typeMovement_id==Movement::TYPE_RENT_PUSH) {
//            foreach ($this->movements as $movement) {
//                if ($movement->type_id==Movement::TYPE_RESERVE) {
//                    $depend_id=$movement->id;
//                }
//            }
//        } elseif ($typeMovement_id==Movement::TYPE_RENT_PULL) {
//            foreach ($this->movements as $movement) {
//                if ($movement->type_id==Movement::TYPE_RESERVE) {
//                    $depend_id=$movement->id;
//                }
//            }
//        }
        $depend_id=null;
        foreach ($this->movements as $movement) {
            if ($movement->type_id==Movement::TYPE_RESERVE) {
                $depend_id=$movement->id;
            }
        }

        $movements=$this->movements;
        $movements[]=Movement::create(
            $this->order->date_begin,
            $this->order->date_end,
            $qty,
            $this->product_id?:null,
            $typeMovement_id,
            true,
            $depend_id
        );
        $this->changeThis();
        $this->movements=$movements;
        $this->updateStatus();

    }



    private $_typeMovement=null;
    public function getTypeMovementFromOperation($operation_id): int
    {
        if (empty($this->_typeMovement)) {
            switch ($operation_id){
                case Order::OPERATION_ISSUE:
                    if ($this->type_id==OrderItem::TYPE_RENT) {
                        $this->_typeMovement = Movement::TYPE_RENT_PUSH;
                    } else {
                        $this->_typeMovement = Movement::TYPE_SALE;
                    }
                    break;
                case Order::OPERATION_RETURN:
                    if ($this->type_id==OrderItem::TYPE_RENT) {
                        $this->_typeMovement = Movement::TYPE_RENT_PULL;
                    } else {
                        throw new \DomainException('Нельзя вернуть проданный товар');
                    }
                    break;
                default:
                    throw new \DomainException('Некорректная операция');
            }
        }
        return $this->_typeMovement;
    }

###Movement
    public function updateMovement():void
    {
        $movements=$this->movements;
        foreach ($movements as $movement) {
            //только для брони
            if ($movement->type_id==Movement::TYPE_RESERVE) {
                $movement->qty=$this->qty;
            }
        }
        $this->movements=$movements;
    }
    public function balance(int $typeMovement_id):int
    {
        $sum=0;
        foreach ($this->movements as $movement) {
            if ($movement->type_id==$typeMovement_id) {
                $sum+=$movement->qty;
            }
        }
        return $this->qty-$sum;
    }

    public function balanceByOperation(int $operation_id):int
    {
        $typeMovement_id=$this->getTypeMovementFromOperation($operation_id);
        return $this->balance($typeMovement_id);
    }
    public function canOperation($typeMovement_id,$qty):void
    {
        if ($this->balance($typeMovement_id) < $qty) {
            throw new \DomainException('Нельзя совершить операцию по позиции на такое количество');
        }
    }
    public function clearMovementForce():void
    {
        $this->clearMovement(true);
        $this->current_status=Status::NEW;
    }
    public function clearMovement($force=false):void
    {
        if ($force) {
            $this->movements=[];
        } else {
            $movements=$this->movements;
            foreach ($movements as $i => $movement) {
                if (($movement->product_id) and ($movement->isSale())) {
                    continue;
                }
                unset($movements[$i]);
            }
            $this->movements = $movements;
        }
    }
    public function reserve($qty=null)
    {
        $this->addOperation(null,Movement::TYPE_RESERVE,$qty);
        $this->current_status=Status::ESTIMATE;
    }

    public function complete()
    {
        $this->current_status=Status::COMPLETED;
    }
    public function cancel()
    {
        $this->clearMovement();
        $this->current_status=Status::CANCELLED;
    }

    public function isIdEqualTo($id): bool
    {
        return $this->id == $id;
    }

    public function isProductIdEqualTo($product_id): bool
    {
        return $this->product_id == $product_id;
    }

    public function setSort($sort): void
    {
        $this->sort = $sort;
    }

    public function isBlock(): bool
    {
        return $this->type_id == self::TYPE_BLOCK;
    }

    public function isCollect(): bool
    {
        return $this->type_id == self::TYPE_COLLECT;
    }

    public function readOnly(): bool
    {
        // Ситуация когда товар добавлен в ридонли заказ. Сейчас это исправлено, в будущем эту строчку можно удалить
        // 20201111 {
        if ($this->isNew()) return false;
        // }
        if ($this->order_id) {
            return $this->order->readOnly();
        } else {
            return $this->parent->order->readOnly();
        }
    }
    public function isRent(): bool
    {
        return $this->type_id==self::TYPE_RENT;
    }
    /**
     * Не большой кастыль. Таким образом метим данную запись на то что она изменилась и нужно пройти по не
     * SaveRelationsBehavior Это нужно для вложенных вложенных связей
     */
    private function changeThis():void
    {
        $this->updated_at=1;
    }
##############################################
    public function getOrder(): ActiveQuery
    {
        return $this->hasOne(Order::class, ['id' => 'order_id']);
    }

    public function getParent(): ActiveQuery
    {
        return $this->hasOne(OrderItem::class, ['id' => 'parent_id']);
    }

    public function getChildren(): ActiveQuery
    {
        return $this->hasMany(OrderItem::class, ['parent_id' => 'id'])->orderBy('sort');
    }

    public function getCollects(): ActiveQuery
    {
        return $this->getChildren()->andWhere(['type_id' => self::TYPE_COLLECT]);
    }
    public function getProduct(): ActiveQuery
    {
        return $this->hasOne(Product::class, ['id' => 'product_id']);
    }
    public function getService(): ActiveQuery
    {
        return $this->hasOne(Service::class, ['id' => 'service_id']);
    }
    public function getMovements(): ActiveQuery
    {
        return $this->hasMany(Movement::class, ['order_item_id' => 'id']);
    }

    public function getCost(): float
    {
        $cost = $this->price * $this->qty;
        switch ($this->type_id) {
            case self::TYPE_BLOCK:
                foreach ($this->children as $child) {
                    $cost += $child->getCost();
                }
                break;
            case self::TYPE_RENT:
                $cost *= $this->period_qty;
                break;
        }
        return $cost;
    }

    public function getBlock(): ?self
    {
        if ($this->parent) {
            if ($this->parent->isBlock()) {
                return $this->parent;
            } else {
                return $this->parent->getBlock();
            }
        }
        return null;
    }
    public function getCountChildren(): ?int
    {
        if ($this->isBlock()) {
            return count($this->children);
        }
        return null;
    }

##############################################
    public static function tableName(): string
    {
        return '{{%shop_order_items}}';
    }
    public function afterFind(): void
    {
//        $this->blockData = new BlockData(
//            $this->getAttribute('block_id'),
//            $this->getAttribute('block_name')
//        );
        $this->periodData = new PeriodData(
            $this->getAttribute('period_qty'),
            $this->getAttribute('period_id')
        );

        parent::afterFind();
    }

    public function beforeSave($insert): bool
    {
        if ($this->periodData) {
            $this->setAttribute('period_qty', $this->periodData->qty);
            $this->setAttribute('period_id', $this->periodData->type);
        }
        if (empty($this->qty)) {
            $this->qty=1;
        }

        return parent::beforeSave($insert);
    }
    public function beforeDelete()
    {

        return parent::beforeDelete(); // TODO: Change the autogenerated stub
    }

    public function behaviors(): array
    {
        return [
            ClientBehavior::class,
            TimestampBehavior::class,
            [
                'class' => SaveRelationsBehavior::class,
                'relations' => ['movements','children'],
            ],
        ];
    }

    public function transactions()
    {
        return [
            self::SCENARIO_DEFAULT => self::OP_ALL,
        ];
    }
    public function attributeLabels()
    {
        return [
            'date_begin' => 'Начало',
            'date_end' => 'Окончание',
            'type_id'=>'Тип движения',
        ];
    }
}