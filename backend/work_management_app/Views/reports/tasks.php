<section class="stats-grid">
    <article class="stat-card"><span>Total</span><strong><?= (int) ($stats['total_tasks'] ?? 0) ?></strong><small>All tracked tasks</small></article>
    <article class="stat-card"><span>Completed</span><strong><?= (int) ($stats['completed_tasks'] ?? 0) ?></strong><small>Done and scored</small></article>
    <article class="stat-card"><span>Pending</span><strong><?= (int) ($stats['pending_tasks'] ?? 0) ?></strong><small>Still in queue</small></article>
    <article class="stat-card"><span>Overdue</span><strong><?= (int) ($stats['overdue_tasks'] ?? 0) ?></strong><small>Past deadline</small></article>
</section>

<section class="panel">
    <div class="panel-head">
        <div><p class="eyebrow">Task report</p><h2>Total: <?= (int) ($stats['total_tasks'] ?? 0) ?> | Completed: <?= (int) ($stats['completed_tasks'] ?? 0) ?> | Pending: <?= (int) ($stats['pending_tasks'] ?? 0) ?> | Overdue: <?= (int) ($stats['overdue_tasks'] ?? 0) ?></h2></div>
        <div class="button-row"><button class="btn btn-light" type="button">Download PDF</button><button class="btn btn-primary" type="button">Export Excel</button></div>
    </div>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Task</th><th>Status</th><th>Deadline</th><th>Points</th></tr></thead>
            <tbody>
            <?php foreach ($tasks as $task): ?>
                <tr>
                    <td><?= htmlspecialchars($task['title']) ?></td>
                    <td><span class="badge"><?= htmlspecialchars(ucwords(str_replace('_', ' ', $task['status']))) ?></span></td>
                    <td><?= htmlspecialchars($task['deadline']) ?></td>
                    <td><?= (int) $task['points'] ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
