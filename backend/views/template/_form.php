<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use yii\helpers\Url;
use dosamigos\ckeditor\CKEditor;

/** @var yii\web\View $this */
/** @var common\models\Template $model */
/** @var yii\widgets\ActiveForm $form */
?>

<div class="template-form">

    <?php $form = ActiveForm::begin(); ?>
    <?php
    $currentImage = $product_model && !$product_model->isNewRecord ? trim((string)$product_model->images) : '';
    $currentShowImage = $product_model && !$product_model->isNewRecord ? trim((string)$product_model->show_image) : '';
    $currentLanguageId = $product_model && !$product_model->isNewRecord ? (int)$product_model->language_id : 0;
    $stage = (int)($stage ?? 1);
    $workflow = $workflow ?? null;
    $canVerify = (bool)($canVerify ?? false);
    $stageItem = function ($targetStage, $label) use ($model, $stage) {
        $class = $stage === $targetStage ? 'is-current' : ($stage > $targetStage ? 'is-done' : '');
        if (!$model->isNewRecord) {
            return Html::a($label, ['template/update', 'id' => $model->id, 'stage' => $targetStage], ['class' => $class]);
        }

        return Html::tag('span', Html::encode($label), ['class' => $class]);
    };
    ?>
    <input type="hidden" name="stage" value="<?= $stage ?>">
    <div class="template-stage-strip">
        <?= $stageItem(1, '1. Photos') ?>
        <?= $stageItem(2, '2. Content') ?>
        <?= $stageItem(3, '3. Verification') ?>
    </div>

    <!-- </?= $form->field($model, 'category_id')->dropDownList($category_name, ['prompt' => 'Select Category Name', 'required' => true, 'onchange' => 'get_category(this.value)'])->label(false) ?>

    </?= $form->field($model, 'subcategory_id')->dropDownList($subcategory_name, ['prompt' => 'Select Sub category Name', 'required' => true])->label(false) ?> -->

    <?php
    $isMapped = !$model->isNewRecord && \common\models\TemplateMapping::find()->where(['template_id' => $model->id, 'is_delete' => 0])->exists();
    ?>
    <?= $form->field($model, 'template_name')->textInput(['maxlength' => true, 'readonly' => ($stage === 3 || $isMapped)]) ?>

    <?php if ($stage === 1): ?>
    <div>
        <?php foreach ($language as $key => $value) {
        ?>
            <div>
                <input type="radio" id="check<?= $value->id ?>" name="language[]" value="<?= $value->id ?>" onchange="get_file(<?= $value->id ?>)" <?= ($product_model && $product_model->language_id == $value->id) ? 'checked' : '' ?>>
                <label for="">
                    <?= $value->language ?>
                </label>
                <?= $form->field($product_model, 'images[]')->fileInput([
                    'multiple' => true,
                    'id' => 'file' . $value->id,
                    'class' => 'template-image-input',
                    'onchange' => 'previewSelectedTemplateImages(this)',
                    'style' => $currentLanguageId === (int)$value->id ? '' : 'display:none',
                ])->label(false) ?>
                <div class="template-selected-preview template-thumb-row" id="selectedPreview<?= $value->id ?>"></div>
                <input type="hidden" name="lang[]" value="<?= $value->id ?>">
            </div>
        <?php } ?>

    </div>

    <?php if ($currentImage) { ?>
        <div class="template-current-images">
            <label>Current Template Image</label>
            <div class="template-thumb-row">
                <?php $imageUrl = Yii::getAlias('@storageUrl') . '/images/' . $currentImage; ?>
                <a href="<?= Html::encode($imageUrl) ?>" class="template-preview-thumb" data-preview-src="<?= Html::encode($imageUrl) ?>" data-preview-title="Template Image" data-preview-file="<?= Html::encode($currentImage) ?>" onclick="return openTemplatePreview(this);">
                    <?= Html::img($imageUrl, ['alt' => 'Template Image', 'loading' => 'lazy']) ?>
                    <span>Template Image</span>
                </a>
            </div>
        </div>
    <?php } ?>

    <?php if ($product_model->isNewRecord) { ?>
        <?= $form->field($product_model, 'show_image')->fileInput(['required' => true, 'class' => 'template-show-image-input', 'onchange' => 'previewSelectedShowImage(this)'])->label('Show Image') ?>
    <?php } else { ?>
        <?= $form->field($product_model, 'show_image')->fileInput(['class' => 'template-show-image-input', 'onchange' => 'previewSelectedShowImage(this)'])->label('Show Image') ?>
    <?php } ?>
    <div class="template-selected-preview template-thumb-row" id="selectedShowImagePreview"></div>
    <?php if ($currentShowImage) {
        $showImageUrl = Yii::getAlias('@storageUrl') . '/images/' . $currentShowImage;
    ?>
        <div class="template-show-image-preview">
            <a href="<?= Html::encode($showImageUrl) ?>" class="template-preview-thumb" data-preview-src="<?= Html::encode($showImageUrl) ?>" data-preview-title="Show Image" data-preview-file="<?= Html::encode($currentShowImage) ?>" onclick="return openTemplatePreview(this);">
                <?= Html::img($showImageUrl, ['alt' => 'Show Image', 'loading' => 'lazy']) ?>
                <span>Show Image</span>
            </a>
        </div>
    <?php } ?>

    <?= $form->field($model, 'image_type')->dropDownList(['square' => 'square', 'rectangle' => 'rectangle']) ?>

    <?php endif; ?>

    <?php if ($stage === 2): ?>
    <div class="template-content-stage">
        <h3>Content Entry</h3>
        <p>Content progress will save. Stage 2 completes only after all fields are filled.</p>
    <?= $form->field($product_model, 'title')->textInput(['maxlength' => true]) ?>

    <?= $form->field($product_model, 'description')
        ->widget(
            CKEditor::className(),
            [
                'options' => [],
                'preset' => 'custom',
                'clientOptions' => [
                    'extraPlugins' => '',
                    'height' => 500,


                    'toolbarGroups' => [
                        ['name' => 'undo'],
                        ['name' => 'basicstyles', 'groups' => ['basicstyles', 'cleanup']],
                        ['name' => 'paragraph', 'groups' => ['list', 'indent', 'blocks', 'align', 'bidi']],
                        ['name' => 'styles'],
                        ['name' => 'links', 'groups' => ['links', 'insert']]
                    ]

                ]

            ]
        )

    ?>

    <?= $form->field($product_model, 'caption')->textInput(['maxlength' => true]) ?>

    <?= $form->field($product_model, 'meta_title')->textInput(['maxlength' => true]) ?>

    <?= $form->field($product_model, 'meta_description')->textInput(['maxlength' => true]) ?>

    <?= $form->field($product_model, 'keywords')->textInput(['maxlength' => true]) ?>

    <?= $form->field($product_model, 'alt')->textInput(['maxlength' => true])->label('Alternative Text') ?>

    <!-- </?= $form->field($model, 'title')->textInput(['maxlength' => true]) ?> -->

    <!-- </?= $form->field($model, 'description')
         ->widget(CKEditor::className(), 
            [
              'options' => [], 
              'preset' => 'custom',
              'clientOptions' => [
                  'extraPlugins' => '',
                  'height' => 500,


                  'toolbarGroups' => [
                      ['name' => 'undo'],
                      ['name' => 'basicstyles', 'groups' => ['basicstyles', 'cleanup']],
                      ['name' => 'paragraph', 'groups' => ['list', 'indent', 'blocks', 'align', 'bidi' ]],
                      ['name' => 'styles'],
                      ['name' => 'links', 'groups' => ['links', 'insert']]
                  ]

              ]

            ]) 

