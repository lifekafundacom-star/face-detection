<?php

use common\models\GoogleAnalytics;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\grid\ActionColumn;
use yii\grid\GridView;
use yii\widgets\Pjax;

/** @var yii\web\View $this */
/** @var common\models\GoogleAnalyticsSearch $searchModel */
/** @var yii\data\ActiveDataProvider $dataProvider */

$this->title = Yii::t('app', 'Google Analytics');
$this->params['breadcrumbs'][] = $this->title;

$totalCount = GoogleAnalytics::find()->where(['is_delete' => 0])->count();
$textPreview = function ($text, $label) {
    $plainText = trim((string) $text);

    if ($plainText === '') {
        return '<span class="text-muted">-</span>';
    }

    return Html::tag('div',
        Html::tag('div', Html::encode($plainText), ['class' => 'admin-text-preview-copy']) .
        Html::button('See more', [
            'type' => 'button',
            'class' => 'admin-see-more',
            'data-admin-text-title' => $label,
            'data-admin-text-preview' => '<pre>' . Html::encode($plainText) . '</pre>',
        ]),
        ['class' => 'admin-text-preview']
    );
};
// echo $totalCount; die;
?>
<div class="google-analytics-index admin-list-page">

    <div class="admin-list-header">
        <div>
            <span class="admin-list-kicker">Tracking</span>
            <h1><?= Html::encode($this->title) ?></h1>
            <p>Manage head and body tracking snippets for analytics setup.</p>
        </div>
        <?php if ($totalCount == 0) { ?>
            <?= Html::a(Yii::t('app', 'Create Google Analytics'), ['create'], ['class' => 'btn admin-primary-btn']) ?>
        <?php } ?>
    </div>
    <?php Pjax::begin(); ?>
    <?php // echo $this->render('_search', ['model' => $searchModel]); 
    ?>

    <div class="admin-grid-card">
    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'layout' => '<div class="admin-grid-toolbar"><div class="admin-grid-summary">{summary}</div></div><div class="admin-table-scroll">{items}</div><div class="admin-grid-footer">{pager}</div>',
        'summary' => 'Showing <strong>{begin}-{end}</strong> of <strong>{totalCount}</strong> analytics records',
        'emptyText' => 'No analytics records found.',
        'tableOptions' => ['class' => 'table admin-data-table'],
        'rowOptions' => ['class' => 'admin-data-row'],
        'filterRowOptions' => ['class' => 'admin-filter-row'],
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],


            ['attribute' => 'head', 'format' => 'raw', 'contentOptions' => ['class' => 'admin-code-cell'], 'filterInputOptions' => ['class' => 'form-control', 'placeholder' => 'Head code'], 'value' => function ($model) use ($textPreview) { return $textPreview($model->head, 'Head Code'); }],
            ['attribute' => 'body', 'format' => 'raw', 'contentOptions' => ['class' => 'admin-code-cell'], 'filterInputOptions' => ['class' => 'form-control', 'placeholder' => 'Body code'], 'value' => function ($model) use ($textPreview) { return $textPreview($model->body, 'Body Code'); }],

            [
                'class' => 'yii\grid\ActionColumn',
                'template' => ' {update} ',
                'contentOptions' => ['class' => 'admin-action-cell'],
            ]
        ],
    ]); ?>
    </div>

    <?php Pjax::end(); ?>

</div>
