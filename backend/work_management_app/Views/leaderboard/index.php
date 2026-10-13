<section class="panel">
    <div class="panel-head">
        <div><p class="eyebrow">Leaderboard</p><h2><?= htmlspecialchars(ucfirst($scope)) ?> ranking</h2></div>
        <div class="button-row">
            <a class="btn btn-light" href="<?= htmlspecialchars(($basePath ?? '') . '/leaderboard?scope=weekly') ?>">Weekly</a>
            <a class="btn btn-primary" href="<?= htmlspecialchars(($basePath ?? '') . '/leaderboard?scope=monthly') ?>">Monthly</a>
        </div>
    </div>
    <div class="ranking-list">
        <?php foreach ($ranking as $index => $member): ?>
            <article class="ranking-card">
                <div class="rank-number">#<?= $index + 1 ?></div>
                <div>
                    <h3><?= htmlspecialchars($member['name']) ?></h3>
                    <p><?= htmlspecialchars(ucfirst($member['role'])) ?> • <?= (int) $member['completed_tasks'] ?> completed tasks</p>
                </div>
                <div class="score-pill"><?= (int) $member['score'] ?> pts</div>
            </article>
        <?php endforeach; ?>
    </div>
</section>
