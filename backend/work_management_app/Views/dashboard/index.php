<?php
$quickLinks = [
    [
        'label' => 'Scan Attendance',
        'desc' => 'Launch Face Scanner',
        'url' => ($basePath ?? '') . '/attendance/scan',
        'tone' => 'blue',
        'action' => 'Open Scanner',
    ],
    [
        'label' => 'Attendance Records',
        'desc' => 'View Daily Punch Logs',
        'url' => ($basePath ?? '') . '/attendance',
        'tone' => 'teal',
        'action' => 'View Attendance',
    ],
    [
        'label' => 'Attendance Report',
        'desc' => 'Monthly Summary & Analytics',
        'url' => ($basePath ?? '') . '/reports/attendance',
        'tone' => 'amber',
        'action' => 'View Report',
    ],
    [
        'label' => 'Manage Users',
        'desc' => 'Employees & Face Profiles',
        'url' => ($basePath ?? '') . '/admin/users',
        'tone' => 'rose',
        'action' => 'Manage Users',
    ],
];
?>
<section class="hero-card">
    <div>
        <p class="eyebrow">Biometric Attendance Station</p>
        <h2>Welcome, <?= htmlspecialchars(explode(' ', $user['name'] ?? 'Admin')[0]) ?></h2>
        <p class="lede">Face recognition biometric attendance and employee verification station.</p>
    </div>
    <div class="hero-badge">
        <a href="<?= htmlspecialchars(($basePath ?? '') . '/attendance/scan') ?>" style="color: #fff; text-decoration: none; font-weight: 700;">
            <i class="fa-solid fa-camera"></i> Open Scanner &rarr;
        </a>
    </div>
</section>

<!-- Quick Action Shortcuts -->
<section class="stats-grid" style="margin-top: 20px;">
    <?php foreach ($quickLinks as $item): ?>
        <a href="<?= htmlspecialchars($item['url']) ?>" class="stat-card" style="text-decoration: none; color: inherit;">
            <span style="font-size: 0.85rem; color: var(--text-muted, #94a3b8);"><?= htmlspecialchars($item['label']) ?></span>
            <strong style="font-size: 1.15rem; color: #fff;"><?= htmlspecialchars($item['desc']) ?></strong>
            <small style="color: #3b82f6; font-weight: 600;"><?= htmlspecialchars($item['action']) ?> &rarr;</small>
        </a>
    <?php endforeach; ?>
</section>

<!-- Recent Attendance Entries -->
<section class="panel" style="margin-top: 24px;">
    <div class="panel-head">
        <div>
            <p class="eyebrow">Attendance Snapshot</p>
            <h3>Recent Attendance Entries</h3>
        </div>
        <a href="<?= htmlspecialchars(($basePath ?? '') . '/attendance') ?>" class="text-link">Open attendance &rarr;</a>
    </div>
    <?php if (!empty($attendance)): ?>
        <div class="attendance-list">
            <?php foreach ($attendance as $entry): ?>
                <div class="list-row">
                    <div>
                        <strong><?= htmlspecialchars($entry['name']) ?></strong>
                        <span>
                            <?= htmlspecialchars($entry['date']) ?>
                            <?php if (!empty($entry['clock_in'])): ?>
                                • In: <?= htmlspecialchars($entry['clock_in']) ?>
                            <?php endif; ?>
                        </span>
                    </div>
                    <span class="badge status-<?= htmlspecialchars(strtolower($entry['status'] ?? 'present')) ?>"><?= htmlspecialchars(ucfirst($entry['status'] ?? 'Present')) ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div style="padding: 30px; text-align: center; color: var(--text-muted, #94a3b8);">
            <p>No scans recorded yet today.</p>
        </div>
    <?php endif; ?>
</section>
