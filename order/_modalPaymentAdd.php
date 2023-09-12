<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use kartik\datecontrol\DateControl;
use yii\bootstrap\Modal;
use yii\helpers\Url;
use kartik\touchspin\TouchSpin;
use yii\helpers\ArrayHelper;
use rent\helpers\PaymentHelper;

/* @var $this yii\web\View */
/* @var $model \rent\forms\manage\Shop\Order\PaymentForm */
/* @var $order rent\entities\Shop\Order\Order */

?>

<?php $form = ActiveForm::begin([
        'id' => 'form-order-add-payment',
        'action' => ['payment-add-ajax','id'=>$order->id],
        'options' => [ 'data-pjax_reload' => '#pjax_alerts'],
    ]); ?>
<?php
Modal::begin([
    'header' => '<h4 id="Добавление платежа"><h4>Добавление платежа</h4>',
    'id' => '_modalPaymentAdd',
    'size' => 'modal-md',
    'clientOptions' => ['backdrop' => 'static'],
    'footer' => '<button type="button" class="btn btn-default pull-left" data-dismiss="modal">Закрыть</button>
                    <button type="submit" class="btn btn-success" class="">Сохранить</button>',
]);
?>
<div id='mainModalContent'>



    <div class="row">
        <div class="col-md-6">
            <?=
            $form->field($model, 'dateTime')->widget(DateControl::class, [
                'type'=>DateControl::FORMAT_DATE,
                'widgetOptions' => [
                    'pluginOptions' => [
                        'autoclose' => true
                    ]
                ]
            ])
            ?>
        </div>
        <div class="col-md-6">
            <?= $form->field($model, 'type_id')->dropDownList(PaymentHelper::paymentTypeList(), ['prompt' => Yii::t('app', 'Выберите')]) ?>
        </div>

        <div class="col-md-6">
            <?= $form->field($model, 'purpose_id')->dropDownList(PaymentHelper::paymentPurposeList(), ['prompt' => Yii::t('app', 'Выберите')]) ?>
        </div>

        <div class="col-md-6">
            <?= $form->field($model, 'sum')->textInput(['maxlength' => true]) ?>
        </div>

        <div class="col-md-6">
            <?= $form->field($model->payer, 'name')->textInput(['maxlength' => true]) ?>
        </div>

        <div class="col-md-6">
            <?= $form->field($model->payer, 'phone')->textInput(['maxlength' => true]) ?>
        </div>

        <div class="col-md-12">
            <?= $form->field($model, 'note')->textarea(['maxlength' => true]) ?>
        </div>
    </div>



</div>
<?php
Modal::end();
?>
<?php ActiveForm::end(); ?>
<?php
$js = <<<JS
    $('#form-order-add-payment').on('beforeSubmit', function () {
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
                    // console.log('data is saved');
                    // reloadPjaxs("#pjax_alerts");
                    modal.modal('hide');
                    yiiform.trigger('reset');
                    document.location.reload();
                    // $.pjax.reload("#order-payment-grid");
                    // $.pjax.reload("#pjax_alerts");
                } else if (data.validation) {
                    // console.log('server validation failed');
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
