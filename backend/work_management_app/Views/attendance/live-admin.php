<section class="panel">
    <div class="panel-head">
        <div>
            <p class="eyebrow">Admin Only</p>
            <h2>Live Attendance Tracking</h2>
        </div>
    </div>
    <div class="live-attendance-grid">
        <?php foreach (($liveAttendanceCards ?? []) as $employeeCard): ?>
            <?php
                $imageUrl = !empty($employeeCard['profile_thumbnail']) ? (($basePath ?? '') . $employeeCard['profile_thumbnail']) : (!empty($employeeCard['profile_image']) ? (($basePath ?? '') . $employeeCard['profile_image']) : (!empty($employeeCard['profile_image_path']) ? (($basePath ?? '') . $employeeCard['profile_image_path']) : ''));
                $statusClass = 'status-absent';
                if (($employeeCard['ui_status'] ?? '') === 'present') {
                    $statusClass = 'status-present';
                } elseif (($employeeCard['ui_status'] ?? '') === 'on_break') {
                    $statusClass = 'status-break';
                } elseif (($employeeCard['current_status'] ?? '') === 'Punched Out') {
                    $statusClass = 'status-offline';
                }
            ?>
            <article
                class="employee-live-card <?= htmlspecialchars($statusClass) ?>"
                data-href="<?= htmlspecialchars(($basePath ?? '') . '/attendance/employee/' . (int) $employeeCard['id']) ?>"
                tabindex="0"
                role="link"
                aria-label="<?= htmlspecialchars('Open live attendance report for ' . $employeeCard['name']) ?>"
            >
                <div class="employee-live-avatar-wrap">
                    <div class="employee-live-avatar <?= $imageUrl ? 'employee-live-avatar-image' : '' ?>">
                        <?php if ($imageUrl): ?>
                            <img src="<?= htmlspecialchars($imageUrl) ?>" alt="<?= htmlspecialchars($employeeCard['name'] . ' profile image') ?>">
                        <?php else: ?>
                            <?= htmlspecialchars(strtoupper(substr($employeeCard['name'], 0, 1))) ?>
                        <?php endif; ?>
                    </div>
                    <span class="employee-live-status-dot" aria-hidden="true"></span>
                </div>
                <div class="employee-live-name <?= htmlspecialchars($employeeCard['live_status_class'] ?? 'status-punchout') ?>"><?= htmlspecialchars($employeeCard['name']) ?></div>
                <div class="employee-live-tooltip">
                    <strong><?= htmlspecialchars($employeeCard['name']) ?></strong>
                    <span><?= htmlspecialchars($employeeCard['designation']) ?></span>
                    <span><?= htmlspecialchars($employeeCard['department']) ?></span>
                    <span>Work Time: <?= htmlspecialchars($employeeCard['work_time']) ?></span>
                    <span>Break Time: <?= htmlspecialchars($employeeCard['break_time']) ?></span>
                    <span>Over Time: <?= htmlspecialchars($employeeCard['overtime_time']) ?></span>
                    <span>Current Status: <?= htmlspecialchars($employeeCard['current_status']) ?></span>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
    <?php if (empty($liveAttendanceCards)): ?>
        <p class="empty-copy">No employee attendance data is available for today.</p>
    <?php endif; ?>
</section>
