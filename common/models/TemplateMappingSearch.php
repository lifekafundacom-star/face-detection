<?php

namespace common\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use common\models\TemplateMapping;

/**
 * TemplateMappingSearch represents the model behind the search form of `common\models\TemplateMapping`.
 */
class TemplateMappingSearch extends TemplateMapping
{
    public $title;
    public $page_url;
    public $image_unique_no;

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
                'is_route_only' => stripos($decodedValue, 'site/create') !== false,
            ];
        }

        return [
            'raw' => $decodedValue,
            'category' => isset($segments[$createcardIndex + 1]) ? $segments[$createcardIndex + 1] : null,
            'product_id' => isset($segments[$createcardIndex + 2]) ? $segments[$createcardIndex + 2] : null,
            'title' => isset($segments[$createcardIndex + 3]) ? $segments[$createcardIndex + 3] : null,
            'is_route_only' => !isset($segments[$createcardIndex + 1]),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id', 'is_delete', 'featured_section'], 'integer'],
            [['created_at', 'updated_at', 'template_id', 'category_id', 'subcategory_id', 'publish_type', 'scheduled_at', 'title', 'page_url', 'image_unique_no'], 'safe'],
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
        $query = TemplateMapping::find()
            ->joinWith(['categoryname', 'subcategoryname', 'templatename', 'products'])
            ->where(['template_mapping.is_delete' => 0])
            ->orderBy(['template_mapping.id' => SORT_DESC])
            ->distinct();

        // add conditions that should always apply here

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort' => [
                'defaultOrder' => ['id' => SORT_DESC],
                'attributes' => [
                    'id' => [
                        'asc' => ['template_mapping.id' => SORT_ASC],
                        'desc' => ['template_mapping.id' => SORT_DESC],
                    ],
                    'created_at' => [
                        'asc' => ['template_mapping.created_at' => SORT_ASC],
                        'desc' => ['template_mapping.created_at' => SORT_DESC],
                    ],
                    'updated_at' => [
                        'asc' => ['template_mapping.updated_at' => SORT_ASC],
                        'desc' => ['template_mapping.updated_at' => SORT_DESC],
                    ],
                    'scheduled_at' => [
                        'asc' => ['template_mapping.scheduled_at' => SORT_ASC],
                        'desc' => ['template_mapping.scheduled_at' => SORT_DESC],
                    ],
                ],
            ],
        ]);

        $this->load($params);
        $hasPublishTypeFilter = isset($params['TemplateMappingSearch']['publish_type']) && $params['TemplateMappingSearch']['publish_type'] !== '';

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }

        if (!$hasPublishTypeFilter) {
            $this->publish_type = null;
        }

        // grid filtering conditions
        $query->andFilterWhere([
            'id' => $this->id,
            'is_delete' => $this->is_delete,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'publish_type' => $this->publish_type,
            'scheduled_at' => $this->scheduled_at,
            'template_mapping.featured_section' => $this->featured_section,
        ]);

        if ($this->template_id !== null && $this->template_id !== '') {
            $templateTerms = $this->spacedAndUnderscored($this->template_id);
            $query->andWhere([
                'or',
                ['like', 'template.template_name', $templateTerms[0]],
                ['like', 'template.template_name', $templateTerms[1]],
                ['like', 'template.template_name', $templateTerms[2]],
            ]);
        }

        if ($this->category_id !== null && $this->category_id !== '') {
            $categoryTerms = $this->spacedAndUnderscored($this->category_id);
            $query->andWhere([
                'or',
                ['like', 'category.category_name', $categoryTerms[0]],
                ['like', 'category.category_name', $categoryTerms[1]],
                ['like', 'category.category_name', $categoryTerms[2]],
            ]);
        }

        if ($this->subcategory_id !== null && $this->subcategory_id !== '') {
            $subcategoryTerms = $this->spacedAndUnderscored($this->subcategory_id);
            $query->andWhere([
                'or',
                ['like', 'subcategory.subcategory_name', $subcategoryTerms[0]],
                ['like', 'subcategory.subcategory_name', $subcategoryTerms[1]],
                ['like', 'subcategory.subcategory_name', $subcategoryTerms[2]],
            ]);
        }

        if ($this->title !== null && $this->title !== '') {
            $titleTerms = $this->spacedAndUnderscored($this->title);
            $query->andWhere([
                'or',
                ['like', 'product.title', $titleTerms[0]],
                ['like', 'product.title', $titleTerms[1]],
                ['like', 'product.title', $titleTerms[2]],
            ]);
        }

        if ($this->image_unique_no !== null && $this->image_unique_no !== '') {
            $imageUniqueNo = ltrim(trim($this->image_unique_no), '#');
            $query->andFilterWhere(['like', 'product.id', $imageUniqueNo]);
        }

        if ($this->page_url !== null && $this->page_url !== '') {
            $pageUrl = $this->pageUrlSearchParts($this->page_url);

            if (!$pageUrl['is_route_only']) {
                if ($pageUrl['product_id']) {
                    $query->andFilterWhere(['like', 'product.id', ltrim($pageUrl['product_id'], '#')]);
                } else {
                    $conditions = ['or'];

                    foreach ($this->spacedAndUnderscored($pageUrl['raw']) as $term) {
                        $conditions[] = ['like', 'product.id', ltrim($term, '#')];
                        $conditions[] = ['like', 'product.title', $term];
                        $conditions[] = ['like', 'product.item_name', $term];
                        $conditions[] = ['like', 'category.category_name', $term];
                    }

                    if ($pageUrl['category']) {
                        foreach ($this->spacedAndUnderscored($pageUrl['category']) as $term) {
                            $conditions[] = ['like', 'category.category_name', $term];
                        }
                    }

                    if ($pageUrl['title']) {
                        foreach ($this->spacedAndUnderscored($pageUrl['title']) as $term) {
                            $conditions[] = ['like', 'product.title', $term];
                            $conditions[] = ['like', 'product.item_name', $term];
                        }
                    }

                    $query->andFilterWhere($conditions);
                }
            }
        }


        return $dataProvider;
    }
}
