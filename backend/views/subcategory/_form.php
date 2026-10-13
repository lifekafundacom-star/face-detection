<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/** @var yii\web\View $this */
/** @var common\models\Subcategory $model */
/** @var yii\widgets\ActiveForm $form */
?>

<div class="subcategory-form">

    <?php $form = ActiveForm::begin(); ?>


    <?= $form->field($model, 'category_id')->dropDownList($category_name, ['prompt' => 'Select Category Name', 'required' => true])->label(false) ?>
    <?php if ($model->isNewRecord) { ?>
        <?= $form->field($model, 'subcategory_name')->textInput(['maxlength' => true]) ?>
    <?php } else { ?>
        <?= $form->field($model, 'subcategory_name')->textInput(['maxlength' => true,'value' => $sc_name]) ?>
    <?php }  ?>

    <?= $form->field($model, 'description')->textarea(['maxlength' => true]) ?>

    <?= $form->field($model, 'subcategory_icon')->fileInput(['required' => $model->subcategory_icon != '' ? false : true, 'onchange' => "readURL(this);"])->label('SubCategory Icon upload') ?>

    <?= $form->field($model, 'meta_title')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'meta_description')->textarea(['maxlength' => true]) ?>

    <?= $form->field($model, 'keywords')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'tag')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'whatsapp')->textInput([
        'placeholder' => 'https://chat.whatsapp.com/your-group-link',
    ])->label('WhatsApp Group Link') ?>

    <div class="form-group">
        <?= Html::submitButton(Yii::t('app', 'Save'), ['class' => 'btn btn-success']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
