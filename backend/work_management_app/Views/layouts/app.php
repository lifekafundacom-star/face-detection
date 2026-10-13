<?php
$config = require __DIR__ . '/../../Config/config.php';
$appName = $config['app']['name'];
$isAuthPage = ($page ?? '') === 'login';
$scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
$uriPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '';
$mountPath = '/backend/work-management';
$mountPosition = strpos($uriPath, $mountPath);
if ($mountPosition !== false) {
    $basePath = substr($uriPath, 0, $mountPosition + strlen($mountPath));
} else {
    $basePath = rtrim(str_replace('\\', '/', dirname($scriptName)), '/');
    $basePath = ($basePath && $basePath !== '.') ? $basePath : '';
}
$profileImageUrl = '';
if (!empty($user['profile_thumbnail'])) {
    $profileImageUrl = $basePath . $user['profile_thumbnail'];
} elseif (!empty($user['profile_image'])) {
    $profileImageUrl = $basePath . $user['profile_image'];
} elseif (!empty($user['profile_image_path'])) {
    $profileImageUrl = $basePath . $user['profile_image_path'];
}
$hideTracker = in_array(($page ?? ''), ['attendance', 'attendance-employee-detail', 'admin-live-attendance'], true) && (($user['role'] ?? '') === 'admin');
$url = static function (string $path = '/') use ($basePath): string {
    if ($path === '' || $path === '/') {
        return $basePath . '/';
    }

    return $basePath . $path;
};
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars(($title ?? 'App') . ' | ' . $appName) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Space+Grotesk:wght@500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= htmlspecialchars($url('/assets/css/app.css') . '?v=backend-ui-20260520a') ?>">
</head>
<body data-page="<?= htmlspecialchars($page ?? '') ?>">
<?php if ($isAuthPage): ?>
    <?php require $viewFile; ?>
