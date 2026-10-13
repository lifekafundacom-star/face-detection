<?php

use common\models\Template;
use common\models\TemplateMapping;
use common\components\WorkPointService;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\grid\ActionColumn;
use yii\grid\GridView;
use yii\widgets\Pjax;

/** @var yii\web\View $this */
/** @var common\models\TemplateSearch $searchModel */
/** @var yii\data\ActiveDataProvider $dataProvider */

$this->title = Yii::t('app', 'Templates');
$this->params['breadcrumbs'][] = $this->title;
$workPointService = new WorkPointService();
$canVerifyTemplate = $workPointService->canVerifyCurrentUser();
$textPreview = function ($text, $label) {
    $text = (string) $text;
    $plainText = trim(strip_tags(html_entity_decode($text)));

    if ($plainText === '') {
        return '<span class="text-muted">-</span>';
    }

    return Html::tag('div',
        Html::tag('div', Html::encode($plainText), ['class' => 'admin-text-preview-copy']) .
        Html::button('See more', [
            'type' => 'button',
            'class' => 'admin-see-more',
            'data-admin-text-title' => $label,
            'data-admin-text-preview' => nl2br(Html::encode($plainText)),
        ]),
        ['class' => 'admin-text-preview']
    );
};
$templateDetailsPreview = function ($product) {
    if (!$product) {
        return '<span class="text-muted">-</span>';
    }

    $details = [
        'Product Description' => $product->description,
        'Product Caption' => $product->caption,
        'Product Meta Title' => $product->meta_title,
        'Product Meta Description' => $product->meta_description,
        'Product Keyword' => $product->keywords,
        'Product Alt' => $product->alt,
        'Product Tag' => $product->tag,
    ];
    $summaryParts = [];
    $detailHtml = [];

    foreach ($details as $label => $value) {
        $plainText = trim(strip_tags(html_entity_decode((string) $value)));
        if ($plainText === '') {
            continue;
        }

        if (count($summaryParts) < 2) {
            $summaryParts[] = $label . ': ' . $plainText;
        }

        $detailHtml[] = '<p><strong>' . Html::encode($label) . ':</strong><br>' . nl2br(Html::encode($plainText)) . '</p>';
    }

    if (!$detailHtml) {
        return '<span class="text-muted">-</span>';
    }

    return Html::tag('div',
        Html::tag('div', Html::encode(implode(' | ', $summaryParts)), ['class' => 'admin-text-preview-copy']) .
        Html::button('See more', [
            'type' => 'button',
            'class' => 'admin-see-more',
            'data-admin-text-title' => 'Template Details',
            'data-admin-text-preview' => implode('', $detailHtml),
        ]),
        ['class' => 'admin-text-preview']
    );
};
?>
<div class="template-index admin-list-page">

    <div class="admin-list-header">
        <div>
            <span class="admin-list-kicker">Design setup</span>
            <h1><?= Html::encode($this->title) ?></h1>
            <p>Review template types, linked images, and product metadata.</p>
        </div>
        <?= Html::a(Yii::t('app', 'Create Template'), ['create'], ['class' => 'btn admin-primary-btn']) ?>
    </div>

    <?php Pjax::begin(); ?>
    <?php // echo $this->render('_search', ['model' => $searchModel]); 
    ?>

    <div class="admin-grid-card">
    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'layout' => '<div class="admin-grid-toolbar"><div class="admin-grid-summary">{summary}</div></div><div class="admin-table-scroll">{items}</div><div class="admin-grid-footer">{pager}</div>',
        'summary' => 'Showing <strong>{begin}-{end}</strong> of <strong>{totalCount}</strong> templates',
        'emptyText' => 'No matching templates found.',
        'tableOptions' => ['class' => 'table admin-data-table admin-asset-table'],
        'rowOptions' => ['class' => 'admin-data-row'],
        'filterRowOptions' => ['class' => 'admin-filter-row'],
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],

            [
                'attribute' => 'product_id',
                'label' => 'ID',
                'filterInputOptions' => ['class' => 'form-control', 'placeholder' => 'ID'],
                'contentOptions' => ['class' => 'template-map-image-no'],
                'value' => function ($model) {
                    return $model->product ? '#' . (int) $model->product->id : '-';
                },
            ],
            [
                'attribute' => 'template_name',
                'contentOptions' => ['class' => 'admin-text-cell'],
                'filterInputOptions' => ['class' => 'form-control', 'placeholder' => 'Template name'],
            ],
            [
                'attribute' => 'image_type',
                'format' => 'raw',
                'filterInputOptions' => ['class' => 'form-control', 'placeholder' => 'Image type'],
                'value' => function ($model) {
                    return Html::tag('span', Html::encode($model->image_type), ['class' => 'tm-soft-badge']);
                },
            ],
            [
                'label' => 'Images',
                'attribute' => 'images',
                'format' => 'raw', 
                'filterInputOptions' => ['class' => 'form-control', 'placeholder' => 'Image file'],
                'value' => function ($product_model) {
                    $product = $product_model->product ?? null;
                    if ($product && $product->images) {
                    $img = Yii::getAlias('@storageUrl').'/images/'.$product_model->product->images;
                    return Html::button('<img class="tm-thumb-img" src="'.$img.'" alt="Image" loading="lazy">', [
                        'type' => 'button',
                        'class' => 'tm-thumb-button',
                        'data-admin-preview-src' => $img,
                        'data-admin-preview-title' => $product->title ?: $product_model->template_name,
                        'data-admin-preview-file' => $product->images,
                    ]);
                    }
                    return '<span class="text-muted">-</span>';
                }
            ],
            [
                'label' => 'Show Image',
                'attribute' => 'show_image',
                'format' => 'raw', 
                'filterInputOptions' => ['class' => 'form-control', 'placeholder' => 'Show image'],
                'value' => function ($product_model) {
                    $product = $product_model->product ?? null;
                    if ($product && $product->show_image) {
                    $img1 = Yii::getAlias('@storageUrl').'/images/'.$product_model->product->show_image;
                    return Html::button('<img class="tm-thumb-img" src="'.$img1.'" alt="Show image" loading="lazy">', [
                        'type' => 'button',
                        'class' => 'tm-thumb-button',
                        'data-admin-preview-src' => $img1,
                        'data-admin-preview-title' => $product->title ?: $product_model->template_name,
                        'data-admin-preview-file' => $product->show_image,
                    ]);
                    }
                    return '<span class="text-muted">-</span>';
                }
            ],
            [
                'label' => 'Product Title',
                'attribute' => 'title',
                'format' => 'raw',
                'filterInputOptions' => ['class' => 'form-control', 'placeholder' => 'Product title'],
                'value' => function ($product_model) use ($textPreview) {
                    return $textPreview(($product_model->product && $product_model->product->title) ? $product_model->product->title : "", 'Product Title');
                }
            ],
            [
                'attribute' => 'page_url',
                'label' => 'Page URL',
                'format' => 'raw',
                'contentOptions' => ['class' => 'template-map-url'],
                'filterInputOptions' => ['class' => 'form-control', 'placeholder' => 'Paste page URL'],
                'value' => function ($model) {
                    $product = $model->product ?? null;
                    if (!$product) {
                        return '<span class="text-muted">No product found</span>';
                    }

                    $mapping = $model->mapping;

                    if (!$mapping || !$mapping->categoryname) {
                        return '<span class="text-muted">No mapping found</span>';
                    }

                    $title = $product->getCreatecardSlug();
                    $url = Yii::getAlias('@frontendUrl') . '/site/createcard/' . $mapping->categoryname->category_name . '/' . $product->id . '/' . $title;

                    return Html::tag('div',
                        Html::a($url, $url, [
                            'target' => '_blank',
                            'class' => 'tm-page-url',
                            'title' => 'Open product #' . (int) $product->id,
                        ]) .
                        Html::button('Copy', [
                            'type' => 'button',
                            'class' => 'tm-copy-url',
                            'data-url' => $url,
                            'title' => 'Copy URL',
                        ]),
                        ['class' => 'tm-url-row']
                    );
                },
            ],
            [
                'label' => 'Template Details',
                'attribute' => 'template_details',
                'format' => 'raw',
                'contentOptions' => ['class' => 'admin-text-cell'],
                'filterInputOptions' => ['class' => 'form-control', 'placeholder' => 'Search details'],
                'value' => function ($product_model) use ($templateDetailsPreview) {
                    return $templateDetailsPreview($product_model->product ?? null);
                }
            ],
            [
                'attribute' => 'status',
                'label' => 'Status',
                'format' => 'raw',
                'filter' => [
                    'photo_pending' => 'Photo Pending',
                    'content_pending' => 'Content Pending',
                    'verification_pending' => 'Verification Pending',
                    'verified' => 'Verified',
                    'rejected' => 'Changes Requested',
                ],
                'filterInputOptions' => ['class' => 'form-control', 'prompt' => 'All Statuses'],
                'value' => function ($model) use ($workPointService, $canVerifyTemplate) {
                    $workflow = $workPointService->workflowForTemplate((int)$model->id);
                    $status = $workflow['status'] ?? 'verified';
                    $labels = [
                        'photo_pending' => 'Photo Pending',
                        'content_pending' => 'Content Pending',
                        'verification_pending' => 'Verification Pending',
                        'verified' => 'Verified',
                        'rejected' => 'Changes Requested',
                    ];
                    $html = Html::tag('span', Html::encode($labels[$status] ?? $status), ['class' => 'template-status-badge status-' . Html::encode($status)]);
                    $stage = 1;
                    if ($status === 'content_pending') {
                        $stage = 2;
                    } elseif (in_array($status, ['verification_pending', 'verified', 'rejected'], true)) {
                        $stage = 3;
                    }

                    if ($status === 'content_pending') {
                        $html .= Html::a('Continue Content', ['template/update', 'id' => $model->id, 'stage' => 2], ['class' => 'template-status-action']);
                    } elseif (in_array($status, ['verification_pending', 'rejected'], true)) {
                        $html .= Html::a('Review', ['template/update', 'id' => $model->id, 'stage' => 3], ['class' => 'template-status-action']);
                        if ($canVerifyTemplate) {
                            $html .= Html::beginForm(['template/verify', 'id' => $model->id], 'post', ['class' => 'template-status-form'])
                                . Html::submitButton('Verify', ['class' => 'template-status-verify', 'name' => 'decision', 'value' => 'verify'])
                                . Html::endForm();
                        }
                    }
                    $html .= Html::a('Edit', ['template/update', 'id' => $model->id, 'stage' => $stage], ['class' => 'template-status-action']);

                    return Html::tag('div', $html, ['class' => 'template-status-cell']);
                },
            ],
            [
                'class' => 'yii\grid\ActionColumn',
                'template' => ' {update}',
                'contentOptions' => ['class' => 'admin-action-cell'],
                'buttons' => [
                    'update' => function ($url, $model) use ($workPointService) {
                        $workflow = $workPointService->workflowForTemplate((int)$model->id);
                        $status = $workflow['status'] ?? 'verified';
                        $stage = $status === 'content_pending' ? 2 : (in_array($status, ['verification_pending', 'verified', 'rejected'], true) ? 3 : 1);

                        return Html::a('Edit', ['template/update', 'id' => $model->id, 'stage' => $stage], [
                            'class' => 'admin-row-edit-btn',
                            'title' => 'Edit template',
                        ]);
                    },
                ],
            ]
        ],
    ]); ?>
    </div>

    <?php Pjax::end(); ?>

</div>
<?php
$this->registerJs(<<<JS
$(document).on('click', '.tm-copy-url', function () {
    var button = $(this);
    var url = button.data('url');

    function done() {
        var oldText = button.text();
        button.text('Copied');
        setTimeout(function () {
            button.text(oldText);
        }, 1200);
    }

    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(url).then(done);
    } else {
        var input = $('<textarea readonly></textarea>').val(url).css({
            position: 'fixed',
            left: '-9999px',
            top: '-9999px'
        }).appendTo('body');
        input[0].select();
        document.execCommand('copy');
        input.remove();
        done();
    }
});
JS);
?>
