<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "product".
 *
 * @property int $id
 * @property int $template_id
 * @property int $language_id
 * @property string $images
 * @property string $item_name
 * @property string $url_slug
 * @property int $x_axis
 * @property int $y_axis
 * @property int $is_delete
 * @property string $created_at
 * @property string $updated_at
 */
class Product extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'product';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            // [['template_id', 'language_id','title','description','caption'], 'required'],
            [['template_id', 'language_id',  'is_delete'], 'integer'],
            [['created_at', 'updated_at','images'], 'safe'],
            [['images','image_details','title','caption','meta_title','alt','url_slug'], 'string', 'max' => 255],
            [['item_name','title','description','caption','meta_title','meta_description','keywords','alt'], 'string'],
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
            'language_id' => Yii::t('app', 'Language Name'),
            'images' => Yii::t('app', 'Images'),
            'item_name' => Yii::t('app', 'Item Name'),
            'image_details' => Yii::t('app', 'Image Details'),     
            'title' => Yii::t('app', 'Title'),
            'description' => Yii::t('app', 'Description'),
            'caption' => Yii::t('app', 'Caption'),
            'url_slug' => Yii::t('app', 'URL Slug'),
            'meta_title' => Yii::t('app', 'Meta Title'),
            'meta_description' => Yii::t('app', 'Meta Description'),
            'keywords' => Yii::t('app', 'Keywords'),
            'alt' => Yii::t('app', 'Alternative Text'), 
            'is_delete' => Yii::t('app', 'Is Delete'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
        ];
    }

    public function getLanguage()
    {
        return $this->hasOne(Language::className(), ['id' => 'language_id']);
    }

    public function getTemplate()
    {
        return $this->hasOne(Template::className(), ['id' => 'template_id']);
    }

    public static function makeUrlSlug($title)
    {
        $slug = trim((string)$title);
        return $slug === '' ? 'card' : str_replace(' ', '_', $slug);
    }

    public static function makeTemplateSlug($templateName)
    {
        $slug = trim((string)$templateName);
        if ($slug === '') {
            return 'card';
        }
        // Replace spaces with underscores
        $slug = str_replace(' ', '_', $slug);
        // Remove any characters that are not alphanumeric or underscores
        $slug = preg_replace('/[^A-Za-z0-9_]+/', '', $slug);
        // Clean up duplicate underscores
        $slug = preg_replace('/_+/', '_', $slug);
        return trim($slug, '_');
    }

    public function getCreatecardSlug()
    {
        $slug = $this->hasAttribute('url_slug') ? trim((string)$this->url_slug) : '';
        if ($slug !== '') {
            return $slug;
        }
        $template = $this->template;
        if ($template && trim((string)$template->template_name) !== '') {
            return self::makeTemplateSlug($template->template_name);
        }
        return self::makeUrlSlug($this->title ?: $this->item_name ?: 'card');
    }

    public function beforeSave($insert)
    {
        if ($this->hasAttribute('url_slug')) {
            $isMapped = !$this->isNewRecord && \common\models\TemplateMapping::find()->where(['template_id' => $this->template_id, 'is_delete' => 0])->exists();
            if (trim((string)$this->url_slug) === '' || !$isMapped) {
                $template = $this->template;
                if ($template && trim((string)$template->template_name) !== '') {
                    $this->url_slug = self::makeTemplateSlug($template->template_name);
                } else {
                    $slugSource = $this->title ?: $this->item_name;
                    if (trim((string)$slugSource) !== '') {
                        $this->url_slug = self::makeUrlSlug($slugSource);
                    }
                }
            }
        }

        return parent::beforeSave($insert);
    }
}
