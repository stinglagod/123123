<?php
use yii\widgets\Pjax;
use kartik\grid\GridView;
use yii\helpers\Url;
/**
 * Created by PhpStorm.
 * User: Алексей
 * Date: 24.12.2018
 * Time: 23:32
 */
?>
<div class="row">
   <div class="col-md-12">
       <div class="btn-group pull-right" role="group" aria-label="toolbar">
           <button type="button" class="btn btn-danger lst_clear-movement" title="Очистить движения" data-url="<?=Url::toRoute(['order/clear-movement','id'=>$model->id,'deactive'=>0])?>">Очистить движения</button>
           <button type="button" class="btn btn-warning lst_clear-movement" title="Деактивировать движения" data-url="<?=Url::toRoute(['order/clear-movement','id'=>$model->id,'deactive'=>1])?>">Деактивировать движения</button>
       </div>
    </div>
</div>
<br>

<?= GridView::widget([
    'dataProvider' => $dataProviderMovement,
    'id' => 'order-movement-grid',
    'pjax' => true,
    'columns' => [
        ['class' => 'yii\grid\SerialColumn'],

        [
            'attribute' => 'dateTime',
            'group' => true,  // enable grouping
            'value' => function (\common\models\Movement $data) {
                return $data->dateTime."<br><small>".$data->autor->getShortName()."</small>";
            },
            'format' => 'raw',
        ],
        [
            'attribute' => 'product_id',
            'value' => function (\common\models\Movement $data) {
                if ($data->product_id) {
                    return $data->product->name;
                }else {
                    return $data->orderProducts[0]->name;
                }

            },
            'format' => 'raw',
        ],
        [
            'attribute' => 'action_id',
            'value' => function (\common\models\Movement $data) {
                return $data->action->name;
            },
            'format' => 'raw',
        ],
        'qty',
        'active'
    ],
]); ?>

<?php
$_csrf=Yii::$app->request->getCsrfToken();
$js = <<<JS
    $("body").on("click", '.lst_clear-movement', function() {
        // alert('Добавляем новый блок')
        var url=this.dataset.url
        $.ajax({
            url: url,
            type: "POST",
            data: {
                 _csrf : "$_csrf"
             },
            success: function (data) {
                // $.pjax.reload({container: "#order-movement-grid-pjax", async: false});
                // $.pjax.reload({container: "#pjax_alerts", async: false});
                reloadPjaxs('#order-movement-grid-pjax','#pjax_alerts');
            }
        });
        return false;
    });
JS;
$this->registerJs($js);

?>