<?php

use kartik\grid\GridView;
use yii\helpers\Html;
use yii\widgets\ActiveForm;
use yii\widgets\Pjax;
use yii\bootstrap\Modal;
use kartik\touchspin\TouchSpin;
use \common\models\Action;
use yii\helpers\Url;
use \common\models\OrderProduct;
use rent\helpers\OrderHelper;
use rent\entities\Shop\Order\Item\OrderItem;
use rent\entities\Shop\Order\Order;

/* @var $this yii\web\View */
/* @var $order  Order*/
/* @var $items_provider \yii\data\ActiveDataProvider */
/* @var $items OrderItem[] */
/* @var $operation_id integer */


?>
<?php
$form = ActiveForm::begin([
    'id' => 'form-operation-confirm',
    'action' => ['operation-add-ajax','id'=>$order->id,'operation_id'=>$operation_id],
]);
?>
<?php
    Modal::begin([
        'header' => '<h4 id="modalTitle"><h4>'.OrderHelper::operationName($operation_id).'</h4>',
        'id' => 'modal-operation-confirm',
        'size' => 'modal-lg',
        'clientOptions' => ['backdrop' => 'static'],

        'footer' => '<button type="button" class="btn btn-default pull-left" data-dismiss="modal">Закрыть</button>
                    <button type="submit" class="btn btn-success" class="">'.OrderHelper::operationName($operation_id).'</button>',
    ]);
?>
<?php

?>
    <div id='mainModalContent'>
<!--        --><?php //foreach ($blocks as $block) :?>
<!--            <h4>--><?//=$block->name?><!--</h4>-->
            <?=$this->render('_grid',[
                'items_provider' => $items_provider,
                'operation_id' => $operation_id,
                'form' => $form,
            ])
            ?>
<!--        --><?php //endforeach;?>



<!--        --><?//= GridView::widget([
//            'dataProvider' => $items_provider,
//            'pjax' => true,
//            'columns' => [
////                ['class' => 'yii\grid\SerialColumn'],
//                [
//                    'header' => 'Код',
//                    'width' => '10px',
//                    'value' => function (\common\models\OrderProduct $data) {
//                        if ($data->type=='collect') {
//                            return "";
//                        } else {
//                            return $data->product->name;
//                        }
//
//                    },
//                    'format' => 'raw'
//                ],
//                [
//                    'attribute' => 'product_id',
////                    'width' => '10px',
//                    'value' => function (\common\models\OrderProduct $data) {
//                        if ($data->type=='collect') {
//                            return $data->name.' (продажа)';
//                        } else {
//                            $name=$data->product->name;
//                            if ($data->type==OrderProduct::RENT) {
//                                $name.=' (аренда)';
//                            } else if ($data->type==OrderProduct::SALE) {
//                                $name.=' (продажа)';
//                            }
//                            if ($data->parent_id!=$data->id) {
//                                $name.='<br><small>в рамках составной позиции: '.$data->parent->name.'</small>';
//                            }
//                            return $name;
//                        }
//                    },
//                    'format' => 'raw',
//                ],
//                [
//                    'attribute' => 'qty',
//                    'filter' => false,
//                    'format' => 'raw',
//                    'width' => '20%',
//                    'value' => function(\common\models\OrderProduct $data) use ($form,$operation){
//                        return $form->field($data, "qty[$data->id]")->widget(TouchSpin::classname(), [
////                            'disabled' => true,
//                            'pluginOptions' => [
//                                'min' => 0,
//                                'max' => $data->getOperationBalance($operation),
//                                'step' => 1,
//                                'initval' => $data->qty,
//                                'maxboostedstep' => 10,
//                                'buttonup_class' => 'btn btn-primary',
//                                'buttondown_class' => 'btn btn-info',
//                                'buttonup_txt' => '<i class="glyphicon glyphicon-plus-sign"></i>',
//                                'buttondown_txt' => '<i class="glyphicon glyphicon-minus-sign"></i>'
//                            ],
//                            'value' => $data->qty,
//                        ])->label(false);
////                        return $form->field($searchModel, "qty[$searchModel->id]")->textInput([
////                            'class' => 'form-control',
////                            'value' => $searchModel->qty,
////                        ])->label(false);
//                    }
//                ],
//            ],
//        ]); ?>

    </div>
<?php
    Modal::end();
    ActiveForm::end();
?>

<?php
$urlOrder_product_movement_ajax=Url::toRoute("order-product/movement-ajax");
$js = <<<JS
    $('#form-operation-confirm').on('beforeSubmit', function () {
        let yiiform = $(this);
        let modal = $(this).find('.modal');
        
        $.ajax({
                type: yiiform.attr('method'),
                url: yiiform.attr('action'),
                data: yiiform.serializeArray(),
            }
        )
            .done(function(data) {
                if(data.success) {
                    console.log('data is saved');
                    // reloadPjaxs("#pjax_alerts");
                    modal.modal('hide');
                    yiiform.trigger('reset');
                    document.location.reload();
                    // $.pjax.reload("#order-payment-grid");
                    // $.pjax.reload("#pjax_alerts");
                } else if (data.validation) {
                    console.log('server validation failed');
                    yiiform.yiiActiveForm('updateMessages', data.validation, true); // renders validation messages at appropriate places
                } else {
                    console.log('incorrect server response');
                }
                // reloadPjaxs("#pjax_alerts",);
            })
            .fail(function () {
                // request failed
            })
    
        return false; // prevent default form submission
    })
JS;
$this->registerJs($js);
?>
