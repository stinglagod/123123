<?php

namespace rent\entities\Shop\Order;

use rent\entities\Client\Client;
use rent\entities\CRM\Contact;
use function GuzzleHttp\Psr7\str;
use lhs\Yii2SaveRelationsBehavior\SaveRelationsBehavior;
use rent\cart\CartItem;
use rent\entities\Client\Site;
use rent\entities\Shop\Order\Item\BlockData;
use rent\entities\Shop\Order\Item\ItemBlock;
use rent\entities\Shop\Order\Item\OrderItem;
use rent\entities\Shop\Order\Item\PeriodData;
use rent\entities\Shop\Service;
use rent\entities\User\User;
use rent\entities\Shop\Order\DeliveryData;
use rent\forms\manage\Shop\Order\OrderCartForm;
use rent\helpers\OrderHelper;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;
use yii\helpers\Json;
use rent\entities\behaviors\ClientBehavior;
use yii\behaviors\TimestampBehavior;
use Yii;

/**
 * @property int $id
 * @property string $code
 * @property int $date_begin
 * @property int $date_end
 * @property string $name
 * @property int $cost
 * @property int $note
 * @property int $current_status
 * @property string $cancel_reason
 * @property CustomerData $customerData
 * @property DeliveryData $deliveryData
 * @property integer $responsible_id
 * @property string $responsible_name
 * @property integer $site_id
 * @property integer $created_at
 * @property integer $updated_at
 * @property integer $author_id
 * @property integer $lastChangeUser_id
 * @property float $paid
 * @property float $totalCost
 * @property integer $paidStatus
 * @property integer $customer_id
 * @property integer $client_id
 * @property integer $contact_id
 *
 * @property OrderItem[] $items
 * @property OrderItem[] $itemsWithoutBlocks
 * @property OrderItem[] $blocks
 * @property OrderItem[] $services
 * @property OrderItem[] $itemsDependByService
 * @property Payment[] $payments
 * @property Status[] $statuses
 * @property ResponsibleHistory[] $responsibleHistory
 * @property Site $site
 * @property User $responsible
 * @property Client $client
 * @property Contact $contact
 *
 */
class Order extends ActiveRecord
{

    const OPERATION_ISSUE = 1;          //Выдача
    const OPERATION_RETURN = 2;         //Возрат
    const OPERATION_DELETE = 0;         //Возрат

    const DEFAULT_BOOKING_TIME = 30*60*24*14;  // по умолчанию срок бронирования

    const DEFAULT_NAME_FROM_SITE= 'Заказ с сайта';

    public $customerData;
    public $deliveryData;
    public $statuses = [];
    public $responsibleHistory = [];

    public static function create(
        $responsible_id,
        string $name,
        $code,
        int $date_begin,
        int $date_end=null,
        int $contact_id,
        DeliveryData $deliveryData,
        array $items,
        float $cost,
        string $note,
        bool $createCustomer = null): self
    {
        if (empty($items)) {
            $items[]=OrderItem::createBlock(ItemBlock::DEFAULT_NAME);
        }
        $order = new static();
        $order->name = $name;
        $order->code = $code;
        $order->date_begin = $date_begin;
        $order->date_end = $date_end;
        $order->contact_id = $contact_id;
        $order->deliveryData = $deliveryData;
        $order->items = $items;
        $order->cost = $cost;
        $order->note = $note;
        $order->addStatus($createCustomer ? Status::NEW_BY_CUSTOMER : Status::NEW);
        if ($responsible_id) $order->changeResponsible($responsible_id);

        return $order;
    }
    public static function createFromSite(
        int $user_id,
        int $date_begin,
        int $date_end=null,
        CustomerData $customerData,
        DeliveryData $deliveryData,
        array $items,
        float $cost,
        string $note
        ): self
    {
        $item=OrderItem::createBlock(ItemBlock::DEFAULT_NAME);
        $item->children=$items;

        $order = new static();
        $order->name=self::getDefaultName();
        $order->date_begin = $date_begin;
        $order->date_end = $date_end;
        $order->customerData = $customerData;
        $order->deliveryData = $deliveryData;
//        $order->items = [$item];
        $order->items = array_merge([$item],$items);
        $order->cost = $cost;
        $order->note = $note;
        $order->addStatus(Status::NEW_BY_CUSTOMER);

        return $order;
    }

