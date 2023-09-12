<?php

use rent\entities\CRM\Contact;
use yii\data\ActiveDataProvider;
use yii\widgets\ActiveForm;
use kartik\datecontrol\DateControl;
use yii\helpers\Html;
use kartik\select2\Select2;
use yii\helpers\Url;
use yii\widgets\Pjax;
use yii\helpers\ArrayHelper;
use rent\helpers\OrderHelper;
use rent\entities\User\User;
use rent\entities\Shop\Order\Status;
use rent\entities\Shop\Order\Order;
use rent\forms\manage\Shop\Order\Item\BlockForm;
use rent\entities\Shop\Order\Item\ItemBlock;
use rent\entities\Shop\Service;
/**
 * Created by PhpStorm.
 * User: Алексей
 * Date: 24.12.2018
 * Time: 23:32
 */
/* @var $model rent\forms\manage\Shop\Order\OrderEditForm */
/* @var $order rent\entities\Shop\Order\Order */
/* @var $itemBlocks_provider \yii\data\ActiveDataProvider */
/* @var $service_provider \yii\data\ActiveDataProvider */
/* @var $modalCreateForm string */
?>

<div class="tab-main" id="tab-main">

    <?php $form = ActiveForm::begin(); ?>
    <?= $form->errorSummary($model); ?>
    <div class="row">
        <div class="col-md-6">
            <?= $form->field($model, 'name')->textInput(['maxlength' => true,'disabled' => $order->readOnly('name')]) ?>
        </div>
        <div class="col-md-3">
            <?=
            $form->field($model, 'date_begin')->widget(DateControl::class, [
                'type'=>DateControl::FORMAT_DATE,
                'disabled'=>$order->readOnly('date_begin'),
                'widgetOptions' => [
                    'pluginOptions' => [
                        'autoclose' => true
                    ]
                ],
//                'displayTimezone'=> date_default_timezone_get(),
//                'saveTimezone'=> date_default_timezone_get(),
            ])
            ?>
        </div>
        <div class="col-md-3">
            <?=
            $form->field($model, 'date_end')->widget(DateControl::class, [
                'type'=>DateControl::FORMAT_DATE,
                'disabled'=>$order->readOnly('date_end'),
                'widgetOptions' => [
                    'pluginOptions' => [
                        'autoclose' => true,
                    ]
                ]
            ])
            ?>
        </div>
        <div class="col-md-4">
            <?=$form->field($model, 'contact_id')->widget(Select2::class, [
                'data' => ['-1'=>'<Добавить новый контакт>']+Contact::getContactList(),
                'disabled'=>$order->readOnly('contact_id'),
                'options' => [
                    'placeholder' => 'Выберите ...',
                    'id'=>'orderselect_contact_id',
                ],
                'pluginOptions' => [
                    'allowClear' => true
                ],
                'pluginEvents' => [
                    "select2:select" =>"changeSelectContact",
                ],
            ]);
            ?>
            <a href="<?=Url::to(['crm/contact/update','id'=>$model->contact_id])?>"
               id="a_contact_edit"
               data-url="<?=Url::to(['crm/contact/update'])?>"
               target="_blank"
            >
                Редактировать контакт
            </a>
        </div>
        <div class="col-md-12">
            <?= $form->field($model->delivery, 'address')->textInput(['maxlength' => true,'disabled' => $order->readOnly('delivery.address')]) ?>
        </div>
        <div class="col-md-12">
            <?= $form->field($model, 'note')->textInput(['maxlength' => true,'disabled' => $order->readOnly('note')]) ?>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <?= $form->field($order, 'current_status')->dropDownList(OrderHelper::statusList(), ['prompt' => 'Выберите','disabled' => true]) ?>
        </div>
        <div class="col-md-6">
            <?= $form->field($model, 'responsible_id')->dropDownList(User::getResponsibleList(), ['prompt' => 'Выберите','disabled' => $order->readOnly('responsible_id')]) ?>
        </div>
    </div>
    <div class="row">
        <div class="col-md-6">
