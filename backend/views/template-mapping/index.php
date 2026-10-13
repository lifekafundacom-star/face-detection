<?php

use common\models\TemplateMapping;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\grid\ActionColumn;
use yii\grid\GridView;
use yii\widgets\Pjax;
/** @var yii\web\View $this */
/** @var common\models\TemplateMappingSearch $searchModel */
/** @var yii\data\ActiveDataProvider $dataProvider */

$this->title = Yii::t('app', 'Template Mappings');
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="template-mapping-index admin-list-page">

    <div class="admin-list-header">
        <div>
            <span class="admin-list-kicker">Content setup</span>
            <h1><?= Html::encode($this->title) ?></h1>
            <p>Manage template links, image numbers, titles, and page URLs from one place.</p>
        </div>
        <?= Html::a(Yii::t('app', 'Create Template Mapping'), ['create'], ['class' => 'btn admin-primary-btn']) ?>
    </div>

    <?php Pjax::begin(); ?>
    <?php // echo $this->render('_search', ['model' => $searchModel]); ?>

    <div class="admin-grid-card">
    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'layout' => '<div class="admin-grid-toolbar"><div class="admin-grid-summary">{summary}</div></div><div class="admin-table-scroll">{items}</div><div class="admin-grid-footer">{pager}</div>',
        'summary' => 'Showing <strong>{begin}-{end}</strong> of <strong>{totalCount}</strong> mappings',
        'emptyText' => 'No matching template mappings found.',
        'tableOptions' => ['class' => 'table admin-data-table template-mapping-table'],
        'rowOptions' => ['class' => 'admin-data-row'],
        'filterRowOptions' => ['class' => 'admin-filter-row'],
        'columns' => [
           [
            'attribute' => 'id',
            'label' => '#',
            'contentOptions' => ['class' => 'template-map-id'],
            'filterInputOptions' => [
                'class' => 'form-control',
                'placeholder' => 'ID',
            ],
           ],
           [
            'label' => 'Image',
            'format' => 'raw',
            'contentOptions' => ['class' => 'tm-preview-cell'],
            'value' => function ($model) {
                $products = $model->products;
                if (empty($products)) {
                    return '<span class="text-muted">-</span>';
                }

                $thumbs = [];
                foreach ($products as $product) {
                    $image = $product->show_image ?: $product->images;
                    if (!$image) {
                        continue;
                    }

                    $title = $product->title ?: $product->item_name ?: 'Template image';
                    $src = Yii::getAlias('@storageUrl') . '/images/' . $image;
                    $thumbs[] = Html::button(
                        Html::img($src, [
                            'alt' => $product->alt ?: $title,
                            'class' => 'tm-thumb-img',
                            'loading' => 'lazy',
                        ]),
                        [
                            'type' => 'button',
                            'class' => 'tm-thumb-button',
                            'data-preview-src' => $src,
                            'data-preview-title' => $title,
                            'data-preview-file' => $image,
                            'title' => 'Preview ' . $title,
                        ]
                    );
                }

                return $thumbs ? implode('', $thumbs) : '<span class="text-muted">No image</span>';
            },
           ],
           [
            'attribute' => 'template_id',
            'format' => 'raw',
            'contentOptions' => ['class' => 'tm-template-name'],
            'filterInputOptions' => [
                'class' => 'form-control',
                'placeholder' => 'Search template',
            ],
            'value' => 'templatename.template_name'
           ],
           [
            'attribute' => 'title',
            'label' => 'Title',
            'format' => 'raw',
            'contentOptions' => ['class' => 'tm-title-cell'],
            'filterInputOptions' => [
                'class' => 'form-control',
                'placeholder' => 'Search title',
            ],
            'value' => function ($model) {
                $products = $model->products;
                if (empty($products)) {
                    return '<span class="text-muted">-</span>';
                }

                $titles = [];
                foreach ($products as $product) {
                    $titles[] = Html::encode($product->title ?: $product->item_name ?: '-');
                }

                return implode('<br>', $titles);
            },
           ],

           [
            'attribute' => 'category_id',
            'format' => 'raw',
            'contentOptions' => ['class' => 'tm-category-cell'],
            'filterInputOptions' => [
                'class' => 'form-control',
                'placeholder' => 'Category',
            ],
            'value' => function ($model) {
                return Html::tag('span', str_replace('_', ' ', $model->categoryname->category_name), ['class' => 'tm-soft-badge']);
            },
           ],

           [
            'attribute' => 'subcategory_id',
            'format' => 'raw',
            'contentOptions' => ['class' => 'tm-category-cell'],
            'filterInputOptions' => [
                'class' => 'form-control',
                'placeholder' => 'Subcategory',
            ],
            'value' => function ($model) {
               return str_replace('_', ' ', $model->subcategoryname->subcategory_name);
            },
           ],
           [
            'attribute' => 'publish_type',
            'label' => 'Mapping Type',
            'format' => 'raw',
            'filter' => TemplateMapping::publishTypeOptions(),
            'value' => function ($model) {
                $type = $model->publish_type ?: TemplateMapping::PUBLISH_TYPE_DIRECT;

                if ($type === TemplateMapping::PUBLISH_TYPE_SCHEDULED) {
                    $isComplete = $model->scheduled_at && strtotime($model->scheduled_at) <= time();
                    $label = $isComplete ? 'Scheduled Complete' : 'Scheduled';
                    $class = $isComplete ? 'tm-soft-badge tm-status-complete' : 'tm-soft-badge tm-status-scheduled';
                    $dateTime = $model->scheduled_at ? date('d M Y, h:i A', strtotime($model->scheduled_at)) : 'No time set';

                    return Html::tag('div',
                        Html::tag('span', $label, ['class' => $class]) . '<br>' .
                        Html::tag('small', Html::encode($dateTime), ['class' => 'text-muted'])
                    );
                }

                return Html::tag('span', 'Direct Mapping', ['class' => 'tm-soft-badge']);
            },
           ],
           [
            'attribute' => 'scheduled_at',
            'label' => 'Scheduled Date Time',
            'format' => 'raw',
            'filterInputOptions' => [
                'class' => 'form-control',
                'placeholder' => 'YYYY-MM-DD HH:MM',
            ],
            'value' => function ($model) {
                if (($model->publish_type ?: TemplateMapping::PUBLISH_TYPE_DIRECT) !== TemplateMapping::PUBLISH_TYPE_SCHEDULED) {
                    return '<span class="text-muted">Direct</span>';
                }

                if (!$model->scheduled_at) {
                    return '<span class="text-muted">Not set</span>';
                }

                $isComplete = strtotime($model->scheduled_at) <= time();
                $status = $isComplete ? 'Scheduled Complete' : 'Scheduled';
                $dateTime = date('d M Y, h:i A', strtotime($model->scheduled_at));

                return Html::tag('div',
                    Html::tag('strong', $status) . '<br>' . Html::tag('span', Html::encode($dateTime)),
                    ['class' => $isComplete ? 'tm-schedule-complete' : 'tm-schedule-pending']
                );
            },
           ],
           [
             'attribute' => 'featured_section',
             'label' => 'Homepage Section',
             'filter' => TemplateMapping::featuredSectionOptions(),
             'value' => function ($model) {
                 return TemplateMapping::featuredSectionOptions()[$model->featured_section] ?? 'Not Featured';
             },
            ],
           [
            'attribute' => 'image_unique_no',
            'label' => 'Image Unique No',
            'format' => 'raw',
            'contentOptions' => ['class' => 'template-map-image-no'],
            'filterInputOptions' => [
                'class' => 'form-control',
                'placeholder' => '#1346',
            ],
            'value' => function ($model) {
                $products = $model->products;
                if (empty($products)) {
                    return '<span class="text-muted">-</span>';
                }

                $ids = [];
                foreach ($products as $product) {
                    $ids[] = Html::tag('span', '#' . (int) $product->id, ['class' => 'tm-id-badge']);
                }

                return implode(' ', $ids);
            },
           ],
           [
            'attribute' => 'page_url',
            'label' => 'Page URL',
            'format' => 'raw',
            'contentOptions' => ['class' => 'template-map-url'],
            'filterInputOptions' => [
                'class' => 'form-control',
                'placeholder' => 'Paste page URL',
            ],
            'value' => function ($model) {
                $products = $model->products;
                if (empty($products) || empty($model->categoryname)) {
                    return '<span class="text-muted">No product found</span>';
                }

                $links = [];
                foreach ($products as $product) {
                    $title = $product->getCreatecardSlug();
                    $url = Yii::getAlias('@frontendUrl') . '/site/createcard/' . $model->categoryname->category_name . '/' . $product->id . '/' . $title;
                    $links[] = Html::tag('div',
                        Html::a($url, $url, [
                            'target' => '_blank',
                            'class' => 'tm-page-url',
                            'title' => 'Open image #' . (int) $product->id,
                        ]) .
                        Html::button('Copy', [
                            'type' => 'button',
                            'class' => 'tm-copy-url',
                            'data-url' => $url,
                            'title' => 'Copy URL',
                        ]),
                        ['class' => 'tm-url-row']
                    );
                }

                return implode('', $links);
            },
           ],
           [
            'label' => 'Page Views',
            'format' => 'raw',
            'contentOptions' => ['class' => 'template-map-views'],
            'value' => function ($model) {
                static $viewCache = [];

                $products = $model->products;
                if (empty($products)) {
                    return '<span class="text-muted">0</span>';
                }

                $views = [];
                foreach ($products as $product) {
                    $productId = (int) $product->id;

                    if (!isset($viewCache[$productId])) {
                        $viewCache[$productId] = (int) (new \yii\db\Query())
                            ->from('{{%analytics_event}}')
                            ->where(['event_type' => 'page_view'])
                            ->andWhere([
                                'or',
                                ['like', 'page_path', '/site/createcard/%/' . $productId . '/%', false],
                                ['like', 'page_url', '/site/createcard/%/' . $productId . '/%', false],
                            ])
                            ->count('*', Yii::$app->db);
                    }

                    $views[] = Html::tag('span', number_format($viewCache[$productId]), [
                        'class' => 'tm-id-badge tm-view-badge',
                        'title' => 'Image #' . $productId . ' page views',
                    ]);
                }

                return implode(' ', $views);
            },
           ],
           [
            'class' => 'yii\grid\ActionColumn',
            'template' => ' {update} {delete} ',
            'contentOptions' => ['class' => 'admin-action-cell'],
           ]
           
        ],
    ]); ?>
    </div>

    <?php Pjax::end(); ?>