    public function edit(
        $responsible_id,
        string $name,
        $code,
        int $date_begin,
        $date_end=null,
        $contact_id,
        DeliveryData $deliveryData,
        string $note=''): void
    {
        $this->name = $name;
        $this->code = $code;
        $this->date_begin = $date_begin;
        $this->date_end = $date_end;
        $this->contact_id = $contact_id;
        $this->deliveryData = $deliveryData;
        $this->note = $note;
        if ($responsible_id) $this->changeResponsible($responsible_id);
    }
###Status
    public function canMakeNew($exception = false):bool
    {
        if ($exception) {
            if ($this->isNew()){
                throw new \DomainException('Снять бронь нельзя. Бронь уже снята ');
            }
            if ($this->isIssuedProduct()){
                throw new \DomainException('Снять бронь нельзя. По заказу есть выданные товары. ');
            }

        }
        return (!$this->isIssuedProduct()and (!$this->isNew()));
    }
    public function makeNew($force=false):void
    {
        if ($this->isNew()) {
            throw new \DomainException('Заказ уже со снятой бронью');
        }

        if (!$force) $this->canMakeNew(true);

        $this->addStatus(Status::isNew($this->statuses[0]->value)?$this->statuses[0]->value:Status::NEW);
    }

    public function estimate():void
    {
        if ($this->isEstimated()) {
            throw new \DomainException('Order is already estimate.');
        }

        $this->canBeEstimated(true);

        $this->addStatus(Status::ESTIMATE);
    }

    public function canBeEstimated($exception = false):bool
    {
        if ($exception) {
            if (!$this->isNew()){
                throw new \DomainException('Забронировать можно только новый заказ');
            }
//            if (!$this->hasPayments()) {
//                throw new \DomainException('Бронировать можно только после предоплаты.');
//            }
        }
//        return ($this->isNew() and $this->hasPayments());
        return ($this->isNew());
    }

    public function complete(): void
    {
        if ($this->isCompleted()) {
            throw new \DomainException('Order is already completed.');
        }
        $this->canBeCompleted(true);

        $this->addStatus(Status::COMPLETED);
    }

    public function canBeCompleted($exception = false): bool
    {
        if ($exception) {
            if (!$this->isPaid()) {
                throw new \DomainException('Заказ не оплачен полностью.');
            }
            if ($this->isNew()) {
                throw new \DomainException('Нельзя завершить новый заказ - только отмена');
            }
            if ($this->isCompleted()) {
                throw new \DomainException('Заказ уже завершен');
            }
            if ($this->isEstimated()) {
                throw new \DomainException('Нельзя завершить заказ со статусом "Составлена смета"');
            }
            if (!$this->isDebtByProduct()) {
                throw new \DomainException('Не все позиции заказа отданы(возращены)');
            }
        }
        return  (($this->isDebtByProduct())and
                ($this->isPaid()and
                (!$this->isNew())and
                (!$this->isCompleted()) and
                (!$this->isEstimated())));
    }

    public function cancel($reason=''): void
    {
        if ($this->isCancelled()) {
            throw new \DomainException('Order is already cancelled.');
        }
        $this->canBeCancel(true);
        $this->cancel_reason = $reason;
        $this->addStatus(Status::CANCELLED);
    }

    public function canBeCancel($exception = false): bool
    {
        if ($exception) {
            if (!$this->isDebtByProduct()) {
                throw new \DomainException('Не все позиции заказа отданы(возращены)');
            }
            if (!$this->hasBalancePayments()) {
                throw new \DomainException('Нельзя отменить заказ если баланс по платежам не равен 0');
            }
            if ($this->isCancelled()) {
                throw new \DomainException('Заказ уже отменен');
            }
        }
        return ($this->isDebtByProduct()and($this->hasBalancePayments())and(!$this->isCancelled()));
    }
    public function isClose(): bool
    {
        return ($this->isCancelled() or $this->isCompleted());
    }

