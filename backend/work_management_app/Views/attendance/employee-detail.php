<?php
$employeeImageUrl = '';
if (!empty($employee['profile_thumbnail'])) {
    $employeeImageUrl = ($basePath ?? '') . $employee['profile_thumbnail'];
} elseif (!empty($employee['profile_image'])) {
    $employeeImageUrl = ($basePath ?? '') . $employee['profile_image'];
} elseif (!empty($employee['profile_image_path'])) {
    $employeeImageUrl = ($basePath ?? '') . $employee['profile_image_path'];
}
$canEditAttendance = !empty($canEditAttendance);
?>
<section class="page-grid">
    <article class="panel panel-span-2">
        <div class="panel-head">
            <div><p class="eyebrow">Employee Attendance Report</p><h2><?= htmlspecialchars($employee['name']) ?></h2></div>
            <a class="btn btn-light" href="<?= htmlspecialchars(($basePath ?? '') . '/attendance') ?>">Back to Attendance</a>
        </div>
        <div class="employee-attendance-hero">
            <div class="employee-attendance-profile">
                <div class="employee-attendance-avatar <?= $employeeImageUrl ? 'employee-attendance-avatar-image' : '' ?>">
                    <?php if ($employeeImageUrl): ?>
                        <img src="<?= htmlspecialchars($employeeImageUrl) ?>" alt="<?= htmlspecialchars($employee['name'] . ' profile image') ?>">
                    <?php else: ?>
                        <?= htmlspecialchars(strtoupper(substr($employee['name'], 0, 1))) ?>
                    <?php endif; ?>
                </div>
                <div class="employee-attendance-meta">
                    <strong><?= htmlspecialchars($employee['designation'] ?: 'Team Member') ?></strong>
                    <span><?= htmlspecialchars($employee['department'] ?: 'Unassigned Department') ?></span>
                    <span><?= htmlspecialchars($employee['email']) ?></span>
                </div>
            </div>
            <form class="button-row" method="GET" action="<?= htmlspecialchars(($basePath ?? '') . '/attendance/employee/' . (int) $employee['id']) ?>">
                <label class="field">
                    <span>Month</span>
                    <input type="number" min="1" max="12" name="month" value="<?= (int) $month ?>">
                </label>
                <label class="field">
                    <span>Year</span>
                    <input type="number" min="2020" max="2100" name="year" value="<?= (int) $year ?>">
                </label>
                <button class="btn btn-primary" type="submit">Load Period</button>
            </form>
        </div>
    </article>

    <article class="stat-card"><span>Total Working Hours</span><strong><?= htmlspecialchars($monthly_summary['total_work_time'] ?? '00:00:00') ?></strong><small>This month</small></article>
    <article class="stat-card"><span>Total Overtime</span><strong><?= htmlspecialchars($monthly_summary['total_overtime'] ?? '00:00:00') ?></strong><small>This month</small></article>
    <article class="stat-card"><span>Total Break Time</span><strong><?= htmlspecialchars($monthly_summary['total_break_time'] ?? '00:00:00') ?></strong><small>This month</small></article>
    <article class="stat-card"><span>Present Days</span><strong><?= (int) ($monthly_summary['present_days'] ?? 0) ?></strong><small>Monthly summary</small></article>
    <article class="stat-card"><span>Late Days</span><strong><?= (int) ($monthly_summary['late_days'] ?? 0) ?></strong><small>Monthly summary</small></article>
    <article class="stat-card"><span>Absent Days</span><strong><?= (int) ($monthly_summary['absent_days'] ?? 0) ?></strong><small>Monthly summary</small></article>

    <article class="panel panel-span-2">
        <div class="panel-head"><div><p class="eyebrow">Daily Attendance</p><h3>Latest attendance records</h3></div></div>
        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th>Date</th>
                    <th>Clock In</th>
                    <th>Clock Out</th>
                    <th>Break Time</th>
                    <th>Total Hours (Raw)</th>
                    <th>Net Hours (After Break)</th>
                    <th>Over Time</th>
                    <th>Status</th>
                    <?php if ($canEditAttendance): ?><th>Action</th><?php endif; ?>
                </tr>
                </thead>
                <tbody>
                <?php foreach (($daily_attendance ?? []) as $entry): ?>
                    <?php $formId = 'attendance-detail-form-' . (int) $entry['id']; ?>
                    <tr class="attendance-detail-row" data-entry-id="<?= (int) $entry['id'] ?>">
                        <td><?= htmlspecialchars($entry['date']) ?></td>
                        <td>
                            <input
                                class="inline-time-input"
                                type="time"
                                name="clock_in"
                                value="<?= !empty($entry['clock_in']) ? htmlspecialchars(substr((string) $entry['clock_in'], 0, 5)) : '' ?>"
                                <?= $canEditAttendance ? 'form="' . htmlspecialchars($formId) . '" disabled' : 'disabled' ?>
                            >
                        </td>
                        <td>
                            <input
                                class="inline-time-input"
                                type="time"
                                name="clock_out"
                                value="<?= !empty($entry['clock_out']) ? htmlspecialchars(substr((string) $entry['clock_out'], 0, 5)) : '' ?>"
                                <?= $canEditAttendance ? 'form="' . htmlspecialchars($formId) . '" disabled' : 'disabled' ?>
                            >
                        </td>
                        <td>
                            <input
                                class="inline-time-input"
                                type="text"
                                name="break_time"
                                value="<?= htmlspecialchars($entry['break_time']) ?>"
                                placeholder="00:00:00"
                                <?= $canEditAttendance ? 'form="' . htmlspecialchars($formId) . '" disabled' : 'disabled' ?>
                            >
                        </td>
                        <td class="attendance-raw-hours"><?= htmlspecialchars($entry['raw_hours'] ?? '00:00:00') ?></td>
                        <td class="attendance-total-hours"><?= htmlspecialchars($entry['net_hours'] ?? $entry['total_hours']) ?></td>
                        <td class="attendance-overtime"><?= htmlspecialchars($entry['overtime']) ?></td>
                        <td class="attendance-status-cell"><span class="badge status-<?= htmlspecialchars($entry['status']) ?>"><?= htmlspecialchars(ucwords(str_replace('_', ' ', $entry['status']))) ?></span></td>
                        <?php if ($canEditAttendance): ?>
                            <td>
                                <form id="<?= htmlspecialchars($formId) ?>" class="attendance-update-form" action="<?= htmlspecialchars(($basePath ?? '') . '/api/attendance/' . (int) $entry['id'] . '/update') ?>" method="POST">
                                    <div class="action-row attendance-detail-actions">
                                        <button class="btn btn-light btn-small attendance-row-primary" type="button">Edit</button>
                                        <button class="btn btn-primary btn-small attendance-row-submit" type="submit" hidden>Save</button>
                                        <button class="btn btn-light btn-small attendance-row-cancel" type="button" hidden>Cancel</button>
                                    </div>
                                </form>
                            </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </article>

    <article class="panel">
        <div class="panel-head"><div><p class="eyebrow">Break History</p><h3>Days with recorded breaks</h3></div></div>
        <div class="monitor-list">
            <?php foreach (($break_history ?? []) as $entry): ?>
                <div class="list-row">
                    <div><strong><?= htmlspecialchars($entry['date']) ?></strong><span><?= htmlspecialchars($entry['break_time']) ?></span></div>
                    <span class="badge"><?= htmlspecialchars($entry['status']) ?></span>
                </div>
            <?php endforeach; ?>
            <?php if (empty($break_history)): ?><p class="empty-copy">No break history recorded for this period.</p><?php endif; ?>
        </div>
    </article>

    <article class="panel">
        <div class="panel-head"><div><p class="eyebrow">Status History</p><h3>Recent status timeline</h3></div></div>
        <div class="monitor-list">
            <?php foreach (($status_history ?? []) as $entry): ?>
                <div class="list-row">
                    <div><strong><?= htmlspecialchars($entry['date']) ?></strong><span><?= htmlspecialchars(($entry['clock_in'] ?? '--') . ' to ' . ($entry['clock_out'] ?? '--')) ?></span></div>
                    <span class="badge status-<?= htmlspecialchars($entry['status']) ?>"><?= htmlspecialchars(ucwords(str_replace('_', ' ', $entry['status']))) ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </article>

    <article class="panel">
        <div class="panel-head"><div><p class="eyebrow">Monthly Calendar</p><h3>Status at a glance</h3></div></div>
        <div class="calendar-grid">
            <?php foreach (($calendar ?? []) as $day): ?>
                <div class="calendar-cell <?= htmlspecialchars($day['status']) ?> <?= !empty($day['is_overtime']) ? 'overtime' : '' ?>">
                    <strong><?= (int) $day['day'] ?></strong>
                    <span><?= htmlspecialchars($day['label']) ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </article>
</section>
