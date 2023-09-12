<?php
use kartik\editable\Editable;
use yii\helpers\Url;
use yii\helpers\Html;
use rent\entities\Shop\Order\Item\OrderItem;
/**
 * Created by PhpStorm.
 * User: Алексей
 * Date: 01.03.2019
 * Time: 11:09
 */

/* @var $parent \rent\entities\Shop\Order\Item\OrderItem */
/* @var $children \rent\entities\Shop\Order\Item\OrderItem[] */
/* @var $parent_id integer */
/* @var $orderBlock_id integer */
/* @var $readonly integer */
?>
<div class="row">
    <div class="col-md-4">
        Состав:
    </div>
    <div class="col-md-8">
        <div class="btn-group pull-right" role="group" aria-label="toolbar">
<!--            <button-->
<!--                    type="button"-->
<!--                    class="btn btn-success lst_add-item --><?//=$parent->readOnly()?'hidden':'' ?><!--"-->
<!--                    data-url="--><?//=Url::toRoute(['shop/order/change-order-cart-form','order_id'=>$parent->parent->parent_id,'block_id'=>$parent->parent_id, 'collect_id'=>$parent->id])?><!--"-->
<!--                    data-iscatalog=1-->
<!--                    data-method="POST">-->
<!--                <span class="glyphicon glyphicon-plus" aria-hidden="true">-->
<!--            </button>-->
            <button class="btn btn-default <?=$order->readOnly()?'disabled':'lst_add-item'?>" type="button" data-url="<?=Url::toRoute(['shop/order/change-order-cart-form','parent_id'=>$parent->id])?>" data-iscatalog=1 data-method="POST" data-block_id="<?=$parent->id?>"><span class="glyphicon glyphicon-plus" aria-hidden="true"></span>из каталога</button>
            <button class="btn btn-default <?=$order->readOnly()?'disabled':'lst_add-item'?>" type="button" data-url="<?=Url::toRoute(['item-add-ajax','parent_id'=>$parent->id,'type_id'=>OrderItem::TYPE_CUSTOM])?>" data-method="POST" data-block_id="<?=$parent->id?>"><span class="glyphicon glyphicon-plus" aria-hidden="true"></span>произвольная</button>
        </div>
    </div>
</div>
<div class="row">
    <div class="col-sm-12">
        <?=$this->render('_expand-grid',[
            'block'=>$parent,
        ])
        ?>
    </div>
</div>