    public function isNew(): bool
    {
        return Status::isNew($this->current_status);
    }
    public function isCompleted(): bool
    {
        return $this->current_status == Status::COMPLETED;
    }

    public function isEstimated(): bool
    {
        return $this->current_status == Status::ESTIMATE;
    }

    public function isCancelled(): bool
    {
        return (($this->current_status == Status::CANCELLED) or ($this->current_status == Status::CANCELLED_BY_CUSTOMER));
    }

    public function canChangeStatus(Status $status): bool
    {
        //TODO: прописать условия когда можно или нельзя менять статус
        return true;
    }

    public function isIssuedProduct($full=false):bool
    {
        foreach ($this->itemsWithoutBlocks as $item) {
            if ($item->isIssued($full))
                return true;
        }
        return false;
    }
    public function isReserveProduct():bool
    {
        foreach ($this->itemsWithoutBlocks as $item) {
            if ($item->isReserved())
                return true;
        }
        return false;
    }
    public function isReturnedProduct($full=false):bool
    {
        if (empty($items=$this->itemsWithoutBlocks)) return true;

        foreach ( $items as $item) {
            if ($item->isReturned($full))
                return true;
        }
        return false;
    }
    public function isDebtByProduct():bool
    {
        foreach ($this->itemsWithoutBlocks as $item) {
            if (!$item->isCompleted()) return false;

        }
        return true;
    }
    private function addStatus($value): void
    {

        if ($this->current_status!=$value) {
            $this->statuses[] = new Status($value, time());
            $this->current_status = $value;
        }

        $operation=null;
        if ($items=$this->itemsWithoutBlocks) {
            switch ($value){
                case Status::isNew($value):                 //освобождаем от резервирования
                    $operation='clearMovementForce';
                    break;
                case Status::ESTIMATE:                      //Бронируем
                    $operation='reserve';
                    break;
                case Status::COMPLETED:                      //Завершаем
                    $operation='complete';
                    break;
                case Status::CANCELLED:                      //Отменяем
                    $operation='cancel';
                    break;
                default:
                    throw new \DomainException('Неопределена операция с товаром, просьба связаться с администратором');
            }
            foreach ($items as $item) {
                $item->$operation();
                $item->current_status=$value;
            }
            $this->itemsWithoutBlocks=$items;
        }
    }

    private function updateStatus():void
    {
        $status_id = Status::NEW;
        if ($this->statuses) {
            $status_id = Status::isNew($this->statuses[0]->value);
        }

        if ($items = $this->itemsWithoutBlocks) {
            $least_item = null;
            foreach ($items as $item) {
                if (empty($least_item)) {
                    $least_item = $item;
                    continue;
                }

                if ($item->current_status < $least_item->current_status) {
                    $least_item = $item;
                }
            }
            if (!Status::isNew($least_item->current_status)) {
                $status_id = $least_item->current_status;
            }
        }
        if ($this->current_status!=$status_id) {
            $this->statuses[] = new Status($status_id, time());
            $this->current_status = $status_id;
        }
    }

    public function updatePaidStatus()
    {
        if ($this->paid==0) {
            $this->paidStatus = Status::PAID_NO;
        } elseif ($this->totalCost < $this->paid) {
            $this->paidStatus = Status::PAID_OVER;
        } elseif ($this->totalCost > $this->paid) {
            $this->paidStatus = Status::PAID_PART;
        } elseif ($this->totalCost == $this->paid) {
            $this->paidStatus = Status::PAID_FULL;
        }
    }

###Payments
    public function getTotalCost(): float
    {
        $sum = 0;
        foreach ($this->blocks as $block) {
            $sum += $block->getCost();
        }
        foreach ($this->services as $service) {
            $sum += $service->getCost();
        }
        return $sum;
    }

