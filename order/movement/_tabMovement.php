<?php
use kartik\grid\GridView;
use yii\helpers\Html;
use yii\helpers\Url;
use \rent\entities\Shop\Product\Movement\Movement;
use rent\helpers\PaymentHelper;
use rent\helpers\MovementTypeHelper;

/**
 * @var $this yii\web\View
 * @var $order rent\entities\Shop\Order\Order
 */

 ?>
<div class="tab-payment" id="tab-payment">
    <?=
        GridView::widget([
            'dataProvider' => $dataProvider,
            'id' => 'order-payment-grid',
            'pjax' => true,
            'columns' => [
                [
                    'class' => 'kartik\grid\SerialColumn',
                    'header' => '',
                    'width' => '10%',
                ],
                [
                    'attribute' => 'date_begin',
                    'format' => 'datetime',
                    'hAlign' => 'center',
                    'vAlign' => 'middle',
                    'width' => '10%',
                ],
                [
                    'attribute' => 'date_end',
                    'format' => 'datetime',
                    'hAlign' => 'center',
                    'vAlign' => 'middle',
                    'width' => '10%',
                ],
                [
                    'attribute' => 'orderItem.name',
                    'hAlign' => 'center',
                    'vAlign' => 'middle',
                    'width' => '10%',
                ],
                [
                    'attribute' => 'type_id',
                    'value' => function ( Movement $model) {
                        return MovementTypeHelper::movementTypeName($model->type_id);
                    },
                    'format' => 'raw',
                    'hAlign' => 'center',
                    'vAlign' => 'middle',
                    'width' => '10%',
                ],
                [
                    'attribute' => 'qty',
                    'value' => function (Movement $model) {
//                        return MovementTypeHelper::movementTypeName($model->type_id);
                        return MovementTypeHelper::getTypeIconHtml($model->type_id).$model->qty;
                    },
                    'format' => 'raw',
                    'hAlign' => 'center',
                    'vAlign' => 'middle',
                    'width' => '10%',
                ],
//                [
//                    'value' => function ( Payment $model) use ($order) {
//                        return Html::a( '<span class="glyphicon glyphicon-trash"></span>',  ['payment-delete', 'id'=>$order->id,'payment_id' => $model->id],['data-method'=>"post"]);
//                    },
//                    'format' => 'raw',
//                ],
            ]
        ]);

    ?>

</div>
