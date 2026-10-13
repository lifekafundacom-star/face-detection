<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use yii\helpers\Url;

/** @var yii\web\View $this */
/** @var common\models\TemplateMapping $model */
/** @var yii\widgets\ActiveForm $form */
?>

<div class="template-mapping-form">

    <?php $form = ActiveForm::begin(); ?>

    <?= $form->field($model, 'template_id')->dropDownList($template, ['prompt' => 'Select Template Name', 'required' => true])->label(false) ?>

    <?= $form->field($model, 'category_id')->dropDownList($category_name, ['prompt' => 'Select Category Name', 'required' => true, 'onchange' => 'get_category(this.value)'])->label(false) ?>

    <?= $form->field($model, 'subcategory_id')->dropDownList($subcategory_name, ['prompt' => 'Select Sub category Name', 'required' => true])->label(false) ?>

    <?= $form->field($model, 'publish_type')->dropDownList($model::publishTypeOptions(), [
        'id' => 'templatemapping-publish_type',
        'onchange' => 'toggleScheduleDateTime(this.value)',
    ])->label(false) ?>

    <div id="template-mapping-schedule-row" style="<?= $model->publish_type === $model::PUBLISH_TYPE_SCHEDULED ? '' : 'display:none;' ?>">
        <?= $form->field($model, 'scheduled_at')->input('datetime-local', [
            'value' => $model->scheduled_at ? date('Y-m-d\TH:i', strtotime($model->scheduled_at)) : '',
        ])->label(false) ?>
    </div>

    <?= $form->field($model, 'featured_section')->dropDownList($model::featuredSectionOptions())->label(false) ?>

    <div class="form-group">
        <?= Html::submitButton(Yii::t('app', 'Save'), ['class' => 'btn btn-success']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>

<script>
    function get_category(category_id) {
        var csrfToken = $('meta[name="csrf-token"]').attr("content");
        $.ajax({
            url: "<?= Url::toRoute(['template-mapping/category']) ?>",
            method: "POST",
            data: {
                category_id: category_id,
                '_csrf-backend': csrfToken
            },
            success: function (result) {
                if (result) {
                    $('#templatemapping-subcategory_id').html('<option value="">Select subcategory name</option>');
                    var category = JSON.parse(result);
                    $.each(category, function (key, value) {
                        $('#templatemapping-subcategory_id').append('<option value="' + key + '">' + value + '</option>');
                    });
                }
            }
        });
    }

    function toggleScheduleDateTime(value) {
        var typeField = document.getElementById('templatemapping-publish_type') || document.getElementById('templatemapping-publish-type');
        var scheduleRow = document.getElementById('template-mapping-schedule-row');
        var scheduleInput = document.getElementById('templatemapping-scheduled_at') || document.getElementById('templatemapping-scheduled-at');
        var selectedValue = value || (typeField ? typeField.value : '');
        var isScheduled = selectedValue === 'scheduled';

        if (scheduleRow) {
            scheduleRow.style.display = isScheduled ? 'block' : 'none';
        }

        if (scheduleInput) {
            scheduleInput.required = isScheduled;

            if (!isScheduled) {
                scheduleInput.value = '';
                var formGroup = scheduleInput.closest('.form-group');
                var helpBlock = formGroup ? formGroup.querySelector('.help-block, .help-block-error') : null;

                if (formGroup) {
                    formGroup.classList.remove('has-error');
                }

                if (helpBlock) {
                    helpBlock.textContent = '';
                }
            }
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        var typeField = document.getElementById('templatemapping-publish_type') || document.getElementById('templatemapping-publish-type');

        if (typeField) {
            typeField.addEventListener('change', function () {
                toggleScheduleDateTime(this.value);
            });
        }

        toggleScheduleDateTime();
    });
</script>
