<?php

use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var common\models\Subcategory $model */

$this->title = Yii::t('app', 'Create Subcategory');
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Subcategories'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="subcategory-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
        'category_name' => $category_name,
    ]) ?>

</div>
