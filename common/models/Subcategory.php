<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "subcategory".
 *
 * @property int $id
 * @property int $category_id
 * @property string $subcategory_name
 * @property string|null $whatsapp
 * @property int $is_delete
 * @property string $created_at
 * @property string $updated_at
 */
class Subcategory extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'subcategory';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['category_id', 'subcategory_name','description'], 'required'],
            [['category_id', 'is_delete'], 'integer'],
            [['created_at', 'updated_at'], 'safe'],
            [['description', 'meta_title', 'meta_description', 'keywords', 'tag', 'whatsapp'], 'string'],
            [['subcategory_name'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'category_id' => Yii::t('app', 'Category Name'),
            'subcategory_name' => Yii::t('app', 'Subcategory Name'),
            'whatsapp' => Yii::t('app', 'WhatsApp Group Link'),
            'is_delete' => Yii::t('app', 'Is Delete'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
        ];
    }

    public function getCategoryname()
    {
        return $this->hasOne(Category::className(), ['id' => 'category_id']);
    }
}
