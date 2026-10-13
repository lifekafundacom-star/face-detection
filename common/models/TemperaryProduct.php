<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "temperary_product".
 *
 * @property int $id
 * @property int $template_id
 * @property int $language_id
 * @property string $item_details
 * @property int $is_delete
 * @property string $created_at
 * @property string $updated_at
 */
class TemperaryProduct extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'temperary_product';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['template_id', 'language_id'], 'required'],
            [['template_id', 'language_id', 'is_delete'], 'integer'],
            [['created_at', 'updated_at', 'item_details','image_details'], 'safe'],
            // [['item_details','image_details'], 'string', 'max' => 255],
            [['item_details','image_details'], 'string'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'template_id' => Yii::t('app', 'Template ID'),
            'language_id' => Yii::t('app', 'Language ID'),
            'item_details' => Yii::t('app', 'Item Details'),
            'image_details' => Yii::t('app', 'Image Details'),
            'is_delete' => Yii::t('app', 'Is Delete'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
        ];
    }
}
