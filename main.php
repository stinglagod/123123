<?php
use \kartik\datecontrol\Module;
$params = array_merge(
    require __DIR__ . '/../../common/config/params.php',
    require __DIR__ . '/../../common/config/params-local.php',
    require __DIR__ . '/params.php',
    require __DIR__ . '/params-local.php'
);
return [
    'id' => 'app-backend',
    'name' => 'Rent4B.ЛК',
    'basePath' => dirname(__DIR__),
    'aliases' => [
        '@staticRoot' => $params['staticPath'],
        '@static'   => $params['staticHostInfo'],
    ],
    'homeUrl' => '/admin',
    'language'=>'ru',
    'controllerNamespace' => 'backend\controllers',
    'defaultRoute' => 'site/index',
    'bootstrap' => [
        'log',
        'common\bootstrap\SetUp',
        'backend\bootstrap\SetUp',
    ],
    'as access' => [
        'class' => 'yii\filters\AccessControl',
        'except'=> [
            'auth/auth/login',
            'auth/signup/signup',
            'auth/signup/confirm',
            'auth/reset/request',
            'auth/reset/confirm',
            'site/error',
            'site/term',
            ],
        'rules' => [
            [
                'allow' => true,
                'roles' => ['@'],
            ],
        ],
    ],
    'modules' => [
        'gridview' =>  [
            'class' => '\kartik\grid\Module'
            // enter optional module parameters below - only if you need to
            // use your own export download action or custom translation
            // message source
            // 'downloadAction' => 'gridview/export/download',
            // 'i18n' => []
        ],
        'treemanager' =>  [
            'class' => '\kartik\tree\Module',
            // other module settings, refer detailed documentation
        ],
        'datecontrol' =>  [
            'class' => 'kartik\datecontrol\Module',

            // format settings for displaying each date attribute (ICU format example)
            'displaySettings' => [
//                Module::FORMAT_DATE => 'dd.MM.yyyy',
                Module::FORMAT_DATE => 'dd.MM.yyyy',
                Module::FORMAT_TIME => 'hh:mm:ss a',
                Module::FORMAT_DATETIME => 'php:d-m-Y H:i:s',
            ],

            // format settings for saving each date attribute (PHP format example)
            'saveSettings' => [
//                Module::FORMAT_DATE => 'php:Y-m-d H:i:s',
                Module::FORMAT_DATE => 'php:U',
                Module::FORMAT_TIME => 'php:H:i:s',
                Module::FORMAT_DATETIME => 'php:U',
//                Module::FORMAT_DATETIME => 'php:Y-m-d H:i:s',
            ],

//            // set your display timezone
            'displayTimezone' => 'Europe/Ulyanovsk',
//            'displayTimezone' => date_default_timezone_get(),
//            'displayTimezone' => 'UTC',
//
//            // set your timezone for date saved to db
//            'saveTimezone' => date_default_timezone_get(),
            'saveTimezone' => 'UTC',

            // automatically use kartik\widgets for each of the above formats
            'autoWidget' => true,
            // use ajax conversion for processing dates from display format to save format.
            'ajaxConversion' => true,

            // default settings for each widget from kartik\widgets used when autoWidget is true
            'autoWidgetSettings' => [
                kartik\datecontrol\Module::FORMAT_DATE => [
                    'type'=>2,
                    'pluginOptions'=>[
                    'autoclose'=>true,
                    'todayHighlight' => true,
                    'todayBtn' => true,
                ]], // example
                Module::FORMAT_DATETIME => [

                ], // setup if needed
                Module::FORMAT_TIME => [], // setup if needed
            ],

            // custom widget settings that will be used to render the date input instead of kartik\widgets,
            // this will be used when autoWidget is set to false at module or widget level.
            'widgetSettings' => [
                Module::FORMAT_DATE => [
                    'class' => 'kartik\date\DatePicker', // example
                    'options' => [
//                        'dateFormat' => 'php:d-M-Y',
                        'options' => ['class'=>'form-control'],
                        'convertFormat' => true,
                    ]
                ],
                Module::FORMAT_DATETIME => [
                    'class' => 'kartik\date\DatePicker', // example
                    'options' => [
//                        'dateFormat' => 'php:d-M-Y',
                        'options' => ['class'=>'form-control'],
                        'convertFormat' => true,
                    ]
                ],
            ]
        ],
    ],
    'controllerMap' => [
        'elfinder' => [
            'class' => 'mihaildev\elfinder\Controller',
            'access' => ['@'],
            'plugin' => [
                [
                    'class'=>'\mihaildev\elfinder\plugin\Sluggable',
                    'lowercase' => true,
                    'replacement' => '-'
                ]
            ],
            'roots' => [
                [
                    'baseUrl'=>'@static',
                    'basePath'=>'@staticRoot',
                    'path' => 'files',
                    'name' => 'Global'
                ],
            ],
        ],
    ],
    'components' => [
        'settings'=>[
            'class' => '\rent\settings\Settings',
            'useSaveToSessionCache'=>true,
        ],
        'urlManager' => [
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'rules' => [
                '/profile' => '/profile',
            ],
        ],
        'request' => [
            'baseUrl' => '/admin',
            'csrfParam' => '_csrf-backend',
            'cookieValidationKey' => $params['cookieValidationKey'],
        ],
        'user' => [
            'identityClass' => 'rent\entities\User\User',
            'enableAutoLogin' => true,
            'identityCookie' => [
                'name' => '_identity',
                'httpOnly' => true,
                'domain' => $params['cookieDomain']
            ],
            'loginUrl' => ['auth/auth/login'],
        ],
        'session' => [
            'name' => '_session',
            'timeout'=>10*365*24*60*60,         //10 ЛЕТ
            'cookieParams' => [
//                'domain' => $params['cookieDomain'],
                'httpOnly' => true
            ]
        ],
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning'],
                ],
            ],
        ],
        'errorHandler' => [
            'errorAction' => 'site/error',
        ],
        'backendUrlManager' => require __DIR__ . '/urlManager.php',
        'frontendUrlManager' => require __DIR__ . '/../../frontend/config/urlManager.php',
        'urlManager' => function () {
            return Yii::$app->get('backendUrlManager');
        },
        'formatter' => [
            'defaultTimeZone' => 'Europe/Moscow',
            'dateFormat' => 'dd.MM.yyyy',
        ],
    ],
    'params' => $params,
];
