<?php

namespace common\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use common\models\Product;

/**
 * ProductSearch represents the model behind the search form of `common\models\Product`.
 */
class ProductSearch extends Product
{
    public $page_url;

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

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id', 'is_delete'], 'integer'],
            [['images', 'item_name', 'created_at', 'updated_at', 'template_id', 'language_id', 'title', 'page_url'], 'safe'],
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
        $query = Product::find()
            ->joinWith(['language','template'])
            ->leftJoin(['tm' => 'template_mapping'], 'tm.template_id = product.template_id AND tm.is_delete = 0')
            ->leftJoin(['pc' => 'category'], 'pc.id = tm.category_id')
            ->where(['product.is_delete' => 0])
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
            'product.id' => $this->id,
            'product.is_delete' => $this->is_delete,
            'product.created_at' => $this->created_at,
            'product.updated_at' => $this->updated_at,
        ]);

        $query->andFilterWhere(['like', 'product.images', $this->images])
            ->andFilterWhere(['like', 'product.item_name', $this->item_name])
            ->andFilterWhere(['like', 'language.language', $this->language_id])
            ->andFilterWhere(['like', 'template.template_name', $this->template_id]);

        if ($this->title !== null && $this->title !== '') {
            $titleConditions = ['or'];
            foreach ($this->spacedAndUnderscored($this->title) as $term) {
                $titleConditions[] = ['like', 'product.title', $term];
                $titleConditions[] = ['like', 'product.item_name', $term];
            }
            $query->andWhere($titleConditions);
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
                    $conditions[] = ['like', 'product.item_name', $term];
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
                        $conditions[] = ['like', 'product.item_name', $term];
                    }
                }

                $query->andWhere($conditions);
            }
        }

        return $dataProvider;
    }
}
