<?php

namespace common\models;

use Yii;

class SearchAnalytics extends \yii\db\ActiveRecord
{
    public static function tableName()
    {
        return '{{%search_analytics}}';
    }

    public function rules()
    {
        return [
            [['keyword', 'normalized_keyword', 'created_at'], 'required'],
            [['category_names', 'subcategory_names', 'page_url', 'referrer', 'user_agent', 'created_at'], 'safe'],
            [['result_count'], 'integer'],
            [['keyword', 'normalized_keyword'], 'string', 'max' => 255],
            [['session_id'], 'string', 'max' => 80],
            [['ip_address'], 'string', 'max' => 45],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'keyword' => Yii::t('app', 'Keyword'),
            'normalized_keyword' => Yii::t('app', 'Normalized Keyword'),
            'result_count' => Yii::t('app', 'Result Count'),
            'category_names' => Yii::t('app', 'Categories'),
            'subcategory_names' => Yii::t('app', 'Subcategories'),
            'page_url' => Yii::t('app', 'Page URL'),
            'referrer' => Yii::t('app', 'Referrer'),
            'session_id' => Yii::t('app', 'Session ID'),
            'ip_address' => Yii::t('app', 'IP Address'),
            'user_agent' => Yii::t('app', 'User Agent'),
            'created_at' => Yii::t('app', 'Created At'),
        ];
    }
}
