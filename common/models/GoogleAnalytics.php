<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "{{%google_analytics}}".
 *
 * @property int $id
 * @property string|null $head
 * @property string|null $body
 * @property int $is_delete
 * @property string $created_at
 * @property string $updated_at
 */
class GoogleAnalytics extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%google_analytics}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['head', 'body'], 'string'],
            [['is_delete'], 'integer'],
            [['created_at', 'updated_at'], 'safe'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'head' => Yii::t('app', 'Head'),
            'body' => Yii::t('app', 'Body'),
            'is_delete' => Yii::t('app', 'Is Delete'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
        ];
    }
}
