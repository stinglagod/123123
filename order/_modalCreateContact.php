<?php

use rent\forms\manage\CRM\ContactForm;
use rent\helpers\ContactHelper;
use yii\helpers\Html;
use yii\widgets\ActiveForm;
use kartik\datecontrol\DateControl;
use yii\bootstrap\Modal;
use yii\helpers\Url;
use kartik\touchspin\TouchSpin;
use yii\helpers\ArrayHelper;
use rent\helpers\PaymentHelper;

/* @var $this yii\web\View */
/* @var $model ContactForm */

?>

<?php $form = ActiveForm::begin([
        'id' => 'form-contact-create',
        'action' => ['crm/contact/create-ajax'],
        'options' => [ 'data-pjax_reload' => '#pjax_alerts'],
    ]); ?>
<?php
Modal::begin([
    'header' => '<h4>Добавление контакт</h4>',
    'id' => '_modalCreateContact',
    'size' => 'modal-md',
    'clientOptions' => ['backdrop' => 'static'],
    'footer' => '<button type="button" class="btn btn-default pull-left" data-dismiss="modal">Закрыть</button>
                    <button type="submit" class="btn btn-success" class="">Сохранить</button>',
]);
?>
<div id='mainModalContent'>
    <div class="row">
        <div class="col-md-4">
            <?= $form->field($model, 'name')->textInput(['maxLength' => true]) ?>
        </div>
        <div class="col-md-4">
            <?= $form->field($model, 'surname')->textInput(['maxLength' => true]) ?>
        </div>
        <div class="col-md-4">
            <?= $form->field($model, 'patronymic')->textInput(['maxLength' => true]) ?>
        </div>

    </div>
    <div class="row">
        <div class="col-md-4">
            <?= $form->field($model, 'email')->textInput(['maxLength' => true]) ?>
        </div>
        <div class="col-md-4">
            <?= $form->field($model, 'telephone')->textInput(['maxLength' => true]) ?>
        </div>
        <div class="col-md-4">
            <?= $form->field($model, 'status')->dropDownList(ContactHelper::statusList()) ?>
        </div>
    </div>
    <div class="row">
        <div class="col-md-12">
            <?= $form->field($model, 'note')->textarea(['maxLength' => true]) ?>
        </div>
    </div>
</div>
<?php
Modal::end();
?>
<?php ActiveForm::end(); ?>

