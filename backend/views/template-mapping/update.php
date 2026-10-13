<?php

use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var common\models\TemplateMapping $model */

$this->title = Yii::t('app', 'Update Template Mapping: {name}', [
    'name' => $model->id,
]);
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Template Mappings'), 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->id, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = Yii::t('app', 'Update');
?>
<div class="template-mapping-update">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
        'template' => $template,
        'category_name' => $category_name,
        'subcategory_name' => $subcategory_name,
    ]) ?>

</div>
