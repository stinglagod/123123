<?php
use kartik\editable\Editable;
use kartik\grid\GridView;
use rent\helpers\OrderHelper;
use yii\helpers\Html;
use yii\helpers\Url;
use rent\entities\Shop\Order\Item\OrderItem;

/* @var $order \rent\entities\Shop\Order\Order */

?>
<hr>
<h2>Услуги<?= OrderHelper::defaultPopoverX('Услуги','<a href="/admin/shop/service/" target="_blank">Добавление, редактирования услуг</a>')?></h2>

<?=GridView::widget([
    'id' => 'service_grid',
    'options' => [
        'class'=>'grid-view grid-orderservice'
    ],
    'pjax' => true,
    'pjaxSettings'=>[
        'options'=>[
            'enablePushState' => false,
            'timeout' => 5000
        ],
     ],
    'dataProvider' => \rent\readModels\Shop\OrderReadRepository::getProvider($order->getServices()),
//            'filterModel' => $searchModel,
    'layout' => "{items}\n{summary}\n{pager}",
    'columns' => [
        [
            'class' => 'kartik\grid\SerialColumn',
            'header' => '',
        ],
        [
            'class' => 'kartik\grid\EditableColumn',

            'attribute' => 'name',
            'header'=> 'Продукт',
            'pageSummary' => 'Итого',
            'headerOptions' => ['class' => 'text-center'],
            'width' => '30%', /*archi увеличил ширину*/
            'vAlign' => 'middle',
            'readonly'=>function ( $model) {
                return $model->readOnly();
            },
            'value' => function (OrderItem $model) {
                return Html::encode($model->name);
            },
            'format' => 'raw',
        ],
        [
            'class' => 'kartik\grid\EditableColumn',
            'attribute' => 'price',
            'header'=>'Цена',
            'format' => ['decimal', 2],
            'pageSummary' => false,
            'hAlign' => 'right',
            'vAlign' => 'middle',
            'headerOptions' => ['class' => 'kv-sticky-column'],
            'contentOptions' => ['class' => 'kv-sticky-column'],
            'editableOptions' => function (OrderItem $model, $key, $index){
                return [
//                                    'name'=>'cost',
                    'header' => 'Цена',
                    'size' => 'md',
                    'options' => ['id'=>'price_'.$model->id,],
                    'formOptions' => [ 'action' => Url::toRoute(['item-update-ajax']) ],
                    'pluginEvents' => [
                                        "editableSuccess"=>'gridOrderItem.onEditableGridSuccess',
                                        "editableSubmit"=> 'gridOrderItem.onEditableGridSubmit',
                    ]
                ];
            },
            'refreshGrid'=>false,
            'readonly' => function(OrderItem $model, $key, $index, $widget) {
                return $model->readOnly();
            },
        ],
        [
            'class' => 'kartik\grid\ActionColumn',
            'template' => '{delete}',
            'contentOptions' => ['class' => 'action-column'],
            'buttons' => [
                'delete' => function ($url, OrderItem $model, $key) {
                    if (!$model->readOnly())
                        return Html::a('<span class="glyphicon glyphicon-trash"></span>', Url::toRoute(['service-delete-ajax','id'=>$model->order_id,'item_id'=>$model->id]), [
                            'title' => Yii::t('yii', 'Delete'),
                            'data-pjax' => '#pjax_service_grid',
                            'data-confirm'=>'Вы действительно хотите удалить позицию из заказа?',
                            'data-method'=>'post'
                        ]);
                },
            ],

        ],

    ]
    ]);
?>
<?php

$js = <<<JS

JS;
$this->registerJs($js,yii\web\View::POS_BEGIN);
?>
