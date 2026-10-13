<?php

namespace common\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use common\models\Category;

/**
 * CategorySearch represents the model behind the search form of `common\models\Category`.
 */
class CategorySearch extends Category
{
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id', 'is_delete'], 'integer'],
            [['category_name', 'credited_at', 'updated_at','meta_title','meta_description','keywords', 'tag', 'whatsapp'], 'safe'],
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
        $query = Category::find()->where(['is_delete' => 0]);

        // add conditions that should always apply here

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort' => [
                'defaultOrder' => ['credited_at' => SORT_DESC], 
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
            'category_icon' => $this->category_icon,
            'is_delete' => $this->is_delete,
            'credited_at' => $this->credited_at,
            'updated_at' => $this->updated_at,
        ]);

        $query->andFilterWhere(['like', 'category_name', $this->category_name])
        ->andFilterWhere(['like', 'meta_title', $this->meta_title])
        ->andFilterWhere(['like', 'meta_description', $this->meta_description])
        ->andFilterWhere(['like', 'keywords', $this->keywords])
        ->andFilterWhere(['like', 'tag', $this->tag])
        ->andFilterWhere(['like', 'whatsapp', $this->whatsapp]);

        return $dataProvider;
    }
}
