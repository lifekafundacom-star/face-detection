<?php

use yii\helpers\Html;

/* @var $this yii\web\View */

$this->title = 'Search Analytics';

$rangeButtons = [
    'today' => 'Today',
    'yesterday' => 'Yesterday',
    '7daysAgo' => 'Last 7 days',
    '30daysAgo' => 'Last 30 days',
    '90daysAgo' => 'Last 90 days',
];

$rangeLabel = $rangeButtons[$dateRange] ?? 'Custom date';
$totalPages = max(1, (int)ceil($totalKeywords / $pageSize));

$shortText = static function ($text, $limit = 90) {
    $text = trim((string)$text);
    if ($text === '') {
        return '-';
    }
    return strlen($text) > $limit ? substr($text, 0, $limit) . '...' : $text;
};

$formatDate = static function ($date) {
    if (!$date) {
        return '-';
    }
    return date('d M Y, h:i A', strtotime($date));
};
?>

<div class="analytics-page">
    <div class="analytics-heading">
        <div>
            <div class="analytics-kicker">INTERNAL SEARCH REPORT</div>
            <h1>Search Analytics</h1>
            <p>Frontend par user jo bhi search karta hai, uske keywords, results aur category data yahan track honge.</p>
        </div>
        <div class="analytics-ranges">
            <?php foreach ($rangeButtons as $rangeKey => $label): ?>
                <?= Html::a($label, ['index', 'range' => $rangeKey], [
                    'class' => 'analytics-range-btn' . ($dateRange === $rangeKey ? ' active' : ''),
                ]) ?>
            <?php endforeach; ?>
        </div>
    </div>

    <form class="analytics-filter" method="get">
        <div class="filter-field">
            <label>Start Date</label>
            <input type="date" name="start_date" value="<?= Html::encode($startDate) ?>">
        </div>
        <div class="filter-field">
            <label>End Date</label>
            <input type="date" name="end_date" value="<?= Html::encode($endDate) ?>">
        </div>
        <div class="filter-field">
            <label>Category</label>
            <select name="category">
                <option value="">All Categories</option>
                <?php foreach ($categoryOptions as $value => $label): ?>
                    <option value="<?= Html::encode($value) ?>" <?= $categoryName === $value ? 'selected' : '' ?>>
                        <?= Html::encode($label) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="filter-field">
            <label>Subcategory</label>
            <select name="subcategory">
                <option value="">All Subcategories</option>
                <?php foreach ($subcategoryOptions as $value => $label): ?>
                    <option value="<?= Html::encode($value) ?>" <?= $subcategoryName === $value ? 'selected' : '' ?>>
                        <?= Html::encode($label) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="filter-field filter-keyword">
            <label>Keyword</label>
            <input type="text" name="keyword" value="<?= Html::encode($keyword) ?>" placeholder="Search keyword">
        </div>
        <div class="filter-actions">
            <button type="submit">Apply Filter</button>
            <?= Html::a('Reset', ['index'], ['class' => 'reset-btn']) ?>
        </div>
    </form>

    <div class="analytics-cards">
        <div class="analytics-card">
            <span>Total Searches</span>
            <strong><?= (int)$summary['total_searches'] ?></strong>
        </div>
        <div class="analytics-card">
            <span>Unique Keywords</span>
            <strong><?= (int)$summary['unique_keywords'] ?></strong>
        </div>
        <div class="analytics-card">
            <span>Total Results</span>
            <strong><?= (int)$summary['total_results'] ?></strong>
        </div>
        <div class="analytics-card">
            <span>Active Users</span>
            <strong><?= (int)$summary['active_users'] ?></strong>
        </div>
    </div>

    <div class="analytics-table-card">
        <div class="table-caption">
            Showing <strong><?= $totalKeywords ? (($page - 1) * $pageSize + 1) : 0 ?>-<?= min($page * $pageSize, $totalKeywords) ?></strong>
            of <strong><?= (int)$totalKeywords ?></strong> keywords for <strong><?= Html::encode($rangeLabel) ?></strong>
        </div>
        <div class="table-responsive">
            <table class="table analytics-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Keyword</th>
                        <th>Searches</th>
                        <th>Total Results</th>
                        <th>Avg Results</th>
                        <th>Categories</th>
                        <th>Subcategories</th>
                        <th>Active Users</th>
                        <th>Last Searched</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($report)): ?>
                        <tr><td colspan="9">No search analytics data found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($report as $index => $row): ?>
                            <tr>
                                <td><?= (($page - 1) * $pageSize) + $index + 1 ?></td>
                                <td><strong><?= Html::encode($row['keyword']) ?></strong></td>
                                <td><?= (int)$row['searches'] ?></td>
                                <td><?= (int)$row['total_results'] ?></td>
                                <td><?= Html::encode($row['avg_results']) ?></td>
                                <td><?= Html::encode($shortText($row['category_names'])) ?></td>
                                <td><?= Html::encode($shortText($row['subcategory_names'])) ?></td>
                                <td><?= (int)$row['active_users'] ?></td>
                                <td><?= Html::encode($formatDate($row['last_searched_at'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if ($totalPages > 1): ?>
            <div class="analytics-pager">
                <?php if ($page > 1): ?>
                    <?= Html::a('Previous', array_merge(['index'], Yii::$app->request->get(), ['page' => $page - 1]), ['class' => 'pager-btn']) ?>
                <?php endif; ?>
                <span>Page <?= (int)$page ?> of <?= (int)$totalPages ?></span>
                <?php if ($page < $totalPages): ?>
                    <?= Html::a('Next', array_merge(['index'], Yii::$app->request->get(), ['page' => $page + 1]), ['class' => 'pager-btn']) ?>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <div class="analytics-table-card">
        <div class="table-caption">Recent search entries</div>
        <div class="table-responsive">
            <table class="table analytics-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Keyword</th>
                        <th>Results</th>
                        <th>Categories</th>
                        <th>Subcategories</th>
                        <th>Page URL</th>
                        <th>IP</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($recentSearches)): ?>
                        <tr><td colspan="8">No recent search entries found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($recentSearches as $index => $row): ?>
                            <tr>
                                <td><?= $index + 1 ?></td>
                                <td><strong><?= Html::encode($row['keyword']) ?></strong></td>
                                <td><?= (int)$row['result_count'] ?></td>
                                <td><?= Html::encode($shortText($row['category_names'])) ?></td>
                                <td><?= Html::encode($shortText($row['subcategory_names'])) ?></td>
                                <td class="url-cell"><?= Html::encode($row['page_url']) ?></td>
                                <td><?= Html::encode($row['ip_address']) ?></td>
                                <td><?= Html::encode($formatDate($row['created_at'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
.analytics-page {
    padding: 46px 28px;
}
.analytics-heading {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    gap: 24px;
    margin-bottom: 22px;
}
.analytics-kicker {
    color: #5f6f8a;
    font-size: 13px;
    font-weight: 800;
    letter-spacing: .04em;
}
.analytics-heading h1 {
    margin: 4px 0 6px;
    color: #06112a;
    font-size: 34px;
    font-weight: 800;
}
.analytics-heading p {
    margin: 0;
    color: #4e5d78;
}
.analytics-ranges {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
    justify-content: flex-end;
}
.analytics-range-btn,
.reset-btn,
.pager-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 36px;
    padding: 0 16px;
    border-radius: 6px;
    background: #eef3fb;
    color: #06112a;
    font-size: 12px;
    font-weight: 800;
    letter-spacing: .18em;
    text-decoration: none;
}
.analytics-range-btn.active,
.analytics-filter button {
    background: #356c35;
    color: #fff;
}
.analytics-filter {
    display: grid;
    grid-template-columns: repeat(4, minmax(160px, 1fr));
    gap: 14px;
    align-items: end;
    padding: 16px;
    margin-bottom: 18px;
    border: 1px solid #d8e0ec;
    border-radius: 7px;
    background: #fff;
}
.filter-field label {
    display: block;
    margin-bottom: 8px;
    color: #34405a;
    font-size: 12px;
    font-weight: 800;
    text-transform: uppercase;
}
.filter-field input,
.filter-field select {
    width: 100%;
    min-height: 34px;
    border: 1px solid #d8e0ec;
    border-radius: 4px;
    padding: 7px 10px;
    color: #23314f;
}
.filter-keyword {
    grid-column: span 2;
}
.filter-actions {
    display: flex;
    gap: 8px;
}
.analytics-filter button {
    border: 0;
    border-radius: 6px;
    min-height: 36px;
    padding: 0 16px;
    font-weight: 800;
}
.analytics-cards {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 14px;
    margin-bottom: 18px;
}
.analytics-card {
    border: 1px solid #d8e0ec;
    border-radius: 7px;
    background: #fff;
    padding: 22px;
}
.analytics-card span {
    display: block;
    color: #5b6681;
    font-size: 13px;
    font-weight: 800;
    letter-spacing: .05em;
    text-transform: uppercase;
}
.analytics-card strong {
    display: block;
    margin-top: 18px;
    color: #06112a;
    font-size: 32px;
}
.analytics-table-card {
    border: 1px solid #d8e0ec;
    border-radius: 7px;
    background: #fff;
    margin-bottom: 20px;
    overflow: hidden;
}
.table-caption {
    padding: 16px;
    border-bottom: 1px solid #e1e6ef;
    color: #34405a;
}
.analytics-table {
    margin: 0;
}
.analytics-table th {
    background: #f8fafd;
    color: #06112a;
    font-size: 13px;
    font-weight: 800;
    white-space: nowrap;
}
.analytics-table td {
    vertical-align: top;
    color: #06112a;
    font-size: 13px;
}
.url-cell {
    max-width: 280px;
    overflow-wrap: anywhere;
    color: #0c4dcd !important;
}
.analytics-pager {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 12px;
    padding: 12px 16px;
    border-top: 1px solid #e1e6ef;
}
@media (max-width: 980px) {
    .analytics-heading {
        display: block;
    }
    .analytics-ranges {
        justify-content: flex-start;
        margin-top: 16px;
    }
    .analytics-filter,
    .analytics-cards {
        grid-template-columns: 1fr;
    }
    .filter-keyword {
        grid-column: auto;
    }
}
</style>
