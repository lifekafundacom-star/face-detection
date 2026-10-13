<?php

namespace backend\controllers;

use common\models\Category;
use common\models\Product;
use common\models\Subcategory;
use Yii;
use yii\db\Expression;
use yii\helpers\ArrayHelper;
use yii\web\Controller;

class AnalyticsController extends Controller
{
    public function actionIndex()
    {
        $dateRange = Yii::$app->request->get('range', '30daysAgo');
        $startDate = Yii::$app->request->get('start_date');
        $endDate = Yii::$app->request->get('end_date');
        $categoryName = Yii::$app->request->get('category');
        $subcategoryName = Yii::$app->request->get('subcategory');
        $urlFilter = Yii::$app->request->get('urls');
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

        $db = Yii::$app->db;
        $baseWhere = ['and', ['between', 'created_at', $fromDate, $toDate]];

        foreach ($this->buildPageFilters($categoryName, $subcategoryName) as $filter) {
            $baseWhere[] = $filter;
        }

        $urlFilterCondition = $this->buildUrlFilter($urlFilter);
        if ($urlFilterCondition) {
            $baseWhere[] = $urlFilterCondition;
        }

        $pageViewsExpression = new Expression("SUM(CASE WHEN event_type = 'page_view' THEN 1 ELSE 0 END)");
        $clicksExpression = new Expression("SUM(CASE WHEN event_type = 'click' THEN 1 ELSE 0 END)");
        $usersExpression = new Expression('COUNT(DISTINCT session_id)');

        $totalPages = (new \yii\db\Query())
            ->from('{{%analytics_event}}')
            ->where($baseWhere)
            ->select(new Expression('COUNT(DISTINCT page_path)'))
            ->scalar($db);

        $report = (new \yii\db\Query())
            ->select([
                'page_path',
                'page_url' => new Expression('MAX(page_url)'),
                'page_title' => new Expression('MAX(page_title)'),
                'views' => $pageViewsExpression,
                'events' => $clicksExpression,
                'activeUsers' => $usersExpression,
            ])
            ->from('{{%analytics_event}}')
            ->where($baseWhere)
            ->groupBy('page_path')
            ->orderBy(['events' => SORT_DESC, 'views' => SORT_DESC])
            ->limit($pageSize)
            ->offset(($page - 1) * $pageSize)
            ->all($db);
        $report = $this->addPageMeta($report);

        $summary = (new \yii\db\Query())
            ->select([
                'views' => $pageViewsExpression,
                'events' => $clicksExpression,
                'activeUsers' => $usersExpression,
            ])
            ->from('{{%analytics_event}}')
            ->where($baseWhere)
            ->one($db);

        $clickWhere = $baseWhere;
        $clickWhere[] = ['event_type' => 'click'];

        $clickReport = (new \yii\db\Query())
            ->select([
                'element_text' => new Expression("COALESCE(NULLIF(element_text, ''), '(No text)')"),
                'element_tag' => new Expression("COALESCE(NULLIF(element_tag, ''), '-')"),
                'element_href' => new Expression('MAX(element_href)'),
                'page_path',
                'clicks' => new Expression('COUNT(*)'),
            ])
            ->from('{{%analytics_event}}')
            ->where($clickWhere)
            ->groupBy(['page_path', 'element_text', 'element_tag'])
            ->orderBy(['clicks' => SORT_DESC])
            ->limit(50)
            ->all($db);

        return $this->render('index', [
            'dateRange' => $dateRange,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'categoryName' => $categoryName,
            'subcategoryName' => $subcategoryName,
            'urlFilter' => $urlFilter,
            'categoryOptions' => $this->categoryOptions(),
            'subcategoryOptions' => $this->subcategoryOptions($categoryName),
            'page' => $page,
            'pageSize' => $pageSize,
            'totalPages' => (int)$totalPages,
            'report' => $report,
            'clickReport' => $clickReport,
            'summary' => [
                'views' => (int)($summary['views'] ?? 0),
                'events' => (int)($summary['events'] ?? 0),
                'activeUsers' => (int)($summary['activeUsers'] ?? 0),
            ],
        ]);
    }

