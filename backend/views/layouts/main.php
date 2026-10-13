<?php

/** @var \yii\web\View $this */
/** @var string $content */

use backend\assets\AppAsset;
use common\widgets\Alert;
use yii\bootstrap5\Breadcrumbs;
use yii\bootstrap5\Html;

AppAsset::register($this);
?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>" class="h-100">
<head>
    <meta charset="<?= Yii::$app->charset ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <?php $this->registerCsrfMetaTags() ?>
    <title><?= Html::encode($this->title) ?></title>
    <?php $this->head() ?>
</head>
<body class="d-flex flex-column h-100">
<?php $this->beginBody() ?>

<header>
    <?php
    $controllerId = Yii::$app->controller->id;
    $navActive = static function ($controllers) use ($controllerId) {
        return in_array($controllerId, (array) $controllers, true) ? ' is-active' : '';
    };
    $navLink = static function ($label, $url, $controllers) use ($navActive) {
        return Html::a($label, $url, ['class' => 'backend-nav-link' . $navActive($controllers)]);
    };
    $navGroup = static function ($label, $controllers, $items) use ($navActive) {
        $links = '';
        foreach ($items as $item) {
            if (!empty($item['children'])) {
                $childLinks = '';
                foreach ($item['children'] as $child) {
                    $childLinks .= Html::a($child['label'], $child['url'], [
                        'class' => 'backend-nav-sub-link backend-nav-child-link' . $navActive($child['controllers'] ?? []),
                    ]);
                }
                $links .= Html::tag(
                    'div',
                    Html::tag('span', Html::encode($item['label']), ['class' => 'backend-nav-sub-heading'])
                    . Html::tag('div', $childLinks, ['class' => 'backend-nav-child-list']),
                    ['class' => 'backend-nav-sub-group']
                );
                continue;
            }

            $links .= Html::a($item['label'], $item['url'], [
                'class' => 'backend-nav-sub-link' . $navActive($item['controllers'] ?? []),
            ]);
        }

        return Html::tag(
            'div',
            Html::button(
                Html::tag('span', Html::encode($label), ['class' => 'backend-nav-parent-label'])
                . Html::tag('span', '', ['class' => 'backend-nav-caret', 'aria-hidden' => 'true']),
                [
                    'type' => 'button',
                    'class' => 'backend-nav-parent',
                    'aria-haspopup' => 'true',
                ]
            )
            . Html::tag('div', $links, ['class' => 'backend-nav-dropdown', 'role' => 'menu']),
            ['class' => 'backend-nav-group' . $navActive($controllers)]
        );
    };
    $workBase = Yii::$app->request->baseUrl . '/work-management';
    $identity = Yii::$app->user->isGuest ? null : Yii::$app->user->identity;
    $identityName = $identity ? ($identity->name ?: ($identity->username ?? $identity->email ?? 'User')) : '';
    $identityDesignation = $identity ? ($identity->designation ?: ($identity->getCustomRoleName() ?: ucfirst($identity->role ?? 'User'))) : '';
    $identityImage = '';
    if ($identity) {
        $imagePath = $identity->profile_thumbnail ?: ($identity->profile_image ?: $identity->profile_image_path);
        if ($imagePath) {
            $identityImage = Yii::$app->request->baseUrl . '/work-management' . $imagePath;
        }
    }

    $canAccess = static function ($permission) use ($identity) {
        if (!$identity) {
            return false;
        }
        if ($identity->role === 'admin') {
            return true;
        }
        return $identity->canAccess($permission);
    };

    $menuList = [
        [
            'type' => 'link',
            'label' => 'Dashboard',
            'url' => ['/site/index'],
            'controllers' => ['site'],
            'permission' => 'backend.dashboard',
        ],
        [
            'type' => 'group',
            'label' => 'Work Management',
            'controllers' => [],
            'items' => [
                ['label' => 'Scan Attendance', 'url' => $workBase . '/attendance/scan', 'permission' => 'work.attendance'],
                ['label' => 'Attendance', 'url' => $workBase . '/attendance', 'permission' => 'work.attendance'],
                ['label' => 'Attendance Report', 'url' => $workBase . '/reports/attendance', 'permission' => 'work.reports.attendance'],
            ],
        ],
        [
            'type' => 'group',
            'label' => 'Employee Management',
            'controllers' => [],
            'items' => [
                ['label' => 'Manage Users', 'url' => $workBase . '/admin/users', 'permission' => 'employees.users'],
                ['label' => 'Role Management', 'url' => $workBase . '/admin/roles', 'permission' => 'employees.roles'],
                ['label' => 'Live Attendance', 'url' => $workBase . '/admin_live_attendance', 'permission' => 'employees.live_attendance'],
            ],
        ],
        [
            'type' => 'group',
            'label' => 'Account',
            'controllers' => [],
            'items' => [
                ['label' => 'Profile', 'url' => $workBase . '/profile', 'permission' => 'account.profile'],
                ['label' => 'Settings', 'url' => $workBase . '/settings', 'permission' => 'account.settings'],
            ],
        ],
    ];

    $filterMenu = static function ($items) use (&$filterMenu, $canAccess) {
        $filtered = [];
        foreach ($items as $item) {
            if (isset($item['permission']) && !empty($item['permission'])) {
                if (strpos($item['permission'], 'group.') !== 0) {
                    if (!$canAccess($item['permission'])) {
                        continue;
                    }
                }
            }

            if (isset($item['children'])) {
                $item['children'] = $filterMenu($item['children']);
                if (empty($item['children'])) {
                    continue;
                }
            }

            if (isset($item['items'])) {
                $item['items'] = $filterMenu($item['items']);
                if (empty($item['items'])) {
                    continue;
                }
            }

            $filtered[] = $item;
        }
        return $filtered;
    };

    $filteredMenu = $filterMenu($menuList);
    ?>
    <nav class="backend-navbar fixed-top" aria-label="Backend navigation">
        <div class="backend-navbar-inner">
            <div class="backend-navbar-top">
                <?= Html::a(
                    Html::img(Yii::$app->request->baseUrl . '/images/cropped-logo-copy3.png', [
                        'alt' => 'Lifeka Funda Admin',
                        'style' => 'height: 40px; width: auto; display: block; object-fit: contain;',
                        'decoding' => 'async'
                    ]),
                    Yii::$app->homeUrl,
                    ['class' => 'navbar-brand backend-brand', 'style' => 'padding: 0; display: flex; align-items: center;']
                ) ?>
                <div class="backend-navbar-account">
                    <?php if (Yii::$app->user->isGuest): ?>
                        <?= Html::a('Login', ['/site/login'], ['class' => 'btn btn-link login backend-login text-decoration-none']) ?>
                    <?php else: ?>
                        <div class="backend-user-chip">
                            <div class="backend-user-thumb">
                                <?php if ($identityImage): ?>
                                    <?= Html::img($identityImage, ['alt' => $identityName]) ?>
                                <?php else: ?>
                                    <span><?= Html::encode(strtoupper(substr($identityName ?: 'U', 0, 1))) ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="backend-user-meta">
                                <strong><?= Html::encode($identityName) ?></strong>
                                <small><?= Html::encode($identityDesignation) ?></small>
                            </div>
                        </div>
                        <?= Html::beginForm(['/site/logout'], 'post', ['class' => 'backend-logout-form']) ?>
                            <?= Html::submitButton('Logout', ['class' => 'btn btn-link logout backend-logout text-decoration-none']) ?>
                        <?= Html::endForm() ?>
                    <?php endif; ?>
                </div>
            </div>
            <div class="backend-navbar-bottom">
                <div class="backend-nav-menu">
                    <?php foreach ($filteredMenu as $item): ?>
                        <?php if ($item['type'] === 'link'): ?>
                            <?= $navLink($item['label'], $item['url'], $item['controllers']) ?>
                        <?php elseif ($item['type'] === 'group'): ?>
                            <?= $navGroup($item['label'], $item['controllers'], $item['items']) ?>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </nav>