<!--            --><?//=$model->status->name?>
        </div>
    </div>
    <div class="row">
        <div class="col-md-12">
            <?php Pjax::begin(['id'=>'sum-order-pjax']); ?>
            Сумма заказа: <?=$order->getTotalCost()?>
            <br>
            Оплачено: <?=$order->paid?>
            <br>
            Остаток: <?=($order->getTotalCost() - $order->paid)?>
            <br>
            <br>
            <?php Pjax::end(); ?>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="btn-group pull-left" role="group" aria-label="toolbar">
                <button type="button" class="btn btn-warning <?=$order->canMakeNew()?'order-change-status':'disabled'?>" data-url="<?=Url::toRoute(['change-status-ajax','id'=>$order->id,'status_id'=>Status::NEW])?>" data-method="POST" title="Статус новый">Редактировать смету</button>
<!--                <button type="button" class="btn btn-warning order-change-status" data-url="--><?//=Url::toRoute(['change-status-ajax','id'=>$order->id,'status_id'=>0])?><!--" data-method="POST" title="Статус новый">Редактировать смету(Принудительно)</button>-->
                <button type="button" class="btn btn-primary <?=$order->canBeEstimated()?'order-change-status':'disabled'?>" data-url="<?=Url::toRoute(['change-status-ajax','id'=>$order->id,'status_id'=>Status::ESTIMATE])?>" data-method="POST" title="Забронировать">Сохранить смету</button>
                <button type="button" class="btn btn-success <?=$order->canBeCompleted()?'order-change-status':'disabled'?>" data-url="<?=Url::toRoute(['change-status-ajax','id'=>$order->id,'status_id'=>Status::COMPLETED])?>" data-method="POST"  title="Завершить заказ">Завершить заказ</button>
                <button type="button" class="btn btn-danger  <?=$order->canBeCancel()?'order-change-status':'disabled'?>" data-url="<?=Url::toRoute(['change-status-ajax','id'=>$order->id,'status_id'=>Status::CANCELLED])?>" data-method="POST"  title="Отменить заказ">Отменить заказ</button>
            </div>
        </div>
        <div class="col-md-6">
            <div class="btn-group pull-right" role="group" aria-label="toolbar">
                <button type="button" class="btn btn-warning" id="order-export-to-excel" data-url="<?=Url::toRoute(['export','id'=>$order->id])?>" data-method="POST" title="Выгрузить в Excel"><span class="fa fa-file-excel-o" aria-hidden="true"></button>
                <div class="btn-group">
                    <button type="button" data-toggle="dropdown" class="btn btn-primary dropdown-toggle <?=$order->readOnly()?'disabled':''?>">Добавить блок<span class="caret"></span></button>
                    <ul class="dropdown-menu  <?=$order->readOnly()?'disabled':''?>">
                        <li><a href="#" class="lst_add-block" data-url="<?=Url::toRoute(['block-add-ajax','id'=>$order->id,'name'=>'<НОВЫЙ БЛОК>'])?>" data-method="POST">НОВЫЙ БЛОК</a></li>
                        <li class="divider"></li>
                        <?php /** @var ItemBlock $block */
                        foreach ($itemBlocks_provider->getModels() as $block):?>
                            <li><a href="#" class="lst_add-block" data-url="<?=Url::toRoute(['block-add-ajax','id'=>$order->id,'name'=>Html::encode($block->name)])?>" data-method="POST"><?=Html::encode($block->name)?></a></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <div class="btn-group ">
                    <button type="button" data-toggle="dropdown" class="btn btn-primary dropdown-toggle <?=$order->readOnly()?'disabled':''?>">Добавить услуги<span class="caret"></span></button>
                    <ul class="dropdown-menu">
                        <?php /** @var Service $service */
                        foreach ($service_provider->getModels() as $service):?>
                            <li><a href="#" class="lst_add-service" data-url="<?=Url::toRoute(['service-add-ajax','id'=>$order->id,'service_id'=>Html::encode($service->id)])?>" data-method="POST"><?=Html::encode($service->name)?></a></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <div class="btn-group">
                    <button type="button" data-toggle="dropdown" class="btn btn-default dropdown-toggle">Операция <span class="caret"></span></button>
                    <ul class="dropdown-menu">
                        <li><a href="#" class='lst_operation' data-url="<?=Url::toRoute(['operation-modal-ajax','id'=>$order->id, 'operation_id'=>Order::OPERATION_ISSUE])?>" data-method="POST" data-all="1">Выдать ВСЁ</a></li>
                        <li><a href="#" class='lst_operation' data-url="<?=Url::toRoute(['operation-modal-ajax','id'=>$order->id, 'operation_id'=>Order::OPERATION_ISSUE])?>" data-method="POST" data-all="0">Выдать отмеченные</a></li>
                        <li><a href="#" class='lst_operation' data-url="<?=Url::toRoute(['operation-modal-ajax','id'=>$order->id, 'operation_id'=>Order::OPERATION_RETURN])?>" data-method="POST" data-all="1">Получить ВСЁ</a></li>
                        <li><a href="#" class='lst_operation' data-url="<?=Url::toRoute(['operation-modal-ajax','id'=>$order->id, 'operation_id'=>Order::OPERATION_RETURN])?>" data-method="POST" data-all="0">Получить отмеченные</a></li>
