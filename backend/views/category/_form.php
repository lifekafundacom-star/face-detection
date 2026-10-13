<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/** @var yii\web\View $this */
/** @var common\models\Category $model */
/** @var yii\widgets\ActiveForm $form */
?>

<div class="category-form">

    <?php $form = ActiveForm::begin(); ?>
    <?php if ($model->isNewRecord) { ?>
        <?= $form->field($model, 'category_name')->textInput(['maxlength' => true]) ?>
    <?php } else { ?>
        <?= $form->field($model, 'category_name')->textInput(['maxlength' => true, 'value' => $catName]) ?>
    <?php } ?>

    <?= $form->field($model, 'description')->textarea(['maxlength' => true]) ?>

    <?= $form->field($model, 'category_icon')->fileInput(['required' => $model->category_icon != '' ? false : true, 'onchange' => "readURL(this);"])->label('Category Icon upload') ?>

    <?= $form->field($model, 'meta_title')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'meta_description')->textarea(['maxlength' => true]) ?>

    <?= $form->field($model, 'keywords')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'tag')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'whatsapp')->textInput([
        'placeholder' => 'https://chat.whatsapp.com/your-group-link',
    ])->label('WhatsApp Group Link') ?>

    <div>
        <img src="<?= Yii::getAlias('@storageUrl') ?>/icon/<?= $model->category_icon ?>" alt="">
    </div>

    <div class="form-group">
        <?= Html::submitButton(Yii::t('app', 'Save'), ['class' => 'btn btn-success']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>

<script>
    var url = '<?= Yii::getAlias('@storageUrl') ?>/icon/';

    function readURL(input) {
        var ext = input.files[0]['name'].substring(input.files[0]['name'].lastIndexOf('.') + 1).toLowerCase();
        if (input.files && input.files[0] && (ext == "png" || ext == "svg")) {

        } else {
            alert("Only svg/png are allowed!");
            $('input[type=file]').val(null);
        }

    }
</script>
