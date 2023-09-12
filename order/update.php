<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use kartik\datecontrol\DateControl;
use kartik\grid\GridView;
use kartik\tabs\TabsX;
use yii\widgets\Pjax;
use yii\helpers\Url;
use kartik\select2\Select2;
use common\models\Action;
use kartik\dialog\Dialog;
use yii\web\JsExpression;
use yii\bootstrap\Modal;
use rent\helpers\OrderHelper;

/* @var $this yii\web\View */
/* @var $order rent\entities\Shop\Order\Order */
/* @var $model rent\forms\manage\Shop\Order\OrderEditForm */
/* @var $form yii\widgets\ActiveForm */
/* @var $blocks \common\models\Block[] */
/* @var $payments_provider \yii\data\ActiveDataProvider */
/* @var $payments_form \rent\forms\manage\Shop\Order\PaymentForm */
/* @var $modalCreateForm string */

$this->title = OrderHelper::orderName($order);

$this->params['breadcrumbs'][] = ['label' => 'Все заказы', 'url' => ['index']];
$this->params['breadcrumbs'][] = $order->id;


$items = [
    [
        'label'=>'<i class="glyphicon glyphicon-home"></i> Общее',
        'content'=>$this->render('_tabMain', [
            'model'=>$model,
            'order'=>$order,
            'itemBlocks_provider'=>$itemBlocks_provider,
            'service_provider'=>$service_provider,
            'modalCreateForm'=>$modalCreateForm
        ]),
        'active'=>true
    ],
    [
        'label'=>'<i class="glyphicon glyphicon-list-alt"></i> Оплата',
        'content'=>$this->render('_tabPayment', [
            'order'=>$order,
            'dataProvider' => $payments_provider,
            'payments_form' => $payments_form
        ]),
    ],
    [
        'label'=>'<i class="glyphicon glyphicon-list-alt"></i> Движения товаров',
        'content'=>$this->render('movement/_tabMovement', [
            'order'=>$order,
            'dataProvider' => $movements_provider,
        ]),
    ],
//    [
//        'label'=>'<i class="glyphicon glyphicon-list-alt"></i> Движения товаров',
//        'content'=>$this->render('_tabWarehouse', [
//            'model'=>$model,
//            'dataProviderMovement'=>$dataProviderMovement,
////            'form'=>$form,
//        ]),
////            'linkOptions'=>[
//////                                'data-url'=>Url::to(['/file/index','hash'=>new JsExpression("function (){return 'hi'}")])
////                'data-url'=>Url::to(['/file/index'])
////            ],
//    ],
//    [
//        'label'=>'<i class="glyphicon glyphicon-user"></i> Профиль клиента',
//        'linkOptions'=>[
////                                'data-url'=>Url::to(['/file/index','hash'=>new JsExpression("function (){return 'hi'}")])
////            'data-url'=>Url::to(['/user/profile','id'=>$model->client_id])
//        ],
//    ],


];
?>

    <div class="user-index box box-primary">
    <?=TabsX::widget([
        'items'=>$items,
        'position'=>TabsX::POS_ABOVE,
        'encodeLabels'=>false,
        'enableStickyTabs'=>true,
        'id'=>'order'
    ]);
    ?>
    </div>


<?php
    Modal::begin([
        'header' => '<h4 id="modalTitle"><h4></h4>',
        'id' => 'order-confirm-modal',
        'size' => 'modal-md',
        'clientOptions' => ['backdrop' => 'static'],
        'footer' => 'Кнопка',
    ]);
?>
<?php
    Pjax::begin(['id' => 'pjax_order-content-confirm-modal']);
    Pjax::end();
?>
<?php
    Modal::end();
?>
<?php

$js = <<<JS


JS;
$this->registerJs($js);
?>