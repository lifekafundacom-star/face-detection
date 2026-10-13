<?php

namespace common\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use common\models\Template;

/**
 * TemplateSearch represents the model behind the search form of `common\models\Template`.
 */
class TemplateSearch extends Template
{
    /**
     * {@inheritdoc}
     */
    public $title;
    public $description;
    public $caption;
    public $meta_title;
    public $meta_description;
    public $keywords;
    public $alt;
    public $tag;
    public $images;
    public $show_image;
    public $template_details;
    public $product_id;
    public $page_url;
    public $status;

    private function spacedAndUnderscored($value)
    {
        $value = trim((string) $value);
        return [$value, str_replace(' ', '_', $value), str_replace('_', ' ', $value)];
    }

    private function pageUrlSearchParts($value)
    {
        $value = trim((string) $value);
        $decodedValue = urldecode($value);
        $path = parse_url($decodedValue, PHP_URL_PATH);
        $path = $path ?: $decodedValue;
        $segments = array_values(array_filter(explode('/', trim($path, '/')), 'strlen'));
        $createcardIndex = array_search('createcard', $segments, true);

        if ($createcardIndex === false) {
            return [
                'raw' => $decodedValue,
                'category' => null,
                'product_id' => null,
                'title' => null,
            ];
        }

        return [
            'raw' => $decodedValue,
            'category' => isset($segments[$createcardIndex + 1]) ? $segments[$createcardIndex + 1] : null,
            'product_id' => isset($segments[$createcardIndex + 2]) ? $segments[$createcardIndex + 2] : null,
            'title' => isset($segments[$createcardIndex + 3]) ? $segments[$createcardIndex + 3] : null,
        ];
    }


    public function rules()
    {
        return [
            [['id', 'category_id', 'subcategory_id', 'language_id', 'is_delete', 'created_at', 'updated'], 'integer'],
            [['template_name', 'image_type', 'title', 'description', 'caption', 'meta_title', 'meta_description', 'keywords', 'alt', 'tag', 'show_image', 'images', 'template_details', 'product_id', 'page_url', 'status'], 'safe'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function scenarios()
    {
        // bypass scenarios() implementation in the parent class
        return Model::scenarios();
    }

    /**
     * Creates data provider instance with search query applied
     *
     * @param array $params
     *
     * @return ActiveDataProvider
     */
    public function search($params)
    {
        $dbEmployee = \Yii::$app->has('dbEmployee') ? \Yii::$app->get('dbEmployee') : null;
        $dbName = 'face_detection_ice_emp';
        if ($dbEmployee && !empty($dbEmployee->dsn) && preg_match('/dbname=([^;]+)/', $dbEmployee->dsn, $matches)) {
            $dbName = $matches[1];
        }

        $query = Template::find()
            ->joinWith('product')
            ->leftJoin(['tm' => 'template_mapping'], 'tm.template_id = template.id AND tm.is_delete = 0')
            ->leftJoin(['pc' => 'category'], 'pc.id = tm.category_id')
            ->leftJoin($dbName . '.template_workflows tw', 'tw.template_id = template.id')
            ->where(['template.is_delete' => 0])
            ->with(['mapping.categoryname'])
            ->distinct();

        // add conditions that should always apply here

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort' => [
                'defaultOrder' => ['created_at' => SORT_DESC], 
            ],
        ]);

        $this->load($params);

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }

        // grid filtering conditions
        $query->andFilterWhere([
            'id' => $this->id,
            'category_id' => $this->category_id,
            'subcategory_id' => $this->subcategory_id,
            'language_id' => $this->language_id,
            'template_name' => $this->template_name,
            'is_delete' => $this->is_delete,
            'created_at' => $this->created_at,
            'updated' => $this->updated,
        ]);

        //$query->andFilterWhere(['like', 'images', $this->images]);
        $query
            ->andFilterWhere(['like', 'template_name', $this->template_name])
            ->andFilterWhere(['like', 'product.title', $this->title])
            ->andFilterWhere(['like', 'product.description', $this->description])
            ->andFilterWhere(['like', 'product.caption', $this->caption])
            ->andFilterWhere(['like', 'product.meta_title', $this->meta_title])
            ->andFilterWhere(['like', 'product.meta_description', $this->meta_description])
            ->andFilterWhere(['like', 'product.keywords', $this->keywords])
            ->andFilterWhere(['like', 'product.alt', $this->alt])
            ->andFilterWhere(['like', 'product.tag', $this->tag])
            ->andFilterWhere(['like', 'product.images', $this->images])
            ->andFilterWhere(['like', 'product.show_image', $this->show_image]);

        if ($this->product_id !== null && $this->product_id !== '') {
            $query->andFilterWhere(['like', 'product.id', ltrim((string) $this->product_id, '#')]);
        }

        if ($this->template_details !== null && $this->template_details !== '') {
            $query->andFilterWhere([
                'or',
                ['like', 'product.description', $this->template_details],
                ['like', 'product.caption', $this->template_details],
                ['like', 'product.meta_title', $this->template_details],
                ['like', 'product.meta_description', $this->template_details],
                ['like', 'product.keywords', $this->template_details],
                ['like', 'product.alt', $this->template_details],
                ['like', 'product.tag', $this->template_details],
            ]);
        }

        if ($this->page_url !== null && $this->page_url !== '') {
            $pageUrl = $this->pageUrlSearchParts($this->page_url);

            if ($pageUrl['product_id']) {
                $query->andFilterWhere(['like', 'product.id', ltrim($pageUrl['product_id'], '#')]);
            } else {
                $conditions = ['or'];

                foreach ($this->spacedAndUnderscored($pageUrl['raw']) as $term) {
                    $conditions[] = ['like', 'product.id', ltrim($term, '#')];
                    $conditions[] = ['like', 'product.title', $term];
                    $conditions[] = ['like', 'template.template_name', $term];
                    $conditions[] = ['like', 'pc.category_name', $term];
                }

                if ($pageUrl['category']) {
                    foreach ($this->spacedAndUnderscored($pageUrl['category']) as $term) {
                        $conditions[] = ['like', 'pc.category_name', $term];
                    }
                }

                if ($pageUrl['title']) {
                    foreach ($this->spacedAndUnderscored($pageUrl['title']) as $term) {
                        $conditions[] = ['like', 'product.title', $term];
                        $conditions[] = ['like', 'template.template_name', $term];
                    }
                }

                $query->andWhere($conditions);
            }
        }


        if ($this->status !== null && $this->status !== '') {
            if ($this->status === 'verified') {
                $query->andWhere([
                    'or',
                    ['tw.status' => 'verified'],
                    ['tw.status' => null]
                ]);
            } else {
                $query->andFilterWhere(['tw.status' => $this->status]);
            }
        }

        return $dataProvider;
    }
}
