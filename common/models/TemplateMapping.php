<?php

namespace common\models;

use Yii;
use yii\db\Expression;

/**
 * This is the model class for table "template_mapping".
 *
 * @property int $id
 * @property int $template_id
 * @property int $category_id
 * @property int $subcategory_id
 * @property string $publish_type
 * @property string|null $scheduled_at
 * @property int $is_delete
 * @property string $created_at
 * @property string $updated_at
 */
class TemplateMapping extends \yii\db\ActiveRecord
{
    const PUBLISH_TYPE_DIRECT = 'direct';
    const PUBLISH_TYPE_SCHEDULED = 'scheduled';

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'template_mapping';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['template_id', 'category_id', 'subcategory_id'], 'required'],
            [['template_id', 'category_id', 'subcategory_id', 'is_delete', 'featured_section'], 'integer'],
            [['created_at', 'updated_at', 'scheduled_at'], 'safe'],
            [['publish_type'], 'string', 'max' => 20],
            [['publish_type'], 'in', 'range' => array_keys(self::publishTypeOptions())],
            [['scheduled_at'], 'required', 'when' => function ($model) {
                return $model->publish_type === self::PUBLISH_TYPE_SCHEDULED;
            }, 'whenClient' => 'function (attribute, value) {
                var field = document.getElementById("templatemapping-publish_type");
                return field && field.value === "scheduled";
            }'],
            [['featured_section'], 'default', 'value' => 0],
            [['featured_section'], 'in', 'range' => array_keys(self::featuredSectionOptions())],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'template_id' => Yii::t('app', 'Template Name'),
            'category_id' => Yii::t('app', 'Category Name'),
            'subcategory_id' => Yii::t('app', 'Subcategory Name'),
            'publish_type' => Yii::t('app', 'Mapping Type'),
            'scheduled_at' => Yii::t('app', 'Scheduled Date Time'),
            'featured_section' => Yii::t('app', 'Homepage Section'),
            'is_delete' => Yii::t('app', 'Is Delete'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
        ];
    }

    public function getCategoryname()
    {
        return $this->hasOne(Category::className(), ['id' => 'category_id']);
    }

    public function getSubcategoryname()
    {
        return $this->hasOne(Subcategory::className(), ['id' => 'subcategory_id']);
    }

    public function getTemplatename()
    {
        return $this->hasOne(Template::className(), ['id' => 'template_id']);
    }

    public function getProducts()
    {
        return $this->hasMany(Product::className(), ['template_id' => 'template_id'])
            ->andWhere(['product.is_delete' => 0]);
    }

    public function getProduct()
    {
        return $this->hasOne(Product::className(), ['template_id' => 'template_id'])
            ->andWhere(['product.is_delete' => 0]);
    }

    public static function publishTypeOptions()
    {
        return [
            self::PUBLISH_TYPE_DIRECT => 'Direct Mapping',
            self::PUBLISH_TYPE_SCHEDULED => 'Scheduled Mapping',
        ];
    }

    public static function featuredSectionOptions()
    {
        return [
            0 => 'Not Featured',
            1 => 'Recent Greeting cards (Section 1)',
            2 => 'Greeting cards with photo (Section 2)',
        ];
    }

    public static function activeFrontendCondition($alias = 'template_mapping')
    {
        $prefix = $alias ? $alias . '.' : '';

        return [
            'or',
            [$prefix . 'publish_type' => self::PUBLISH_TYPE_DIRECT],
            [$prefix . 'publish_type' => null],
            [
                'and',
                [$prefix . 'publish_type' => self::PUBLISH_TYPE_SCHEDULED],
                ['<=', $prefix . 'scheduled_at', new Expression('NOW()')],
            ],
        ];
    }

    public function beforeValidate()
    {
        if (!parent::beforeValidate()) {
            return false;
        }

        if ($this->publish_type === null || $this->publish_type === '') {
            $this->publish_type = self::PUBLISH_TYPE_DIRECT;
        }

        if ($this->publish_type === self::PUBLISH_TYPE_DIRECT) {
            $this->scheduled_at = null;
        }

        if ($this->scheduled_at !== null && $this->scheduled_at !== '') {
            $this->scheduled_at = str_replace('T', ' ', $this->scheduled_at);
        }

        return true;
    }
}