<?php else: ?>
    <?php
    $backendBase = preg_replace('#/work-management$#', '', $basePath);
    $frontendBase = preg_replace('#/backend$#', '', $backendBase);
    $activePage = static function ($pages) use ($page): string {
        return in_array($page ?? '', (array) $pages, true) ? ' is-active' : '';
    };
    $topLink = static function (string $label, string $href, array $pages = []) use ($activePage): string {
        return '<a class="backend-nav-link' . $activePage($pages) . '" href="' . htmlspecialchars($href) . '">' . htmlspecialchars($label) . '</a>';
    };
    $topGroup = static function (string $label, array $items, array $pages = []) use ($activePage): string {
        $links = '';
        foreach ($items as $item) {
            if (!empty($item['children'])) {
                $childLinks = '';
                foreach ($item['children'] as $child) {
                    $childLinks .= '<a class="backend-nav-sub-link backend-nav-child-link' . $activePage($child['pages'] ?? []) . '" href="' . htmlspecialchars($child['href']) . '">' . htmlspecialchars($child['label']) . '</a>';
                }
                $links .= '<div class="backend-nav-sub-group"><span class="backend-nav-sub-heading">' . htmlspecialchars($item['label']) . '</span><div class="backend-nav-child-list">' . $childLinks . '</div></div>';
                continue;
            }
            $links .= '<a class="backend-nav-sub-link' . $activePage($item['pages'] ?? []) . '" href="' . htmlspecialchars($item['href']) . '">' . htmlspecialchars($item['label']) . '</a>';
        }

        return '<div class="backend-nav-group' . $activePage($pages) . '"><button type="button" class="backend-nav-parent"><span>' . htmlspecialchars($label) . '</span><span class="backend-nav-caret" aria-hidden="true"></span></button><div class="backend-nav-dropdown">' . $links . '</div></div>';
    };
    $navItems = \App\Core\MenuRegistry::groups($backendBase, $url);
    $canShowItem = static function (array $item) use ($user): bool {
        if (empty($item['permission']) || strpos((string) $item['permission'], 'group.') === 0) {
            return true;
        }

        return \App\Core\Auth::canAccess((string) $item['permission'], $user);
    };
    $filterItems = null;
    $filterItems = static function (array $items) use (&$filterItems, $canShowItem): array {
        $filtered = [];
        foreach ($items as $item) {
            if (!empty($item['children'])) {
                $item['children'] = $filterItems($item['children']);
                if (!empty($item['children'])) {
                    $filtered[] = $item;
                }
                continue;
            }

            if ($canShowItem($item)) {
                $filtered[] = $item;
            }
        }

        return $filtered;
    };
    $navItems = $filterItems($navItems);
    ?>
    <header class="backend-work-navbar">
        <div class="backend-work-nav-inner">
            <a class="backend-work-brand" href="<?= htmlspecialchars($backendBase . '/site/index') ?>" style="padding: 0; display: flex; align-items: center;">
                <img src="<?= htmlspecialchars($frontendBase . '/images/cropped-logo-copy3.png') ?>" alt="Lifeka Funda Admin" style="height: 40px; width: auto; display: block; object-fit: contain;" decoding="async">
            </a>
            <nav class="backend-nav-menu">
                <?php foreach ($navItems as $navItem): ?>
                    <?php if (!empty($navItem['children'])): ?>
                        <?= $topGroup($navItem['label'], $navItem['children'], $navItem['pages'] ?? []) ?>
                    <?php else: ?>
                        <?= $topLink($navItem['label'], $navItem['href'], $navItem['pages'] ?? []) ?>
                    <?php endif; ?>
                <?php endforeach; ?>
                <?= $topLink('Logout', $url('/logout')) ?>
            </nav>
        </div>
    </header>

    <main class="backend-work-main">
        <section class="backend-work-page-head">
            <div>
                <p class="eyebrow">Employee Productivity Suite</p>
                <h1><?= htmlspecialchars($title ?? 'Dashboard') ?></h1>
            </div>
            <div class="backend-work-head-actions">
                <?php if (!$hideTracker): ?>
                    <section class="time-tracker" id="timeTracker" data-status="<?= htmlspecialchars($timeTracking['status'] ?? 'offline') ?>">
                        <div class="tracker-state">
                            <span class="status-dot"></span>
                            <div>
                                <strong id="trackerLabel"><?= htmlspecialchars(ucfirst($timeTracking['status'] ?? 'offline')) ?></strong>
                                <small>Global work timer</small>
                            </div>
                        </div>
                        <div class="tracker-clock">
                            <div class="clock-block">
                                <span>Total (Raw)</span>
                                <strong id="rawWorkTimer">00:00:00</strong>
                            </div>
                            <div class="clock-block">
                                <span>Net Work</span>
                                <strong id="workTimer">00:00:00</strong>
                            </div>
                            <div class="clock-block">
                                <span>Break</span>
                                <strong id="breakTimer">00:00:00</strong>
                            </div>
                            <div class="clock-block clock-block-overtime">
                                <span>Over Time</span>
                                <strong id="overtimeTimer">00:00:00</strong>
                            </div>
                        </div>
                        <div class="tracker-actions">
                            <button class="btn btn-success" data-action="punch-in" type="button">Punch In</button>
                            <button class="btn btn-warning" data-action="break" type="button">Break</button>
                            <button class="btn btn-light" data-action="resume" type="button">Resume</button>
                            <button class="btn btn-danger" data-action="punch-out" type="button">Punch Out</button>
                        </div>
                    </section>
                <?php endif; ?>
                <div class="user-chip">
                    <div class="avatar <?= $profileImageUrl ? 'avatar-image' : '' ?>">
                        <?php if ($profileImageUrl): ?>
                            <img src="<?= htmlspecialchars($profileImageUrl) ?>" alt="<?= htmlspecialchars(($user['name'] ?? 'User') . ' profile image') ?>">
                        <?php else: ?>
                            <?= htmlspecialchars(strtoupper(substr($user['name'] ?? 'U', 0, 1))) ?>
                        <?php endif; ?>
                    </div>
                    <div>
                        <strong><?= htmlspecialchars($user['name'] ?? 'User') ?></strong>
                        <span><?= htmlspecialchars($user['custom_role_name'] ?? ucfirst($user['role'] ?? 'employee')) ?></span>
                    </div>
                </div>
            </div>
        </section>
        <section class="content">
            <?php require $viewFile; ?>
        </section>
    </main>
<?php endif; ?>

<script>
window.APP_BOOT = <?= json_encode(['user' => $user ?? null, 'timeTracking' => $timeTracking ?? null], JSON_UNESCAPED_SLASHES) ?>;
</script>
<?php if (in_array(($page ?? ''), ['dashboard', 'reports-performance', 'employee-performance'], true)): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<?php endif; ?>
<script src="https://cdn.socket.io/4.7.5/socket.io.min.js" defer></script>
<?php if (in_array(($page ?? ''), ['profile', 'admin-camera-attendance'], true)): ?>
<script src="<?= htmlspecialchars($url('/assets/vendor/face-api/face-api.min.js')) ?>"></script>
<?php endif; ?>
<script>
window.APP_BOOT = Object.assign({}, window.APP_BOOT || {}, {
    basePath: <?= json_encode($basePath, JSON_UNESCAPED_SLASHES) ?>,
    faceApiModelsUrl: <?= json_encode($url('/assets/vendor/face-api/models'), JSON_UNESCAPED_SLASHES) ?>
});
</script>
<script src="<?= htmlspecialchars($url('/assets/js/app.js')) ?>"></script>
</body>
</html>