</div>
<div class="tm-preview-modal" id="tmPreviewModal" aria-hidden="true">
    <div class="tm-preview-dialog" role="dialog" aria-modal="true" aria-labelledby="tmPreviewTitle">
        <button type="button" class="tm-preview-close" aria-label="Close preview">&times;</button>
        <div class="tm-preview-image-wrap">
            <img src="" alt="" id="tmPreviewImage">
        </div>
        <div class="tm-preview-meta">
            <h2 id="tmPreviewTitle"></h2>
            <p id="tmPreviewFile"></p>
        </div>
    </div>
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

$(document).on('click', '.tm-thumb-button', function () {
    var button = $(this);
    $('#tmPreviewImage')
        .attr('src', button.data('preview-src'))
        .attr('alt', button.data('preview-title'));
    $('#tmPreviewTitle').text(button.data('preview-title'));
    $('#tmPreviewFile').text(button.data('preview-file'));
    $('#tmPreviewModal').addClass('is-open').attr('aria-hidden', 'false');
});

$(document).on('click', '.tm-preview-close, #tmPreviewModal', function (event) {
    if (event.target !== this) {
        return;
    }

    $('#tmPreviewModal').removeClass('is-open').attr('aria-hidden', 'true');
    $('#tmPreviewImage').attr('src', '').attr('alt', '');
});

$(document).on('keyup', function (event) {
    if (event.key === 'Escape') {
        $('#tmPreviewModal').removeClass('is-open').attr('aria-hidden', 'true');
        $('#tmPreviewImage').attr('src', '').attr('alt', '');
    }
});
JS);
?>
