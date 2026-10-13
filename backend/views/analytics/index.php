<?php

use yii\helpers\Html;
use yii\helpers\Url;

/** @var yii\web\View $this */
/** @var string $dateRange */
/** @var string $startDate */
/** @var string $endDate */
/** @var string|null $categoryName */
/** @var string|null $subcategoryName */
/** @var string|null $urlFilter */
/** @var array $categoryOptions */
/** @var array $subcategoryOptions */
/** @var int $page */
/** @var int $pageSize */
/** @var int $totalPages */
/** @var array $report */
/** @var array $clickReport */
/** @var array $summary */

$this->title = Yii::t('app', 'Analytics');
$this->params['breadcrumbs'][] = $this->title;

$rangeLabels = [
    'today' => 'Today',
    'yesterday' => 'Yesterday',
    '7daysAgo' => 'Last 7 days',
    '30daysAgo' => 'Last 30 days',
    '90daysAgo' => 'Last 90 days',
    'custom' => 'Custom date',
];
$totalPageCount = (int)ceil($totalPages / $pageSize);
$pageStart = $totalPages ? (($page - 1) * $pageSize) + 1 : 0;
$pageEnd = min($totalPages, $page * $pageSize);
$pagerParams = Yii::$app->request->get();
?>
<div class="analytics-index admin-list-page">
    <div class="admin-list-header">
        <div>
            <span class="admin-list-kicker">Internal Analytics Report</span>
            <h1><?= Html::encode($this->title) ?></h1>
            <p>Check which pages are getting the most views and clicks from your website.</p>
        </div>
        <div class="analytics-range-actions">
            <?php foreach (['today', 'yesterday', '7daysAgo', '30daysAgo', '90daysAgo'] as $range) { ?>
                <?php $label = $rangeLabels[$range]; ?>
                <?= Html::a($label, ['index', 'range' => $range], [
                    'class' => 'btn ' . ($dateRange === $range ? 'admin-primary-btn' : 'admin-secondary-btn'),
                ]) ?>
            <?php } ?>
        </div>
    </div>

    <div class="admin-grid-card analytics-filter-card">
        <?= Html::beginForm(['index'], 'get', ['class' => 'analytics-filter-form']) ?>
            <div class="analytics-filter-field">
                <label>Start Date</label>
                <?= Html::input('date', 'start_date', $startDate, ['class' => 'form-control']) ?>
            </div>
            <div class="analytics-filter-field">
                <label>End Date</label>
                <?= Html::input('date', 'end_date', $endDate, ['class' => 'form-control']) ?>
            </div>
            <div class="analytics-filter-field">
                <label>Category</label>
                <?= Html::dropDownList('category', $categoryName, ['' => 'All Categories'] + $categoryOptions, ['class' => 'form-control']) ?>
            </div>
            <div class="analytics-filter-field">
                <label>Subcategory</label>
                <?= Html::dropDownList('subcategory', $subcategoryName, ['' => 'All Subcategories'] + $subcategoryOptions, ['class' => 'form-control']) ?>
            </div>
            <div class="analytics-filter-field analytics-url-filter-field">
                <label>Page URLs</label>
                <?= Html::textarea('urls', $urlFilter, [
                    'class' => 'form-control analytics-url-filter',
                    'placeholder' => "Multiple URL filter\nEk URL/path per line ya comma se separate karein",
                    'rows' => 3,
                ]) ?>
            </div>
            <div class="analytics-filter-actions">
                <?= Html::submitButton('Apply Filter', ['class' => 'btn admin-primary-btn']) ?>
                <?= Html::a('Reset', ['index'], ['class' => 'btn admin-secondary-btn']) ?>
            </div>
        <?= Html::endForm() ?>
    </div>

    <div class="analytics-summary-grid">
        <div class="analytics-summary-card">
            <span>Total Page Views</span>
            <strong><?= number_format((int)$summary['views']) ?></strong>
        </div>
        <div class="analytics-summary-card">
            <span>Total Events / Clicks</span>
            <strong><?= number_format((int)$summary['events']) ?></strong>
        </div>
        <div class="analytics-summary-card">
            <span>Active Users</span>
            <strong><?= number_format((int)$summary['activeUsers']) ?></strong>
        </div>
    </div>

    <div class="admin-grid-card">
        <div class="admin-grid-toolbar">
            <div class="admin-grid-summary">
                Showing <strong><?= $pageStart ?>-<?= $pageEnd ?></strong> of <strong><?= (int)$totalPages ?></strong> pages by <strong>clicks</strong> for <strong><?= Html::encode($rangeLabels[$dateRange] ?? 'Custom date') ?></strong>
            </div>
        </div>
        <div class="admin-table-scroll">
            <table class="table admin-data-table analytics-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Image</th>
                        <th>Page Type</th>
                        <th>Page Title</th>
                        <th>Page URL</th>
                        <th>Views</th>
                        <th>Events / Clicks</th>
                        <th>Active Users</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($report) { ?>
                        <?php foreach ($report as $index => $row) { ?>
                            <?php $pageUrl = $row['page_url'] ?: Yii::getAlias('@frontendUrl') . $row['page_path']; ?>
                            <tr class="admin-data-row">
                                <td><?= $index + 1 ?></td>
                                <td class="analytics-thumb-cell">
                                    <?php if (!empty($row['thumbnail_src'])) { ?>
                                        <?= Html::button(
                                            Html::img($row['thumbnail_src'], [
                                                'alt' => $row['thumbnail_title'] ?: 'Card image',
                                                'class' => 'analytics-thumb-img',
                                                'loading' => 'lazy',
                                            ]),
                                            [
                                                'type' => 'button',
                                                'class' => 'analytics-thumb-button',
                                                'data-admin-preview-src' => $row['thumbnail_src'],
                                                'data-admin-preview-title' => $row['thumbnail_title'] ?: 'Card image',
                                                'data-admin-preview-file' => $row['thumbnail_file'] ?: '',
                                                'title' => 'Preview image',
                                            ]
                                        ) ?>
                                    <?php } else { ?>
                                        <span class="text-muted">-</span>
                                    <?php } ?>
                                </td>
                                <td><?= Html::tag('span', Html::encode($row['page_type'] ?? 'Other Page'), ['class' => 'analytics-type-badge']) ?></td>
                                <td><?= Html::encode($row['page_title'] ?: '-') ?></td>
                                <td class="template-map-url">
                                    <?= Html::a($pageUrl, $pageUrl, ['target' => '_blank', 'class' => 'tm-page-url']) ?>
                                </td>
                                <td><strong><?= number_format((int)$row['views']) ?></strong></td>
                                <td><strong><?= number_format((int)$row['events']) ?></strong></td>
                                <td><?= number_format((int)$row['activeUsers']) ?></td>
                            </tr>
                        <?php } ?>
                    <?php } else { ?>
                        <tr>
                            <td colspan="8">No analytics data found.</td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
        <?php if ($totalPageCount > 1) { ?>
            <div class="analytics-pager">
                <?php if ($page > 1) { ?>
                    <?= Html::a('Previous', array_merge(['index'], $pagerParams, ['page' => $page - 1]), ['class' => 'btn admin-secondary-btn']) ?>
                <?php } ?>
                <span>Page <?= (int)$page ?> of <?= (int)$totalPageCount ?></span>
                <?php if ($page < $totalPageCount) { ?>
                    <?= Html::a('Next', array_merge(['index'], $pagerParams, ['page' => $page + 1]), ['class' => 'btn admin-primary-btn']) ?>
                <?php } ?>
            </div>
        <?php } ?>
    </div>

    <div class="admin-grid-card analytics-click-card">
        <div class="admin-grid-toolbar">
            <div class="admin-grid-summary">
                Top clicked buttons / links for <strong><?= Html::encode($rangeLabels[$dateRange] ?? 'Custom date') ?></strong>
            </div>
        </div>
        <div class="admin-table-scroll">
            <table class="table admin-data-table analytics-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Clicked Text</th>
                        <th>Type</th>
                        <th>Page</th>
                        <th>Target URL</th>
                        <th>Clicks</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($clickReport) { ?>
                        <?php foreach ($clickReport as $index => $row) { ?>
                            <tr class="admin-data-row">
                                <td><?= $index + 1 ?></td>
                                <td><?= Html::encode($row['element_text']) ?></td>
                                <td><?= Html::encode($row['element_tag']) ?></td>
                                <td class="analytics-path-cell"><?= Html::encode($row['page_path']) ?></td>
                                <td class="template-map-url">
                                    <?php if (!empty($row['element_href'])) { ?>
                                        <?= Html::a($row['element_href'], $row['element_href'], ['target' => '_blank', 'class' => 'tm-page-url analytics-url-link']) ?>
                                    <?php } else { ?>
                                        -
                                    <?php } ?>
                                </td>
                                <td><strong><?= number_format((int)$row['clicks']) ?></strong></td>
                            </tr>
                        <?php } ?>
                    <?php } else { ?>
                        <tr>
                            <td colspan="6">No click data found.</td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
$this->registerCss(<<<CSS
.analytics-range-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}
.admin-secondary-btn {
    background: #e8eef7;
    border: 0;
    border-radius: 5px;
    color: #1f2a44;
    font-weight: 700;
    padding: 10px 14px;
}
.analytics-summary-grid {
    display: grid;
    gap: 14px;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    margin-bottom: 18px;
}
.analytics-filter-card {
    margin-bottom: 18px;
    padding: 16px;
}
.analytics-filter-form {
    align-items: end;
    display: grid;
    gap: 12px;
    grid-template-columns: repeat(4, minmax(0, 1fr)) auto;
}
.analytics-filter-field label {
    color: #475569;
    display: block;
    font-size: 12px;
    font-weight: 800;
    margin-bottom: 6px;
    text-transform: uppercase;
}
.analytics-url-filter-field {
    grid-column: span 2;
}
.analytics-url-filter {
    min-height: 76px;
    resize: vertical;
}
.analytics-filter-actions {
    display: flex;
    gap: 8px;
}
.analytics-summary-card {
    background: #ffffff;
    border: 1px solid #dbe3ec;
    border-radius: 6px;
    padding: 18px;
}
.analytics-summary-card span {
    color: #64748b;
    display: block;
    font-size: 12px;
    font-weight: 700;
    letter-spacing: .04em;
    text-transform: uppercase;
}
.analytics-summary-card strong {
    color: #0f172a;
    display: block;
    font-size: 30px;
    margin-top: 8px;
}
.analytics-setup-card {
    margin-bottom: 18px;
    padding: 18px;
}
.analytics-setup-card h2 {
    font-size: 20px;
    margin-top: 0;
}
.analytics-setup-card pre {
    background: #0f172a;
    border-radius: 6px;
    color: #e2e8f0;
    padding: 14px;
    white-space: pre-wrap;
}
.analytics-click-card {
    margin-top: 18px;
}
.analytics-pager {
    align-items: center;
    border-top: 1px solid #e2e8f0;
    display: flex;
    gap: 10px;
    justify-content: flex-end;
    padding: 14px 16px;
}
.analytics-pager span {
    color: #475569;
    font-weight: 800;
}
.analytics-thumb-cell {
    min-width: 72px;
    width: 72px;
}
.analytics-thumb-button {
    background: #ffffff;
    border: 1px solid #dbe3ec;
    border-radius: 6px;
    box-shadow: 0 4px 10px rgba(15, 23, 42, .12);
    cursor: pointer;
    height: 54px;
    padding: 3px;
    width: 54px;
}
.analytics-thumb-img {
    border-radius: 4px;
    height: 46px;
    object-fit: cover;
    width: 46px;
}
.analytics-type-badge {
    background: #eef4ff;
    border: 1px solid #cfe0ff;
    border-radius: 999px;
    color: #1d4ed8;
    display: inline-block;
    font-size: 12px;
    font-weight: 700;
    padding: 4px 10px;
    white-space: nowrap;
}
.analytics-table {
    table-layout: fixed;
}
.analytics-table td,
.analytics-table th {
    vertical-align: top;
}
.analytics-table .template-map-url,
.analytics-path-cell {
    max-width: 280px;
    overflow-wrap: anywhere;
    word-break: break-word;
}
.analytics-url-link {
    display: block;
    line-height: 1.35;
    max-width: 100%;
    overflow-wrap: anywhere;
    word-break: break-word;
}
@media (max-width: 900px) {
    .analytics-filter-form {
        grid-template-columns: 1fr;
    }
    .analytics-filter-actions {
        align-items: stretch;
        flex-direction: column;
    }
    .analytics-summary-grid {
        grid-template-columns: 1fr;
    }
}
CSS);
?>
