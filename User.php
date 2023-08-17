<?php
namespace rent\entities\User;

use lhs\Yii2SaveRelationsBehavior\SaveRelationsBehavior;
use rent\entities\Client\Client;
use common\models\File;
use rent\entities\Client\Site;
use rent\entities\Client\UserAssignment;
use rent\entities\Shop\Product\SiteAssignment;
use Yii;
use yii\base\NotSupportedException;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
use yii\web\IdentityInterface;
use yii\helpers\ArrayHelper;
use yii\db\ActiveQuery;
use yii\web\UploadedFile;
//use yiidreamteam\upload\ImageUploadBehavior;
use rent\entities\behaviors\ImageUploadBehavior;

/**
 * User model
 *
 * @property integer $id
 * @property string $username
 * @property string $password_hash
 * @property string $password_reset_token
 * @property string $email
 * @property string $email_confirm_token
 * @property string $auth_key
 * @property integer $status
 * @property integer $created_at
 * @property integer $updated_at
 * @property string $password write-only password
 * @property int $avatar_id
 * @property integer $name
 * @property integer $surname
 * @property integer $patronymic
 * @property integer $default_site
 * @property string $telephone
 * @property string $timezone
 * @property string $avatar
 * @property array $roles
 * @property string $role
 * @property integer $default_client_id
 *
 * @property Site $site
 * @property File
 * @property Network[] $networks
 * @property WishlistItem[] $wishlistItems
 * @property Client $defaultClient
 * @property Client[] clients
 * @property Client[] clientsAll                    //defaultClient + clients
 */
class User extends ActiveRecord implements IdentityInterface
{
    const STATUS_DELETED = 0;
    const STATUS_WAIT = 9;
    const STATUS_ACTIVE = 10;

    const DEFAULT_ROLE = 'user';
    const ROLE_DIRECTOR = 'director';
    const ROLE_ADMIN = 'admin';

    const PWD_MIN_LENGTH=6;


    public static function create(string $name, string $email, string $password,$surname,$patronymic,$telephone, $default_site, $defaultClientId): self
    {
        $user = new User();
        $user->name = $name;
        $user->email = $email;
        $user->setPassword(!empty($password) ? $password : Yii::$app->security->generateRandomString());
        $user->surname =$surname;
        $user->patronymic =$patronymic;
        $user->telephone =$telephone;
        $user->default_site =$default_site;
        $user->default_client_id=$defaultClientId;
        $user->status = self::STATUS_ACTIVE;
        $user->auth_key = Yii::$app->security->generateRandomString();
        return $user;
    }

    public function edit(string $name, string $email,$surname,$patronymic,$telephone, $default_site, $defaultClientId): void
    {
        $this->name = $name;
        $this->email = $email;
        $this->surname =$surname;
        $this->patronymic =$patronymic;
        $this->telephone =$telephone;
        $this->default_site =$default_site;
        $this->default_client_id=$defaultClientId;
    }


    public static function requestSignup(string $name,string $surname,string $email, string $password): self
    {
        $user = new static();
        $user->name=$name;
        $user->surname=$surname;
        $user->email=$email;
        $user->setPassword($password);
        $user->created_at=date('Y-m-d H:i:s');
        $user->status=self::STATUS_WAIT;
        $user->email_confirm_token = Yii::$app->security->generateRandomString();
        $user->generateAuthKey();
        return $user;
    }

    public function confirmSignup(): void
    {
        if (!$this->isWait()) {
            throw new \DomainException('User is already active.');
        }
        $this->status = self::STATUS_ACTIVE;
        $this->email_confirm_token = null;
    }

    public static function signupByNetwork($network, $identity): self
    {
        $user = new User();
        $user->created_at = date('Y-m-d H:i:s');
        $user->status = self::STATUS_ACTIVE;
        $user->generateAuthKey();
        $user->networks = [Network::create($network, $identity)];
        return $user;
    }

    public function addToWishList($productId): void
    {
        $items = $this->wishlistItems;
        foreach ($items as $item) {
            if ($item->isForProduct($productId)) {
                throw new \DomainException('Item is already added.');
            }
        }
        $items[] = WishlistItem::create($productId);
        $this->wishlistItems = $items;
    }

    public function removeFromWishList($productId): void
    {
        $items = $this->wishlistItems;
        foreach ($items as $i => $item) {
            if ($item->isForProduct($productId)) {
                unset($items[$i]);
                $this->wishlistItems = $items;
                return;
            }
        }
        throw new \DomainException('Item is not found.');
    }
    public function getAmountWishListItems():int
    {
        return count( $this->wishlistItems);
    }
    /**
     * Запрос на сброс пароля. Прежде чем сбрасываем пароль, прроверяем не сброшен ли он ранее
     *
     * @throws \yii\base\Exception
     */
    public function requestPasswordReset(): void
    {
        if (self::isPasswordResetTokenValid($this->password_reset_token)) {
            throw new \DomainException('Сброс пароля уже запрошен.');
        }
        $this->password_reset_token = $this->generatePasswordResetToken();
    }

