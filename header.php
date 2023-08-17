<?php
use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\Pjax;
use rent\entities\Support\Task\Task;

/* @var $this \yii\web\View */
/* @var $content string */
$user = Yii::$app->user->identity;
$responsible = Task::findOne(['responsible_id' => $user->id]);
$responsibleId = $responsible ? $responsible->id : null;
$unansweredCount = Task::find()
    ->andWhere(['status' => Task::STATUS_WAITING_RESPONSE])
    ->andWhere(['responsible_id' => $user->id])
    ->count();
?>

<header class="main-header">

    <?= Html::a('<span class="logo-mini">R4B</span><span class="logo-lg">' . Yii::$app->name . '</span>', Yii::$app->homeUrl, ['class' => 'logo']) ?>

    <nav class="navbar navbar-static-top" role="navigation">

        <a href="#" class="sidebar-toggle" data-toggle="push-menu" role="button">
            <span class="sr-only">Toggle navigation</span>
        </a>
        <div class="navbar-custom-menu">
            <ul class="nav navbar-nav">
                <li class="dropdown user user-menu">
                    <a href="#" class="dropdown-toggle" data-toggle="dropdown">
                        <img src="<?=Yii::$app->user->identity->avatarUrl?>" class="user-image" alt="User Image"/>
                        <span class="hidden-xs"><?=Yii::$app->user->identity->shortName?></span>
                    </a>
                    <ul class="dropdown-menu">
                        <!-- User image -->
                        <li class="user-header">
                            <img src="<?=Yii::$app->user->identity->avatarUrl?>" class="img-circle"
                                 alt="User Image"/>
                            <p>
                                <?=Yii::$app->user->identity->shortName?> - Web Developer
                                <small>Member since Nov. 2012</small>
                            </p>
                        </li>
                        <!-- Menu Body -->
                        <li class="user-body">
                            <div class="col-xs-4 text-center">
                                <a href="#">Followers</a>
                            </div>
                            <div class="col-xs-4 text-center">
                                <a href="#">Sales</a>
                            </div>
                            <div class="col-xs-4 text-center">
                                <a href="#">Friends</a>
                            </div>
                        </li>
                        <!-- Menu Footer-->
                        <li class="user-footer">
                            <div class="pull-left">
                                <?= Html::a('Профиль', ['/profile'], ['class' => 'btn btn-default btn-flat']) ?>
                            </div>
                            <div class="pull-right">
                                <?= Html::a(
                                    'Выйти',
                                    ['/auth/auth/logout'],
                                    ['data-method' => 'post', 'class' => 'btn btn-default btn-flat']
                                ) ?>
                            </div>
                        </li>
                    </ul>
                </li>
<li>
    <?php if ($unansweredCount > 0) : ?>
        <a href="<?= Url::to(['/support/task/index']) ?>" class="dropdown-toggle" target="_self">
            <i class="fa fa-bell<?= $unansweredCount > 1 ? '-o' : '' ?>"></i>
            <span class="label label-danger"><?= $unansweredCount ?></span>
        </a>
    <?php else : ?>
        <a href="#"><i class="fa fa-bell-slash-o"></i></a>
    <?php endif; ?>
                </li>
                <!-- User Account: style can be found in dropdown.less -->
                <li>
                    <a href="#" data-toggle="control-sidebar"><i class="fa fa-gears"></i></a>
                </li>
            </ul>
        </div>
        <?=$this->render('_header-clients',[]);?>
    </nav>
</header>