    public function canBePaid(): bool
    {
        return !$this->isClose();
    }

    /**
     * Оплачен в полном объеме?
     * @return bool
     */
    public function isPaid(): bool
    {
        return $this->totalCost == $this->paid;
    }

    /**
     * Есть платежи?
     * @return bool
     */
    public function hasPayments():bool
    {
        return count($this->payments)>0;
    }

    /**
     * Есть ли баланс по палатежам. Никто никому не должен
     * @return bool
     */
    public function hasBalancePayments():bool
    {
        return $this->paid==0;
    }


    public function removePayment($id): void
    {
        $payments = $this->payments;
        foreach ($payments as $i => $payment) {
            if ($payment->isIdEqualTo($id)) {
                unset($payments[$i]);
                $this->payments = $payments;
                return;
            }
        }
        throw new \DomainException('Payment is not found.');
    }


###Service
    public function hasService(Service $service): bool
    {
        /** @var OrderItem $item_service */
        foreach ($this->services as $item_service) {
            if ($item_service->service_id == $service->id) return true;
        }
        return false;
    }
    public function addService(Service $service): void
    {
        if ($this->hasService($service)) {
            throw new \DomainException('Услуга (' . $service->name . ') уже есть в заказе');
        }

        $services = $this->services;
        $item_service = OrderItem::createService($service);
        if ($service->is_depend) {
            $cost = 0;
            /** @var OrderItem $item */
            foreach ($this->itemsDependByService as $item) {
                $cost += $item->getCost() * $service->percent / 100;
            }
            $item_service->price = round($cost);
            $item_service->qty = 1;
        }
        $services[] = $item_service;
        $this->services = $services;
    }
    public function calcService(): void
    {
        $item_services = $this->services;
        /** @var OrderItem $item_service */
        foreach ($item_services as $item_service) {
            if ($item_service->service->is_depend) {
                $cost = 0;
                /** @var OrderItem $item */
                foreach ($this->itemsDependByService as $item) {
                    $cost += $item->getCost() * $item_service->service->percent / 100;
                }
                $item_service->price = round($cost);
            }
        }
        $this->services=$item_services;
    }
###Responsible
    private function changeResponsible($responsible_id): void
    {
        if (!$responsible = User::findOne($responsible_id))
            throw new \DomainException('Don not find responsible.');
        $this->responsibleHistory[] = new ResponsibleHistory($responsible_id, $responsible->getShortName(), time());
        $this->responsible_id = $responsible_id;
    }

###Block
    public function addBlock($name): OrderItem
    {
        $blocks = $this->blocks;
        $block = OrderItem::createBlock($name);
        $blocks[] = $block;
        $this->blocks = $blocks;
        $this->updateBlocks($blocks);
        return $block;
    }

    public function editBlock($id, $name): void
    {
        $blocks = $this->blocks;
        foreach ($blocks as $i => $block) {
            if ($block->isIdEqualTo($id)) {
                $block->editBlock($name);
                $this->blocks = $blocks;
                return;
            }
        }
        throw new \DomainException('Block is not found.');
    }

    public function removeBlock($id): void
    {
        $items = $this->items;
        foreach ($items as $i => $item) {
            if ($item->isIdEqualTo($id)) {
                if (!$item->children) {
                    unset($items[$i]);
                    $this->updateBlocks($items);
                    return;
                } else {
                    throw new \DomainException('Блок не пустой. Удаление не возможно');
                }
            }
        }

    }

    public function countBlocks(): int
    {
        return count($this->blocks);
    }

    public function moveBlockUp($id): void
    {
        $blocks = $this->blocks;
        foreach ($blocks as $i => $block) {

            if ($block->isIdEqualTo($id)) {

                if ($prev = $blocks[$i - 1] ?? null) {
                    $blocks[$i - 1] = $block;
                    $blocks[$i] = $prev;
                    $this->updateBlocks($blocks);
                }
                return;
            }
        }
        throw new \DomainException('Block is not found.');
    }

