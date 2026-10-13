<?php

namespace backend\controllers;

use common\components\WorkPointService;
use Yii;
use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\web\NotFoundHttpException;

class WorkDefinitionController extends Controller
{
    public function behaviors()
    {
        return array_merge(parent::behaviors(), [
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'delete' => ['POST'],
                ],
            ],
        ]);
    }

    public function actionIndex()
    {
        (new WorkPointService())->ensureSchema();
        $db = Yii::$app->dbEmployee;
        $request = Yii::$app->request;
        $editId = (int) $request->get('id', 0);
        $editing = null;

        if ($editId > 0) {
            $editing = $this->findDefinition($editId);
            if (!in_array($editing['work_key'] ?? '', [
                WorkPointService::TEMPLATE_PHOTO_KEY,
                WorkPointService::TEMPLATE_CONTENT_KEY,
                WorkPointService::TEMPLATE_VERIFY_KEY,
                WorkPointService::TEMPLATE_MAPPING_KEY
            ], true)) {
                $editing = null;
            }
        }

        if ($request->isPost) {
            $id = (int) $request->post('id', 0);
            if ($id <= 0) {
                Yii::$app->session->setFlash('error', 'Adding new work definitions is not allowed.');
                return $this->redirect(['index']);
            }

            // Ensure the edited item is one of the allowed keys
            try {
                $targetDef = $this->findDefinition($id);
                if (!in_array($targetDef['work_key'] ?? '', [
                    WorkPointService::TEMPLATE_PHOTO_KEY,
                    WorkPointService::TEMPLATE_CONTENT_KEY,
                    WorkPointService::TEMPLATE_VERIFY_KEY,
                    WorkPointService::TEMPLATE_MAPPING_KEY
                ], true)) {
                    Yii::$app->session->setFlash('error', 'Modifying this work definition is not allowed.');
                    return $this->redirect(['index']);
                }
            } catch (\Throwable $e) {
                Yii::$app->session->setFlash('error', 'Work definition not found.');
                return $this->redirect(['index']);
            }

            $points = max(0, (int) $request->post('completion_points', 0));

            try {
                $db->createCommand()->update('work_definitions', [
                    'completion_points' => $points,
                ], ['id' => $id])->execute();
                Yii::$app->session->setFlash('success', 'Work completion points updated successfully.');
            } catch (\Throwable $exception) {
                Yii::$app->session->setFlash('error', 'Unable to update work points: ' . $exception->getMessage());
                return $this->redirect(['index', 'id' => $id]);
            }

            return $this->redirect(['index']);
        }

        $definitions = (new \yii\db\Query())
            ->from('work_definitions')
            ->where(['in', 'work_key', [
                WorkPointService::TEMPLATE_PHOTO_KEY,
                WorkPointService::TEMPLATE_CONTENT_KEY,
                WorkPointService::TEMPLATE_VERIFY_KEY,
                WorkPointService::TEMPLATE_MAPPING_KEY,
            ]])
            ->orderBy(['id' => SORT_ASC])
            ->all($db);

        return $this->render('index', [
            'definitions' => $definitions,
            'editing' => $editing,
        ]);
    }

    public function actionDelete($id)
    {
        Yii::$app->session->setFlash('error', 'Disabling work definitions is not allowed.');
        return $this->redirect(['index']);
    }

    private function findDefinition(int $id): array
    {
        $row = (new \yii\db\Query())->from('work_definitions')->where(['id' => $id])->one(Yii::$app->dbEmployee);

        if (!$row) {
            throw new NotFoundHttpException('Work definition not found.');
        }

        return $row;
    }

    private function normalizeWorkKey($value): string
    {
        $key = strtolower(trim((string) $value));
        $key = preg_replace('/[^a-z0-9]+/', '_', $key);

        return trim((string) $key, '_');
    }
}
