<?php

use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var common\models\TemplateMapping $model */

$this->title = Yii::t('app', 'Create Template Mapping');
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Template Mappings'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="template-mapping-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
        'template' => $template,
        'category_name' => $category_name,
        'subcategory_name' => $subcategory_name,
    ]) ?>

</div>