</header>

<main role="main" class="flex-shrink-0">
    <div class="container1">
        <?= Breadcrumbs::widget([
            'links' => isset($this->params['breadcrumbs']) ? $this->params['breadcrumbs'] : [],
        ]) ?>
        <?= Alert::widget() ?>
        <?= $content ?>
    </div>
</main>

<div class="admin-image-preview-modal" id="adminImagePreviewModal" aria-hidden="true">
    <div class="admin-image-preview-dialog" role="dialog" aria-modal="true" aria-labelledby="adminImagePreviewTitle">
        <button type="button" class="admin-image-preview-close" aria-label="Close preview">&times;</button>
        <div class="admin-image-preview-frame">
            <img src="" alt="" id="adminImagePreviewImage">
        </div>
        <div class="admin-image-preview-meta">
            <h2 id="adminImagePreviewTitle"></h2>
            <p id="adminImagePreviewFile"></p>
        </div>
    </div>
</div>

<div class="admin-text-preview-modal" id="adminTextPreviewModal" aria-hidden="true">
    <div class="admin-text-preview-dialog" role="dialog" aria-modal="true" aria-labelledby="adminTextPreviewTitle">
        <button type="button" class="admin-text-preview-close" aria-label="Close preview">&times;</button>
        <div class="admin-text-preview-head">
            <h2 id="adminTextPreviewTitle"></h2>
        </div>
        <div class="admin-text-preview-body" id="adminTextPreviewBody"></div>
    </div>
