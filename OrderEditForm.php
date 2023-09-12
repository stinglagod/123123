<?php

namespace rent\forms\manage\Shop\Order;

use rent\entities\Shop\Order\Order;
use rent\entities\User\User;
use rent\forms\CompositeForm;

/**
 * @property integer $date_begin
 * @property integer $date_end
 * @property integer $responsible_id
 * @property string $name
 * @property string $code
 * @property string $note
 * @property int $contact_id
 *
 *
 * @property DeliveryForm $delivery
 */
class OrderEditForm extends CompositeForm
{
    public $date_begin;
    public $date_end;
    public $responsible_id;
    public $name;
    public $code;
    public $contact_id;
    public $files;

    public $note;

    public function __construct(Order $order, array $config = [])
    {
        $this->date_begin=$order->date_begin;
        $this->date_end=$order->date_end;
        $this->responsible_id=$order->responsible_id;
        $this->name=$order->name;
        $this->code=$order->code;
        $this->note = $order->note;
        $this->contact_id=$order->contact_id;
        $this->delivery = new DeliveryForm($order);
        parent::__construct($config);
    }

    public function rules(): array
    {
        return [
            [[ 'name','date_begin'], 'required'],
            [['responsible_id','date_begin', 'date_end','contact_id'], 'integer'],
            [['date_begin', 'date_end'], 'validateDate'],
            [['responsible_id'], 'exist', 'skipOnError' => true, 'targetClass' => User::class, 'targetAttribute' => ['responsible_id' => 'id']],
            [['name','note'], 'string'],
            [['name'],'string', 'max' => 100],
            [['code'],'string', 'max' => 50   ],
        ];
    }

    protected function internalForms(): array
    {
        return ['customer','delivery'];
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
            'contact_id' => 'Заказчик',
        ];
    }

    public function validateDate()
    {
        if ($this->date_end) {
            if ($this->date_begin > $this->date_end){
                $this->addError(null,'"Дата окончания", не может быть раньше "даты начала"');
            }
        }
    }
}
