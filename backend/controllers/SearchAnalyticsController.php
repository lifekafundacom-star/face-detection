<?php

namespace backend\controllers;

use common\models\Category;
use common\models\SearchAnalytics;
use common\models\Subcategory;
use Yii;
use yii\db\Expression;
use yii\helpers\ArrayHelper;
use yii\web\Controller;

class SearchAnalyticsController extends Controller
{
    public function actionIndex()
    {
        $dateRange = Yii::$app->request->get('range', '30daysAgo');
        $startDate = Yii::$app->request->get('start_date');
        $endDate = Yii::$app->request->get('end_date');
        $categoryName = Yii::$app->request->get('category');
        $subcategoryName = Yii::$app->request->get('subcategory');
        $keyword = trim((string)Yii::$app->request->get('keyword', ''));
        $page = max(1, (int)Yii::$app->request->get('page', 1));
        $pageSize = 10;
        $allowedRanges = [
            'today' => ['start' => 'today', 'end' => 'today'],
            'yesterday' => ['start' => 'yesterday', 'end' => 'yesterday'],
            '7daysAgo' => ['start' => '-7 days', 'end' => 'today'],
            '30daysAgo' => ['start' => '-30 days', 'end' => 'today'],
            '90daysAgo' => ['start' => '-90 days', 'end' => 'today'],
        ];

        if (!isset($allowedRanges[$dateRange])) {
            $dateRange = '30daysAgo';
        }

        if ($startDate && $endDate) {
            $fromDate = date('Y-m-d 00:00:00', strtotime($startDate));
            $toDate = date('Y-m-d 23:59:59', strtotime($endDate));
            $dateRange = 'custom';
        } else {
            $fromDate = date('Y-m-d 00:00:00', strtotime($allowedRanges[$dateRange]['start']));
            $toDate = date('Y-m-d 23:59:59', strtotime($allowedRanges[$dateRange]['end']));
            $startDate = date('Y-m-d', strtotime($allowedRanges[$dateRange]['start']));
            $endDate = date('Y-m-d', strtotime($allowedRanges[$dateRange]['end']));
        }

        $where = ['and', ['between', 'created_at', $fromDate, $toDate]];

        if ($keyword !== '') {
            $where[] = ['or', ['like', 'keyword', $keyword], ['like', 'normalized_keyword', strtolower($keyword)]];
        }

        if ($categoryName) {
            $where[] = ['like', 'category_names', trim((string)$categoryName)];
        }

        if ($subcategoryName) {
            $where[] = ['like', 'subcategory_names', trim((string)$subcategoryName)];
        }

        $db = Yii::$app->db;
        $totalKeywords = (new \yii\db\Query())
            ->from(SearchAnalytics::tableName())
            ->where($where)
            ->select(new Expression('COUNT(DISTINCT normalized_keyword)'))
            ->scalar($db);

        $report = (new \yii\db\Query())
            ->select([
                'keyword' => new Expression('MAX(keyword)'),
                'normalized_keyword',
                'searches' => new Expression('COUNT(*)'),
                'total_results' => new Expression('SUM(result_count)'),
                'avg_results' => new Expression('ROUND(AVG(result_count), 1)'),
                'category_names' => new Expression('MAX(category_names)'),
                'subcategory_names' => new Expression('MAX(subcategory_names)'),
                'active_users' => new Expression('COUNT(DISTINCT session_id)'),
                'last_searched_at' => new Expression('MAX(created_at)'),
            ])
            ->from(SearchAnalytics::tableName())
            ->where($where)
            ->groupBy('normalized_keyword')
            ->orderBy(['searches' => SORT_DESC, 'last_searched_at' => SORT_DESC])
            ->limit($pageSize)
            ->offset(($page - 1) * $pageSize)
            ->all($db);

        $summary = (new \yii\db\Query())
            ->select([
                'total_searches' => new Expression('COUNT(*)'),
                'unique_keywords' => new Expression('COUNT(DISTINCT normalized_keyword)'),
                'total_results' => new Expression('SUM(result_count)'),
                'active_users' => new Expression('COUNT(DISTINCT session_id)'),
            ])
            ->from(SearchAnalytics::tableName())
            ->where($where)
            ->one($db);

        $recentSearches = (new \yii\db\Query())
            ->from(SearchAnalytics::tableName())
            ->where($where)
            ->orderBy(['created_at' => SORT_DESC, 'id' => SORT_DESC])
            ->limit(50)
            ->all($db);

        return $this->render('index', [
            'dateRange' => $dateRange,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'categoryName' => $categoryName,
            'subcategoryName' => $subcategoryName,
            'keyword' => $keyword,
            'categoryOptions' => $this->categoryOptions(),
            'subcategoryOptions' => $this->subcategoryOptions($categoryName),
            'page' => $page,
            'pageSize' => $pageSize,
            'totalKeywords' => (int)$totalKeywords,
            'report' => $report,
            'recentSearches' => $recentSearches,
            'summary' => [
                'total_searches' => (int)($summary['total_searches'] ?? 0),
                'unique_keywords' => (int)($summary['unique_keywords'] ?? 0),
                'total_results' => (int)($summary['total_results'] ?? 0),
                'active_users' => (int)($summary['active_users'] ?? 0),
            ],
        ]);
    }

    private function categoryOptions()
    {
        $categories = Category::find()
            ->select(['category_name'])
            ->where(['is_delete' => 0])
            ->orderBy(['category_name' => SORT_ASC])
            ->asArray()
            ->all();

        return ArrayHelper::map($categories, 'category_name', 'category_name');
    }

    private function subcategoryOptions($categoryName = null)
    {
        $query = Subcategory::find()
            ->alias('s')
            ->select(['s.subcategory_name'])
            ->where(['s.is_delete' => 0])
            ->orderBy(['s.subcategory_name' => SORT_ASC]);

        if ($categoryName) {
            $query->innerJoin(['c' => Category::tableName()], 'c.id = s.category_id')
                ->andWhere(['c.category_name' => $categoryName]);
        }

        return ArrayHelper::map($query->asArray()->all(), 'subcategory_name', 'subcategory_name');
    }
}
