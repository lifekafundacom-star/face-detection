<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/** @var yii\web\View $this */
/** @var common\models\GoogleAnalytics $model */
/** @var yii\widgets\ActiveForm $form */
?>

<div class="google-analytics-form">

    <?php $form = ActiveForm::begin(); ?>

    <?= $form->field($model, 'head')->textarea(['rows' => 6]) ?>

    <?= $form->field($model, 'body')->textarea(['rows' => 6]) ?>

    <div class="form-group">
        <?= Html::submitButton(Yii::t('app', 'Save'), ['class' => 'btn btn-success']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
