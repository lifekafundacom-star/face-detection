<?php

use common\models\Language;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\grid\ActionColumn;
use yii\grid\GridView;
use yii\widgets\Pjax;
/** @var yii\web\View $this */
/** @var common\models\LanguageSearch $searchModel */
/** @var yii\data\ActiveDataProvider $dataProvider */

$this->title = Yii::t('app', 'Languages');
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="language-index admin-list-page">

    <div class="admin-list-header">
        <div>
            <span class="admin-list-kicker">Localization</span>
            <h1><?= Html::encode($this->title) ?></h1>
            <p>Manage language names and tags used across cards.</p>
        </div>
        <?= Html::a(Yii::t('app', 'Create Language'), ['create'], ['class' => 'btn admin-primary-btn']) ?>
    </div>

    <?php Pjax::begin(); ?>
    <?php // echo $this->render('_search', ['model' => $searchModel]); ?>

    <div class="admin-grid-card admin-grid-card-narrow">
    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'layout' => '<div class="admin-grid-toolbar"><div class="admin-grid-summary">{summary}</div></div><div class="admin-table-scroll">{items}</div><div class="admin-grid-footer">{pager}</div>',
        'summary' => 'Showing <strong>{begin}-{end}</strong> of <strong>{totalCount}</strong> languages',
        'emptyText' => 'No matching languages found.',
        'tableOptions' => ['class' => 'table admin-data-table'],
        'rowOptions' => ['class' => 'admin-data-row'],
        'filterRowOptions' => ['class' => 'admin-filter-row'],
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],

            // 'id',
            ['attribute' => 'language', 'filterInputOptions' => ['class' => 'form-control', 'placeholder' => 'Language']],
            ['attribute' => 'tag', 'filterInputOptions' => ['class' => 'form-control', 'placeholder' => 'Tag']],
            [
                'class' => ActionColumn::className(),
                'contentOptions' => ['class' => 'admin-action-cell'],
                'urlCreator' => function ($action, Language $model, $key, $index, $column) {
                    return Url::toRoute([$action, 'id' => $model->id]);
                 }
            ],
        ],
    ]); ?>
    </div>

    <?php Pjax::end(); ?>

</div>
