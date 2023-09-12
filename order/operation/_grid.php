<?php
use yii\widgets\Pjax;
use kartik\editable\Editable;
use yii\helpers\Html;
use yii\helpers\Url;
use kartik\grid\GridView;
use rent\entities\Shop\Order\Item\OrderItem;
use rent\forms\manage\Shop\Order\Item\ItemForm;
use rent\readModels\Shop\OrderReadRepository;
use \rent\forms\manage\Shop\Order\OperationForm;
use kartik\touchspin\TouchSpin;

/* @var $block \rent\entities\Shop\Order\Item\OrderItem */
/* @var $items_provider \yii\data\ActiveDataProvider */
/* @var $operation_id integer */
/* @var $order Order*/

?>

<?= GridView::widget([
    'id' => 'operation-grid',
    'options' => [
        'class'=>'grid-view'
    ],
    'pjax' => true,
    'pjaxSettings'=>[
        'options'=>[
            'enablePushState' => false
        ],
    ],
    'dataProvider' => $items_provider,
    'layout' => "{items}\n{summary}\n{pager}",
    'columns' => [
        [
            'class' => 'kartik\grid\SerialColumn',
            'header' => '',
        ],
        'name',
        [
            'attribute' => 'qty',
            'filter' => false,
            'format' => 'raw',
            'width' => '20%',
            'value' => function(OrderItem $model) use ($form,$operation_id){
                return $form->field($model, "qty[$model->id]")->widget(TouchSpin::class, [
//                            'disabled' => true,
                    'pluginOptions' => [
                        'min' => 0,
                        'max' => $model->balanceByOperation($operation_id),
                        'step' => 1,
                        'initval' => $model->qty,
                        'maxboostedstep' => 10,
                        'buttonup_class' => 'btn btn-primary',
                        'buttondown_class' => 'btn btn-info',
                        'buttonup_txt' => '<i class="glyphicon glyphicon-plus-sign"></i>',
                        'buttondown_txt' => '<i class="glyphicon glyphicon-minus-sign"></i>'
                    ],
                    'value' => $model->qty,
                ])->label(false);
//                        return $form->field($searchModel, "qty[$searchModel->id]")->textInput([
//                            'class' => 'form-control',
//                            'value' => $searchModel->qty,
//                        ])->label(false);
            }
        ],
    ],
    ]);
?>

<?php

?>
