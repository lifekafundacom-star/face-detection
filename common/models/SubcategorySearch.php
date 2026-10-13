<?php

namespace common\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use common\models\Subcategory;

/**
 * SubcategorySearch represents the model behind the search form of `common\models\Subcategory`.
 */
class SubcategorySearch extends Subcategory
{
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id', 'is_delete'], 'integer'],
            [['subcategory_name', 'category_id','created_at', 'updated_at','meta_title','meta_description','keywords', 'whatsapp'], 'safe'],
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
        $query = Subcategory::find()->joinWith(['categoryname'])->where(['subcategory.is_delete' => 0]);

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
            'is_delete' => $this->is_delete,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ]);

        $query->andFilterWhere(['like', 'subcategory_name', $this->subcategory_name])
        ->andFilterWhere(['like', 'category.category_name', $this->category_id])
        ->andFilterWhere(['like', 'subcategory.meta_title', $this->meta_title])
        ->andFilterWhere(['like', 'subcategory.meta_description', $this->meta_description])
        ->andFilterWhere(['like', 'subcategory.keywords', $this->keywords])
        ->andFilterWhere(['like', 'subcategory.whatsapp', $this->whatsapp]);


        return $dataProvider;
    }
}
