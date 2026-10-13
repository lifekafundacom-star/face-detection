<section class="stats-grid">
    <article class="stat-card"><span>Total score</span><strong><?= (int) ($stats['total_points'] ?? 0) ?></strong><small>Points from completed tasks</small></article>
    <article class="stat-card"><span>Tasks completed</span><strong><?= (int) ($stats['completed_tasks'] ?? 0) ?></strong><small>Closed across the selected scope</small></article>
    <article class="stat-card"><span>Ranking trend</span><strong>+8%</strong><small>Month-over-month uplift</small></article>
</section>

<section class="page-grid">
    <article class="panel panel-span-2">
        <div class="panel-head">
            <div><p class="eyebrow">Performance report</p><h2>Weekly and monthly productivity charts</h2></div>
            <div class="button-row"><button class="btn btn-light" type="button">Download PDF</button><button class="btn btn-primary" type="button">Export Excel</button></div>
        </div>
        <canvas id="performanceChartSecondary" height="260"></canvas>
    </article>

    <article class="panel">
        <div class="panel-head"><div><p class="eyebrow">Top performers</p><h3>Ranking snapshot</h3></div></div>
        <?php foreach ($leaderboard as $index => $member): ?>
            <div class="list-row">
                <div><strong>#<?= $index + 1 ?> <?= htmlspecialchars($member['name']) ?></strong><span><?= htmlspecialchars(ucfirst($member['role'])) ?></span></div>
                <span class="score-pill"><?= (int) $member['score'] ?> pts</span>
            </div>
        <?php endforeach; ?>
    </article>
</section>

<script>
window.REPORT_DATA = <?= json_encode(['performanceTrend' => $performanceTrend], JSON_UNESCAPED_SLASHES) ?>;
</script>
