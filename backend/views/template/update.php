<?php

use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var common\models\Template $model */

$this->title = Yii::t('app', 'Update Template: {name}', [
    'name' => $model->id,
]);
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Templates'), 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->id, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = Yii::t('app', 'Update');
?>
<div class="template-update">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
        'language' => $language,
        'product_model' => $product_model,
        'stage' => $stage ?? 1,
        'workflow' => $workflow ?? null,
        'canVerify' => $canVerify ?? false,
    ]) ?>

</div>
