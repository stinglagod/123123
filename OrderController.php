<?php

namespace backend\controllers\shop;

use kartik\grid\EditableColumnAction;
use rent\cart\CartItem;
use rent\entities\Shop\Service;
use rent\entities\Shop\Product\Product;
use rent\entities\Shop\Order\Item\OrderItem;
use rent\entities\Shop\Order\Order;
use rent\entities\User\User;
use rent\forms\manage\CRM\ContactForm;
use rent\forms\manage\Shop\Order\Item\BlockForm;
use rent\forms\manage\Shop\Order\Item\ItemForm;
use rent\forms\manage\Shop\Order\OrderCreateForm;
use rent\forms\manage\Shop\Order\OrderEditForm;
use rent\forms\manage\Shop\Order\PaymentForm;
use rent\readModels\Shop\OrderReadRepository;
use rent\useCases\manage\Shop\OrderManageService;
use Yii;
use backend\forms\Shop\OrderSearch;
use yii\data\ArrayDataProvider;
use yii\filters\AccessControl;
use yii\helpers\ArrayHelper;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\data\ActiveDataProvider;
use PhpOffice\PhpSpreadsheet;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * OrderController implements the CRUD actions for Order model.
 */
class OrderController extends Controller
{

    private $service;
    private $orders;

    public function __construct(
        $id,
        $module,
        OrderManageService $service,
        OrderReadRepository $orders,
        $config = [])
    {
        parent::__construct($id, $module, $config);
        $this->service = $service;
        $this->orders = $orders;
    }
    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'delete' => ['POST'],
                ],
            ],
