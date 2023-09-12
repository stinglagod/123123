<?php
use kartik\grid\GridView;
use yii\helpers\Html;
use yii\helpers\Url;
use rent\entities\Shop\Order\Payment;
use rent\helpers\PaymentHelper;
use yii\widgets\Pjax;

/**
 * @var $this yii\web\View
 * @var $order rent\entities\Shop\Order\Order
 * @var $payments_form \rent\forms\manage\Shop\Order\PaymentForm
 */

 ?>
<div class="tab-payment" id="tab-payment">
    <div class="row">
        <div class="col-md-6">
            <?php Pjax::begin(['id'=>'sum-order-tab-payment-pjax']); ?>
            Сумма заказа: <?=$order->getTotalCost()?>
            <br>
            Оплачено: <?=$order->paid?>
            <br>
            Остаток: <?=($order->getTotalCost() - $order->paid)?>
            <br>
            <br>
            <?php Pjax::end(); ?>
        </div>
        <div class="col-md-6">
            <div class="btn-group pull-right" role="group" aria-label="toolbar">
                <button type="button" class="btn btn-success <?=$order->readOnly('add-payment')?'disabled':''?>" title="Добавить платеж"  data-toggle="modal" data-target="#_modalPaymentAdd">Добавить платеж</button>
            </div>
        </div>
    </div>
    <?=
        GridView::widget([
            'dataProvider' => $dataProvider,
            'id' => 'order-payment-grid',
            'pjax' => true,
            'columns' => [
                [
                    'class' => 'kartik\grid\SerialColumn',
                    'header' => '',
                ],
                [
                    'attribute' => 'dateTime',
                    'format' => 'datetime',
                    'hAlign' => 'center',
                    'vAlign' => 'middle',
                    'width' => '10%',
                ],
                [
                    'label'=>'',
                    'attribute' => 'type_id',
                    'value' => function ( Payment $model) {
                        return PaymentHelper::getTypeIconHtml($model->type_id);
                    },
                    'format' => 'raw',
                    'hAlign' => 'center',
                    'vAlign' => 'left',
                ],
                [
                    'attribute' => 'purpose_id',
                    'value' => function ( Payment $model) {
                        return PaymentHelper::paymentPurposeName($model->purpose_id);
                    },
                ],
                [
                    'attribute'=>'sum',
                    'value' => function ( Payment $model) use ($order) {
                        return PaymentHelper::getSumHtml($model);
                    },
                    'format' => 'raw'
                ],
                'note',
                [
                    'value' => function ( Payment $model) use ($order) {
                        return Html::a( '<span class="glyphicon glyphicon-trash"></span>',  ['payment-delete', 'id'=>$order->id,'payment_id' => $model->id],['data-method'=>"post"]);
                    },
                    'format' => 'raw',
                ],
            ]
        ]);

    ?>

    <?=
        $this->render('_modalPaymentAdd',[
            'model' => $payments_form,
            'order' => $order,
        ])
    ?>
</div>