    public function moveBlockDown($id): void
    {
        $blocks = $this->blocks;
        foreach ($blocks as $i => $block) {
            if ($block->isIdEqualTo($id)) {
                if ($prev = $blocks[$i + 1] ?? null) {
                    $blocks[$i + 1] = $block;
                    $blocks[$i] = $prev;
                    $this->updateBlocks($blocks);
                }
                return;
            }
        }
        throw new \DomainException('Block is not found.');
    }
    /**
     * @param OrderItem[] $blocks
     */
    private function updateBlocks(array $blocks): void
    {
        foreach ($blocks as $i => $block) {
            $block->setSort($i);
        }
        $this->blocks = $blocks;
    }

    /**
     * @param OrderItem[] $items
     */
    private function updateItems(array $items): void
    {
        $itemBlock=$items[0]->block;
        $blocks= $this->blocks;

        foreach ($items as $i => $item) {
            $item->setSort($i);
            $item->save();
         }

        foreach ($blocks as $block) {

            if ($block->isIdEqualTo($itemBlock->id)) {
                $block->children=$items;
            }
        }
        $this->blocks = $blocks;
    }


###Item
    public function addItem(CartItem $cartItem): void
    {
        //проверка на возможность добавления
        $this->canAddItem(true);

        $items = $this->items;
        $notInOrder = true;
        if ($cartItem->product) {
            foreach ($items as $item) {
                if ($item->product) {
                    if (($item->product->isIdEqualTo($cartItem->product->id)) and
                        ($item->parent->isIdEqualTo($cartItem->parent->id))) {
                        $item->qty += $cartItem->qty;
                        $notInOrder = false;
                    }
                }
            }
        }

        if ($notInOrder)
            $items[] = OrderItem::create($cartItem);

        $this->items = $items;
    }

    public function editItem($item_id, $name, $price, $qty, $period, $is_montage, $note): void
    {
        $items = $this->items;
        foreach ($items as $i => $item) {
            if ($item->isIdEqualTo($item_id)) {
                $item->name = $name;
                $item->price = $price;
                $item->qty = $qty;
                $item->periodData->qty = $period;
                $item->period_qty = $period;

                $item->is_montage = $is_montage;
                $item->note = $note;

                $this->items = $items;
                return;
            }
        }
        throw new \DomainException('Item is not found.');
    }

    public function removeItem($id): void
    {
        $items = $this->items;
        foreach ($items as $i => $item) {
            if ($item->isIdEqualTo($id)) {
                if (!$item->children) {
                    unset($items[$i]);
                    $this->items = $items;
                    return;
                } else {
                    throw new \DomainException('Составная позиция не пустая.');
                }
            }
        }

    }
    public function getCountProductInOrder($productId):int
    {
        $items = $this->items;
        $count=0;
        foreach ($items as $i => $item) {
            if ($item->isProductIdEqualTo($productId)) {
                $count+=$item->qty;
            }
        }
        return $count;
    }
    public function moveItemUp(OrderItem $oderItem): void
    {
        $items=$oderItem->block->children;
        foreach ($items as $i => $item) {

            if ($item->isIdEqualTo($oderItem->id)) {

                if ($prev = $items[$i - 1] ?? null) {
                    $items[$i - 1] = $item;
                    $items[$i] = $prev;
                    $this->updateItems($items);
                }
                return;
            }
        }
        throw new \DomainException('Item is not found.');
    }

