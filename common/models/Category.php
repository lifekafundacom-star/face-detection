<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "category".
 *
 * @property int $id
 * @property string $category_name
 * @property string|null $whatsapp
 * @property int $is_delete
 * @property string $credited_at
 * @property string $updated_at
 */
class Category extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'category';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['category_name','description'], 'required'],
            [['is_delete'], 'integer'],
            [['credited_at', 'updated_at'], 'safe'],
            [['category_name','category_icon'], 'string', 'max' => 200],
            [['meta_title','meta_description','keywords', 'tag', 'whatsapp'], 'string'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'category_name' => Yii::t('app', 'Category Name'),
            'category_icon' => Yii::t('app','Category Icon'),
            'whatsapp' => Yii::t('app', 'WhatsApp Group Link'),
            'is_delete' => Yii::t('app', 'Is Delete'),
            'credited_at' => Yii::t('app', 'Credited At'),
            'updated_at' => Yii::t('app', 'Updated At'),
        ];
    }

    public function getSubcategoryname()
    {
        return $this->hasMany(Subcategory::className(), ['category_id' => 'id']);
    }

    public function getcategorysubcategory(){
        return Yii::$app->cache->getOrSet('frontend.category_subcategory_menu', function () {
            $data = [];
            $category = Category::find()
                ->where(['is_delete' => 0])
                ->with(['subcategoryname' => function ($query) {
                    $query->andWhere(['is_delete' => 0]);
                }])
                ->all();

            foreach($category as $cat){
                foreach($cat->subcategoryname as $subcat){ 
                    $data[$cat->category_name][$subcat->id]= $subcat->subcategory_name ;      
                }
            }

            return $data;
        }, 300);
    }
}
