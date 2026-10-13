<?php

use common\models\Product;
use common\models\TemplateMapping;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\grid\ActionColumn;
use yii\grid\GridView;
use yii\widgets\Pjax;
/** @var yii\web\View $this */
/** @var common\models\ProductSearch $searchModel */
/** @var yii\data\ActiveDataProvider $dataProvider */

$this->title = Yii::t('app', 'Products');
$this->params['breadcrumbs'][] = $this->title;
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
?>
<div class="product-index admin-list-page">

    <div class="admin-list-header">
        <div>
            <span class="admin-list-kicker">Card library</span>
            <h1><?= Html::encode($this->title) ?></h1>
            <p>Manage card images, templates, language, and product content.</p>
        </div>
        <?= Html::a(Yii::t('app', 'Create Product'), ['create'], ['class' => 'btn admin-primary-btn']) ?>
    </div>

    <?php Pjax::begin(); ?>
    <?php // echo $this->render('_search', ['model' => $searchModel]); ?>
    <div class="admin-grid-card">
    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'layout' => '<div class="admin-grid-toolbar"><div class="admin-grid-summary">{summary}</div></div><div class="admin-table-scroll">{items}</div><div class="admin-grid-footer">{pager}</div>',
        'summary' => 'Showing <strong>{begin}-{end}</strong> of <strong>{totalCount}</strong> products',
        'emptyText' => 'No matching products found.',
        'tableOptions' => ['class' => 'table admin-data-table admin-asset-table'],
        'rowOptions' => ['class' => 'admin-data-row'],
        'filterRowOptions' => ['class' => 'admin-filter-row'],
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],
            [
                'attribute' => 'id',
                'label' => 'ID',
                'filterInputOptions' => ['class' => 'form-control', 'placeholder' => 'ID'],
                'contentOptions' => ['class' => 'template-map-image-no'],
                'value' => function ($model) {
                    return '#' . (int) $model->id;
                },
            ],
            [
                'attribute' => 'template_id',
                'format' => 'raw',
                'filterInputOptions' => ['class' => 'form-control', 'placeholder' => 'Template name'],
                'value' => 'template.template_name'
               ],
            [
                'attribute' => 'title',
                'format' => 'raw',
                'contentOptions' => ['class' => 'admin-text-cell'],
                'filterInputOptions' => ['class' => 'form-control', 'placeholder' => 'Title'],
                'value' => function ($model) use ($textPreview) {
                    return $textPreview($model->title, 'Title');
                },
            ],
           
           [
            'attribute' => 'language_id',
            'format' => 'raw',
            'filterInputOptions' => ['class' => 'form-control', 'placeholder' => 'Language'],
            'value' => 'language.language'
           ],
            [
                'attribute' => 'images',
                'format' => 'raw',
                'value' => function ($model){
                    $img = Yii::getAlias('@storageUrl').'/images/'.$model->images;
                    return Html::button('<img class="tm-thumb-img" src="'.$img.'" alt="Image" loading="lazy">', [
                        'type' => 'button',
                        'class' => 'tm-thumb-button',
                        'data-admin-preview-src' => $img,
                        'data-admin-preview-title' => $model->title ?: $model->item_name ?: 'Product image',
                        'data-admin-preview-file' => $model->images,
                    ]);
                }
            ],
            [
                'attribute' => 'show_image',
                'format' => 'raw',
                'value' => function ($model){
                    $img = Yii::getAlias('@storageUrl').'/images/'.$model->show_image;
                    return Html::button('<img class="tm-thumb-img" src="'.$img.'" alt="Show image" loading="lazy">', [
                        'type' => 'button',
                        'class' => 'tm-thumb-button',
                        'data-admin-preview-src' => $img,
                        'data-admin-preview-title' => $model->title ?: $model->item_name ?: 'Show image',
                        'data-admin-preview-file' => $model->show_image,
                    ]);
                }
            ],
            [
                'attribute' => 'item_name',
                'format' => 'raw',
                'contentOptions' => ['class' => 'admin-text-cell'],
                'filterInputOptions' => ['class' => 'form-control', 'placeholder' => 'Item name'],
                'value' => function ($model) use ($textPreview) {
                    return $textPreview($model->item_name, 'Item Name');
                },
            ],
            [
                'attribute' => 'page_url',
                'label' => 'Page URL',
                'format' => 'raw',
                'contentOptions' => ['class' => 'template-map-url'],
                'filterInputOptions' => ['class' => 'form-control', 'placeholder' => 'Paste page URL'],
                'value' => function ($model) {
                    $mapping = TemplateMapping::find()
                        ->joinWith('categoryname')
                        ->where([
                            'template_mapping.template_id' => $model->template_id,
                            'template_mapping.is_delete' => 0,
                        ])
                        ->one();

                    if (!$mapping || !$mapping->categoryname) {
                        return '<span class="text-muted">No mapping found</span>';
                    }

                    $title = $model->getCreatecardSlug();
                    $url = Yii::getAlias('@frontendUrl') . '/site/createcard/' . $mapping->categoryname->category_name . '/' . $model->id . '/' . $title;

                    return Html::tag('div',
                        Html::a($url, $url, [
                            'target' => '_blank',
                            'class' => 'tm-page-url',
                            'title' => 'Open product #' . (int) $model->id,
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
            // 'title',
            // 'description',
            // 'caption',
            // 'meta_title',
            // 'meta_description',
            // 'keywords',
            // 'alt',
           [
            'class' => 'yii\grid\ActionColumn',
            'template' => ' {update} {delete} ',
            'contentOptions' => ['class' => 'admin-action-cell'],
           ]
        ],
    ]); ?>
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
    <?php Pjax::end(); ?>
    
</div>
