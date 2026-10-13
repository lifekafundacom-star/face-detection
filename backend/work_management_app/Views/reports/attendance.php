<section class="stats-grid">
    <article class="stat-card"><span>Monthly attendance %</span><strong><?= htmlspecialchars((string) ($stats['attendance_percentage'] ?? '0.00')) ?>%</strong><small>Present days over total days</small></article>
    <article class="stat-card"><span>Present</span><strong><?= (int) ($stats['present_days'] ?? 0) ?></strong><small>On-time days this month</small></article>
    <article class="stat-card"><span>Absent</span><strong><?= (int) ($stats['absent_days'] ?? 0) ?></strong><small>Missed workdays</small></article>
    <article class="stat-card"><span>Late</span><strong><?= (int) ($stats['late_days'] ?? 0) ?></strong><small>Late arrivals</small></article>
    <article class="stat-card"><span>Total Hours (Raw)</span><strong><?= htmlspecialchars($stats['total_raw_work_time'] ?? '00:00:00') ?></strong><small>Clock out minus clock in</small></article>
    <article class="stat-card"><span>Net Hours</span><strong><?= htmlspecialchars($stats['total_work_time'] ?? '00:00:00') ?></strong><small>After break deduction</small></article>
    <article class="stat-card"><span>Total Over Time</span><strong><?= htmlspecialchars($stats['total_overtime'] ?? '00:00:00') ?></strong><small>Hours above standard duty</small></article>
</section>

<section class="panel">
    <div class="panel-head">
        <div><p class="eyebrow">Attendance report</p><h2>Monthly attendance health</h2></div>
        <div class="button-row"><button class="btn btn-light" type="button">Download PDF</button><button class="btn btn-primary" type="button">Export Excel</button></div>
    </div>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Name</th><th>Date</th><th>Status</th><th>Clock In</th><th>Clock Out</th><th>Break Time</th><th>Total Hours (Raw)</th><th>Net Hours (After Break)</th><th>Over Time</th></tr></thead>
            <tbody>
            <?php foreach ($attendance as $entry): ?>
                <tr>
                    <td><?= htmlspecialchars($entry['name']) ?></td>
                    <td><?= htmlspecialchars($entry['date']) ?></td>
                    <td><span class="badge"><?= htmlspecialchars(ucfirst($entry['status'])) ?></span></td>
                    <td><?= htmlspecialchars($entry['clock_in'] ?? '--') ?></td>
                    <td><?= htmlspecialchars($entry['clock_out'] ?? '--') ?></td>
                    <td><?= htmlspecialchars($entry['break_time']) ?></td>
                    <td><?= htmlspecialchars($entry['raw_hours'] ?? '00:00:00') ?></td>
                    <td><?= htmlspecialchars($entry['net_hours'] ?? $entry['total_hours']) ?></td>
                    <td><span class="<?= !empty($entry['has_overtime']) ? 'score-pill' : 'badge' ?>"><?= htmlspecialchars($entry['overtime']) ?></span></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