?> -->

    <!-- </?= $form->field($model, 'caption')->textInput(['maxlength' => true]) ?> -->

    <?= $form->field($product_model, 'tag')->textInput(['maxlength' => true])->label('Tag') ?>
    </div>
    <?php endif; ?>

    <?php if ($stage === 3): ?>
        <div class="template-verification-panel">
            <h3><?= ($workflow['status'] ?? '') === 'rejected' ? 'Changes requested' : 'Verification pending' ?></h3>
            <p>Status: <strong><?= Html::encode($workflow['status'] ?? 'verification_pending') ?></strong></p>
            <p>Product will stay hidden until admin/manager or verification-access employee approves it.</p>
            <?php if (!empty($workflow['verification_note'])): ?>
                <div class="template-verification-note">
                    <strong>Verifier note</strong>
                    <p><?= nl2br(Html::encode($workflow['verification_note'])) ?></p>
                </div>
            <?php endif; ?>
            <div class="template-stage-actions">
                <?= Html::a('Edit Photos', ['template/update', 'id' => $model->id, 'stage' => 1], ['class' => 'dashboard-btn dashboard-btn-light']) ?>
                <?= Html::a('Edit Content', ['template/update', 'id' => $model->id, 'stage' => 2], ['class' => 'dashboard-btn dashboard-btn-light']) ?>
            </div>
            <?php if ($currentImage || $currentShowImage): ?>
                <div class="template-thumb-row">
                    <?php if ($currentImage): ?>
                        <?php $imageUrl = Yii::getAlias('@storageUrl') . '/images/' . $currentImage; ?>
                        <a href="<?= Html::encode($imageUrl) ?>" class="template-preview-thumb" data-preview-src="<?= Html::encode($imageUrl) ?>" data-preview-title="Template Image" data-preview-file="<?= Html::encode($currentImage) ?>" onclick="return openTemplatePreview(this);">
                            <?= Html::img($imageUrl, ['alt' => 'Template Image', 'loading' => 'lazy']) ?>
                            <span>Template Image</span>
                        </a>
                    <?php endif; ?>
                    <?php if ($currentShowImage): ?>
                        <?php $showImageUrl = Yii::getAlias('@storageUrl') . '/images/' . $currentShowImage; ?>
                        <a href="<?= Html::encode($showImageUrl) ?>" class="template-preview-thumb" data-preview-src="<?= Html::encode($showImageUrl) ?>" data-preview-title="Show Image" data-preview-file="<?= Html::encode($currentShowImage) ?>" onclick="return openTemplatePreview(this);">
                            <?= Html::img($showImageUrl, ['alt' => 'Show Image', 'loading' => 'lazy']) ?>
                            <span>Show Image</span>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <div class="form-group">
        <?php if ($stage < 3): ?>
            <?= Html::submitButton($stage === 1 ? 'Save Photos & Continue' : 'Save Content', ['class' => 'btn btn-success']) ?>
        <?php endif; ?>
    </div>

    <?php ActiveForm::end(); ?>

    <?php if ($stage === 3 && $canVerify && in_array(($workflow['status'] ?? ''), ['verification_pending', 'rejected'], true)): ?>
        <?= Html::beginForm(['template/verify', 'id' => $model->id], 'post', ['class' => 'template-verify-form']) ?>
            <label>Verification note
                <textarea name="verification_note" rows="3" placeholder="Write approval note or required changes"></textarea>
            </label>
            <div class="template-verify-actions">
                <?= Html::submitButton('Changes Required', ['class' => 'btn btn-warning', 'name' => 'decision', 'value' => 'changes']) ?>
                <?= Html::submitButton('Confirm Verify & Open Product', ['class' => 'btn btn-primary', 'name' => 'decision', 'value' => 'verify']) ?>
            </div>
        <?= Html::endForm() ?>
    <?php endif; ?>

