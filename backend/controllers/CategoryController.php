<?php

namespace backend\controllers;

use Yii;
use common\models\Category;
use common\models\CategorySearch;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\web\UploadedFile;

/**
 * CategoryController implements the CRUD actions for Category model.
 */
class CategoryController extends Controller
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
     * Lists all Category models.
     *
     * @return string
     */
    public function actionIndex()
    {
        $searchModel = new CategorySearch();
        $dataProvider = $searchModel->search($this->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays a single Category model.
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
     * Creates a new Category model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return string|\yii\web\Response
     */
    public function actionCreate()
    {
        $model = new Category();

        if ($this->request->isPost) {
            if ($model->load($this->request->post())) {
                $category = Yii::$app->request->post()['Category']['category_name'];
                $category_description = Yii::$app->request->post()['Category']['description'];
                $category_name = str_replace(' ', '_', $category);
                $category_icon = UploadedFile::getInstance($model, 'category_icon');
                if ($category_icon) {
                    $cicon = time() . date('Y-m-d') . '.' . $category_icon->extension;
                    $cicon_folder = Yii::getAlias('@storage') . '/icon/' . $cicon;
                    $category_icon->saveAs($cicon_folder);
                    $model->category_icon = (string)$cicon;
                }
                $meta_title = Yii::$app->request->post()['Category']['meta_title'];
                $meta_description = Yii::$app->request->post()['Category']['meta_description'];
                $keywords = Yii::$app->request->post()['Category']['keywords'];
                $tag = Yii::$app->request->post()['Category']['tag'];
                $whatsapp = Yii::$app->request->post()['Category']['whatsapp'] ?? '';

                $model->category_name = $category_name;
                $model->description = $category_description;
                $model->meta_title = $meta_title;
                $model->meta_description = $meta_description;
                $model->keywords = $keywords;
                $model->tag = $tag;
                $model->whatsapp = $whatsapp;
                
                if (!$model->save()) {
                    print_r($model->getErrors());die;
                }

                return $this->redirect(['index']);
            }
        } else {
            $model->loadDefaultValues();
        }

        return $this->render('create', [
            'model' => $model,
        ]);
    }

    /**
     * Updates an existing Category model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param int $id ID
     * @return string|\yii\web\Response
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        $category_name = Category::find()->where(['is_delete' => 0, 'id' => $id])-> one();
        $catName = str_replace('_', ' ', $category_name->category_name);
        if ($this->request->isPost) {

            $category = Yii::$app->request->post()['Category']['category_name'];
            $category_description = Yii::$app->request->post()['Category']['description'];
            $category_name = str_replace(' ', '_', $category);

            $category_icon = UploadedFile::getInstance($model, 'category_icon');
            if ($category_icon) {
                $cicon = time() . date('Y-m-d') . '.' . $category_icon->extension;
                $cicon_folder = Yii::getAlias('@storage') . '/icon/' . $cicon;
                $category_icon->saveAs($cicon_folder);
                $model->category_icon = $cicon;
            }
            $meta_title = Yii::$app->request->post()['Category']['meta_title'];
            $meta_description = Yii::$app->request->post()['Category']['meta_description'];
            $keywords = Yii::$app->request->post()['Category']['keywords'];
            $tag = Yii::$app->request->post()['Category']['tag'];
            $whatsapp = Yii::$app->request->post()['Category']['whatsapp'] ?? '';

            $model->category_name = $category_name;
            $model->description = $category_description;
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
            'catName' => $catName,
        ]);
    }

    /**
     * Deletes an existing Category model.
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
     * Finds the Category model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param int $id ID
     * @return Category the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Category::findOne(['id' => $id, 'is_delete' => 0])) !== null) {
            return $model;
        }

        throw new NotFoundHttpException(Yii::t('app', 'The requested page does not exist.'));
    }
}
