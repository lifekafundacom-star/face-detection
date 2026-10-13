<?php $isAdminAttendance = ($user['role'] ?? '') === 'admin'; ?>

<?php if ($isAdminAttendance): ?>
<section class="stats-grid admin-stats-grid">
    <article class="stat-card"><span>Total Employees</span><strong><?= (int) ($attendanceOverview['total_employees'] ?? 0) ?></strong><small>Attendance eligible staff</small></article>
    <article class="stat-card"><span>Present Today</span><strong><?= (int) ($attendanceOverview['present_today'] ?? 0) ?></strong><small>On-time employees</small></article>
    <article class="stat-card"><span>Late Today</span><strong><?= (int) ($attendanceOverview['late_today'] ?? 0) ?></strong><small>After grace time</small></article>
    <article class="stat-card"><span>Absent Today</span><strong><?= (int) ($attendanceOverview['absent_today'] ?? 0) ?></strong><small>Missing attendance</small></article>
    <article class="stat-card"><span>On Break</span><strong><?= (int) ($attendanceOverview['on_break_now'] ?? 0) ?></strong><small>Currently paused</small></article>
    <article class="stat-card"><span>Overtime Today</span><strong><?= (int) ($attendanceOverview['overtime_today'] ?? 0) ?></strong><small><?= htmlspecialchars($attendanceOverview['overtime_time_today'] ?? '00:00:00') ?></small></article>
</section>

<section class="panel filter-panel">
    <div class="panel-head">
        <div><p class="eyebrow">Admin Attendance Grid</p><h2>Team presence and live shift activity</h2></div>
    </div>
    <form class="filter-grid" method="GET" action="<?= htmlspecialchars(($basePath ?? '') . '/attendance') ?>">
        <label class="field">
            <span>Date</span>
            <input type="date" name="date" value="<?= htmlspecialchars($attendanceFilters['date'] ?? date('Y-m-d')) ?>">
        </label>
        <label class="field">
            <span>Status</span>
            <select name="status">
                <option value="">All statuses</option>
                <option value="present" <?= ($attendanceFilters['status'] ?? '') === 'present' ? 'selected' : '' ?>>Present</option>
                <option value="late" <?= ($attendanceFilters['status'] ?? '') === 'late' ? 'selected' : '' ?>>Late</option>
                <option value="absent" <?= ($attendanceFilters['status'] ?? '') === 'absent' ? 'selected' : '' ?>>Absent</option>
                <option value="on_break" <?= ($attendanceFilters['status'] ?? '') === 'on_break' ? 'selected' : '' ?>>On Break</option>
                <option value="half_day" <?= ($attendanceFilters['status'] ?? '') === 'half_day' ? 'selected' : '' ?>>Half Day</option>
                <option value="on_leave" <?= ($attendanceFilters['status'] ?? '') === 'on_leave' ? 'selected' : '' ?>>On Leave</option>
            </select>
        </label>
        <label class="field">
            <span>Department</span>
            <select name="department">
                <option value="">All departments</option>
                <?php foreach (($attendanceDepartments ?? []) as $department): ?>
                    <option value="<?= htmlspecialchars($department['department']) ?>" <?= ($attendanceFilters['department'] ?? '') === $department['department'] ? 'selected' : '' ?>><?= htmlspecialchars($department['department']) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <div class="filter-actions">
            <button class="btn btn-primary" type="submit">Apply Filters</button>
            <a class="btn btn-light" href="<?= htmlspecialchars(($basePath ?? '') . '/attendance') ?>">Reset</a>
        </div>
    </form>
</section>

<?php else: ?>
<section class="page-grid">
    <article class="panel panel-span-2">
        <div class="panel-head">
            <div><p class="eyebrow">Attendance</p><h2>Daily status and monthly summary</h2></div>
        </div>
        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th>Employee Name</th>
                    <th>Date</th>
                    <th>Clock In</th>
                    <th>Clock Out</th>
                    <th>Break Time</th>
                    <th>Total Hours (Raw)</th>
                    <th>Net Hours (After Break)</th>
                    <th>Over Time</th>
                    <th>Status</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($attendance as $entry): ?>
                    <tr>
                        <td><?= htmlspecialchars($entry['name']) ?></td>
                        <td><?= htmlspecialchars($entry['date']) ?></td>
                        <td><?= htmlspecialchars($entry['clock_in'] ?? '--') ?></td>
                        <td><?= htmlspecialchars($entry['clock_out'] ?? '--') ?></td>
                        <td><?= htmlspecialchars($entry['break_time']) ?></td>
                        <td><?= htmlspecialchars($entry['raw_hours'] ?? '00:00:00') ?></td>
                        <td><?= htmlspecialchars($entry['net_hours'] ?? $entry['total_hours']) ?></td>
                        <td><span class="<?= !empty($entry['has_overtime']) ? 'score-pill' : 'badge' ?>"><?= htmlspecialchars($entry['overtime']) ?></span></td>
                        <td><span class="badge status-<?= htmlspecialchars($entry['status']) ?>"><?= htmlspecialchars(ucwords(str_replace('_', ' ', $entry['status']))) ?></span></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </article>

    <article class="panel">
        <div class="panel-head"><div><p class="eyebrow">Calendar View</p><h3>Month at a glance</h3></div></div>
        <div class="calendar-grid">
            <?php foreach (($attendanceCalendar ?? []) as $day): ?>
                <div class="calendar-cell <?= htmlspecialchars($day['status']) ?> <?= !empty($day['is_overtime']) ? 'overtime' : '' ?>">
                    <strong><?= (int) $day['day'] ?></strong>
                    <span><?= htmlspecialchars($day['label']) ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </article>
</section>
<?php endif; ?>
