<?php $dashboardAwards = $awards ?? []; ?>
<?php $awardContext = $dashboardAwards['context'] ?? []; ?>
<section class="panel awards-panel">
    <div class="panel-head">
        <div>
            <p class="eyebrow">Awards & Rankings</p>
            <h3>Recognition highlights</h3>
        </div>
        <span class="text-link"><?= htmlspecialchars(($awardContext['last_month_label'] ?? date('F Y')) . ' snapshot') ?></span>
    </div>
    <div class="awards-grid">
        <?php foreach ([
            'employee_of_year' => ['label' => 'Employee of the Year', 'class' => 'award-badge-year'],
            'top_performance' => ['label' => 'Top Performance', 'class' => 'award-badge-performance'],
            'best_working_time' => ['label' => 'Top Working Time', 'class' => 'award-badge-working-time'],
        ] as $awardKey => $awardMeta): ?>
            <?php $award = $dashboardAwards[$awardKey] ?? []; ?>
            <?php
                $awardImage = $award['profile_thumbnail']
                    ?? $award['profile_image']
                    ?? $award['profile_image_path']
                    ?? null;
            ?>
            <article class="award-card <?= !empty($award['is_empty']) ? 'award-card-empty' : '' ?>">
                <span class="award-meta-label"><?= htmlspecialchars($awardMeta['label']) ?></span>
                <div class="award-avatar-wrap">
                    <div class="award-avatar <?= $awardImage ? 'award-avatar-image' : '' ?>">
                        <?php if ($awardImage): ?>
                            <img src="<?= htmlspecialchars(($basePath ?? '') . $awardImage) ?>" alt="<?= htmlspecialchars(($award['name'] ?? 'Award') . ' profile image') ?>">
                        <?php else: ?>
                            <?= htmlspecialchars(strtoupper(substr((string) ($award['name'] ?? 'A'), 0, 1))) ?>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="award-name <?= htmlspecialchars($awardMeta['class']) ?>">
                    <?= htmlspecialchars($award['name'] ?? 'Not available') ?>
                </div>
                <div class="award-copy">
                    <strong><?= htmlspecialchars($award['designation'] ?? 'Team Member') ?></strong>
                    <span><?= htmlspecialchars($award['award_title'] ?? 'Recognition') ?></span>
                </div>
                <?php if ($awardKey === 'employee_of_year'): ?>
                    <small class="award-footnote">Selected by Admin for <?= htmlspecialchars((string) ($awardContext['last_year'] ?? ((int) date('Y') - 1))) ?></small>
                <?php endif; ?>
            </article>
        <?php endforeach; ?>
    </div>
</section>