    public function moveItemDown(OrderItem $oderItem): void
    {
        $items=$oderItem->block->children;

        foreach ($items as $i => $item) {

            if ($item->isIdEqualTo($oderItem->id)) {
                if ($prev = $items[$i + 1] ?? null) {
                    $items[$i + 1] = $item;
                    $items[$i] = $prev;
                    $this->updateItems($items);
                }
                return;
            }
        }
        throw new \DomainException('Item is not found.');
    }
###Проверки
###ReadOnly
    public function readOnly(string $attrb=null):bool
    {
        if ($this->isNew()) return false;

        if ($this->isClose()) return true;

        if ($attrb) {
            switch ($attrb){
                case 'customer.name':
                    return false;
                    break;
                case 'customer.phone':
                    return false;
                    break;
                case 'customer.email':
                    return false;
                    break;
                case 'delivery.address':
                    return false;
                    break;
                case 'add-payment':
                    return false;
                    break;
                case 'note':
                    return false;
                    break;
            }
        }

        return true;

        if ($this->isEstimated()) return true;

    }
###canAddItem
    public function canAddItem($exception = false):bool
    {
        return true;
        $condition=$this->isNew();

        if ($exception) {
            if (!$condition) {
                throw new \DomainException('Нельзя добавить товар в заказ. Товары можно добавлять в заказы со статусом "Черновик"');
            }

        }
        return $condition;
    }

    #############################################

    public function getResponsible(): ActiveQuery
    {
        return $this->hasOne(User::class, ['id' => 'responsible_id']);
    }


    public function getItems(): ActiveQuery
    {
        return $this->hasMany(OrderItem::class, ['order_id' => 'id']);
    }

    public function getItemsWithoutBlocks(): ActiveQuery
    {
        return $this->hasMany(OrderItem::class, ['order_id' => 'id'])->andWhere(['<>','type_id', OrderItem::TYPE_BLOCK])->andWhere(['<>','type_id', OrderItem::TYPE_SERVICE]);
    }


    public function getBlocks(): ActiveQuery
    {
        return $this->hasMany(OrderItem::class, ['order_id' => 'id'])->andWhere(['type_id' => OrderItem::TYPE_BLOCK])->orderBy('sort');
    }

    public function getServices(): ActiveQuery
    {
        return $this->hasMany(OrderItem::class, ['order_id' => 'id'])->andWhere(['type_id' => OrderItem::TYPE_SERVICE])->orderBy('sort');
    }

    public function getPayments(): ActiveQuery
    {
        return $this->hasMany(Payment::class, ['order_id' => 'id']);
    }

    public function getSite() :ActiveQuery
    {
        return $this->hasOne(Site::class, ['id' => 'site_id']);
    }
    public function getClient() :ActiveQuery
    {
        return $this->hasOne(Client::class, ['id' => 'client_id']);
    }
    public function getContact() :ActiveQuery
    {
        return $this->hasOne(Contact::class, ['id' => 'contact_id']);
    }

    public $_paid=null;
    public function getPaid(): float
    {
        $payments=$this->payments;
        $sum=0;
        foreach ($payments as $payment) {
            $sum+= $payment->sumWithSign;
        }
        return $sum;
    }

    public function getPeriod(): PeriodData
    {
        if (empty($this->date_end)) {
            throw new \DomainException('Data end is empty.');
        }
        $days = OrderHelper::countDaysBetweenDates($this->date_begin, $this->date_end);
        //попросили по умолчанию период сделать 1
        $days=1;
        return new PeriodData($days?:1);
    }

    public function getItemsDependByService(): ActiveQuery
    {
        return $this->hasMany(OrderItem::class, ['order_id' => 'id'])->andWhere(['is_montage' => true]);
    }

    public function getItemsForOperation(int $operation_id, array $item_ids=null): ActiveQuery
    {
        $query=$this->getItems()->andWhere(['in','current_status',$this->getStatuesByOperation($operation_id)]);
        if ($operation_id==self::OPERATION_RETURN) {
            $query->andWhere(['type_id'=>OrderItem::TYPE_RENT]);
        }
        if ($item_ids) {
            $query->andWhere(['in', 'id', $item_ids]);
        }
        return $query;

    }
    private function getStatuesByOperation($operation_id):array
    {
        switch ($operation_id){
            case self::OPERATION_ISSUE:
                return [Status::ESTIMATE,Status::PART_ISSUE];
            case self::OPERATION_RETURN:
                return [Status::PART_ISSUE,Status::ISSUE,Status::PART_RETURN];
        }
        throw new \DomainException('Operation failed.');
    }

