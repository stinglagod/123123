<?php

use rent\entities\CRM\Contact;
use yii\helpers\Html;
use kartik\grid\GridView;
use yii\widgets\Pjax;
use yii\helpers\Url;
use kartik\date\DatePicker;
use rent\entities\User\User;
use yii\helpers\ArrayHelper;
use yii\widgets\ActiveForm;
use rent\entities\Shop\Order\Order;
use rent\helpers\OrderHelper;
use rent\entities\Shop\Order\Status;

/* @var $this yii\web\View */
/* @var $searchModel \backend\forms\Shop\OrderSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'Все заказы';
$this->params['breadcrumbs'][] = $this->title;
?>
<br>
<div class="order-index box box-primary">
    <div class="box-header with-border">
        <div class="row">
            <div class="col-md-12">
                <div class="btn-group pull-right" role="group" aria-label="toolbar">
                    <button type="button" class="btn btn-warning" id="orders-export-to-excel" data-url='<?=Url::toRoute(["shop/order/export"]);?>' title="Выгрузить в Excel">
                        <span class="fa fa-file-excel-o" aria-hidden="true"> Выгрузить заказы
                    </button>
                </div>
            </div>
        </div>
        <br>
        <div class="row">
            <div class="col-md-3">
                <div class="btn-group" role="group" aria-label="toolbar">
                    <?= Html::a('Новый заказ', ['create'], ['class' => 'btn btn-success']) ?>
                </div>
            </div>
<!--            --><?php //$form = ActiveForm::begin([
//                'action' => ['index'],
//                'method' => 'get',
//                'options' => [
//                    'data-pjax' => 1,
//                    'class' =>"form-inline"
//                ],
//            ]); ?>
<!--            <div class="col-md-7">-->
<!---->
<!--                <div class="form-group" style="padding-right: 20px;">-->
<!--                    --><?//= $form->field($searchModel, 'owner')->checkbox(['class'=>'filterField']) ?>
<!--                </div>-->
<!--                <div class="form-group" style="padding-right: 20px;">-->
<!--                    --><?//= $form->field($searchModel, 'hideClose')->checkbox(['class'=>'filterField']) ?>
<!--                </div>-->
<!--                <div class="form-group"style="padding-right: 20px;">-->
<!--                    --><?//= $form->field($searchModel, 'hidePaid')->checkbox(['class'=>'filterField']) ?>
<!--                </div>-->
<!--            </div>-->
<!--            <div class="col-md-2">-->
<!--                <div class="form-group pull-right">-->
<!--                    --><?//= Html::submitButton(Yii::t('app', 'Поиск'), ['class' => 'btn btn-primary']) ?>
<!--                </div>-->
<!--            </div>-->
<!--            --><?php //ActiveForm::end(); ?>

        </div>

    </div>
    <br><br>
    <div class="box-body table-responsive">
        <?php // echo $this->render('_search', ['model' => $searchModel]); ?>
        <?= GridView::widget([
            'dataProvider' => $dataProvider,
            'filterModel' => $searchModel,
            'layout' => "{items}\n{summary}\n{pager}",
            'id' => 'order-index-grid',
            'filterRowOptions' => ['class' => 'kartik-sheet-style'],
//            'pjax' => true,
            'columns' => [
//                ['class' => 'yii\grid\SerialColumn'],
                [
                    'attribute' => 'id',
                    'width' => '5%',
                    'hAlign' => 'center',
                    'vAlign' => 'left',
                ],
                [
                    'attribute' => 'date_begin',
                    'format' => ['date', 'php:D, d F Y'],
                    'hAlign' => 'center',
                    'vAlign' => 'middle',
                    'width' => '25%',
                    'headerOptions' => ['class' => 'kv-sticky-column'],
                    'filter' => DatePicker::widget([
                        'model' => $searchModel,
                        'attribute' => 'date_from',
                        'attribute2' => 'date_to',
                        'type' => DatePicker::TYPE_RANGE,
                        'separator' => '-',
                        'pluginOptions' => [
                            'todayHighlight' => true,
                            'autoclose'=>true,
                            'format' => 'yyyy-mm-dd',
                            ],
                    ]),
                    'contentOptions' => function ( Order $model) {
                        $date=strtotime(date("Y-m-d 00:00:00"));
                        $currentNumWeek=(int)date("W",$date);
                        $numWeek=(int)date("W",$model->date_begin);

                        if ($model->date_begin >= $date) {
                            if ($numWeek == $currentNumWeek) {
                                return ['style' => 'background-color:#ea9999'];
                            } else if ($numWeek == ($currentNumWeek+1)) {
                                return ['style' => 'background-color:#ffe599'];
                            } else if ($numWeek == ($currentNumWeek+2)) {
                                return ['style' => 'background-color:#b6d7a8'];
                            }
                        }else {
                            return ['style' => 'background-color:#b7b7b7'];
                        }
                    },
                ],
                [
                    'attribute' => 'name',
                    'vAlign' => 'middle',
                    'value' => function (Order $data) {
                        return Html::a(Html::encode($data->name).'<br><small>'.$data->customerData->name.'</small>', Url::to(['update', 'id' => $data->id]),['data-pjax'=>0,'target'=>"_blank"]);
                    },
                    'format' => 'raw',
                ],
                [
                    'attribute' => 'contact_id',
                    'hAlign' => 'left',
                    'vAlign' => 'middle',
                    'width' => '15%',
                    'value' => function (Order $data) {
                        return $data->contact?$data->contact->getNameWithPhoneEmail():'нет';

                    },
                    'filterType' => GridView::FILTER_SELECT2,
                    'filter' => Contact::getContactList(),
                    'filterWidgetOptions' => [
                        'hideSearch' => true,
                        'pluginOptions' => ['allowClear' => true],
                    ],
                    'filterInputOptions' => ['placeholder' => 'Заказчик', 'multiple' => false],
                    'format' => 'raw',
                ],
                [
                    'attribute' => 'responsible_id',
                    'hAlign' => 'left',
                    'vAlign' => 'middle',
                    'width' => '15%',
                    'value' => function (Order $data) {
                        if ($data->responsible_id) {
//                            return $data->responsible->getShortName();
                            $url='';
                            if ($user=$data->responsible) {
                                $url=$user->getAvatarUrl();
                            }

                            return '<img src="'.$url.'" class="img-circle" style="width: 30px;" alt="User Image">'.'&nbsp'.$data->responsible->getShortName(); /*archi*/
                        } else {
                            return $data->responsible_name;
                        }

                    },
                    'filterType' => GridView::FILTER_SELECT2,
                    'filter' => User::getUserArray(),
                    'filterWidgetOptions' => [
                        'hideSearch' => true,
                        'pluginOptions' => ['allowClear' => true],
                    ],
                    'filterInputOptions' => ['placeholder' => 'Менеджер', 'multiple' => false],
                    'format' => 'raw',
                ],
                [
                    'attribute' => 'current_status',
                    'width' => '5%',
                    'value' => function (Order $model) {
                        return OrderHelper::statusName($model->current_status);
                    },
                    'filter' => $searchModel::statusList(),
                    'filterType' => GridView::FILTER_SELECT2,
                    'format' => 'raw',
                ],
                [
                    'attribute' => 'paidStatus',
                    'hAlign' => 'center',
                    'vAlign' => 'middle',
                    'value' => function (Order $model) {
                        return OrderHelper::paidStatusName($model->paidStatus);
                    },
                    'contentOptions' => function (Order $model, $key, $index, $column) {
                        switch ($model->paidStatus) {
                            case Status::PAID_NO:
                                return ['style' => 'background-color:#ea9999'];
                            case Status::PAID_FULL:
                                return ['style' => 'background-color:#b6d7a8'];
                            case Status::PAID_PART:
                                return ['style' => 'background-color:#ffe599'];
                            case Status::PAID_OVER:
                                return ['style' => 'background-color:#ea9999'];

                        }
                    },
                    'filterType' => GridView::FILTER_SELECT2,
                    'filter' => $searchModel::paidStatusList(),
                    'filterWidgetOptions' => [
                        'pluginOptions' => ['allowClear' => true],
                    ],
                    'filterInputOptions' => ['placeholder' => 'Cтатус', 'multiple' => false],
                    'format' => 'raw',
                ],
                'note',

                ['class' => 'yii\grid\ActionColumn'],
            ],

        ]); ?>
    </div>
</div>
<?php
$_csrf=Yii::$app->request->getCsrfToken();
$js = <<<JS
    //Выгрузка отображенных заказов
    $("body").on("click", '#orders-export-to-excel', function() {
        // alert('Выгружаем заказ');
        let url=this.dataset.url+'?'+window.location.search.replace( '?', '');
        $.post({
           url: url,
           type: "POST",
           data: {
                 _csrf : "$_csrf"
           },
           success: function(response) {
               if (response.status === 'success') {
                   document.location.href=response.data;
               }
           },
        });
    })
     
JS;
$this->registerJs($js);

?>