//            'access' => [
//                'class' => AccessControl::class,
//                'only' => ['index'],
//                'rules' => [
//                    [
//                        'allow' => true,
//                        'matchCallback' => function ($rule, $action) {
//                            return empty(Yii::$app->settings->getClientId());
//                        }
//                    ],
//                ],
//            ],
        ];
    }

    /**
     * Lists all Order models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new OrderSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays a single Order model.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionView($id)
    {
        return $this->render('view', [
            'model' => $this->findModel($id),
        ]);
    }

    /**
     * Creates a new Order model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $form = new OrderCreateForm();

        if ($form->load(Yii::$app->request->post()) && $form->validate()) {
            try {
                $order = $this->service->create($form);
                Yii::$app->session->setFlash('success', 'Заказ успешно создан');
                return $this->redirect(['update', 'id' => $order->id]);
            } catch (\DomainException $e) {
                Yii::$app->errorHandler->logException($e);
                Yii::$app->session->setFlash('error', $e->getMessage());
            }

        }

        $modalCreateForm= $this->renderPartial('_modalCreateContact',[
            'model' => new ContactForm(),
        ]);

        return $this->render('create', [
            'model' => $form,
            'modalCreateForm'=>$modalCreateForm
        ]);
    }

    /**
     * Updates an existing Order model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate($id)
    {
        $order = $this->findModel($id);

        $form = new OrderEditForm($order);

        $payments_provider=$this->orders->getAllPayments($order);
        $payments_form = new PaymentForm($order);

        $movements_provider=$this->orders->getAllMovements($order);

        $itemBlocks_provider=$this->orders->getAllItemBlocks($order);

        $service_provider=$this->orders->getAllServices($order);

        $modalCreateForm= $this->renderPartial('_modalCreateContact',[
            'model' => new ContactForm(),
        ]);

        if ($form->load(Yii::$app->request->post()) && $form->validate()) {
            try {
                $this->service->edit($order->id, $form);
                Yii::$app->session->setFlash('success', 'Заказ обновлен');
//                $order = $this->findModel($id);
                return $this->redirect(['update', 'id' => $order->id]);
            } catch (\DomainException $e) {
                Yii::$app->errorHandler->logException($e);
                Yii::$app->session->setFlash('error', $e->getMessage());
            }
        }


        return $this->render('update', [
            'model' => $form,
            'order' => $order,
            'payments_provider' => $payments_provider,
            'payments_form' => $payments_form,
            'itemBlocks_provider'=>$itemBlocks_provider,
            'service_provider'=>$service_provider,
            'movements_provider' => $movements_provider,
            'modalCreateForm'=>$modalCreateForm
        ]);
    }
    /**
     * Deletes an existing Order model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionDelete($id)
    {
        $this->findModel($id)->delete();

        return $this->redirect(['index']);
    }
###Payment
    public function actionPaymentAddAjax($id)
    {
        $order = $this->findModel($id);
        $form = new PaymentForm($order);

        if ($form->load(Yii::$app->request->post()) && $form->validate()) {
            try {
                $this->service->addPayment($order->id, $form);
                return $this->asJson(['success' => true]);
            } catch (\DomainException $e) {
                Yii::$app->errorHandler->logException($e);
                Yii::$app->session->setFlash('error', $e->getMessage());
                return ['status' => 'success', 'data' => ''];
                $result = [];
                // The code below comes from ActiveForm::validate(). We do not need to validate the model
                // again, as it was already validated by save(). Just collect the messages.
                foreach ($form->getErrors() as $attribute => $errors) {
                    $result[yii\helpers\Html::getInputId($form, $attribute)] = $errors;
                }
                return $this->asJson(['validation' => $result]);
            }
        }
    }

    public function actionPaymentDelete($id,$payment_id)
    {
        try {
            $this->service->removePayment($id, $payment_id);
        } catch (\DomainException $e) {
            Yii::$app->session->setFlash('error', $e->getMessage());
        }
        return $this->redirect(['update', 'id' => $id,'#' => 'order-tab1']);
    }
###Block
    public function actionBlockAddAjax(int $id, string $name = null)
    {
        $order = $this->findModel($id);
        try {
            $block=$this->service->addBlock($order->id,$name);
            Yii::$app->session->setFlash('success', 'Добавлен новый блок в заказ');
            $formBlock=new BlockForm($block);
            $data = $this->renderAjax('item/_block', [
                'block' => $block,
                'model'=>$formBlock
            ]);
            return $this->asJson(['status' => 'success', 'html' => $data]);
//            return $this->asJson(['status' => 'success', 'html' => '']);
        }catch (\DomainException $e) {
            Yii::$app->session->setFlash('error', $e->getMessage());
            return $this->asJson(['status' => 'error', 'html' => '']);
        }
    }

    public function actionBlockUpdateAjax(int $item_id)
    {
//        $order = $this->findModel($id);
        $item = $this->findOrderItemModel($item_id);
        $form=new BlockForm();
        $output='';
        if ($form->load(Yii::$app->request->post()) && $form->validate()) {
            try {
                $this->service->editBlock($item->order_id, $item->id,$form);
//                Yii::$app->session->setFlash('success', 'Блок обновлен');
                return $this->asJson(['output' => $output, 'message' => '']);
            } catch (\DomainException $e) {
                Yii::$app->errorHandler->logException($e);
                Yii::$app->session->setFlash('error', $e->getMessage());
                return $this->asJson(['out' => $e->getMessage(), 'status' => 'error']);
            }
        }
        return $this->asJson(['out' => 'Ошибка валидации', 'status' => 'error']);
    }
    public function actionBlockDeleteAjax($id,$block_id)
    {
        try {
            $this->service->removeBlock($id, $block_id);
            Yii::$app->session->setFlash('success', 'Блок удален');
            return $this->asJson(['status' => 'success', 'data' => '']);
        } catch (\DomainException $e) {
            Yii::$app->session->setFlash('error', $e->getMessage());
            return $this->asJson(['status' => 'error', 'data' => $e->getMessage()]);
        }
    }
    public function actionBlockMoveUpAjax($id,$block_id)
    {
        try {
            $this->service->moveBlockUp($id, $block_id);
            return $this->asJson(['status' => 'success', 'data' => '']);
        } catch (\DomainException $e) {
            Yii::$app->session->setFlash('error', $e->getMessage());
            return $this->asJson(['status' => 'error', 'data' => $e->getMessage()]);
        }
    }
    public function actionBlockMoveDownAjax($id,$block_id)
    {
        try {
            $this->service->moveBlockDown($id, $block_id);
            return $this->asJson(['status' => 'success', 'data' => '']);
        } catch (\DomainException $e) {
            Yii::$app->session->setFlash('error', $e->getMessage());
            return $this->asJson(['status' => 'error', 'data' => $e->getMessage()]);
        }
    }

    public function actionListBlocks()
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        if (isset($_POST['depdrop_parents'])) {
            $parents = $_POST['depdrop_parents'];
            if ($parents != null) {
                $order_id = $parents[0];
                $order=$this->findModel($order_id);
                $out = $this->orders->getBlockFromOrderArray($order);
                return ['output'=>$out, 'selected'=>''];
            }
        }
        return ['output'=>'', 'selected'=>''];
    }
###Service
    public function actionServiceAddAjax(int $id, int $service_id)
    {
        $order = $this->findModel($id);
        $service=$this->findService($service_id);
        try {
            $this->service->addService($order->id,$service);
            return $this->asJson(['status' => 'success', 'html' => '']);
        }catch (\DomainException $e) {
            Yii::$app->session->setFlash('error', $e->getMessage());
            return $this->asJson(['status' => 'error', 'html' => '']);
        }
    }
    public function actionServiceDeleteAjax($id,$item_id)
    {
        try {
            $item=$this->findOrderItemModel($item_id);
            $this->service->removeItem($id, $item->id);
            return $this->asJson($this->render('item/_service-grid', [
                'order'=>$item->order,
            ]));
        } catch (\DomainException $e) {
            Yii::$app->session->setFlash('error', $e->getMessage());
            return $this->asJson(['status' => 'error', 'data' => $e->getMessage()]);
        }
    }
###Collect
    public function actionListCollects()
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        if (isset($_POST['depdrop_parents'])) {
            $ids = $_POST['depdrop_parents'];
            if (!empty($block_id = $ids[0])) {
                $block=$this->findBlockModel($block_id);
                $out = $this->orders->getCollectFromBlockArray($block);
                return ['output'=>$out, 'selected'=>''];
            }
        }
        return ['output'=>'', 'selected'=>''];
    }
###OrderCartForm
    public function actionChangeOrderCartForm($order_id=null,$parent_id=null)
    {

        if ((empty($order_id)) and (empty($parent_id))) {
            $message='Is empty order_id and parent_id ';
            Yii::$app->session->setFlash('error',$message);
            return $this->asJson(['status' => 'error', 'data' => $message]);
        }
        if ($parent_id) {
            $parent=$this->findOrderItemModel($parent_id);
            $this->updateOrderInSession($parent);
        }   else {
            $order = $this->findModel($order_id);
            Yii::$app->session->set('order_id',$order->id);
        }
        return $this->asJson(['status' => 'success', 'data' => '']);
    }

###Item
    public function actionItemAddAjax($type_id,$parent_id=null,$qty=1, $product_id=null, $name=null)
    {
        if (empty($parent_id)) {
            $parent_id=$this->getParentFromSession();
        }
        $parent=$this->findOrderItemModel($parent_id);
        $this->updateOrderInSession($parent);
        try {
            $this->service->addItem($type_id,$parent_id,$qty, $product_id, $name);
            Yii::$app->session->setFlash('success', 'Товар добавлен в заказ');
            return $this->asJson(['status' => 'success', 'data' => ['block_id'=>Yii::$app->session->get('block_id')]]);
        } catch (\DomainException $e) {
            Yii::$app->session->setFlash('error', $e->getMessage());
            return $this->asJson(['status' => 'error', 'data' => $e->getMessage()]);
        }

    }

    public function actionItemUpdateAjax()
    {
        /**
        *   В связи в с тем что editable в grid не позволяет менять model на ItemForm пришлось так извратиться
        **/
        if (Yii::$app->request->post('hasEditable')) {
            $item = $this->findOrderItemModel(Yii::$app->request->post('editableKey'));
            $form=new ItemForm($item);
            $post = ['ItemForm' =>  current($_POST['OrderItem'])];

            $output='';
            if ($form->load($post) && $form->validate()) {
                try {

                    $this->service->editItem($item->order_id, $item->id, $form);
                    $block_id=$item->block?$item->block->id:'';
                    return $this->asJson(['output' => $output, 'message' => '','data'=>['block_id'=>$block_id]]);
                } catch (\DomainException $e) {
                    Yii::$app->errorHandler->logException($e);
                    Yii::$app->session->setFlash('error', $e->getMessage());
                    return $this->asJson(['output' => '', 'message' => $e->getMessage(), 'status' => 'error']);
                }
            }
            return $this->asJson(['message' =>current($form->firstErrors), 'output' => '']);
        }
        return $this->asJson(['message' => 'Ошибка валидации', 'output' => '']);
    }

    public function actionItemDeleteAjax($id,$item_id)
    {
        $order=$this->findModel($id);
        $item=$this->findOrderItemModel($item_id);
        try {
            $this->service->removeItem($id, $item_id);
            $block=$item->getBlock();
            return $this->asJson($this->render('item/_grid', [
                'block'=>$block,
                'order'=>$order
            ]));
        } catch (\DomainException $e) {
            Yii::$app->session->setFlash('error', $e->getMessage());
            $block=$item->getBlock();
            return $this->asJson($this->render('item/_grid', [
                'block'=>$block,
                'order'=>$order
            ]));
        }
    }
    public function actionItemsDeleteAjax($id)
    {
        $order=$this->findModel($id);

        $post=Yii::$app->request->post();
        $keyList=$post['keylist']?:null;

        try {
            $this->service->removeItems($id, $keyList);
            return $this->asJson(['status' => 'success']);
        } catch (\DomainException $e) {
            Yii::$app->session->setFlash('error', $e->getMessage());
            return $this->asJson(['output' => '', 'message' => $e->getMessage(), 'status' => 'error']);
        }
    }
    public function actionItemMoveUpAjax($id,$item_id)
    {
        try {
            $this->service->moveItemUp($id, $item_id);
            return $this->asJson(['status' => 'success', 'data' => '']);
        } catch (\DomainException $e) {
            Yii::$app->session->setFlash('error', $e->getMessage());
            return $this->asJson(['status' => 'error', 'data' => $e->getMessage()]);
        }
    }
    public function actionItemMoveDownAjax($id,$item_id)
    {
        try {
            $this->service->moveItemDown($id, $item_id);
            return $this->asJson(['status' => 'success', 'data' => '']);
        } catch (\DomainException $e) {
            Yii::$app->session->setFlash('error', $e->getMessage());
            return $this->asJson(['status' => 'error', 'data' => $e->getMessage()]);
        }
    }
