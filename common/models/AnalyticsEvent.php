<?php

namespace common\models;

use Yii;

class AnalyticsEvent extends \yii\db\ActiveRecord
{
    public static function tableName()
    {
        return '{{%analytics_event}}';
    }

    public function rules()
    {
        return [
            [['event_type', 'page_url', 'page_path', 'created_at'], 'required'],
            [['page_url', 'element_href', 'referrer', 'user_agent'], 'string'],
            [['created_at'], 'safe'],
            [['event_type'], 'string', 'max' => 30],
            [['page_path', 'page_title', 'element_text'], 'string', 'max' => 500],
            [['element_tag'], 'string', 'max' => 50],
            [['session_id'], 'string', 'max' => 80],
            [['ip_address'], 'string', 'max' => 45],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'event_type' => Yii::t('app', 'Event Type'),
            'page_url' => Yii::t('app', 'Page URL'),
            'page_path' => Yii::t('app', 'Page Path'),
            'page_title' => Yii::t('app', 'Page Title'),
            'element_text' => Yii::t('app', 'Element Text'),
            'element_tag' => Yii::t('app', 'Element Tag'),
            'element_href' => Yii::t('app', 'Element URL'),
            'referrer' => Yii::t('app', 'Referrer'),
            'session_id' => Yii::t('app', 'Session ID'),
            'ip_address' => Yii::t('app', 'IP Address'),
            'user_agent' => Yii::t('app', 'User Agent'),
            'created_at' => Yii::t('app', 'Created At'),
        ];
    }
}
