<?php

use common\models\Category;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\grid\ActionColumn;
use yii\grid\GridView;
use yii\widgets\Pjax;

/** @var yii\web\View $this */
/** @var common\models\CategorySearch $searchModel */
/** @var yii\data\ActiveDataProvider $dataProvider */

$this->title = Yii::t('app', 'Categories');
$this->params['breadcrumbs'][] = $this->title;
$textPreview = function ($text, $label) {
    $plainText = trim(strip_tags(html_entity_decode((string) $text)));

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
<div class="category-index admin-list-page">

    <div class="admin-list-header">
        <div>
            <span class="admin-list-kicker">Navigation setup</span>
            <h1><?= Html::encode($this->title) ?></h1>
            <p>Manage frontend category names, icons, and SEO metadata.</p>
        </div>
        <?= Html::a(Yii::t('app', 'Create Category'), ['create'], ['class' => 'btn admin-primary-btn']) ?>
    </div>

    <?php Pjax::begin(); ?>
    <?php // echo $this->render('_search', ['model' => $searchModel]); 
    ?>

    <div class="admin-grid-card">
    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'layout' => '<div class="admin-grid-toolbar"><div class="admin-grid-summary">{summary}</div></div><div class="admin-table-scroll">{items}</div><div class="admin-grid-footer">{pager}</div>',
        'summary' => 'Showing <strong>{begin}-{end}</strong> of <strong>{totalCount}</strong> categories',
        'emptyText' => 'No matching categories found.',
        'tableOptions' => ['class' => 'table admin-data-table admin-asset-table'],
        'rowOptions' => ['class' => 'admin-data-row'],
        'filterRowOptions' => ['class' => 'admin-filter-row'],
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],

            // 'id',
            [
                'attribute' => 'category_name',
                'filterInputOptions' => ['class' => 'form-control', 'placeholder' => 'Category'],
                'value' => function ($model) {
                    return str_replace('_', ' ', $model->category_name);
                },
            ],
            [
                'attribute' => 'description',
                'format' => 'raw',
                'contentOptions' => ['class' => 'admin-text-cell'],
                'filterInputOptions' => ['class' => 'form-control', 'placeholder' => 'Description'],
                'value' => function ($model) use ($textPreview) {
                    return $textPreview($model->description, 'Category Description');
                },
            ],
            [
                'attribute' => 'icon',
                'format' => 'raw',
                'value' => function ($model) {
                    $img = Yii::getAlias('@storageUrl') . '/icon/' . $model->category_icon;
                    return Html::button('<img class="tm-thumb-img" src="' . $img . '" alt="Icon" loading="lazy">', [
                        'type' => 'button',
                        'class' => 'tm-thumb-button',
                        'data-admin-preview-src' => $img,
                        'data-admin-preview-title' => str_replace('_', ' ', $model->category_name),
                        'data-admin-preview-file' => $model->category_icon,
                    ]);
                }
            ],
            ['attribute' => 'meta_title', 'format' => 'raw', 'contentOptions' => ['class' => 'admin-text-cell'], 'filterInputOptions' => ['class' => 'form-control', 'placeholder' => 'Meta title'], 'value' => function ($model) use ($textPreview) { return $textPreview($model->meta_title, 'Meta Title'); }],
            ['attribute' => 'meta_description', 'format' => 'raw', 'contentOptions' => ['class' => 'admin-text-cell'], 'filterInputOptions' => ['class' => 'form-control', 'placeholder' => 'Meta description'], 'value' => function ($model) use ($textPreview) { return $textPreview($model->meta_description, 'Meta Description'); }],
            ['attribute' => 'keywords', 'format' => 'raw', 'contentOptions' => ['class' => 'admin-text-cell'], 'filterInputOptions' => ['class' => 'form-control', 'placeholder' => 'Keywords'], 'value' => function ($model) use ($textPreview) { return $textPreview($model->keywords, 'Keywords'); }],
            ['attribute' => 'tag', 'filterInputOptions' => ['class' => 'form-control', 'placeholder' => 'Tag']],
            [
                'attribute' => 'whatsapp',
                'label' => 'WhatsApp',
                'format' => 'raw',
                'contentOptions' => ['class' => 'admin-text-cell'],
                'filterInputOptions' => ['class' => 'form-control', 'placeholder' => 'WhatsApp link'],
                'value' => function ($model) {
                    $link = trim((string) $model->whatsapp);
                    if ($link === '') {
                        return '<span class="text-muted">-</span>';
                    }

                    return Html::tag('div',
                        Html::a('Open Link', $link, [
                            'class' => 'btn btn-xs btn-primary',
                            'target' => '_blank',
                            'rel' => 'noopener noreferrer',
                        ]) .
                        Html::tag('div', Html::encode($link), ['class' => 'admin-text-preview-copy']),
                        ['class' => 'admin-text-preview']
                    );
                },
            ],
            [
                'class' => ActionColumn::className(),
                'contentOptions' => ['class' => 'admin-action-cell'],
                'urlCreator' => function ($action, Category $model, $key, $index, $column) {
                    return Url::toRoute([$action, 'id' => $model->id]);
                }
            ],
        ],
    ]); ?>
    </div>

    <?php Pjax::end(); ?>

</div>
