<?php

use common\models\Contact;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\grid\ActionColumn;
use yii\grid\GridView;

/** @var yii\web\View $this */
/** @var common\models\ContactSearch $searchModel */
/** @var yii\data\ActiveDataProvider $dataProvider */

$this->title = Yii::t('app', 'Contacts');
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
<div class="contact-index admin-list-page">

    <div class="admin-list-header">
        <div>
            <span class="admin-list-kicker">Messages</span>
            <h1><?= Html::encode($this->title) ?></h1>
            <p>Review user enquiries and contact form submissions.</p>
        </div>
    </div>

    <?php // echo $this->render('_search', ['model' => $searchModel]); ?>

    <div class="admin-grid-card">
    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'layout' => '<div class="admin-grid-toolbar"><div class="admin-grid-summary">{summary}</div></div><div class="admin-table-scroll">{items}</div><div class="admin-grid-footer">{pager}</div>',
        'summary' => 'Showing <strong>{begin}-{end}</strong> of <strong>{totalCount}</strong> contacts',
        'emptyText' => 'No matching contacts found.',
        'tableOptions' => ['class' => 'table admin-data-table'],
        'rowOptions' => ['class' => 'admin-data-row'],
        'filterRowOptions' => ['class' => 'admin-filter-row'],
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],

            ['attribute' => 'name', 'filterInputOptions' => ['class' => 'form-control', 'placeholder' => 'Name']],
            ['attribute' => 'email', 'format' => 'email', 'filterInputOptions' => ['class' => 'form-control', 'placeholder' => 'Email']],
            ['attribute' => 'message', 'format' => 'raw', 'contentOptions' => ['class' => 'admin-text-cell'], 'filterInputOptions' => ['class' => 'form-control', 'placeholder' => 'Message'], 'value' => function ($model) use ($textPreview) { return $textPreview($model->message, 'Message'); }],
        ],
    ]); ?>
    </div>


</div>
