<?php

use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var common\models\Template $model */

$this->title = Yii::t('app', 'Create Template');
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Templates'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="template-create">

    <h1>
        <?= Html::encode($this->title) ?>
    </h1>

    <?= $this->render('_form', [
        'model' => $model,
        // 'category_name' => $category_name,
        // 'subcategory_name' => $subcategory_name,
        'language' => $language,
        'product_model' => $product_model,
        'stage' => $stage ?? 1,
        'workflow' => $workflow ?? null,
        'canVerify' => $canVerify ?? false,
    ]) ?>

</div>