<!--                        <li><a href="#" class='lst_operation' data-operation_id="--><?//=Action::TOREPAIR?><!--">Отправить в ремонт</a></li>-->
<!--                        <li><a href="#" class='lst_operation' data-operation_id="--><?//=Action::TOREPAIR?><!--">Получить из ремонта</a></li>-->
                        <li><a href="#" class='lst_delete' data-url="<?=Url::toRoute(['items-delete-ajax','id'=>$order->id])?>" data-confirm ="1" data-method="POST" data-all="0">Удалить отмеченные</a></li>
<!--                        <li><a href="#" class='lst_operation_delete' data-confirm="Вы уверены, что хотиле удалить позици из заказа?" data-url="--><?//=Url::toRoute(['operation-modal-ajax','id'=>$order->id, 'operation_id'=>Order::OPERATION_DELETE])?><!--" data-method="POST" data-all="0">Удалить отмеченные</a></li>-->
<!--                        <li><a href="#" class='lst_operation' data-operation_id="0">Удалить отмеченные</a></li>-->
                    </ul>
                </div>

                <button type="submit" class="btn btn-success">Сохранить</button>
            </div>
        </div>
    </div>
    <?php ActiveForm::end(); ?>
    <br>
    <div class="row">
        <div class="col-md-12" id="orderBlank">
            <?php
            foreach ($order->blocks as $block) {
                echo $this->render('item/_block', [
                    'block'=>$block,
                    'model'=>new BlockForm($block)
                ]);
            }
            ?>
        </div>
    </div>
    <div class="row">
        <div class="col-md-12" id="service">
            <?php
                echo $this->render('item/_service-grid',[
                    'order'=>$order,
                ]);
            ?>
        </div>
    </div>

</div>

<?php
$js = <<<JS
//###Block
    //Добавление нового блока
    $("body").on("click", '.lst_add-block', function() {
        $.ajax({
            url: this.dataset.url,
            type: this.dataset.method,
            success: function (data) {
                $("#orderBlank").append(data.html);
                reloadPjaxs('#pjax_alerts')  
            }
        });
        return false;
    });
    // удаление блока
    $("body").on("click", '.lst_delete-block', function() {
        let block_id=this.dataset.block_id;
        $.ajax({
            url: this.dataset.url,
            type: this.dataset.method,
            success: function (data) {
                if (data.status=='success') {
                    $("#"+block_id).remove();
                } 
                reloadPjaxs('#pjax_alerts')  
            }
        });
        return false;
    });
    //перемещение блоков
    $("body").on("click", '.move-block', function() {
        $.ajax({
            url: this.dataset.url,
            type: this.dataset.method,
            success: function (data) {
               document.location.reload(); 
            }
        });
        return false;
    });
