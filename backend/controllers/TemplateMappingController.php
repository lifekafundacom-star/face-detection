<?php

namespace backend\controllers;

use Yii;
use common\models\TemplateMapping;
use common\models\TemplateMappingSearch;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\helpers\ArrayHelper;
use common\models\Template;
use common\models\Category;
use common\models\Subcategory;

/**
 * TemplateMappingController implements the CRUD actions for TemplateMapping model.
 */
class TemplateMappingController extends Controller
{
    /**
     * @inheritDoc
     */
    public function behaviors()
    {
        return array_merge(
            parent::behaviors(),
            [
                'verbs' => [
                    'class' => VerbFilter::className(),
                    'actions' => [
                        'delete' => ['POST'],
                    ],
                ],
            ]
        );
    }

    /**
     * Lists all TemplateMapping models.
     *
     * @return string
     */
    public function actionIndex()
    {
        $searchModel = new TemplateMappingSearch();
        $dataProvider = $searchModel->search($this->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays a single TemplateMapping model.
     * @param int $id ID
     * @return string
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionView($id)
    {
        return $this->render('view', [
            'model' => $this->findModel($id),
        ]);
    }

    /**
     * Creates a new TemplateMapping model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return string|\yii\web\Response
     */
    public function actionCreate()
    {
        $model = new TemplateMapping();

        $template = ArrayHelper::map(Template::find()->where(['is_delete' => 0])->orderBy(['id' => SORT_DESC])->all(), 'id', 'template_name');

        $categoryData = Category::find()->where(['is_delete' => 0])->orderBy(['id' => SORT_DESC])->all();
        $category_name = ArrayHelper::map($categoryData, 'id', function ($category) {
            return str_replace('_', ' ', $category->category_name);
        });
        
        $subcategoryData = Subcategory::find()->where(['is_delete' => 0])->orderBy(['id' => SORT_DESC])->all();
        $subcategory_name = ArrayHelper::map($subcategoryData, 'id', function ($subcategory) {
            return str_replace('_', ' ', $subcategory->subcategory_name);
        });

        if ($this->request->isPost) {

            if ($model->load($this->request->post()) && $model->save()) {
                (new \common\components\WorkPointService())->recordTemplateMappingWork($model);
                return $this->redirect(['index']);
            }
        } else {
            $model->loadDefaultValues();
        }

        return $this->render('create', [
            'model' => $model,
            'template' => $template,
            'category_name' => $category_name,
            'subcategory_name' => $subcategory_name,
        ]);
    }

    /**
     * Updates an existing TemplateMapping model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param int $id ID
     * @return string|\yii\web\Response
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        $template = ArrayHelper::map(Template::find()->where(['is_delete' => 0])->orderBy(['id' => SORT_DESC])->all(), 'id', 'template_name');

        $categoryData = Category::find()->where(['is_delete' => 0])->orderBy(['id' => SORT_DESC])->all();
        $category_name = ArrayHelper::map($categoryData, 'id', function ($category) {
            return str_replace('_', ' ', $category->category_name);
        });
        
        $subcategoryData = Subcategory::find()->where(['is_delete' => 0])->orderBy(['id' => SORT_DESC])->all();
        $subcategory_name = ArrayHelper::map($subcategoryData, 'id', function ($subcategory) {
            return str_replace('_', ' ', $subcategory->subcategory_name);
        });

        if ($this->request->isPost) {

            if ($model->load($this->request->post()) && $model->save()) {
                return $this->redirect(['index']);
            }
        } else {
            $model->loadDefaultValues();
        }

        return $this->render('update', [
            'model' => $model,
            'template' => $template,
            'category_name' => $category_name,
            'subcategory_name' => $subcategory_name,
        ]);
    }

    /**
     * Deletes an existing TemplateMapping model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param int $id ID
     * @return \yii\web\Response
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionDelete($id)
    {
        $findModel = $this->findModel($id);
        $findModel->is_delete = 1;
        $findModel->save();

        return $this->redirect(['index']);
    }

    /**
     * Finds the TemplateMapping model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param int $id ID
     * @return TemplateMapping the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = TemplateMapping::findOne(['id' => $id, 'is_delete' => 0])) !== null) {
            return $model;
        }

        throw new NotFoundHttpException(Yii::t('app', 'The requested page does not exist.'));
    }

    public function actionCategory()
    {
        $category = Subcategory::find()->where(['category_id' => Yii::$app->request->post()['category_id']])->all();
        foreach ($category as $key => $value) {
            // $subcategory[$value->id] = $value->subcategory_name;
            $subcategory[$value->id] = str_replace('_', ' ', $value->subcategory_name);
        }
        return json_encode($subcategory);
    }
}
