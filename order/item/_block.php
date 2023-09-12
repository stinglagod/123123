<?php
use yii\widgets\Pjax;
use kartik\editable\Editable;
use yii\helpers\Html;
use yii\helpers\Url;
use kartik\grid\GridView;
use rent\entities\Shop\Order\Item\OrderItem;
use rent\forms\manage\Shop\Order\Item\ItemForm;
/**
 * Created by PhpStorm.
 * User: Алексей
 * Date: 20.02.2019
 * Time: 14:40
 */
/* @var $block \rent\entities\Shop\Order\Item\OrderItem */
/* @var $model \rent\forms\manage\Shop\Order\Item\BlockForm */

//echo "<pre>";
//var_dump($block);
//echo "</pre>";exit;
$htmlId='block_'.rand();
/* @var $order \rent\entities\Shop\Order\Order */
$order=$block->order;
?>
<div class="panel panel-default col-md-12 item-block" id="<?=$block->id?>">
    <div class="panel-heading row">
        <div class="col-md-1">
            <a class="btn btn-primary" data-toggle="collapse" href="#<?=$htmlId?>" role="button" aria-expanded="true" aria-controls="<?=$htmlId?>">
                <i class="glyphicon glyphicon-minus"></i>
                <i class="glyphicon glyphicon-plus"></i>
            </a>
        </div>
        <div class="col-md-5 col-sm-11 col-xs-6">
            <?=Editable::widget([
                'model' => $model,
                'attribute' => 'name',
                'asPopover' => false,
                'value' => '<h4>'.Html::encode($model->name).'</h4>',
                'header' => 'Название блока',
                'format' => Editable::FORMAT_BUTTON,

                'formOptions' => [
                    'action' => ['block-update-ajax','item_id'=>$block->id],
                    'method' => 'post',
                ],
                'options' => [
                    'readOnly'=>$order->readOnly(),
                    'class'=>'form-control',
                    'prompt'=>'Блок',
                    'id'=> 'order-block_id'.$block->id,
                ],
            ])?>
        </div>
        <div class="col-md-6">
                <div class="btn-group  pull-right" role="group" aria-label="toolbar">
                    <button class="btn btn-default <?=$order->readOnly()?'disabled':'lst_add-item'?>" type="button" data-url="<?=Url::toRoute(['shop/order/change-order-cart-form','parent_id'=>$block->id])?>" data-iscatalog=1 data-method="POST" data-block_id="<?=$block->id?>" ><span class="glyphicon glyphicon-plus" aria-hidden="true"></span>из каталога</button>
                    <button class="btn btn-default <?=$order->readOnly()?'disabled':'lst_add-item'?>" type="button" data-url="<?=Url::toRoute(['item-add-ajax','parent_id'=>$block->id,'type_id'=>OrderItem::TYPE_CUSTOM])?>" data-method="POST" data-block_id="<?=$block->id?>"><span class="glyphicon glyphicon-plus" aria-hidden="true"></span>произвольная</button>
                    <button class="btn btn-default <?=$order->readOnly()?'disabled':'lst_add-item'?>" type="button" data-url="<?=Url::toRoute(['item-add-ajax','parent_id'=>$block->id,'type_id'=>OrderItem::TYPE_COLLECT])?>" data-method="POST" data-block_id="<?=$block->id?>"><span class="glyphicon glyphicon-plus" aria-hidden="true"></span>составная</button>
<!--                    <div class="btn-group">-->
<!--                        <button type="button" data-toggle="dropdown" class="btn btn-primary dropdown-toggle">Добавить позицию<span class="caret"></span></button>-->
<!--                        <ul class="dropdown-menu">-->
<!--                            <li><a href="#" class="lst_add-item" data-url="--><?//=Url::toRoute(['item-add-ajax','block_id'=>$block->id,'type_id'=>OrderItem::TYPE_RENT])?><!--" data-iscatalog=1 data-method="POST">Выбрать из каталога</a></li>-->
<!--                            <li><a href="#" class="lst_add-item" data-url="--><?//=Url::toRoute(['item-add-ajax','parent_id'=>$block->id,'type_id'=>OrderItem::TYPE_CUSTOM])?><!--" data-method="POST">Произвольная</a></li>-->
<!--                            <li><a href="#" class="lst_add-item" data-url="--><?//=Url::toRoute(['item-add-ajax','parent_id'=>$block->id,'type_id'=>OrderItem::TYPE_COLLECT])?><!--" data-method="POST">Составная</a></li>-->
<!--                        </ul>-->
<!--                    </div>-->
                    <button class="btn btn-default <?=$order->readOnly()?'disabled':'lst_delete-block'?>" data-url="<?=Url::toRoute(['block-delete-ajax','id'=>$order->id,'block_id'=>$block->id])?>" data-method="POST"  data-block_id="<?=$block->id?>" type="button"><span class="glyphicon glyphicon-remove" aria-hidden="true"></span></button>
                    <?php if ($block->sort!=0):?>
                    <button class="btn btn-default <?=$order->readOnly()?'disabled':'move-block'?>" data-url="<?=Url::toRoute(['block-move-up-ajax','id'=>$order->id,'block_id'=>$block->id])?>" data-method="POST" type="button"><span class="glyphicon glyphicon-arrow-up" aria-hidden="true"></span></button>
                    <?php endif ?>
                    <?php if ($block->sort!=($block->order->countBlocks()-1)):?>
                    <button class="btn btn-default <?=$order->readOnly()?'disabled':'move-block'?>" data-url="<?=Url::toRoute(['block-move-down-ajax','id'=>$order->id,'block_id'=>$block->id])?>" data-method="POST" type="button"><span class="glyphicon glyphicon-arrow-down" aria-hidden="true"></span></button>
                    <?php endif ?>
                </div>
        </div>
    </div>
    <div class="collapse in" aria-expanded="true" id="<?=$htmlId?>">
        <div class="panel-body" >
            <?=$this->render('_grid',[
                'block'=>$block,
                'order'=>$order,
            ])
            ?>
        </div>
    </div>
</div>