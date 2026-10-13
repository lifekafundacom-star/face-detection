<?php

namespace backend\controllers;

use Yii;
use common\models\Category;
use common\models\Subcategory;
use common\models\SubcategorySearch;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\helpers\ArrayHelper;
use yii\web\UploadedFile;
/**
 * SubcategoryController implements the CRUD actions for Subcategory model.
 */
class SubcategoryController extends Controller
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
     * Lists all Subcategory models.
     *
     * @return string
     */
    public function actionIndex()
    {
        $searchModel = new SubcategorySearch();
        $dataProvider = $searchModel->search($this->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays a single Subcategory model.
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
     * Creates a new Subcategory model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return string|\yii\web\Response
     */
    public function actionCreate()
    {
        $model = new Subcategory();
        $categoryData = Category::find()->where(['is_delete' => 0])->orderBy(['id' => SORT_DESC])->all();
        $category_name = ArrayHelper::map($categoryData, 'id', function ($category) {
            return str_replace('_', ' ', $category->category_name);
        });
        if ($this->request->isPost) {
            if ($model->load($this->request->post())) {

                $Subcategory = Yii::$app->request->post()['Subcategory']['subcategory_name'];
                $Subcategory_name = str_replace(' ', '_', $Subcategory);
                $Subcategory_description = Yii::$app->request->post()['Subcategory']['description'];
                $meta_title = Yii::$app->request->post()['Subcategory']['meta_title'];
                $meta_description = Yii::$app->request->post()['Subcategory']['meta_description'];
                $keywords = Yii::$app->request->post()['Subcategory']['keywords'];
                $tag = Yii::$app->request->post()['Subcategory']['tag'];
                $whatsapp = Yii::$app->request->post()['Subcategory']['whatsapp'] ?? '';

                $subcategory_icon = UploadedFile::getInstance($model, 'subcategory_icon');
                if ($subcategory_icon) {
                    $scicon = time() . date('Y-m-d') . '.' . $subcategory_icon->extension;
                    $scicon_folder = Yii::getAlias('@storage') . '/icon/' . $scicon;
                    $subcategory_icon->saveAs($scicon_folder);
                    $model->subcategory_icon = $scicon;
                }

                $model->subcategory_name = $Subcategory_name;
                $model->description = $Subcategory_description;
                $model->meta_title = $meta_title;
                $model->meta_description = $meta_description;
                $model->keywords = $keywords;
                $model->tag = $tag;
                $model->whatsapp = $whatsapp;
                $model->save();
                return $this->redirect(['index']);
            }
        } else {
            $model->loadDefaultValues();
        }

        return $this->render('create', [
            'model' => $model,
            'category_name' => $category_name,
        ]);
    }

    /**
     * Updates an existing Subcategory model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param int $id ID
     * @return string|\yii\web\Response
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);
        $categoryData = Category::find()->where(['is_delete' => 0])->orderBy(['id' => SORT_DESC])->all();
        $category_name = ArrayHelper::map($categoryData, 'id', function ($category) {
            return str_replace('_', ' ', $category->category_name);
        });
        $sc = Subcategory::find()->where(['is_delete' => 0, 'id' => $id])->one();
        $sc_name  = str_replace('_', ' ', $sc->subcategory_name);
        if ($this->request->isPost) {

            $category_id = Yii::$app->request->post()['Subcategory']['category_id'];
            $Subcategory = Yii::$app->request->post()['Subcategory']['subcategory_name'];
            $Subcategory_description = Yii::$app->request->post()['Subcategory']['description'];
            $meta_title = Yii::$app->request->post()['Subcategory']['meta_title'];
            $meta_description = Yii::$app->request->post()['Subcategory']['meta_description'];
            $keywords = Yii::$app->request->post()['Subcategory']['keywords'];
            $tag = Yii::$app->request->post()['Subcategory']['tag'];
            $whatsapp = Yii::$app->request->post()['Subcategory']['whatsapp'] ?? '';

            $subcategory_icon = UploadedFile::getInstance($model, 'subcategory_icon');
                if ($subcategory_icon) {
                    $scicon = time() . date('Y-m-d') . '.' . $subcategory_icon->extension;
                    $scicon_folder = Yii::getAlias('@storage') . '/icon/' . $scicon;
                    $subcategory_icon->saveAs($scicon_folder);
                    $model->subcategory_icon = $scicon;
                }

            $Subcategory_name = str_replace(' ', '_', $Subcategory);

            $model->category_id = $category_id;
            $model->subcategory_name = $Subcategory_name;
            $model->description = $Subcategory_description;
            $model->meta_title = $meta_title;
            $model->meta_description = $meta_description;
            $model->keywords = $keywords;
            $model->tag = $tag;
            $model->whatsapp = $whatsapp;

            $model->save();
            return $this->redirect(['index']);
        }

        return $this->render('update', [
            'model' => $model,
            'category_name' => $category_name,
            'sc_name' => $sc_name,
        ]);
    }

    /**
     * Deletes an existing Subcategory model.
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
     * Finds the Subcategory model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param int $id ID
     * @return Subcategory the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Subcategory::findOne(['id' => $id, 'is_delete' => 0])) !== null) {
            return $model;
        }

        throw new NotFoundHttpException(Yii::t('app', 'The requested page does not exist.'));
    }
}