    private function buildPageFilters($categoryName, $subcategoryName)
    {
        $filters = [];

        if ($categoryName) {
            $category = trim((string)$categoryName);
            $filters[] = [
                'or',
                ['like', 'page_path', '/site/card/' . $category, false],
                ['like', 'page_path', '/site/subcard/' . $category . '/', false],
                ['like', 'page_path', '/site/createcard/' . $category . '/', false],
                ['in', new Expression($this->createCardProductIdExpression()), $this->productIdsByMapping($category, null)],
            ];
        }

        if ($subcategoryName) {
            $subcategory = trim((string)$subcategoryName);
            $filters[] = [
                'or',
                ['like', 'page_path', '/site/subcard/%/' . $subcategory, false],
                ['in', new Expression($this->createCardProductIdExpression()), $this->productIdsByMapping($categoryName, $subcategory)],
            ];
        }

        return $filters;
    }

    private function buildUrlFilter($urlFilter)
    {
        $urls = preg_split('/[\r\n,]+/', (string)$urlFilter);
        $conditions = ['or'];

        foreach ($urls as $url) {
            $url = trim($url);
            if ($url === '') {
                continue;
            }

            $path = parse_url($url, PHP_URL_PATH);
            $conditions[] = ['like', 'page_url', $url];

            if ($path && $path !== $url) {
                $conditions[] = ['like', 'page_path', $path];
            } else {
                $conditions[] = ['like', 'page_path', $url];
            }
        }

        return count($conditions) > 1 ? $conditions : null;
    }

    private function createCardProductIdExpression()
    {
        return "CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(page_path, '/site/createcard/', -1), '/', -2) AS UNSIGNED)";
    }

    private function productIdsByMapping($categoryName = null, $subcategoryName = null)
    {
        $query = (new \yii\db\Query())
            ->select(['p.id'])
            ->from(['p' => Product::tableName()])
            ->innerJoin(['tm' => 'template_mapping'], 'tm.template_id = p.template_id')
            ->innerJoin(['c' => Category::tableName()], 'c.id = tm.category_id')
            ->innerJoin(['s' => Subcategory::tableName()], 's.id = tm.subcategory_id')
            ->where(['p.is_delete' => 0, 'tm.is_delete' => 0]);

        if ($categoryName) {
            $query->andWhere(['c.category_name' => $categoryName]);
        }

        if ($subcategoryName) {
            $query->andWhere(['s.subcategory_name' => $subcategoryName]);
        }

        $ids = $query->column();
        return $ids ?: [0];
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

    private function addPageMeta(array $rows)
    {
        $productIds = [];

        foreach ($rows as $row) {
            $productId = $this->extractCreateCardProductId($row['page_path'] ?? '');
            if ($productId) {
                $productIds[] = $productId;
            }
        }

        $products = [];
        if ($productIds) {
            $products = Product::find()
                ->where(['id' => array_unique($productIds)])
                ->indexBy('id')
                ->all();
        }

        foreach ($rows as $index => $row) {
            $productId = $this->extractCreateCardProductId($row['page_path'] ?? '');
            $product = $productId && isset($products[$productId]) ? $products[$productId] : null;

            $rows[$index]['page_type'] = $this->detectPageType($row['page_path'] ?? '');
            $rows[$index]['thumbnail_src'] = null;
            $rows[$index]['thumbnail_title'] = null;
            $rows[$index]['thumbnail_file'] = null;

            if ($product) {
                $image = $product->show_image ?: $product->images;
                if ($image) {
                    $rows[$index]['thumbnail_src'] = Yii::getAlias('@storageUrl') . '/images/' . $image;
                    $rows[$index]['thumbnail_title'] = $product->title ?: $product->item_name ?: 'Card image';
                    $rows[$index]['thumbnail_file'] = $image;
                }
            }
        }

        return $rows;
    }

    private function detectPageType($pagePath)
    {
        $path = (string)parse_url($pagePath, PHP_URL_PATH);

        if (preg_match('#/site/createcard/#', $path)) {
            return 'Createcard Page';
        }

        if (preg_match('#/site/subcard/#', $path)) {
            return 'Subcategory Page';
        }

        if (preg_match('#/site/card/#', $path)) {
            return 'Category Page';
        }

        if (preg_match('#/site/categories#', $path)) {
            return 'Categories Page';
        }

        if (preg_match('#/site/contact|/site/contactus#', $path)) {
            return 'Contact Page';
        }

        if ($path === '/' || preg_match('#/site/index$#', $path)) {
            return 'Home Page';
        }

        return 'Other Page';
    }

    private function extractCreateCardProductId($pagePath)
    {
        $path = (string)parse_url($pagePath, PHP_URL_PATH);

        if (preg_match('#/site/createcard/[^/]+/(\d+)(?:/|$)#', $path, $matches)) {
            return (int)$matches[1];
        }

        return null;
    }
}
