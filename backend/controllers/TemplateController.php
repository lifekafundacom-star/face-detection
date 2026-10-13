<?php

namespace backend\controllers;

use Yii;
use common\models\Template;
use common\models\TemplateSearch;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\helpers\ArrayHelper;
use yii\web\UploadedFile;
use common\models\Category;
use common\models\Subcategory;
use common\models\Language;
use common\models\Product;
use common\components\WorkPointService;

/**
 * TemplateController implements the CRUD actions for Template model.
 */
class TemplateController extends Controller
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
     * Lists all Template models.
     *
     * @return string
     */
    public function actionIndex()
    {
        $searchModel = new TemplateSearch();
        $dataProvider = $searchModel->search($this->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    public function actionContentWriting()
    {
        $searchModel = new TemplateSearch();
        $dataProvider = $searchModel->search($this->request->queryParams);

        // Force query to show all templates that are NOT verified yet
        $dataProvider->query->andWhere(['in', 'tw.status', [
            'photo_pending',
            'content_pending',
            'verification_pending',
            'rejected'
        ]]);

        return $this->render('contentwriting', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays a single Template model.
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
     * Creates a new Template model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return string|\yii\web\Response
     */
    public function actionCreate()
    {
        $model = new Template();
        $product_model = new Product();
        $stage = 1;
        $workflow = null;


        $language = Language::find()->where(['is_delete' => 0])->all();

        if ($this->request->isPost) {
            $languages = Yii::$app->request->post('language', []);
            $selectedLanguageId = (int)reset($languages);
            $template_name = Yii::$app->request->post('Template')['template_name'] ?? '';
            $image_type = Yii::$app->request->post('Template')['image_type'] ?? 'square';

            if ($selectedLanguageId <= 0) {
                Yii::$app->session->setFlash('error', 'Please select language for template images.');
                return $this->redirect(['create']);
            }


            $model->template_name = $template_name;
            $model->image_type = $image_type;

            if ($model->save()) {
                $temp_img = UploadedFile::getInstances($product_model, 'images');
                $show_image = UploadedFile::getInstance($product_model, 'show_image');

                if (empty($temp_img) || !$show_image) {
                    Yii::$app->session->setFlash('error', 'Stage 1 requires template image and show image.');
                    return $this->redirect(['update', 'id' => $model->id, 'stage' => 1]);
                }

                $img = reset($temp_img);
                $product_model = new Product();
                $product_model->images = $this->saveTemplateUpload($img);
                $product_model->show_image = $this->saveTemplateUpload($show_image);
                $product_model->template_id = $model->id;
                $product_model->language_id = $selectedLanguageId;
                $product_model->is_delete = 1;
                $product_model->save(false);

                $service = new WorkPointService();
                $service->syncTemplateWorkflow($model, $product_model, 'content_pending', [
                    'photo_user_id' => Yii::$app->user->id,
                    'photo_completed_at' => new \yii\db\Expression('NOW()'),
                ]);
                $service->recordTemplateWork(WorkPointService::TEMPLATE_PHOTO_KEY, $model, $product_model, 'photo_upload');

                return $this->redirect(['update', 'id' => $model->id, 'stage' => 2]);
            }
        } else {
            $model->loadDefaultValues();
        }

        return $this->render('create', [
            'model' => $model,
            'language' => $language,
            'product_model' => $product_model,
            'stage' => $stage,
            'workflow' => $workflow,
            'canVerify' => false,
        ]);
    }

    /**
     * Updates an existing Template model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param int $id ID
     * @return string|\yii\web\Response
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);
        $service = new WorkPointService();
        $product_model = Product::find()
            ->where(['template_id' => $id])
            ->orderBy(['id' => SORT_ASC])
            ->one();
        if ($product_model === null) {
            $product_model = new Product();
            $product_model->template_id = $model->id;
        }
        $workflow = $service->workflowForTemplate((int) $model->id);
        $requestedStage = (int)Yii::$app->request->get('stage', 0);
        $stage = $requestedStage > 0 ? $requestedStage : $this->stageFromWorkflow($workflow);


        $language = Language::find()->where(['is_delete' => 0])->all();

        if ($this->request->isPost) {

            $languages = Yii::$app->request->post('language', []);
            $selectedLanguageId = (int)reset($languages);
            if ($selectedLanguageId <= 0 && $product_model) {
                $selectedLanguageId = (int)$product_model->language_id;
            }
            $postedStage = (int)Yii::$app->request->post('stage', $stage);
            $template_name = Yii::$app->request->post('Template')['template_name'] ?? $model->template_name;
            $image_type = Yii::$app->request->post('Template')['image_type'] ?? $model->image_type;

            $model->template_name = $template_name;
            $model->image_type = $image_type;
           
            if ($model->save()) {
                $targetProduct = $product_model;

                if ($targetProduct === null) {
                    $targetProduct = new Product();
                    $targetProduct->template_id = $model->id;
                }

                $product_model = $targetProduct;
                $product_model->template_id = $model->id;
                $product_model->language_id = $selectedLanguageId;
                $temp_img = UploadedFile::getInstances($product_model, 'images');
                $show_image = UploadedFile::getInstance($product_model, 'show_image');
                $imageChanged = !empty($temp_img) || $show_image !== null;

                if ($postedStage === 1 && (empty($temp_img) && !$product_model->images || !$show_image && !$product_model->show_image)) {
                    Yii::$app->session->setFlash('error', 'Stage 1 requires template image and show image.');
                    return $this->redirect(['update', 'id' => $model->id, 'stage' => 1]);
                }

                if (!empty($temp_img)) {
                    $img = reset($temp_img);
                    $product_model->images = $this->saveTemplateUpload($img);
                }

                if ($show_image) {  
                    $product_model->show_image = $this->saveTemplateUpload($show_image);
                }

                if ($product_model->hasAttribute('url_slug')) {
                    $isMapped = !$product_model->isNewRecord && \common\models\TemplateMapping::find()->where(['template_id' => $product_model->template_id, 'is_delete' => 0])->exists();
                    if (trim((string)$product_model->url_slug) === '' || !$isMapped) {
                        $template = $product_model->template;
                        if ($template && trim((string)$template->template_name) !== '') {
                            $product_model->url_slug = Product::makeTemplateSlug($template->template_name);
                        } else {
                            $product_model->url_slug = Product::makeUrlSlug($product_model->title ?: 'card');
                        }
                    }
                }

                if ($postedStage >= 2) {
                    $this->applyTemplateContent($product_model);
                }

                if ($postedStage < 3 && (!$workflow || ($workflow['status'] ?? '') !== 'verified')) {
                    $product_model->is_delete = 1;
                }

                if ($product_model->save()) {
                    if ($postedStage === 1) {
                        $nextStatus = $this->isTemplateContentComplete($product_model) ? 'verification_pending' : 'content_pending';
                        $workflowData = [
                            'photo_user_id' => Yii::$app->user->id,
                            'photo_completed_at' => new \yii\db\Expression('NOW()'),
                        ];
                        if ($nextStatus === 'verification_pending') {
                            $workflowData['content_user_id'] = $workflowData['content_user_id'] ?? ($workflow['content_user_id'] ?? Yii::$app->user->id);
                            $workflowData['content_completed_at'] = $workflow['content_completed_at'] ?? new \yii\db\Expression('NOW()');
                        }
                        $service->syncTemplateWorkflow($model, $product_model, $nextStatus, $workflowData);
                        $service->recordTemplateWork(WorkPointService::TEMPLATE_PHOTO_KEY, $model, $product_model, $imageChanged ? 'photo_update' : 'photo_confirm');

                        return $this->redirect(['update', 'id' => $model->id, 'stage' => $nextStatus === 'verification_pending' ? 3 : 2]);
                    }

                    if ($postedStage === 2) {
                        if ($this->isTemplateContentComplete($product_model)) {
                            $service->syncTemplateWorkflow($model, $product_model, 'verification_pending', [
                                'content_user_id' => Yii::$app->user->id,
                                'content_completed_at' => new \yii\db\Expression('NOW()'),
                            ]);
                            Yii::$app->session->setFlash('success', 'Stage 2 complete. Template sent for verification.');
                            return $this->redirect(['index']);
                        }

                        $service->syncTemplateWorkflow($model, $product_model, 'content_pending', [
                            'content_user_id' => Yii::$app->user->id,
                        ]);
                        Yii::$app->session->setFlash('success', 'Content progress saved. Fill all fields to complete Stage 2.');
                        return $this->redirect(['update', 'id' => $model->id, 'stage' => 2]);
                    }
                }
            }
            return $this->redirect(['index']);
        }

        return $this->render('update', [
            'model' => $model,
            'language' => $language,
            'product_model' => $product_model
            ,'stage' => $stage,
            'workflow' => $workflow,
            'canVerify' => $service->canVerifyCurrentUser(),
        ]);
    }

    public function actionVerify($id)
    {
        $model = $this->findModel($id);
        $service = new WorkPointService();

        if (!$service->canVerifyCurrentUser()) {
            throw new \yii\web\ForbiddenHttpException('You do not have verification access.');
        }

        $product = Product::find()->where(['template_id' => $id])->orderBy(['id' => SORT_ASC])->one();
        if (!$product) {
            throw new NotFoundHttpException('Template product not found.');
        }

        $decision = Yii::$app->request->post('decision', 'verify');
        $note = trim((string) Yii::$app->request->post('verification_note', ''));
        $workflow = $service->workflowForTemplate((int) $model->id);

        if ($decision === 'changes') {
            if ($note === '') {
                Yii::$app->session->setFlash('error', 'Please write what changes are required.');
                return $this->redirect(['template/update', 'id' => $model->id, 'stage' => 3]);
            }

            $noteHistory = $this->appendVerificationNote((string)($workflow['verification_note'] ?? ''), $note);
            $product->is_delete = 1;
            $product->save(false);
            $service->syncTemplateWorkflow($model, $product, 'rejected', [
                'verified_by' => Yii::$app->user->id,
                'verified_at' => null,
                'verification_note' => $noteHistory,
            ]);

            Yii::$app->session->setFlash('success', 'Changes requested with note. Verification is not complete yet.');
            return $this->redirect(['template/update', 'id' => $model->id, 'stage' => 3]);
        }

        $product->is_delete = 0;
        $product->save(false);
        $service->syncTemplateWorkflow($model, $product, 'verified', [
            'verified_by' => Yii::$app->user->id,
            'verified_at' => new \yii\db\Expression('NOW()'),
            'verification_note' => $note !== '' ? $this->appendVerificationNote((string)($workflow['verification_note'] ?? ''), $note, 'Verified note') : ($workflow['verification_note'] ?? ''),
        ]);
        $service->recordTemplateWork(WorkPointService::TEMPLATE_VERIFY_KEY, $model, $product, 'verification');

        $contentUserId = $workflow['content_user_id'] ?? null;
        if ($contentUserId) {
            $dbEmployee = Yii::$app->dbEmployee;
            $contentDefId = (int)$dbEmployee->createCommand("SELECT id FROM work_definitions WHERE work_key = :key", [':key' => WorkPointService::TEMPLATE_CONTENT_KEY])->queryScalar();
            if ($contentDefId > 0) {
                $alreadyAwarded = (new \yii\db\Query())
                    ->from('completed_work_logs')
                    ->where([
                        'user_id' => $contentUserId,
                        'work_definition_id' => $contentDefId,
                        'source_type' => 'template',
                        'source_id' => (int)$model->id,
                    ])->exists($dbEmployee);

                if (!$alreadyAwarded) {
                    $service->recordTemplateWork(WorkPointService::TEMPLATE_CONTENT_KEY, $model, $product, 'content_entry', (int)$contentUserId);
                }
            }
        }

        Yii::$app->session->setFlash('success', 'Template verified. Product is now visible.');
        return $this->redirect(['product/update', 'id' => $product->id]);
    }

    /**
     * Deletes an existing Template model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param int $id ID
     * @return \yii\web\Response
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionDelete($id)
    {
        $this->findModel($id)->delete();

        return $this->redirect(['index']);
    }

    /**
     * Finds the Template model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param int $id ID
     * @return Template the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Template::findOne(['id' => $id])) !== null) {
            return $model;
        }

        throw new NotFoundHttpException(Yii::t('app', 'The requested page does not exist.'));
    }

    private function saveTemplateUpload(UploadedFile $file): string
    {
        $pathInfo = pathinfo($file->name);
        $fileName = $pathInfo['filename'] ?? 'template';
        $imageName = preg_replace('/[^A-Za-z0-9_-]+/', '_', $fileName);
        $storedName = trim($imageName, '_') . '-' . date('Y-m-d') . '-' . time() . '-' . mt_rand(1000, 9999) . '.' . $file->extension;
        $targetPath = Yii::getAlias('@storage') . '/images/' . $storedName;
        $file->saveAs($targetPath);

        return $storedName;
    }

    private function stageFromWorkflow(?array $workflow): int
    {
        $status = $workflow['status'] ?? '';
        if ($status === 'content_pending') {
            return 2;
        }

        if (in_array($status, ['verification_pending', 'verified', 'rejected'], true)) {
            return 3;
        }

        return 1;
    }

    private function applyTemplateContent(Product $product): void
    {
        $productPost = Yii::$app->request->post('Product', []);
        foreach (['title', 'description', 'caption', 'meta_title', 'meta_description', 'keywords', 'alt', 'tag'] as $field) {
            if (array_key_exists($field, $productPost)) {
                $product->{$field} = $productPost[$field];
            }
        }
    }

    private function isTemplateContentComplete(Product $product): bool
    {
        foreach (['title', 'description', 'caption', 'meta_title', 'meta_description', 'keywords', 'alt', 'tag'] as $field) {
            if (trim(strip_tags((string)$product->{$field})) === '') {
                return false;
            }
        }

        return true;
    }

    private function appendVerificationNote(string $existingNote, string $newNote, string $label = 'Changes required'): string
    {
        $existingNote = trim($existingNote);
        $newNote = trim($newNote);
        $userName = Yii::$app->user->identity->name ?? Yii::$app->user->identity->username ?? 'Verifier';
        $entry = sprintf('[%s by %s at %s] %s', $label, $userName, date('Y-m-d H:i'), $newNote);

        return $existingNote === '' ? $entry : $existingNote . "\n\n" . $entry;
    }

    public function actionCategory()
    {
        $category = Subcategory::find()->where(['category_id' => Yii::$app->request->post()['category_id']])->all();
        foreach ($category as $key => $value) {
            $subcategory[$value->id] = $value->subcategory_name;
        }
        return json_encode($subcategory);
    }
}