//###Item
    //вызов добавление товара из заказа
    $("body").on("click", '.lst_add-item', function() {
        let is_catalog=this.dataset.iscatalog;
        let block_id=this.dataset.block_id;
        $.ajax({
                url: this.dataset.url,
                type: this.dataset.method,
                success: function (data) {
                    if (is_catalog==1) {
                        window.open("/admin/shop/order/catalog", "Каталог", "width=1024,height=600");    
                    } else {
                        reloadPjaxs("#grid_"+block_id+"-pjax", '#pjax_alerts')
                    }
                }
        });
        return false;
    });
    
        //при изменения checkbox is_montage. не нашел как реализовать через картик. Поэтому изобретаю велосипед
    $("body").on("change", '.chk_is_montage', function(e) {
        let checkbox=0;
        if(this.checked) {
            checkbox=1;
        }
        let elcheckbox=this;
        $.ajax({
            url: this.dataset.url,
            type: this.dataset.method,
            async: true,
            data: {
                 // 'csrfParam' : csrfToken,
                 'hasEditable' : 1,
                 'editableKey' : this.dataset.key,
                 'editableAttribute' : 'is_montage',
                 'is_montage' : checkbox,
                 'OrderItem' : {
                     0:{
                         'is_montage': checkbox
                     }
                 }
             },
            success: function (data) {
                // var data = JSON.parse(data);
                // if (data.output) {
                //     elcheckbox.checked = !elcheckbox.checked;
                //     $(elcheckbox).prop('checked', !elcheckbox.checked);
                    reloadPjaxs("#service_grid-pjax");
                    // $.pjax.reload({container: "#pjax_alerts", async: false});
                // } else {
                    // reloadPjaxs('#pjax_orderservice_grid-pjax','#sum-order-pjax');
                // }
            },
            error: function(data) {
                elcheckbox.checked = !elcheckbox.checked;
                // $.pjax.reload({container: "#pjax_alerts", async: false});
            }
        });
        return false;
    });
//###Service    
    //Добавление новой услуги
    $("body").on("click", '.lst_add-service', function() {
        $.ajax({
            url: this.dataset.url,
            type: this.dataset.method,
            success: function (data) {
               $.pjax.reload({container: "#service_grid-pjax"});
            }
        });
        return false;
    });
//###Operation
    $("body").on("click", '.lst_operation', function(e) {
        let length=0;
        let allKeys=[];
        
        if (this.dataset.all==0) {
            $('.grid-order-items').each(function(i,elem) {
                let keys=$(this).yiiGridView('getSelectedRows');
                if (keys.length) {
                    length+=keys.length
                    allKeys=allKeys.concat(keys)    
                }
            });
    
           if (length==0) {
               alert('Не выделено ни одного элемента');
               return false;
           }    
        } else {
            allKeys=null;
        }
        
        // console.log(allKeys);
        $.ajax({
            url: this.dataset.url,
            type: this.dataset.method,
            dataType: 'json',
            data: {
                keylist: allKeys,
            },
           success: function(response) {
               // console.log(response);
               if (response.status === 'success') {
                    $("#tab-main").append(response.data)
                    $('#modal-operation-confirm').removeClass('fade');
                    $('#modal-operation-confirm').modal('show'); 
               }
           },
        });
        return false;
    });
    function sendAjax(el,allKeys) {
        $.ajax({
           url: el.dataset.url,
           type: el.dataset.method,
           dataType: 'json',
           data: {
               keylist: allKeys,
           },
          success: function(response) {
              // console.log(response);
              if (response.status === 'success') {
                   // alert(response)
                   document.location.reload();
                   return;
              }
          },
        });
        reloadPjaxs('#pjax_alerts');
    };
    $("body").on("click",'.lst_delete',function (e){
        let length=0;
        let allKeys=[];
        if (this.dataset.all==0) {
            $('.grid-order-items').each(function(i,elem) {
                let keys=$(this).yiiGridView('getSelectedRows');
                if (keys.length) {
                    length+=keys.length
                    allKeys=allKeys.concat(keys)    
                }
            });
    
           if (length==0) {
               alert('Не выделено ни одного элемента');
               return false;
           }    
        } else {
            allKeys=null;
        }
        let el=this;
        yii.confirm('Вы уверены, что хотите удалить '+ allKeys.length+' позиций заказа ?', function(){sendAjax(el,allKeys)});

        return false;
    })
//###Status
    // Изменение статуса
    $("body").on("click", '.order-change-status', function(e) {
         $.ajax({
            url: this.dataset.url,
            type: this.dataset.method,
            success: function() {
                document.location.reload();
                // reloadPjaxs('#sum-order-pjax','#pjax_alerts');
            }
         })
         return false;
    });
//###Export
        $("body").on("click", '#order-export-to-excel', function() {
        // alert('Выгружаем заказ');
        $.ajax({
            url: this.dataset.url,
            type: this.dataset.method,
           success: function(response) {
               if (response.status === 'success') {
                   document.location.href=response.data;
               }
           },
        });
        return false;
    })
JS;
$this->registerJs($js);

$this->registerJsFile('/admin/js/shop/order.js', ['depends' => 'yii\web\YiiAsset']);
if ($modalCreateForm) :?>
    <?= $modalCreateForm ?>
<? endif; ?>