    public function getDateEnd():int
    {
        return $this->date_end?:($this->date_begin+self::DEFAULT_BOOKING_TIME);
    }

    public function getDefaultName():string
    {
        return self::DEFAULT_NAME_FROM_SITE.' от ' .date('d.m.Y');
    }

    ##########################################

    public static function tableName(): string
    {
        return '{{%shop_orders}}';
    }

    public function behaviors(): array
    {
        return [
            ClientBehavior::class,
            TimestampBehavior::class,
            [
                'class' => SaveRelationsBehavior::class,
                'relations' => ['items','payments','blocks','services','itemsWithoutBlocks'],
            ],
        ];
    }

    public function transactions(): array
    {
        return [
            self::SCENARIO_DEFAULT => self::OP_ALL,
        ];
    }

    public function afterFind(): void
    {
        $this->statuses = array_map(function ($row) {
            return new Status(
                $row['value'],
                $row['created_at']
            );
        }, Json::decode($this->getAttribute('statuses_json')));

        $this->responsibleHistory = array_map(function ($row) {
            return new ResponsibleHistory(
                $row['responsible_id'],
                $row['responsible_name'],
                $row['created_at']
            );
        }, Json::decode($this->getAttribute('responsibleHistory_json')));

        $this->customerData = new CustomerData(
            $this->getAttribute('customer_phone'),
            $this->getAttribute('customer_name'),
            $this->getAttribute('customer_email')
        );

        $this->deliveryData = new DeliveryData(
            $this->getAttribute('delivery_address')
        );

        parent::afterFind();
    }

    public function beforeSave($insert): bool
    {
        $this->setAttribute('statuses_json', Json::encode(array_map(function (Status $status) {
            return [
                'value' => $status->value,
                'created_at' => $status->created_at,
            ];
        }, $this->statuses)));

        $this->setAttribute('responsibleHistory_json', Json::encode(array_map(function (ResponsibleHistory $responsibleHistory) {
            return [
                'responsible_id' => $responsibleHistory->responsible_id,
                'responsible_name' => $responsibleHistory->responsible_name,
                'created_at' => $responsibleHistory->created_at,
            ];
        }, $this->responsibleHistory)));

        if ($this->contact) {
            $this->setAttribute('customer_phone', $this->contact->telephone);
            $this->setAttribute('customer_name', $this->contact->name);
            $this->setAttribute('customer_email', $this->contact->email);
        }


        $this->setAttribute('delivery_address', $this->deliveryData->address);

        if ($this->responsible_id) {
            $this->setAttribute('responsible_name', $this->responsible->getShortName());
        }

        $this->updateStatus();

        $this->updatePaidStatus();

        if ($this->isAttributeChanged('date_begin')) {
            $this->date_begin=strtotime(date("Y-m-d 00:00:00", $this->date_begin));
        }
        if ($this->isAttributeChanged('date_end')) {
            $this->date_end=strtotime(date("Y-m-d 23:59:59", $this->date_end));
        }

        return parent::beforeSave($insert);
    }

    public static function find($all=false)
    {
        if ($all) {
            return parent::find();
        } else {
            return parent::find()->where(['client_id' => Yii::$app->settings->getClientId()]);
        }
    }

    public function beforeDelete()
    {
//      T
        foreach ($this->items as $item) {
            $item->clearMovement();
            $item->save();
        }
        return parent::beforeDelete(); // TODO: Change the autogenerated stub
    }

    public function attributeLabels()
    {
        return [
            'name' => 'Имя заказа',
            'date_begin' => 'Дата начала мероприятия',
            'date_end' => 'Окончание',
            'note'=>'Примечание',
            'responsible_id' => 'Менеджер',
            'current_status' => 'Статус',
            'paidStatus' => 'Статус оплаты',
            'contact_id' => 'Заказчик'
        ];
    }
}