<?php

/** @var yii\web\View $this */

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Admin Dashboard';

require_once Yii::getAlias('@backend') . '/work_management_app/Core/Autoload.php';

$identity = Yii::$app->user->identity;
$workBase = Yii::$app->request->baseUrl . '/work-management';

$attendance = [];
if (!Yii::$app->user->isGuest) {
    $userArr = [
        'id' => Yii::$app->user->id,
        'role' => $identity->role,
        'department' => $identity->department,
    ];
    try {
        $attendanceRepo = new \App\Repositories\AttendanceRepository();
        $attendance = array_slice($attendanceRepo->listForUser($userArr), 0, 8);
    } catch (\Throwable $e) {
        $attendance = [];
    }
}

$quickLinks = [
    [
        'label' => 'Scan Attendance',
        'desc' => 'Launch Face Scanner',
        'url' => $workBase . '/attendance/scan',
        'tone' => 'blue',
        'initial' => 'S',
        'action' => 'Open Scanner',
    ],
    [
        'label' => 'Attendance Records',
        'desc' => 'View Daily Punch Logs',
        'url' => $workBase . '/attendance',
        'tone' => 'teal',
        'initial' => 'A',
        'action' => 'View Attendance',
    ],
    [
        'label' => 'Attendance Report',
        'desc' => 'Monthly Summary & Analytics',
        'url' => $workBase . '/reports/attendance',
        'tone' => 'amber',
        'initial' => 'R',
        'action' => 'View Report',
    ],
    [
        'label' => 'Manage Users',
        'desc' => 'Employees & Face Profiles',
        'url' => $workBase . '/admin/users',
        'tone' => 'rose',
        'initial' => 'U',
        'action' => 'Manage Users',
    ],
];
?>
<div class="admin-dashboard">
    <section class="dashboard-hero">
        <div>
            <span class="dashboard-kicker">Biometric Attendance Station</span>
            <h1>Dashboard</h1>
            <?php $displayName = $identity ? ($identity->name ?: ($identity->username ?? 'Admin')) : 'Admin'; ?>
            <p>Welcome, <?= Html::encode(explode(' ', $displayName)[0]) ?>! Face recognition attendance and employee management portal.</p>
        </div>
        <div class="dashboard-hero-actions">
            <?= Html::a('<i class="fa-solid fa-camera"></i> Open Scanner', $workBase . '/attendance/scan', ['class' => 'dashboard-btn dashboard-btn-primary', 'style' => 'background: #3b82f6; color: #fff; font-weight: 600; padding: 10px 20px; border-radius: 8px; text-decoration: none; display: inline-flex; align-items: center; gap: 8px;']) ?>
        </div>
    </section>

    <!-- Quick Attendance Navigation Cards -->
    <section class="dashboard-stat-grid" style="margin-top: 24px; margin-bottom: 24px;">
        <?php foreach ($quickLinks as $item): ?>
            <a class="dashboard-stat-card tone-<?= Html::encode($item['tone']) ?>" href="<?= htmlspecialchars($item['url']) ?>" style="text-decoration: none;">
                <span class="dashboard-stat-icon"><?= Html::encode($item['initial']) ?></span>
                <span class="dashboard-stat-label"><?= Html::encode($item['label']) ?></span>
                <strong style="font-size: 16px; font-weight: 600; color: #334155;"><?= Html::encode($item['desc']) ?></strong>
                <span class="dashboard-card-link"><?= Html::encode($item['action']) ?> &rarr;</span>
            </a>
        <?php endforeach; ?>
    </section>

    <!-- Recent Attendance Activity Section -->
    <div class="dashboard-panel" style="margin-top: 24px;">
        <div class="dashboard-panel-head" style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #e2e8f0; padding-bottom: 12px; margin-bottom: 16px;">
            <div>
                <h2 style="margin: 0; font-size: 1.25rem;">Recent Attendance Activity</h2>
                <p style="margin: 4px 0 0 0; color: #64748b; font-size: 0.88rem;">Recent check-ins and biometric verification logs.</p>
            </div>
            <a href="<?= htmlspecialchars($workBase . '/attendance') ?>" class="dashboard-link" style="color: #3b82f6; text-decoration: none; font-weight: 600; font-size: 0.9rem;">View All Attendance &rarr;</a>
        </div>

        <?php if (!empty($attendance)): ?>
            <div class="recent-product-list">
                <?php foreach ($attendance as $entry): ?>
                    <div class="recent-product-item" style="display: flex; justify-content: space-between; align-items: center; padding: 12px 14px; border-bottom: 1px solid #f1f5f9;">
                        <span class="recent-product-copy">
                            <strong style="font-size: 14px; color: #1e293b;"><?= htmlspecialchars($entry['name']) ?></strong>
                            <small style="display: block; color: #64748b; font-size: 12px; margin-top: 2px;">
                                <?= htmlspecialchars($entry['date']) ?>
                                <?php if (!empty($entry['clock_in'])): ?>
                                    • In: <strong><?= date('h:i A', strtotime($entry['clock_in'])) ?></strong>
                                <?php endif; ?>
                            </small>
                        </span>
                        <span class="badge status-<?= htmlspecialchars(strtolower($entry['status'] ?? 'present')) ?>" style="font-size: 12px; font-weight: 700; padding: 4px 10px; border-radius: 6px;">
                            <?= htmlspecialchars(ucfirst($entry['status'] ?? 'Present')) ?>
                        </span>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div style="text-align: center; padding: 40px 20px; color: #64748b;">
                <p style="font-size: 15px; margin: 0 0 12px 0;">No attendance records found yet today.</p>
                <a href="<?= htmlspecialchars($workBase . '/attendance/scan') ?>" style="display: inline-block; background: #3b82f6; color: #fff; padding: 8px 18px; border-radius: 6px; text-decoration: none; font-weight: 600; font-size: 0.9rem;">Start Face Scanner</a>
            </div>
        <?php endif; ?>
    </div>
</div>