###Operation
    public function actionOperationModalAjax($id,$operation_id)
    {

        try {
            $order=$this->findModel($id);
            $post=Yii::$app->request->post();
            $keylist=$post['keylist']?:null;
//            var_dump($keylist);exit;
            $out = $this->renderAjax('operation/_modalOperationConfirm', [
                'order' => $order,
                'items_provider' =>OrderReadRepository::getProvider($order->getItemsForOperation($operation_id,$keylist)),
                'operation_id' => $operation_id
            ]);
            return $this->asJson(['status' => 'success', 'data' => $out]);
        } catch (\DomainException $e) {
            Yii::$app->session->setFlash('error', $e->getMessage());
            return $this->asJson(['status' => 'error', 'data' => $e->getMessage()]);
        }
    }
    public function actionOperationAddAjax($id,$operation_id)
    {
        $post=Yii::$app->request->post();
        $arrQty=$post['OrderItem']['qty'];
        try {
            $order=$this->findModel($id);
            $this->service->addOperation($order->id,$operation_id,$arrQty);
            return $this->asJson(['success' => true]);
//            return $this->asJson(['status' => 'success', 'data' => '']);
        } catch (\DomainException $e) {
            Yii::$app->session->setFlash('error', $e->getMessage());
            return $this->asJson(['status' => 'error', 'data' => $e->getMessage()]);
        }
    }
