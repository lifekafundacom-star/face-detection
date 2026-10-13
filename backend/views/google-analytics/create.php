<?php

use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var common\models\GoogleAnalytics $model */

$this->title = Yii::t('app', 'Create Google Analytics');
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Google Analytics'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="google-analytics-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
