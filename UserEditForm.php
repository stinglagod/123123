<?php

namespace rent\forms\manage\User;

use rent\access\Rbac;
use rent\entities\Client\Site;
use rent\entities\User\User;
use yii\base\Model;
use yii\helpers\ArrayHelper;
use yii\web\UploadedFile;
use Yii;

class UserEditForm extends UserCreateForm
{
    public $username;
    public $email;
    public $name;
    public $surname;
    public $patronymic;
    public $telephone;
    public $default_site;
    public $avatar;
    public $role;
    public $default_client_id;

    public $_user;

    public function __construct(User $user, $config = [])
    {
        $this->username = $user->username;
        $this->email = $user->email;
        $this->name = $user->name;
        $this->surname = $user->surname;
        $this->patronymic = $user->patronymic;
        $this->telephone = $user->telephone;
        $this->default_site = $user->default_site;
        $this->default_client_id = $user->default_client_id;
        $this->_user = $user;

        $roles = Yii::$app->authManager->getRolesByUser($user->id);
        $this->role = $roles ? reset($roles)->name : null;

        parent::__construct($config);
    }

    public function rules(): array
    {
        return [
            ['name', 'trim'],
            ['name', 'required'],
            ['name', 'string', 'min' => 2, 'max' => 255],
            ['surname', 'string', 'min' => 2, 'max' => 255],
            ['patronymic', 'string', 'min' => 2, 'max' => 255],
            ['telephone', 'string', 'min' => 2, 'max' => 255],

            ['email', 'trim'],
            ['email', 'required'],
            ['email', 'email'],
            ['email', 'string', 'max' => 255],
            ['email', 'unique', 'targetClass' => '\rent\entities\User\User', 'filter' => function ($query) {
                    $query->andWhere(['not', ['id'=>$this->_user->id]]);
                },
                'message' => 'Email уже используется'],

            ['role', 'required'],
            ['role','default','value'=>Rbac::ROLE_USER],
            [['role'], 'in', 'range' => ArrayHelper::map(\Yii::$app->authManager->getRoles(), 'name', 'name')],

            ['avatar', 'image', 'extensions' => ['png', 'jpg','jpeg']],
            [['default_site','default_client_id'], 'integer'],
            ['default_client_id', 'required','when' => function($model) {
                return $model->role!='super_admin';
            }],
        ];
    }
    public function attributeLabels()
    {
        return [
            'name' => 'Имя',
            'surname' => 'Фамилия',
            'patronymic' => 'Отчество',
            'telephone' => 'Телефон',
            'email' => 'Email',
            'role' => 'Роль',
            'default_site' => 'Сайт по умолчанию',
            'default_client_id' => 'Клиент по умолчанию',
        ];
    }
}