###Export

    /**
     * Экспорт заказа в файл, Если заказ не указан, тогда выгражаем все заказы
     * @param null $id
     * @return \yii\web\Response
     */
    public function actionExport($id = null)
    {
        if ($id) {
            if ($url = $this->service->exportOrder($id)) {
                return $this->asJson(['status' => 'success', 'data' => $url]);
            } else {
                return $this->asJson(['status' => 'error', 'data' => ""]);
            }
        } else {
            $searchModel = new OrderSearch();
            $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
            if ($url = $this->service->exportOrders($dataProvider)) {
                return $this->asJson(['status' => 'success', 'data' => $url]);
            } else {
                return $this->asJson(['status' => 'error', 'data' => ""]);
            }
        }
    }
###Status
    public function actionChangeStatusAjax($id,$status_id)
    {

        try {
            $order = $this->findModel($id);
            $this->service->changeStatus($order->id,$status_id);
            return $this->asJson(['status' => 'success', 'data' => '']);
        }catch (\DomainException $e) {
            Yii::$app->session->setFlash('error', $e->getMessage());
            return $this->asJson(['status' => 'error', 'data' => '']);
        }
    }
    #################################################################
    private function getParentFromSession():int
    {
        if  (Yii::$app->session->get('collect_id')) {
            return Yii::$app->session->get('collect_id');
        } else if (Yii::$app->session->get('block_id')) {
            return (Yii::$app->session->get('block_id'));
        }
    }
    private function updateOrderInSession(OrderItem $orderItem): void
    {
        Yii::$app->session->set('order_id',$orderItem->order_id);
        if ($orderItem->isBlock()) {
            Yii::$app->session->set('block_id',$orderItem->id);
            Yii::$app->session->remove('collect_id');
        } else {
            if ($orderItem->isCollect()) {
                Yii::$app->session->set('block_id',$orderItem->parent->id);
                Yii::$app->session->set('collect_id',$orderItem->id);
            } else {
                Yii::$app->session->set('block_id',$orderItem->parent->id);
                Yii::$app->session->remove('collect_id');
            }
        }
    }
#################################################################
    /**
     * @param integer $id
     * @return Order the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id): Order
    {
        if (($model = Order::findOne($id)) !== null) {
            return $model;
        }
        throw new NotFoundHttpException('The requested page does not exist.');
    }
    protected function findBlockModel($id): OrderItem
    {
        if (($model = OrderItem::findOne($id)) !== null) {
            return $model;
        }
        throw new NotFoundHttpException('The requested page does not exist.');
    }
    protected function findOrderItemModel($id): OrderItem
    {
        if (($model = OrderItem::findOne($id)) !== null) {
            return $model;
        }
        throw new NotFoundHttpException('The requested page does not exist.');
    }
    protected function findProduct($id): Product
    {
        if (($model = Product::findOne(['id'=>$id])) !== null) {
            return $model;
        }
        throw new NotFoundHttpException('The requested page does not exist.');
    }
    protected function findService($id): Service
    {
        if (($model = Service::findOne(['id'=>$id])) !== null) {
            return $model;
        }
        throw new NotFoundHttpException('The requested page does not exist.');
    }

}
