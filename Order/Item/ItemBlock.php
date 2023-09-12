<?php

namespace rent\entities\Shop\Order\Item;

use rent\entities\Client\Site;
use rent\entities\behaviors\MetaBehavior;
use rent\entities\Meta;
use yii\db\ActiveRecord;
use yii\db\ActiveQuery;
use rent\entities\behaviors\ClientBehavior;
use Yii;

/**
 * @property integer $id
 * @property string $name
 * @property integer $sort
 * @property integer $site_id
 * @property integer $client_id
 *
 * @property \rent\entities\Client\Site $site
 *
 * @method ActiveQuery find(bool $all)
 */
class ItemBlock extends ActiveRecord
{
    const DEFAULT_NAME='Общее';

    public static function create($name): self
    {
        $itemBlock = new static();
        $itemBlock->name = $name;
        return $itemBlock;
    }

    public function edit($name): void
    {
        $this->name = $name;
    }

    ##########################################

    public static function tableName(): string
    {
        return '{{%shop_item_blocks}}';
    }

    public function behaviors(): array
    {
        return [
            ClientBehavior::class,
        ];
    }

    public function getSite() :ActiveQuery
    {
        return $this->hasOne(Site::class, ['id' => 'site_id']);
    }
}