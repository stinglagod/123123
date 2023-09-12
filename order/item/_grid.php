<?php
use yii\widgets\Pjax;
use kartik\editable\Editable;
use yii\helpers\Html;
use yii\helpers\Url;
use kartik\grid\GridView;
use rent\entities\Shop\Order\Item\OrderItem;
use rent\forms\manage\Shop\Order\Item\ItemForm;
use rent\helpers\OrderHelper;

/* @var $block \rent\entities\Shop\Order\Item\OrderItem */
?>
<?= GridView::widget([
    'id' => 'grid_'.$block->id,
    'options' => [
        'class'=>'grid-view grid-order-items'
    ],
    'pjax' => true,
    'pjaxSettings'=>[
        'options'=>[
            'enablePushState' => false
        ],
    ],
    'dataProvider' => \rent\readModels\Shop\OrderReadRepository::getProvider($block->getChildren()),
    //            'filterModel' => $searchModel,
    'layout' => "{items}\n{summary}\n{pager}",
    'columns' => [
        [
            'class' => 'kartik\grid\SerialColumn',
            'header' => '',
        ],
        [
            'class' => 'kartik\grid\ExpandRowColumn',
            'width' => '30px', /*archi уменьшил ширину*/
            'value' => function (OrderItem $model, $key, $index, $column) {
                if ($model->type_id==OrderItem::TYPE_COLLECT) {
                    return GridView::ROW_COLLAPSED;
                } else {
                    return '';
                }
            },
            'detail' => function (OrderItem $model, $key, $index, $column) use($order){
                return Yii::$app->controller->renderPartial('item/_expand-row-details', [
                    'parent' => $model,
                    'children' => $model->children,
                    'order'=> $order,
                ]);
            },
            'headerOptions' => ['class' => 'kartik-sheet-style'],
            'expandOneOnly' => true
        ],
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
                    return Html::a(Html::encode($model->name), Url::to(['shop/catalog/product', 'id' =>$model->product->id]),[
                        'data-pjax'=>0,
                        'class'=>'popover-product-name',
                        'data-content'=> $model->product->mainPhoto?'<img src="'.Html::encode($model->product->mainPhoto->getThumbFileUrl('file', 'catalog_list')).'"/>':'',
                    ]);
                } else {
                    return Html::encode($model->name);
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
            'attribute' => 'is_montage',
            'header'=>'Монтаж',
            'vAlign' => 'middle',
            'hAlign' => 'center',

            'value' => function (OrderItem $model) {
                if ($model->is_montage == '1') {
                    return Html::checkbox('is_montage',1,['disabled' => $model->readOnly(),'class'=>'chk_is_montage','data-url'=>Url::toRoute(['item-update-ajax']), 'data-method'=>'POST', 'data-key'=>$model->id]);
                } else {
                    return Html::checkbox('is_montage',0,['disabled' => $model->readOnly(),'class'=>'chk_is_montage','data-url'=>Url::toRoute(['item-update-ajax']), 'data-method'=>'POST', 'data-key'=>$model->id]);

                }

            }
            , 'format' => 'raw'
        ],
        [
            'class' => 'kartik\grid\EditableColumn',
            'attribute' => 'note',
            'header'=>'Комментарий',
            'headerOptions' => ['class' => 'text-center'],
            'width' => '19%',
            'vAlign' => 'middle',
            'format' => 'raw',
            'editableOptions' => function (OrderItem $model, $key, $index) {
                return [
                    'name'=>'comment',
                    'value' => $model->note,
                    'header' => 'Примечание',
                    'size' => 'md',
                    'options' => ['id'=>'note_'.$model->id],
                    'formOptions' => [ 'action' => Url::toRoute(['item-update-ajax']) ],
                ];
            },
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
            'header'=>'Статус',
            'value' => function (OrderItem $model) {
                return OrderHelper::statusName($model->current_status);
            }
        ],
//        'sort',
        [
            'class' => 'kartik\grid\ActionColumn',
            'template' => '{sort}{delete}',
            'contentOptions' => ['class' => 'action-column'],
            'buttons' => [
                'delete' => function ($url, OrderItem $model, $key)  {
                    if (!$model->readOnly() and (!$model->children))
                        return Html::a('<span class="glyphicon glyphicon-trash"></span>', Url::toRoute(['item-delete-ajax','id'=>$model->order_id,'item_id'=>$model->id]), [
                            'title' => Yii::t('yii', 'Delete'),
                            'data-pjax' => '#pjax_order-product_grid_'.$model->id,
                            'data-confirm'=>'Вы действительно хотите удалить позицию из заказа?',
                            'data-method'=>'post',
                        ]);
                },
                'sort' => function ($url, OrderItem $model, $key)  {
                    $result='';
                    if (!$model->readOnly() ) {
                        if ($model->sort != 0) {
                            $result.= '<button class="btn btn-default ' . ($model->order->readOnly()?'disabled':'move-block') . '" data-url="'.Url::toRoute(['item-move-up-ajax','id'=>$model->order->id,'item_id'=>$model->id]).'" data-method="POST" type="button"><span class="glyphicon glyphicon-arrow-up" aria-hidden="true"></span></button>';
                        }

                        if (($model->sort+1)!=$model->block->getCountChildren()) {
                            $result.= '<button class="btn btn-default ' . ($model->order->readOnly()?'disabled':'move-block') . '" data-url="'.Url::toRoute(['item-move-down-ajax','id'=>$model->order->id,'item_id'=>$model->id]).'" data-method="POST" type="button"><span class="glyphicon glyphicon-arrow-down" aria-hidden="true"></span></button>';
                        }
                    }
                    return $result;
//                        return
//                            '<button class="btn btn-default ' . ($model->order->readOnly()?'disabled':'move-block') . '" data-url="'.Url::toRoute(['item-move-up-ajax','id'=>$model->order->id,'item_id'=>$model->id]).'" data-method="POST" type="button"><span class="glyphicon glyphicon-arrow-up" aria-hidden="true"></span></button>'
//                            .
//                            '<button class="btn btn-default ' . ($model->order->readOnly()?'disabled':'move-block') . '" data-url="'.Url::toRoute(['item-move-down-ajax','id'=>$model->order->id,'item_id'=>$model->id]).'" data-method="POST" type="button"><span class="glyphicon glyphicon-arrow-down" aria-hidden="true"></span></button>';

//                        return Html::a('<span class="glyphicon glyphicon-arrow-up"></span>', Url::toRoute(['item-delete-ajax','id'=>$model->order_id,'item_id'=>$model->id]), [
//                            'title' => Yii::t('yii', 'Delete'),
//                            'data-pjax' => '#pjax_order-product_grid_'.$model->id,
//                            'data-confirm'=>'Вы действительно хотите удалить позицию из заказа?',
//                            'data-method'=>'post',
//                        ]) .
//                            Html::a('<span class="glyphicon glyphicon-arrow-down"></span>', Url::toRoute(['item-delete-ajax','id'=>$model->order_id,'item_id'=>$model->id]), [
//                                'title' => Yii::t('yii', 'Delete'),
//                                'data-pjax' => '#pjax_order-product_grid_'.$model->id,
//                                'data-confirm'=>'Вы действительно хотите удалить позицию из заказа?',
//                                'data-method'=>'post',
//                            ]);
                },
            ],

        ],
        [
            'class' => 'kartik\grid\CheckboxColumn',
            'headerOptions' => ['class' => 'kartik-sheet-style'],
        ],


    ],
    'showPageSummary' => true,
]);
?>

<?php

$js = <<<JS
//обновляем услуги после обновления гридов
jQuery(document).on("pjax:success", ".grid-order-items",  function(event){
    // alert('hu');
        $.pjax.reload({container: "#service_grid-pjax"});
    }
);

//при расскрытие составных позиций, у таблицы не работают Editable. не навешиваются события. Сделал костыльно перезагружать при открытии
// gridView=$()
jQuery(document).on("kvexprow:toggle", ".grid-view",  function(event, ind, key, extra, state){
    if (state) {
        reloadPjaxs('#grid_'+key+'-pjax');
    }
}
);
JS;
$this->registerJs($js);
?>
