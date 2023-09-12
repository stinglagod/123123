<?php
use yii\widgets\Pjax;
use kartik\editable\Editable;
use yii\helpers\Html;
use yii\helpers\Url;
use kartik\grid\GridView;
use rent\entities\Shop\Order\Item\OrderItem;
use rent\forms\manage\Shop\Order\Item\ItemForm;
use rent\readModels\Shop\OrderReadRepository;

/* @var $block \rent\entities\Shop\Order\Item\OrderItem */
?>
<?= GridView::widget([
    'id' => 'grid_'.$block->id,
    'options' => [
        'class'=>'grid-view'
    ],
    'pjax' => true,
    'pjaxSettings'=>[
        'options'=>[
            'enablePushState' => false
        ],
    ],
    'dataProvider' => OrderReadRepository::getProvider($block->getChildren()),
    'layout' => "{items}\n{summary}\n{pager}",
    'columns' => [
        [
            'class' => 'kartik\grid\EditableColumn',
            'attribute' => 'name',
            'header'=> 'Продукт',
            'pageSummary' => 'Итого',
            'headerOptions' => ['class' => 'text-center'],
            'width' => '30%', /*archi увеличил ширину*/
            'vAlign' => 'middle',
            'readonly'=>function (OrderItem $model) {
                return $model->readOnly();
            },
            'value' => function (OrderItem $model) {
                if ($model->product_id) {
                    return Html::a(Html::encode($model->name), Url::to(['product', 'id' =>$model->product->id]),[
                        'data-pjax'=>0,
                        'class'=>'popover-product-name',
                        'data-content'=> '<img src="'.Html::encode($model->product->mainPhoto->getThumbFileUrl('file', 'catalog_list')).'"/>',
                    ]);
                } else {
                    Html::encode($model->name);
                }
            },
            'format' => 'raw',
            'editableOptions' => function (OrderItem $model, $key, $index) {
                return [
                    'header' => 'Наименование',
                    'size' => 'md',
                    'options' => ['id'=>'name_'.$model->id],
                    'formOptions' => [
                        'action' => Url::toRoute(['item-update-ajax'])
                    ],

                ];
            },
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
            'class' => 'kartik\grid\EditableColumn',
            'attribute' => 'qty',
            'header'=>'Кол-во',
            'format' => ['decimal', 0],
            'pageSummary' => true,
            'hAlign' => 'right',
            'vAlign' => 'middle',
            'headerOptions' => ['class' => 'kv-sticky-column'],
            'contentOptions' => ['class' => 'kv-sticky-column'],
            'editableOptions' => function (OrderItem $model, $key, $index){
                return [
                    'header' => 'Количество',
                    'name'=>'qty',
//                                    'value' => $model->qty,
                    'size' => 'md',
                    'inputType' => \kartik\editable\Editable::INPUT_SPIN,
                    'options' => [
                        'id'=>'qty_'.$model->id,
                        'pluginOptions' => ['min' => 0, 'max' => 5000]
                    ],
                    'formOptions' => ['action' => Url::toRoute(['item-update-ajax']) ],
                    'pluginEvents' => [
                                        "editableSuccess"=>'gridOrderItem.onEditableGridSuccess',
                                        "editableSubmit"=> 'gridOrderItem.onEditableGridSubmit',
                    ]
                ];
            },
            'readonly' => function(OrderItem $model, $key, $index, $widget) {
                return $model->readOnly();
            },
            'refreshGrid'=>false,
        ],
        [
            'class' => 'kartik\grid\EditableColumn',
            'attribute' => 'period_qty',
            'header'=>'Период',
            'format' => ['decimal', 0],
            'pageSummary' => false,
            'hAlign' => 'right',
            'vAlign' => 'middle',
            'headerOptions' => ['class' => 'kv-sticky-column'],
            'contentOptions' => ['class' => 'kv-sticky-column'],
            'editableOptions' => function (OrderItem $model, $key, $index){
                return [
                    'header' => 'Период',
                    'size' => 'md',
                    'name'=> 'period_qty',
                    'value' => $model->period_qty,
                    'inputType' => \kartik\editable\Editable::INPUT_SPIN,
                    'options' => [
                        'id'=>'period_'.$model->id,
                        'pluginOptions' => ['min' => 0, 'max' => 5000]
                    ],
                    'formOptions' => [ 'action' => Url::toRoute(['item-update-ajax']) ],
                    'pluginEvents' => [
                        "editableSuccess"=>'gridOrderItem.onEditableGridSuccess',
                        "editableSubmit"=> 'gridOrderItem.onEditableGridSubmit',
                    ]
                ];
            },
            'readonly' => function(OrderItem $model, $key, $index, $widget){
                return $model->readOnly();
            },
            'refreshGrid'=>false,
        ],
        [
            'class' => 'kartik\grid\FormulaColumn',
            'header' => 'Сумма',
            'vAlign' => 'middle',
            'value' => function (OrderItem $model) {
                return $model->cost;
            },
            'headerOptions' => ['class' => 'kartik-sheet-style'],
            'hAlign' => 'right',
            'format' => ['decimal', 2],
            'mergeHeader' => true,
            'pageSummary' => true,
            'footer' => true
        ],
        [
            'class' => 'kartik\grid\ActionColumn',
            'template' => '{delete}',
            'contentOptions' => ['class' => 'action-column'],
            'buttons' => [
                'delete' => function ($url, OrderItem $model, $key) {
                    if (!$model->readOnly())
                        return Html::a('<span class="glyphicon glyphicon-trash"></span>', Url::toRoute(['item-delete-ajax','id'=>$model->order_id,'item_id'=>$model->id]), [
                            'title' => Yii::t('yii', 'Delete'),
                            'data-pjax' => '#grid'.$model->id.'-pjax',
                            'data-confirm'=>'Вы действительно хотите удалить позицию из заказа?',
                            'data-method'=>'post'
                        ]);
                },
            ],

        ],
    ]
]);