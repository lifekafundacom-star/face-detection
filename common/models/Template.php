<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "template".
 *
 * @property int $id
 * @property int $category_id
 * @property int $subcategory_id
 * @property int $language_id
 * @property string $images
 * @property int $is_delete
 * @property int $created_at
 * @property int $updated
 */
class Template extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'template';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['template_name'], 'required'],
            [['category_id', 'subcategory_id', 'language_id', 'is_delete'], 'integer'],
            [['image_type','title'], 'string', 'max' => 255],
            [['template_name'], 'string', 'max' => 100],
            [['caption', 'description'], 'string'],
            [['image_type','category_id', 'subcategory_id', 'created_at', 'updated'],'safe'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'category_id' => Yii::t('app', 'Category ID'),
            'subcategory_id' => Yii::t('app', 'Subcategory ID'),
            'language_id' => Yii::t('app', 'Language ID'),
            'template_name' => Yii::t('app','Template Name'),
            'images' => Yii::t('app', 'Images'),
            'is_delete' => Yii::t('app', 'Is Delete'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated' => Yii::t('app', 'Updated'),
        ];
    }

    public function getProduct()
    {
        return $this->hasOne(Product::className(), ['template_id' => 'id']);
    }

    public function getMapping()
    {
        return $this->hasOne(TemplateMapping::className(), ['template_id' => 'id'])
            ->andOnCondition(['template_mapping.is_delete' => 0]);
    }
}