</div>

<div class="template-preview-modal" id="templatePreviewModal" aria-hidden="true">
    <div class="template-preview-dialog" role="dialog" aria-modal="true" aria-labelledby="templatePreviewTitle">
        <button type="button" class="template-preview-close" aria-label="Close preview" onclick="closeTemplatePreview(); return false;">&times;</button>
        <h3 id="templatePreviewTitle"></h3>
        <img id="templatePreviewImage" src="" alt="">
        <div id="templatePreviewFile" class="template-preview-file"></div>
    </div>
</div>

<div class="template-convert-modal" id="templateConvertModal" aria-hidden="true">
    <div class="template-convert-dialog" role="dialog" aria-modal="true" aria-labelledby="templateConvertTitle">
        <button type="button" class="template-preview-close" aria-label="Close converter" onclick="cancelTemplateImageConvert(); return false;">&times;</button>
        <h3 id="templateConvertTitle">Convert Image to WebP</h3>
        <div class="template-convert-workspace">
            <div class="template-convert-preview">
                <div class="template-convert-canvas-wrap" id="templateConvertCanvasWrap">
                    <canvas id="templateConvertOriginalCanvas"></canvas>
                    <canvas id="templateConvertOutputCanvas"></canvas>
                    <div class="template-convert-divider" id="templateConvertDivider"><span></span></div>
                    <div class="template-convert-preview-label before">Before</div>
                    <div class="template-convert-preview-label after">After</div>
                </div>
            </div>
            <div class="template-convert-tools">
                <div class="template-convert-meta" id="templateConvertMeta"></div>
                <label>Compare <b id="templateConvertCompareValue">50%</b>
                    <input type="range" id="templateConvertCompare" min="0" max="100" value="50">
                </label>
                <label>Effort <b id="templateConvertEffortValue">4</b>
                    <input type="range" id="templateConvertEffort" min="0" max="6" value="4">
                </label>
                <label>Quality <b id="templateConvertQualityValue">90</b>
                    <input type="range" id="templateConvertQuality" min="20" max="100" value="90">
                </label>
                <label class="template-convert-check" id="templateMagicToggleRow">
                    <input type="checkbox" id="templateMagicWandEnabled">
                    Magic Wand
                </label>
                <div class="template-magic-tools" id="templateMagicTools">
                    <label>Tolerance <b id="templateMagicToleranceValue">36</b>
                        <input type="range" id="templateMagicTolerance" min="0" max="160" value="36">
                    </label>
                    <label>Feather <b id="templateMagicFeatherValue">8</b>
                        <input type="range" id="templateMagicFeather" min="0" max="60" value="8">
                    </label>
                    <div class="template-convert-meta" id="templateMagicInfo">Magic Wand active: click image color to remove</div>
                </div>
                <div class="template-convert-meta" id="templateConvertSize">Size: original dimensions</div>
                <div class="template-convert-meta" id="templateConvertOutput">Output: WebP</div>
                <div class="template-convert-actions">
                    <button type="button" class="btn btn-default" onclick="cancelTemplateImageConvert(); return false;">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="saveTemplateImageConvert(); return false;">Save Converted Image</button>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .template-current-images {
        margin: 16px 0 22px;
    }
    .template-show-image-preview {
        margin: -8px 0 18px;
    }
    .template-selected-preview {
        margin: 8px 0 16px;
    }
    .template-current-images > label {
        display: block;
        margin-bottom: 8px;
        font-weight: 700;
    }
    .template-thumb-row {
        display: flex;
        flex-wrap: wrap;
        gap: 14px;
    }
    .template-preview-thumb {
        display: block;
        width: 106px;
        padding: 8px;
        border: 1px solid #d7dde8;
        border-radius: 6px;
        background: #fff;
        cursor: pointer;
        text-align: center;
        text-decoration: none;
        box-shadow: 0 4px 12px rgba(15, 23, 42, 0.08);
    }
    .template-preview-thumb img {
        width: 82px;
        height: 82px;
        object-fit: cover;
        display: block;
        margin: 0 auto 7px;
        border-radius: 4px;
    }
    .template-preview-thumb span {
        display: block;
        font-size: 12px;
        font-weight: 700;
        color: #1f2a44;
    }
    .template-preview-modal {
        display: none;
        position: fixed;
        z-index: 9999;
        inset: 0;
        padding: 40px 16px;
        background: rgba(15, 23, 42, 0.62);
    }
    .template-preview-modal.is-open {
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .template-preview-dialog {
        position: relative;
        width: min(92vw, 860px);
        max-height: 88vh;
        padding: 18px;
        border-radius: 8px;
        background: #fff;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.25);
        text-align: center;
    }
    .template-preview-dialog h3 {
        margin: 0 36px 14px;
        font-size: 20px;
        font-weight: 700;
    }
    .template-preview-dialog img {
        max-width: 100%;
        max-height: 68vh;
        object-fit: contain;
    }
    .template-preview-close {
        position: absolute;
        top: 10px;
        right: 12px;
        border: 0;
        background: transparent;
        font-size: 30px;
        line-height: 1;
        color: #64748b;
        cursor: pointer;
    }
    .template-preview-file {
        margin-top: 10px;
        color: #64748b;
        font-size: 12px;
        word-break: break-all;
    }
    .template-convert-modal {
        display: none;
        position: fixed;
        z-index: 10000;
        inset: 0;
        padding: 40px 16px;
        background: rgba(15, 23, 42, 0.72);
    }
    .template-convert-modal.is-open {
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .template-convert-dialog {
        position: relative;
        width: min(96vw, 1080px);
        max-height: 92vh;
        overflow: auto;
        padding: 18px;
        border-radius: 8px;
        background: #fff;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.28);
    }
    .template-convert-dialog h3 {
        margin: 0 36px 14px 0;
        font-size: 20px;
        font-weight: 700;
    }
    .template-convert-workspace {
        display: grid;
        grid-template-columns: minmax(0, 1fr) 280px;
        gap: 16px;
    }
    .template-convert-preview {
        align-items: center;
        display: flex;
        justify-content: center;
        min-height: 360px;
        max-height: 68vh;
        overflow: hidden;
        position: relative;
        border: 1px solid #d7dde8;
        border-radius: 6px;
        background-color: #f8fafc;
        background-image: linear-gradient(45deg, #e2e8f0 25%, transparent 25%), linear-gradient(-45deg, #e2e8f0 25%, transparent 25%), linear-gradient(45deg, transparent 75%, #e2e8f0 75%), linear-gradient(-45deg, transparent 75%, #e2e8f0 75%);
        background-position: 0 0, 0 8px, 8px -8px, -8px 0;
        background-size: 16px 16px;
    }
    .template-convert-canvas-wrap {
        position: relative;
        max-height: 64vh;
        max-width: 100%;
        overflow: hidden;
        cursor: crosshair;
    }
    .template-convert-canvas-wrap canvas {
        display: block;
        height: 100%;
        left: 0;
        top: 0;
        width: 100%;
    }
    #templateConvertOriginalCanvas {
        position: relative;
    }
    #templateConvertOutputCanvas {
        position: absolute;
    }
    #templateConvertOutputCanvas {
        clip-path: inset(0 0 0 50%);
        background-color: #f8fafc;
        background-image: linear-gradient(45deg, #dbe3ec 25%, transparent 25%), linear-gradient(-45deg, #dbe3ec 25%, transparent 25%), linear-gradient(45deg, transparent 75%, #dbe3ec 75%), linear-gradient(-45deg, transparent 75%, #dbe3ec 75%);
        background-position: 0 0, 0 8px, 8px -8px, -8px 0;
        background-size: 16px 16px;
    }
    .template-convert-divider {
        background: rgba(15, 23, 42, .86);
        bottom: 0;
        left: 50%;
        pointer-events: none;
        position: absolute;
        top: 0;
        width: 3px;
        z-index: 4;
    }
    .template-convert-divider span {
        align-items: center;
        background: #111827;
        border-radius: 50%;
        color: #fff;
        display: flex;
        font-size: 11px;
        font-weight: 900;
        height: 36px;
        justify-content: center;
        left: 50%;
        position: absolute;
        top: 50%;
        transform: translate(-50%, -50%);
        width: 36px;
    }
    .template-convert-divider span:before {
        content: '< >';
    }
    .template-convert-preview-label {
        background: rgba(15, 23, 42, .78);
        border-radius: 4px;
        color: #fff;
        font-size: 11px;
        font-weight: 800;
        padding: 4px 8px;
        position: absolute;
        top: 10px;
        z-index: 5;
    }
    .template-convert-preview-label.before {
        left: 10px;
    }
    .template-convert-preview-label.after {
        right: 10px;
    }
    .template-convert-tools {
        display: grid;
        gap: 10px;
        align-self: start;
    }
    .template-convert-tools label {
        display: grid;
        gap: 4px;
        margin: 0;
        color: #334155;
        font-size: 12px;
        font-weight: 700;
    }
    .template-convert-tools input[type="range"] {
        width: 100%;
    }
    .template-convert-check {
        display: flex !important;
        align-items: center;
        gap: 8px !important;
    }
    .template-convert-check input {
        margin: 0;
    }
    .template-magic-tools {
        display: none;
        gap: 10px;
        border-left: 3px solid #2563eb;
        padding: 8px 0 8px 10px;
        background: #f8fafc;
        border-radius: 4px;
    }
    .template-magic-tools.is-open {
        display: grid;
    }
    .template-convert-canvas-wrap.magic-active {
        cursor: crosshair;
        outline: 2px solid #2563eb;
        outline-offset: -2px;
    }
    .template-convert-meta {
        color: #64748b;
        font-size: 12px;
        line-height: 1.45;
        word-break: break-word;
    }
    .template-convert-actions {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 8px;
    }
    .template-convert-actions .btn {
        min-height: 34px;
        white-space: normal;
        width: 100%;
    }
    @media (max-width: 760px) {
        .template-convert-workspace {
            grid-template-columns: 1fr;
        }
    }
</style>

<script>
    var templateConvertState = {
        input: null,
        files: [],
        image: null,
        objectUrl: '',
        previewUrl: '',
        ratio: 1,
        magicColor: null,
        magicPoint: null,
        magicSelections: [],
        allowMagic: true,
        sourceCanvas: null,
        busy: false
    };

    function isTemplateWebpFile(file) {
        return file && (/\.webp$/i.test(file.name || '') || file.type === 'image/webp');
    }

    function isTemplateConvertibleFile(file) {
        return file && (/^image\/(png|jpe?g)$/i.test(file.type) || /\.(png|jpe?g)$/i.test(file.name || ''));
    }

    function hasTemplateConvertibleFiles(input) {
        return Array.prototype.some.call(input.files || [], isTemplateConvertibleFile);
    }

    function formatTemplateFileSize(bytes) {
        if (!bytes) {
            return '0 KB';
        }
        if (bytes >= 1048576) {
            return (bytes / 1048576).toFixed(2) + ' MB';
        }
        return Math.max(1, Math.round(bytes / 1024)) + ' KB';
    }

    function getTemplateWebpName(name) {
        return String(name || 'template-image').replace(/\.[^.]+$/, '') + '.webp';
    }

    function drawTemplateConvertPreview() {
        if (!templateConvertState.image) {
            syncTemplateMagicTool();
            return;
        }
        syncTemplateMagicTool();

        var originalCanvas = document.getElementById('templateConvertOriginalCanvas');
        var outputCanvas = document.getElementById('templateConvertOutputCanvas');
        var quality = document.getElementById('templateConvertQuality');
        var qualityValue = document.getElementById('templateConvertQualityValue');
        var effort = document.getElementById('templateConvertEffort');
        var effortValue = document.getElementById('templateConvertEffortValue');
        var magicEnabled = document.getElementById('templateMagicWandEnabled');
        var magicTolerance = document.getElementById('templateMagicTolerance');
        var magicFeather = document.getElementById('templateMagicFeather');
        var output = document.getElementById('templateConvertOutput');
        var size = document.getElementById('templateConvertSize');
        var width = Math.max(1, templateConvertState.image.naturalWidth);
        var height = Math.max(1, templateConvertState.image.naturalHeight);
        var originalContext = originalCanvas.getContext('2d');
        var wrap = document.getElementById('templateConvertCanvasWrap');
        var maxPreviewWidth = 650;
        var maxPreviewHeight = Math.max(280, Math.min(window.innerHeight * 0.64, 560));
        var previewScale = Math.min(1, maxPreviewWidth / width, maxPreviewHeight / height);
        var previewWidth = Math.max(1, Math.round(width * previewScale));
        var previewHeight = Math.max(1, Math.round(height * previewScale));

        originalCanvas.width = width;
        originalCanvas.height = height;
        outputCanvas.width = width;
        outputCanvas.height = height;
        wrap.style.width = previewWidth + 'px';
        wrap.style.height = previewHeight + 'px';
        originalContext.clearRect(0, 0, width, height);
        originalContext.drawImage(templateConvertState.image, 0, 0, width, height);
        templateConvertState.sourceCanvas = originalCanvas;
        qualityValue.textContent = quality.value;
        effortValue.textContent = effort.value;
        document.getElementById('templateMagicToleranceValue').textContent = magicTolerance.value;
        document.getElementById('templateMagicFeatherValue').textContent = magicFeather.value;
        size.textContent = 'Size: ' + width + ' x ' + height + ' px (fixed)';

        encodeTemplateImageToWebp(templateConvertState.image, width, height, (parseInt(quality.value, 10) || 90) / 100, parseInt(effort.value, 10) || 0, magicEnabled.checked ? templateConvertState.magicSelections : [], parseInt(magicTolerance.value, 10) || 0, parseInt(magicFeather.value, 10) || 0)
            .then(function(blob) {
                if (!blob) {
                    output.textContent = 'Output: WebP';
                    return;
                }
                if (templateConvertState.previewUrl) {
                    URL.revokeObjectURL(templateConvertState.previewUrl);
                }
                templateConvertState.previewUrl = URL.createObjectURL(blob);
                var compressedImage = new Image();
                compressedImage.onload = function() {
                    var outputContext = outputCanvas.getContext('2d');
                    outputContext.clearRect(0, 0, width, height);
                    outputContext.drawImage(compressedImage, 0, 0, width, height);
                    output.textContent = 'Output: WebP, ' + formatTemplateFileSize(blob.size);
                    updateTemplateConvertCompare();
                };
                compressedImage.src = templateConvertState.previewUrl;
            });
    }

    function updateTemplateConvertCompare() {
        var compare = document.getElementById('templateConvertCompare');
        var value = parseInt(compare.value, 10) || 50;
        document.getElementById('templateConvertCompareValue').textContent = value + '%';
        document.getElementById('templateConvertOutputCanvas').style.clipPath = 'inset(0 0 0 ' + value + '%)';
        document.getElementById('templateConvertDivider').style.left = value + '%';
    }

    function syncTemplateMagicTool() {
        var enabled = document.getElementById('templateMagicWandEnabled').checked && templateConvertState.allowMagic;
        var checkbox = document.getElementById('templateMagicWandEnabled');
        var checkboxRow = document.getElementById('templateMagicToggleRow');
        var tools = document.getElementById('templateMagicTools');
        var wrap = document.getElementById('templateConvertCanvasWrap');
        var info = document.getElementById('templateMagicInfo');

        checkboxRow.style.display = templateConvertState.allowMagic ? 'flex' : 'none';
        checkbox.disabled = !templateConvertState.allowMagic;
        if (!templateConvertState.allowMagic) {
            checkbox.checked = false;
            templateConvertState.magicColor = null;
            templateConvertState.magicPoint = null;
            templateConvertState.magicSelections = [];
        }
        tools.classList.toggle('is-open', enabled);
        wrap.classList.toggle('magic-active', enabled);

        if (enabled && !templateConvertState.magicSelections.length) {
            info.textContent = 'Magic Wand active: click image color to remove';
        }
        if (!enabled) {
            info.textContent = 'Magic Wand active: click image color to remove';
        }
    }

    function pickTemplateMagicColor(event) {
        if (!templateConvertState.allowMagic || !document.getElementById('templateMagicWandEnabled').checked || !templateConvertState.sourceCanvas) {
            return;
        }

        var canvas = templateConvertState.sourceCanvas;
        var rect = canvas.getBoundingClientRect();
        var x = Math.floor((event.clientX - rect.left) * canvas.width / rect.width);
        var y = Math.floor((event.clientY - rect.top) * canvas.height / rect.height);

        if (x < 0 || y < 0 || x >= canvas.width || y >= canvas.height) {
            return;
        }

        var pixel = canvas.getContext('2d').getImageData(x, y, 1, 1).data;
        templateConvertState.magicColor = {
            r: pixel[0],
            g: pixel[1],
            b: pixel[2]
        };
        templateConvertState.magicPoint = {
            x: x,
            y: y
        };
        templateConvertState.magicSelections.push({
            color: templateConvertState.magicColor,
            point: templateConvertState.magicPoint
        });
        document.getElementById('templateMagicInfo').textContent = 'Magic Wand selections: ' + templateConvertState.magicSelections.length + ' (last rgb(' + pixel[0] + ', ' + pixel[1] + ', ' + pixel[2] + '))';
        drawTemplateConvertPreview();
    }

    function openTemplateImageConvert(input) {
        var firstConvertible = Array.prototype.find.call(input.files || [], isTemplateConvertibleFile);

        if (!firstConvertible) {
            return false;
        }

        cancelTemplateImageConvert(false);
        templateConvertState.input = input;
        templateConvertState.files = Array.prototype.slice.call(input.files || []);
        templateConvertState.allowMagic = !input.classList.contains('template-show-image-input');
        syncTemplateMagicTool();
        templateConvertState.objectUrl = URL.createObjectURL(firstConvertible);
        templateConvertState.image = new Image();

        templateConvertState.image.onload = function() {
            var modal = document.getElementById('templateConvertModal');
            var meta = document.getElementById('templateConvertMeta');

            templateConvertState.ratio = templateConvertState.image.naturalWidth / templateConvertState.image.naturalHeight;
            document.getElementById('templateConvertQuality').value = 90;
            document.getElementById('templateConvertEffort').value = 4;
            document.getElementById('templateConvertCompare').value = 50;
            document.getElementById('templateMagicWandEnabled').checked = false;
            templateConvertState.magicColor = null;
            templateConvertState.magicPoint = null;
            templateConvertState.magicSelections = [];
            syncTemplateMagicTool();
            meta.textContent = 'Source: ' + firstConvertible.name + ', ' + formatTemplateFileSize(firstConvertible.size);
            modal.classList.add('is-open');
            modal.setAttribute('aria-hidden', 'false');
            drawTemplateConvertPreview();
        };

        templateConvertState.image.onerror = function() {
            alert('Image preview failed. Please choose another image.');
            cancelTemplateImageConvert(true);
        };

        templateConvertState.image.src = templateConvertState.objectUrl;
        return true;
    }

    function cancelTemplateImageConvert(clearInput) {
        var modal = document.getElementById('templateConvertModal');

        if (modal) {
            modal.classList.remove('is-open');
            modal.setAttribute('aria-hidden', 'true');
        }

        if (templateConvertState.objectUrl) {
            URL.revokeObjectURL(templateConvertState.objectUrl);
        }
        if (templateConvertState.previewUrl) {
            URL.revokeObjectURL(templateConvertState.previewUrl);
        }

        if (clearInput && templateConvertState.input) {
            templateConvertState.input.value = '';
        }

        templateConvertState.input = null;
        templateConvertState.files = [];
        templateConvertState.image = null;
        templateConvertState.objectUrl = '';
        templateConvertState.previewUrl = '';
        templateConvertState.ratio = 1;
        templateConvertState.magicColor = null;
        templateConvertState.magicPoint = null;
        templateConvertState.magicSelections = [];
        templateConvertState.allowMagic = true;
        templateConvertState.sourceCanvas = null;
        templateConvertState.busy = false;
    }

    function isTemplateMagicMatch(data, index, magicColor, maxDistance) {
        var dr = data[index] - magicColor.r;
        var dg = data[index + 1] - magicColor.g;
        var db = data[index + 2] - magicColor.b;
        return Math.sqrt(dr * dr + dg * dg + db * db) <= maxDistance;
    }

    function applyTemplateMagicWand(canvas, magicColor, magicPoint, tolerance, feather) {
        if (!magicColor || !magicPoint) {
            return;
        }

        var context = canvas.getContext('2d');
        var imageData = context.getImageData(0, 0, canvas.width, canvas.height);
        var data = imageData.data;
        var width = canvas.width;
        var height = canvas.height;
        var maxDistance = Math.max(0, tolerance) * 1.732;
        var startX = Math.max(0, Math.min(width - 1, magicPoint.x));
        var startY = Math.max(0, Math.min(height - 1, magicPoint.y));
        var startIndex = (startY * width + startX) * 4;
        var visited = new Uint8Array(width * height);
        var selected = new Uint8Array(width * height);
        var stack = [startY * width + startX];

        if (!isTemplateMagicMatch(data, startIndex, magicColor, maxDistance)) {
            return;
        }

        while (stack.length) {
            var pos = stack.pop();
            if (visited[pos]) {
                continue;
            }
            visited[pos] = 1;

            var px = pos % width;
            var py = Math.floor(pos / width);
            var offset = pos * 4;

            if (!isTemplateMagicMatch(data, offset, magicColor, maxDistance)) {
                continue;
            }

            selected[pos] = 1;
            data[offset + 3] = 0;

            if (px > 0) stack.push(pos - 1);
            if (px < width - 1) stack.push(pos + 1);
            if (py > 0) stack.push(pos - width);
            if (py < height - 1) stack.push(pos + width);
        }

        var featherRadius = Math.max(0, parseInt(feather, 10) || 0);
        if (featherRadius > 0) {
            var featherAlpha = Math.max(0.2, 1 - featherRadius / 80);
            for (var y = 0; y < height; y++) {
                for (var x = 0; x < width; x++) {
                    var pixelPos = y * width + x;
                    if (selected[pixelPos]) {
                        continue;
                    }
                    var nearSelected = false;
                    for (var fy = -featherRadius; fy <= featherRadius && !nearSelected; fy++) {
                        for (var fx = -featherRadius; fx <= featherRadius; fx++) {
                            if (fx * fx + fy * fy > featherRadius * featherRadius) {
                                continue;
                            }
                            var nx = x + fx;
                            var ny = y + fy;
                            if (nx >= 0 && ny >= 0 && nx < width && ny < height && selected[ny * width + nx]) {
                                nearSelected = true;
                                break;
                            }
                        }
                    }
                    if (nearSelected) {
                        var alphaIndex = pixelPos * 4 + 3;
                        data[alphaIndex] = Math.round(data[alphaIndex] * featherAlpha);
                    }
                }
            }
        }

        context.putImageData(imageData, 0, 0);
    }

    function encodeTemplateImageToWebp(image, width, height, quality, effort, magicSelections, tolerance, feather) {
        var canvas = document.createElement('canvas');
        var context = canvas.getContext('2d');
        var passCount = Math.max(1, Math.min(4, Math.ceil(((effort || 0) + 1) / 2)));
        var qualities = [quality];

        canvas.width = Math.max(1, width);
        canvas.height = Math.max(1, height);
        context.drawImage(image, 0, 0, canvas.width, canvas.height);
        (magicSelections || []).forEach(function(selection) {
            applyTemplateMagicWand(canvas, selection.color, selection.point, tolerance, feather);
        });

        for (var i = 1; i < passCount; i++) {
            qualities.push(Math.max(0.01, quality - (i * 0.015)));
        }

        return Promise.all(qualities.map(function(candidateQuality) {
            return new Promise(function(resolve) {
                canvas.toBlob(function(blob) {
                    resolve(blob);
                }, 'image/webp', candidateQuality);
            });
        })).then(function(blobs) {
            return blobs.reduce(function(best, blob) {
                if (!blob) {
                    return best;
                }
                return !best || blob.size < best.size ? blob : best;
            }, null);
        });
    }

    function convertTemplateFileToWebp(file, quality, effort, magicSelections, tolerance, feather) {
        return new Promise(function(resolve, reject) {
            if (isTemplateWebpFile(file)) {
                resolve(file);
                return;
            }

            if (!isTemplateConvertibleFile(file)) {
                reject(new Error('Only JPG, PNG, or WebP images are allowed'));
                return;
            }

            var image = new Image();
            var url = URL.createObjectURL(file);

            image.onload = function() {
                URL.revokeObjectURL(url);
                encodeTemplateImageToWebp(image, image.naturalWidth, image.naturalHeight, quality, effort, magicSelections, tolerance, feather).then(function(blob) {
                    if (!blob) {
                        reject(new Error('WebP conversion failed'));
                        return;
                    }
                    resolve(new File([blob], getTemplateWebpName(file.name), {
                        type: 'image/webp',
                        lastModified: Date.now()
                    }));
                });
            };

            image.onerror = function() {
                URL.revokeObjectURL(url);
                reject(new Error('Image conversion failed'));
            };

            image.src = url;
        });
    }

    function saveTemplateImageConvert() {
        if (!templateConvertState.input || templateConvertState.busy) {
            return;
        }

        templateConvertState.busy = true;
        var input = templateConvertState.input;
        var quality = (parseInt(document.getElementById('templateConvertQuality').value, 10) || 90) / 100;
        var effort = parseInt(document.getElementById('templateConvertEffort').value, 10) || 0;
        var magicEnabled = document.getElementById('templateMagicWandEnabled').checked && templateConvertState.allowMagic;
        var magicSelections = magicEnabled ? templateConvertState.magicSelections : [];
        var tolerance = parseInt(document.getElementById('templateMagicTolerance').value, 10) || 0;
        var feather = parseInt(document.getElementById('templateMagicFeather').value, 10) || 0;
        var dataTransfer = new DataTransfer();

        Promise.all(templateConvertState.files.map(function(file) {
            if (isTemplateConvertibleFile(file)) {
                return convertTemplateFileToWebp(file, quality, effort, magicSelections, tolerance, feather);
            }
            return Promise.resolve(file);
        })).then(function(files) {
            files.forEach(function(file) {
                dataTransfer.items.add(file);
            });
            input.files = dataTransfer.files;
            input.dataset.webpReady = '1';
            var isShowImage = input.classList.contains('template-show-image-input');

            cancelTemplateImageConvert(false);
            if (isShowImage) {
                previewSelectedShowImage(input);
            } else {
                previewSelectedTemplateImages(input);
            }
        }).catch(function(error) {
            alert(error.message || 'Image conversion failed');
            templateConvertState.busy = false;
        });
    }

    function openTemplatePreview(element) {
        var modal = document.getElementById('templatePreviewModal');
        var title = document.getElementById('templatePreviewTitle');
        var image = document.getElementById('templatePreviewImage');
        var file = document.getElementById('templatePreviewFile');

        if (!modal || !title || !image || !file) {
            return false;
        }

        var previewTitle = element.getAttribute('data-preview-title') || 'Image Preview';
        title.textContent = previewTitle;
        image.src = element.getAttribute('data-preview-src') || element.href;
        image.alt = previewTitle;
        file.textContent = element.getAttribute('data-preview-file') || '';
        modal.classList.add('is-open');
        modal.setAttribute('aria-hidden', 'false');

        return false;
    }

    function closeTemplatePreview() {
        var modal = document.getElementById('templatePreviewModal');
        var image = document.getElementById('templatePreviewImage');

        if (modal) {
            modal.classList.remove('is-open');
            modal.setAttribute('aria-hidden', 'true');
        }

        if (image) {
            image.src = '';
            image.alt = '';
        }
    }

    function addSelectedImagePreview(container, file, title) {
        if (!container || !file || !file.type || file.type.indexOf('image/') !== 0) {
            return;
        }

        var reader = new FileReader();
        reader.onload = function(event) {
            var link = document.createElement('a');
            link.href = event.target.result;
            link.className = 'template-preview-thumb';
            link.setAttribute('data-preview-src', event.target.result);
            link.setAttribute('data-preview-title', title);
            link.setAttribute('data-preview-file', file.name);
            link.onclick = function() {
                return openTemplatePreview(this);
            };

            var image = document.createElement('img');
            image.src = event.target.result;
            image.alt = title;

            var label = document.createElement('span');
            label.textContent = title;

            link.appendChild(image);
            link.appendChild(label);
            container.appendChild(link);
        };
        reader.readAsDataURL(file);
    }

    function previewSelectedTemplateImages(input) {
        if (input.dataset.webpReady === '1') {
            input.dataset.webpReady = '';
        } else if (hasTemplateConvertibleFiles(input)) {
            openTemplateImageConvert(input);
            return;
        }

        var languageId = input.id.replace('file', '');
        var checkedLanguage = document.querySelector('input[name="language[]"]:checked');
        if (!checkedLanguage || checkedLanguage.value !== languageId) {
            input.value = '';
            var inactivePreview = document.getElementById('selectedPreview' + languageId);
            if (inactivePreview) {
                inactivePreview.innerHTML = '';
            }
            return;
        }

        var preview = document.getElementById('selectedPreview' + input.id.replace('file', ''));
        if (!preview) {
            return;
        }

        preview.innerHTML = '';
        Array.prototype.forEach.call(input.files || [], function(file, index) {
            addSelectedImagePreview(preview, file, 'Selected Image' + (index > 0 ? ' ' + (index + 1) : ''));
        });
    }

    function previewSelectedShowImage(input) {
        if (input.dataset.webpReady === '1') {
            input.dataset.webpReady = '';
        } else if (hasTemplateConvertibleFiles(input)) {
            openTemplateImageConvert(input);
            return;
        }

        var preview = document.getElementById('selectedShowImagePreview');
        if (!preview) {
            return;
        }

        preview.innerHTML = '';
        if (input.files && input.files[0]) {
            addSelectedImagePreview(preview, input.files[0], 'Selected Show Image');
        }
    }

    function get_category(category_id) {
        var csrfToken = $('meta[name="csrf-token"]').attr("content");
        $.ajax({
            url: "<?= Url::toRoute(['template/category']) ?>",
            method: "POST",
            data: {
                category_id: category_id,
                '_csrf-backend': csrfToken
            },
            success: function(result) {
                if (result) {
                    $('#template-subcategory_id').html('<option value="">Select</option>');
                    var category = JSON.parse(result);
                    $.each(category, function(key, value) {
                        $('#template-subcategory_id').append('<option value="' + key + '">' + value + '</option>');
                    });
                }
            }
        });
    }
</script>

<script>
    function get_file(data) {
        <?php foreach ($language as $value) { ?>
            if (<?= $value->id ?> !== data) {
                var hiddenInput<?= $value->id ?> = document.getElementById('file<?= $value->id ?>');
                var hiddenPreview<?= $value->id ?> = document.getElementById('selectedPreview<?= $value->id ?>');
                if (hiddenInput<?= $value->id ?>) {
                    hiddenInput<?= $value->id ?>.style.display = 'none';
                    hiddenInput<?= $value->id ?>.removeAttribute('required');
                    hiddenInput<?= $value->id ?>.value = '';
                }
                if (hiddenPreview<?= $value->id ?>) {
                    hiddenPreview<?= $value->id ?>.innerHTML = '';
                }
            }
        <?php } ?>
        var activeInput = document.getElementById('file' + data);
        if (activeInput) {
            activeInput.style.display = '';
            activeInput.setAttribute('required', 'required');
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        var checkedLanguageInput = document.querySelector('input[name="language[]"]:checked');
        var checkedLanguage = checkedLanguageInput ? checkedLanguageInput.value : '';
        if (checkedLanguage) {
            var activeInput = document.getElementById('file' + checkedLanguage);
            if (activeInput) {
                activeInput.style.display = '';
            }
        }

        document.getElementById('templateConvertCompare').addEventListener('input', updateTemplateConvertCompare);

        document.getElementById('templateConvertCanvasWrap').addEventListener('click', pickTemplateMagicColor);

        document.getElementById('templateMagicWandEnabled').addEventListener('change', function() {
            if (!this.checked) {
                templateConvertState.magicColor = null;
                templateConvertState.magicPoint = null;
                templateConvertState.magicSelections = [];
            }
            syncTemplateMagicTool();
            drawTemplateConvertPreview();
        });

        ['templateConvertQuality', 'templateConvertEffort', 'templateMagicTolerance', 'templateMagicFeather'].forEach(function(id) {
            var control = document.getElementById(id);
            if (control) {
                control.addEventListener('input', drawTemplateConvertPreview);
                control.addEventListener('change', drawTemplateConvertPreview);
            }
        });

        document.getElementById('templatePreviewModal').addEventListener('click', function(event) {
            if (event.target === this) {
                closeTemplatePreview();
            }
        });

        document.getElementById('templateConvertModal').addEventListener('click', function(event) {
            if (event.target === this) {
                cancelTemplateImageConvert(true);
            }
        });

        document.addEventListener('keyup', function(event) {
            if (event.key === 'Escape') {
                closeTemplatePreview();
                cancelTemplateImageConvert(false);
            }
        });
    });
</script>



<!-- <script>
    function get_check(){
        alert('please fill all field');
    }
</script> -->
