<?php

use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var common\models\Contact $model */

$this->title = Yii::t('app', 'Create Contact');
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Contacts'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="contact-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
