<?php

namespace backend\controllers;

use Yii;
use yii\helpers\Html;
use common\models\Language;
use common\models\Product;
use common\models\ProductSearch;
use common\models\Template;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\helpers\ArrayHelper;
use common\models\TemperaryProduct;
use yii\web\UploadedFile;
use yii\web\Response;
use common\components\WorkPointService;


/**
 * ProductController implements the CRUD actions for Product model.
 */
class ProductController extends Controller
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
     * Lists all Product models.
     *
     * @return string
     */
    public function actionIndex()
    {
        $searchModel = new ProductSearch();
        $dataProvider = $searchModel->search($this->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays a single Product model.
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
     * Creates a new Product model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return string|\yii\web\Response
     */
    public function actionCreate()
    {
        $model = new Product();

        $template_name = ArrayHelper::map(Template::find()->where(['is_delete' => 0])->orderBy(['id' => SORT_DESC])->all(), 'id', 'template_name');
        $language_name = ArrayHelper::map(Language::find()->where(['is_delete' => 0])->all(), 'id', 'language');

        if ($this->request->isPost) {
            $template_id = Yii::$app->request->post()['Product']['template_id'];
            $language_id = Yii::$app->request->post()['Product']['language_id'];
            $workflow = (new WorkPointService())->workflowForTemplate((int)$template_id);
            if ($workflow && ($workflow['status'] ?? '') !== 'verified') {
                Yii::$app->session->setFlash('error', 'Template must complete content entry and verification before product creation.');
                return $this->redirect(['template/update', 'id' => $template_id, 'stage' => $workflow['status'] === 'content_pending' ? 2 : 3]);
            }
            $product_model = Product::find()->where(['template_id' => $template_id, 'language_id' => $language_id])->one();
            $temp_model = TemperaryProduct::find()->where(['language_id' => $language_id, 'template_id' => $template_id, 'is_delete' => 0])->andWhere(['!=', 'item_details', ''])->orderBy(['id' => SORT_DESC])->one();
            $temp_model1 = TemperaryProduct::find()->where(['language_id' => $language_id, 'template_id' => $template_id, 'is_delete' => 0])->andWhere(['!=', 'image_details', ''])->orderBy(['id' => SORT_DESC])->one();

            if ($temp_model && $temp_model1) {
                $text_details = json_decode($temp_model->item_details);
                $img_details = json_decode($temp_model1->image_details);

                $last_key = array_key_last($img_details);
                $key = $last_key + 1;
                foreach ($text_details as $t) {
                    $img_details[$key] = $t;
                    $key++;
                }
                $product_details = json_encode($img_details);
                $product_model->item_name = $product_details;
                if ($product_model->save()) {
                    TemperaryProduct::updateAll(['is_delete' => 1], 'is_delete = 0');
                }
            } else if ($temp_model) {
                $product_model->item_name = $temp_model->item_details;
                if ($product_model->save()) {
                    TemperaryProduct::updateAll(['is_delete' => 1], 'is_delete = 0');
                }
            } else if ($temp_model1) {
                $product_model->item_name = $temp_model1->image_details;
                if ($product_model->save()) {
                    TemperaryProduct::updateAll(['is_delete' => 1], 'is_delete = 0');
                }
            }

            $product_model->save();

            return $this->redirect(['template-mapping/create']);
        } else {
            $model->loadDefaultValues();
        }

        return $this->render('create', [
            'model' => $model,
            'template_name' => $template_name,
            'language_name' => $language_name,

        ]);
    }

    /**
     * Updates an existing Product model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param int $id ID
     * @return string|\yii\web\Response
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);
        $template_name = ArrayHelper::map(Template::find()->where(['is_delete' => 0])->all(), 'id', 'template_name');
        $language_name = ArrayHelper::map(Language::find()->where(['is_delete' => 0])->all(), 'id', 'language');

        $templates = Template::find()->where(['id' => $model->template_id])->one();
        if ($model->images) {
            $imageData = $model->images;
        } else {
            $imageData = null;
        }
        $data = [];

        $jsonString = $model->item_name;
        $data = json_decode($jsonString, true);

        if ($this->request->isPost) {

            
            $template_id = Yii::$app->request->post()['Product']['template_id'];
            $language_id = Yii::$app->request->post()['Product']['language_id'];
            $product_model = Product::find()->where(['template_id' => $template_id, 'language_id' => $language_id, 'id' => $id])->one();
            $mergedItemName = Yii::$app->request->post('merged_item_name');
            if ($mergedItemName && json_decode($mergedItemName, true) !== null) {
                $product_model->item_name = $mergedItemName;
                if ($product_model->save()) {
                    TemperaryProduct::updateAll(['is_delete' => 1], 'is_delete = 0');
                }
                return $this->redirect(['index']);
            }
            $temp_model = TemperaryProduct::find()->where(['language_id' => $language_id, 'template_id' => $template_id, 'is_delete' => 0])->andWhere(['!=', 'item_details', ''])->orderBy(['id' => SORT_DESC])->one();
            $temp_model1 = TemperaryProduct::find()->where(['language_id' => $language_id, 'template_id' => $template_id, 'is_delete' => 0])->andWhere(['!=', 'image_details', ''])->orderBy(['id' => SORT_DESC])->one();

            // TemperaryProduct::deleteAll(['language_id' => $language_id, 'template_id' => $template_id]);

            if ($temp_model && $temp_model1) {
                $text_details = json_decode($temp_model->item_details);
                $img_details = json_decode($temp_model1->image_details);

                $last_key = array_key_last($img_details);
                $key = $last_key + 1;
                foreach ($text_details as $t) {
                    $img_details[$key] = $t;
                    $key++;
                }
                $product_details = json_encode($img_details);
                $product_model->item_name = $product_details;
                if ($product_model->save()) {
                    TemperaryProduct::updateAll(['is_delete' => 1], 'is_delete = 0');
                }
            } else if ($temp_model) {
                $product_model->item_name = $temp_model->item_details;
                if ($product_model->save()) {
                    TemperaryProduct::updateAll(['is_delete' => 1], 'is_delete = 0');
                }
            } else if ($temp_model1) {
                $product_model->item_name = $temp_model1->image_details;
                if ($product_model->save()) {
                    TemperaryProduct::updateAll(['is_delete' => 1], 'is_delete = 0');
                }
            }
            $product_model->save();
            return $this->redirect(['index']);
        } else {
            $model->loadDefaultValues();
        }


        return $this->render('update', [
            'model' => $model,
            'template_name' => $template_name,
            'language_name' => $language_name,
            'imageData' => $imageData,
            'data' => $data,
            'templates' => $templates,
        ]);
    }

    /**
     * Deletes an existing Product model.
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
     * Finds the Product model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param int $id ID
     * @return Product the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Product::findOne(['id' => $id, 'is_delete' => 0])) !== null) {
            return $model;
        }

        throw new NotFoundHttpException(Yii::t('app', 'The requested page does not exist.'));
    }

    public function actionImages()
    {
        $filename = [];
        $template = Yii::$app->request->post('template_id');
        $language = Yii::$app->request->post('language_id');
        $image = Product::find()->where(['template_id' => $template, 'language_id' => $language])->one();
        $templates = Template::find()->where(['id' => $template])->one();


        if ($image) {
            $filename['img'] = $image->images;
            $filename['image_type'] = $templates->image_type;
        }


        $fileDetails = json_encode($filename);
        return $fileDetails;
    }



    public function actionItems()
    {

        $model = new TemperaryProduct();

        $template = Yii::$app->request->post('template_id');
        $language = Yii::$app->request->post('language_id');
        $list = Yii::$app->request->post('appendedList');

        $model->template_id = $template;
        $model->language_id = $language;
        $model->item_details = $list;
        $model->save();


        if ($model->save()) {
            return 0;
        } else {
            return 1;
        }
    }


    public function actionUpdateitems()
    {

        $model = new TemperaryProduct();

        $template = Yii::$app->request->post('template_id');
        $language = Yii::$app->request->post('language_id');
        $list = Yii::$app->request->post('updateList');



        $model->template_id = $template;
        $model->language_id = $language;
        $model->item_details = $list;

        $textData = json_decode($list, true);



        $imgary = [];
        $txtary = [];

        if ($model->save()) {
            foreach ($textData as $key => $td) {
                if ($td['type'] == 'image') {
                    $imgary[] = $td;
                } else {
                    $txtary[] = $td;
                }
            }

            if ($imgary) {
                $temp_model1 = TemperaryProduct::find()->where(['language_id' => $language, 'template_id' => $template, 'is_delete' => 0])->andWhere(['!=', 'image_details', ''])->orderBy(['id' => SORT_DESC])->one();
                if (!$temp_model1) {
                    $model = new TemperaryProduct();
                    $model->template_id = $template;
                    $model->language_id = $language;
                    $model->image_details = json_encode($imgary);
                    $model->save();
                } else {

                    $model = new TemperaryProduct();
                    $model->template_id = $template;
                    $model->language_id = $language;
                    $model->image_details = $temp_model1->image_details;
                    $model->save();
                }

                $model = new TemperaryProduct();
                $model->template_id = $template;
                $model->language_id = $language;
                $model->item_details = json_encode($txtary);
                $model->save();
            }




            return 0;
        } else {
            return 1;
        }
    }

    public function actionUpdateimageplace()
    {

        $model = new TemperaryProduct();

        $template = Yii::$app->request->post('template_id');
        $language = Yii::$app->request->post('language_id');
        $img = Yii::$app->request->post('updateImage');


        $model->template_id = $template;
        $model->language_id = $language;
        $model->image_details = $img;

        $imageData = json_decode($img, true);

        $imgary = [];
        $txtary = [];

        if ($model->save()) {
            foreach ($imageData as $key => $id) {
                if ($id['type'] == 'text') {
                    $txtary[] = $id;
                } else {
                    $imgary[] = $id;
                }
            }

            if ($txtary) {

                $temp_model = TemperaryProduct::find()->where(['language_id' => $language, 'template_id' => $template, 'is_delete' => 0])->andWhere(['!=', 'item_details', ''])->orderBy(['id' => SORT_DESC])->one();

                if (!$temp_model) {
                    $model = new TemperaryProduct();
                    $model->template_id = $template;
                    $model->language_id = $language;
                    $model->item_details = json_encode($txtary);
                    $model->save();
                } else {
                    $model = new TemperaryProduct();
                    $model->template_id = $template;
                    $model->language_id = $language;
                    $model->item_details = $temp_model->item_details;
                    $model->save();
                }
                $model = new TemperaryProduct();
                $model->template_id = $template;
                $model->language_id = $language;
                $model->image_details = json_encode($imgary);
                $model->save();
            }

            return 0;
        } else {
            return 1;
        }
    }


    public function actionImageplace()
    {
        $model = new TemperaryProduct();
        $template = Yii::$app->request->post('template_id');
        $language = Yii::$app->request->post('language_id');
        $img = Yii::$app->request->post('appendedImage');

        $model->template_id = $template;
        $model->language_id = $language;
        $model->image_details = $img;
        $model->save();

        if ($model->save()) {
            return 0;
        } else {
            return 1;
        }
    }

    public function actionImagetype()
    {
        $template = Yii::$app->request->post('template_id');
        $templates = Template::find()->where(['id' => $template])->one();

        if ($templates) {
            $imagetype = $templates->image_type;
        }
        return $imagetype;
    }

    public function actionUploadLayerImage()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $file = UploadedFile::getInstanceByName('layer_image');
        $reuse = Yii::$app->request->post('save_for_reuse') === '1';

        if (!$file) {
            return ['success' => false, 'message' => 'No image selected'];
        }

        $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'svg'];
        $extension = strtolower($file->extension);
        if (!in_array($extension, $allowedExtensions, true)) {
            return ['success' => false, 'message' => 'Only JPG, PNG, WEBP, GIF, and SVG images are allowed'];
        }

        $storagePath = Yii::getAlias('@storage') . '/images';
        if (!is_dir($storagePath)) {
            mkdir($storagePath, 0775, true);
        }

        $baseName = preg_replace('/[^a-z0-9_-]+/i', '-', pathinfo($file->name, PATHINFO_FILENAME));
        $baseName = trim($baseName, '-') ?: 'image';
        $prefix = $reuse ? 'reusable-layer' : 'layer-upload';
        $filename = $prefix . '-' . time() . '-' . mt_rand(1000, 9999) . '-' . $baseName . '.' . $extension;
        $targetPath = $storagePath . '/' . $filename;

        if (!$file->saveAs($targetPath)) {
            return ['success' => false, 'message' => 'Image upload failed'];
        }

        return [
            'success' => true,
            'filename' => $filename,
            'url' => Yii::getAlias('@storageUrl') . '/images/' . $filename,
            'reusable' => $reuse,
        ];
    }

    public function actionReusableLayerImages()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $storagePath = Yii::getAlias('@storage') . '/images';
        $files = [];

        foreach (glob($storagePath . '/reusable-layer-*.*') ?: [] as $path) {
            if (!is_file($path)) {
                continue;
            }

            $filename = basename($path);
            $files[] = [
                'filename' => $filename,
                'url' => Yii::getAlias('@storageUrl') . '/images/' . $filename,
                'label' => preg_replace('/^reusable-layer-\d+-\d+-/i', '', $filename),
            ];
        }

        usort($files, function ($a, $b) use ($storagePath) {
            return filemtime($storagePath . '/' . $b['filename']) <=> filemtime($storagePath . '/' . $a['filename']);
        });

        return ['success' => true, 'files' => array_slice($files, 0, 100)];
    }

    public function actionAutotextfetch()
    {
        $template = Yii::$app->request->post('template_id');
        $language = Yii::$app->request->post('language_id');
        $textId = Yii::$app->request->post('text_id');

        $temperary_product = TemperaryProduct::find()->where(['language_id' => $language, 'template_id' => $template,])->all();
        $data = [];
        if ($temperary_product) {
            foreach ($temperary_product as $key => $value) {
                $item = $value->item_details;           
                $data = json_decode($item, true);
            }
        }

        return json_encode($data);
    }

    public function actionAutofetchimage()
    {
        $template = Yii::$app->request->post('template_id');
        $language = Yii::$app->request->post('language_id');
        $imgId = Yii::$app->request->post('img_id');

        $temperary_product = TemperaryProduct::find()->where(['language_id' => $language, 'template_id' => $template,])->all();

        if ($temperary_product) {
            foreach ($temperary_product as $key => $value) {
                $item = $value->image_details;
                $data = [];
                $data = json_decode($item, true);
            }
        }

        return json_encode($data);
    }
}