</div>

<footer class="footer backend-footer mt-auto py-3 text-muted">
    <div class="container">
        <p class="float-start">&copy; <?= Html::encode(Yii::$app->name) ?> <?= date('Y') ?></p>
        <p class="float-end"><?= Yii::powered() ?></p>
    </div>
</footer>

<script>
document.addEventListener('click', function (event) {
    var trigger = event.target.closest('[data-admin-preview-src]');
    var textTrigger = event.target.closest('[data-admin-text-preview]');
    var modal = document.getElementById('adminImagePreviewModal');
    var textModal = document.getElementById('adminTextPreviewModal');

    if (trigger && modal) {
        event.preventDefault();
        document.getElementById('adminImagePreviewImage').src = trigger.getAttribute('data-admin-preview-src') || '';
        document.getElementById('adminImagePreviewImage').alt = trigger.getAttribute('data-admin-preview-title') || 'Image preview';
        document.getElementById('adminImagePreviewTitle').textContent = trigger.getAttribute('data-admin-preview-title') || 'Image preview';
        document.getElementById('adminImagePreviewFile').textContent = trigger.getAttribute('data-admin-preview-file') || '';
        modal.classList.add('is-open');
        modal.setAttribute('aria-hidden', 'false');
        return;
    }

    if (textTrigger && textModal) {
        event.preventDefault();
        document.getElementById('adminTextPreviewTitle').textContent = textTrigger.getAttribute('data-admin-text-title') || 'Full preview';
        document.getElementById('adminTextPreviewBody').innerHTML = textTrigger.getAttribute('data-admin-text-preview') || '';
        textModal.classList.add('is-open');
        textModal.setAttribute('aria-hidden', 'false');
        return;
    }

    if (event.target.matches('.admin-image-preview-close') || event.target === modal) {
        closeAdminImagePreview();
    }

    if (event.target.matches('.admin-text-preview-close') || event.target === textModal) {
        closeAdminTextPreview();
    }
});

document.addEventListener('keyup', function (event) {
    if (event.key === 'Escape') {
        closeAdminImagePreview();
        closeAdminTextPreview();
    }
});

function closeAdminImagePreview() {
    var modal = document.getElementById('adminImagePreviewModal');
    var image = document.getElementById('adminImagePreviewImage');

    if (!modal) {
        return;
    }

    modal.classList.remove('is-open');
    modal.setAttribute('aria-hidden', 'true');

    if (image) {
        image.src = '';
        image.alt = '';
    }
}

function closeAdminTextPreview() {
    var modal = document.getElementById('adminTextPreviewModal');
    var body = document.getElementById('adminTextPreviewBody');

    if (!modal) {
        return;
    }

    modal.classList.remove('is-open');
    modal.setAttribute('aria-hidden', 'true');

    if (body) {
        body.innerHTML = '';
    }
}
</script>

<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage();