    public function resetPassword($password): void
    {
        if (empty($this->password_reset_token))
            throw new \DomainException('Запрос на смену пароля не был отправлен.');
        if (!self::isPasswordResetTokenValid($this->password_reset_token))
            throw new \DomainException('Запрос на смену пароля истек.');
        $this->setPassword($password);
        $this->password_reset_token = null;
    }

    public function isWait(): bool
    {
        return $this->status === self::STATUS_WAIT;
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function isIdEqualTo($id)
    {
        return $this->id == $id;
    }

    public function hasClient(int $clientId):bool
    {
        foreach ($this->clientsAll as $client) {
            if ($client->isIdEqualTo($clientId)){
                return true;
            }
        }
        return false;
    }

    public function getNetworks(): ActiveQuery
    {
        return $this->hasMany(Network::class, ['user_id' => 'id']);
    }

    public function getSite(): ActiveQuery
    {
        return $this->hasOne(Site::class, ['id' => 'site_id']);
    }

    public function getWishlistItems(): ActiveQuery
    {
        return $this->hasMany(WishlistItem::class, ['user_id' => 'id']);
    }

    // User
    public function getDefaultClient(): ActiveQuery
    {
        return $this->hasOne(Client::class, ['id' => 'default_client_id']);
    }

    public function getUserAssignments(): ActiveQuery
    {
        return $this->hasMany(UserAssignment::class, ['user_id' => 'id']);
    }

    public function getClients(): ActiveQuery
    {
        return $this->hasMany(Client::class, ['id' => 'client_id'])->via('userAssignments');
    }

    public function getClientsAll():array
    {
        $clients=$this->defaultClient?[$this->defaultClient]:[];
        foreach ($this->clients as $client) {
            if ($client->id==$this->default_client_id) continue;
            $clients[]=$client;
        }
        return $clients;
    }

    public function setAvatar(UploadedFile $avatar): void
    {
        $this->avatar = $avatar;
    }

    public static function getPasswordMinimum():int
    {
        return self::PWD_MIN_LENGTH;
    }
###
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%users}}';
    }

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            TimestampBehavior::class,
            [
                'class' => SaveRelationsBehavior::class,
                'relations' => ['networks','wishlistItems','clients'],
            ],
            [
                'class' => ImageUploadBehavior::class,
                'attribute' => 'avatar',
                'createThumbsOnRequest' => true,
                'filePath' => '@staticRoot/origin/users/[[id]].[[extension]]',
                'fileUrl' => '@static/origin/users/[[id]].[[extension]]',
                'thumbPath' => '@staticRoot/cache/users/[[profile]]_[[id]].[[extension]]',
                'thumbUrl' => '@static/cache/users/[[profile]]_[[id]].[[extension]]',
                'thumbs' => [
                    'admin' => ['width' => 100, 'height' => 100],
                    'thumb' => ['width' => 640, 'height' => 480],
                    'blog_list' => ['width' => 1000, 'height' => 150],
                    'widget_list' => ['width' => 228, 'height' => 228],
                ],
            ],
        ];
    }

    public function transactions()
    {
        return [
            self::SCENARIO_DEFAULT => self::OP_ALL,
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'created_at' => 'Создан',
            'updated_at' => 'Обновлен',
            'username' => 'Логин',
            'email' => 'Email',
            'status' => 'Статус',
            'dateCreate_from' => 'Дата с ',
            'dateCreate_to' => 'Дата до ',
            'role' => 'Роль',
            'name'=>'Имя',
            'surname'=>'Фамилия',
            'patronymic'=>'Отчество',
            'shortName'=>'Пользователь',
            'telephone'=>'Номер телефона',
            'client_id' => 'Клиент',
            'avatar_id' => 'Аватар',
            'clientsName' => 'Клиенты',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public static function findIdentity($id)
    {
        return static::findOne(['id' => $id, 'status' => self::STATUS_ACTIVE]);
    }

    /**
     * {@inheritdoc}
     */
    public static function findIdentityByAccessToken($token, $type = null)
    {
        throw new NotSupportedException('"findIdentityByAccessToken" is not implemented.');
    }

    /**
     * Finds user by username
     *
     * @param string $username
     * @return static|null
     */
    public static function findByUsername($username)
    {
        return static::findOne(['username' => $username, 'status' => self::STATUS_ACTIVE]);
    }
    /**
     * Finds user by email
     *
     * @param string $email
     * @return static|null
     */
    public static function findByEmail($email)
    {
        return static::findOne(['email' => $email, 'status' => self::STATUS_ACTIVE]);
    }

    /**
     * Finds user by password reset token
     *
     * @param string $token password reset token
     * @return static|null
     */
    public static function findByPasswordResetToken($token)
    {
        if (!static::isPasswordResetTokenValid($token)) {
            return null;
        }

        return static::findOne([
            'password_reset_token' => $token,
            'status' => self::STATUS_ACTIVE,
        ]);
    }

    /**
     * Finds out if password reset token is valid
     *
     * @param string $token password reset token
     * @return bool
     */
    public static function isPasswordResetTokenValid($token)
    {
        if (empty($token)) {
            return false;
        }

        $timestamp = (int) substr($token, strrpos($token, '_') + 1);
        $expire = Yii::$app->params['user.passwordResetTokenExpire'];
        return $timestamp + $expire >= time();
    }

    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return $this->getPrimaryKey();
    }

    /**
     * {@inheritdoc}
     */
    public function getAuthKey():string
    {
        return $this->auth_key;
    }

    /**
     * {@inheritdoc}
     */
    public function validateAuthKey($authKey): bool
    {
        return $this->getAuthKey() === $authKey;
    }

    /**
     * Validates password
     *
     * @param string $password password to validate
     * @return bool if password provided is valid for current user
     */
    public function validatePassword($password)
    {
        return Yii::$app->security->validatePassword($password, $this->password_hash);
    }

    /**
     * Generates password hash from password and sets it to the model
     *
     * @param string $password
     */
    public function setPassword($password)
    {
        $this->password_hash = Yii::$app->security->generatePasswordHash($password);
    }

    /**
     * Generates "remember me" authentication key
     */
    public function generateAuthKey()
    {
        $this->auth_key = Yii::$app->security->generateRandomString();
    }

    /**
     * Generates new password reset token
     */
    public function generatePasswordResetToken():string
    {
        return Yii::$app->security->generateRandomString() . '_' . time();
    }

    /**
     * Removes password reset token
     */
    public function removePasswordResetToken()
    {
        $this->password_reset_token = null;
    }
    /**
     Возращаем аватар
     **/
    public function getAvatarUrl($size=null)
    {
        if ($this->avatar) {
            return $this->getThumbFileUrl('avatar', 'admin');
//            return $this->avatar->getUrl($size);

        } else {
//            return Yii::$app->request->baseUrl.'/img/user2-160x160.jpg';
            return Yii::$app->request->baseUrl.'/img/noavatar.jpg';
        }
    }

    /**
    Формируем Короткое имя пользователя
     **/
    public function getShortName()
    {
        return $this->name . ' ' . $this->surname;
    }
    /**
    Формируем полное имя пользователя
     **/
    public function getFullName()
    {
        return $this->name . ' ' .(!empty($this->patronymic))?$this->patronymic:''. ' ' . $this->surname;
    }

    private $_role=null;
    public function getRole()
    {
        if (empty($this->_role)) {
            $roles = Yii::$app->authManager->getRolesByUser($this->id);
            $this->_role = $roles ? reset($roles)->name : null;
        }

        return $this->_role;
    }

    /**
    Возращаем массив пользователей с ролью
     **/
    public static function findUserByRole($role='user')
    {
        $users=array();
        /** @var \yii\rbac\DbManager $authManager */
        $authManager = Yii::$app->get('authManager');
        foreach ($authManager->getUserIdsByRole($role) as $id) {
            $users[]=self::findOne($id);
//            $arrTmp[$id]=$user?$user->shortName:$user->email;
        }
        return $users;
    }

    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
//          TODO: решить надом нам username или не надо
            $this->username=empty($this->username)?$this->email:$this->username;
//          TODO: в связи с тем, что нельзя оставлять след. поля пустыми мы их заполняем. Но надо порешать как лучше
            if (empty($this->auth_key)) {
                $this->generateAuthKey();
            }
            if (empty($this->password_hash)) {
                $this->password_hash= Yii::$app->security->generateRandomString();
            }
            return true;
        } else {
            return false;
        }
    }

    static public function getUserArray()
    {
        $arr=[
            '-2' => "Показать все",
            '-1' => "Показать мои"
        ];
        $arr=$arr + ArrayHelper::map(User::find()
                ->where(['<>','id', Yii::$app->user->id])
                ->where(['default_client_id' => Yii::$app->settings->getClientId()])
                ->orderBy('name')
                ->all(), 'id', 'shortName');
        return $arr;
    }

    public function isOwnerClient($client_id): bool
    {
        return UserAssignment::find()->where(['client_id'=>$client_id,'user_id'=>$this->id,'owner'=>true])->exists();
    }

    static public function getResponsibleList($all=false)
    {
        $query=User::find()->orderBy('name');
        if ($all) {
            return ArrayHelper::map($query->all(), 'id', 'shortName');
        } else {
            return ArrayHelper::map(User::find()->andWhere(['default_client_id' => Yii::$app->settings->getClientId()])->orderBy('name')->all(), 'id', 'shortName');
        }

    }

    public function getClient(): Client
    {
        if ($this->default_client_id) {
            return $this->defaultClient;
        }
        else {
            return Client::findOne(Yii::$app->params['mainClientId']);
        }
    }

    /**
     * Является ли пользователь владельцем компании(одной или нескольких)
     * @return bool
     */
    public function isOwner():bool
    {
        foreach ($this->clients as $client) {
            if ($this->isOwnerClient($client->id)) {
                return true;
            }
        }
        return false;
    }

}
