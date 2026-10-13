<?php

namespace App\Core;

class MenuRegistry
{
    public static function groups(string $backendBase, callable $url): array
    {
        return [
            [
                'label' => 'Dashboard',
                'href' => $backendBase . '/site/index',
                'permission' => 'backend.dashboard',
            ],
            [
                'label' => 'Work Management',
                'permission' => 'group.work',
                'pages' => ['attendance', 'attendance-employee-detail', 'attendance-scan', 'reports-attendance'],
                'children' => [
                    ['label' => 'Scan Attendance', 'href' => $url('/attendance/scan'), 'pages' => ['attendance-scan'], 'permission' => 'work.attendance'],
                    ['label' => 'Attendance', 'href' => $url('/attendance'), 'pages' => ['attendance', 'attendance-employee-detail'], 'permission' => 'work.attendance'],
                    ['label' => 'Attendance Report', 'href' => $url('/reports/attendance'), 'pages' => ['reports-attendance'], 'permission' => 'work.reports.attendance'],
                ],
            ],
            [
                'label' => 'Employee Management',
                'permission' => 'group.employees',
                'pages' => ['admin-users', 'admin-roles', 'admin-live-attendance'],
                'children' => [
                    ['label' => 'Manage Users', 'href' => $url('/admin/users'), 'pages' => ['admin-users'], 'permission' => 'employees.users'],
                    ['label' => 'Role Management', 'href' => $url('/admin/roles'), 'pages' => ['admin-roles'], 'permission' => 'employees.roles'],
                    ['label' => 'Live Attendance', 'href' => $url('/admin_live_attendance'), 'pages' => ['admin-live-attendance'], 'permission' => 'employees.live_attendance'],
                ],
            ],
            [
                'label' => 'Account',
                'permission' => 'group.account',
                'pages' => ['profile', 'settings'],
                'children' => [
                    ['label' => 'Profile', 'href' => $url('/profile'), 'pages' => ['profile'], 'permission' => 'account.profile'],
                    ['label' => 'Settings', 'href' => $url('/settings'), 'pages' => ['settings'], 'permission' => 'account.settings'],
                ],
            ],
        ];
    }

    public static function permissionGroups(): array
    {
        return [
            'Backend' => [
                'backend.dashboard' => 'Dashboard',
                'backend.contact' => 'Contact',
            ],
            'Website Manage' => [
                'website.language' => 'Language',
                'website.category' => 'Category',
                'website.subcategory' => 'Sub Category',
            ],
            'Card Manage' => [
                'cards.template' => 'Template',
                'cards.product' => 'Product',
                'cards.template_mapping' => 'Template Mapping',
            ],
            'Analytics' => [
                'analytics.google' => 'Google Analytics',
                'analytics.internal' => 'Analytics',
                'analytics.search' => 'Search Analytics',
            ],
            'Work Management' => [
                'work.definitions' => 'Work Definitions',
                'work.tasks' => 'My Tasks',
                'work.attendance' => 'Attendance',
                'work.reports.performance' => 'Performance Report',
                'work.reports.tasks' => 'Task Report',
                'work.reports.attendance' => 'Attendance Report',
                'work.leaderboard' => 'Leaderboard',
                'work.workflows' => 'Workflows',
            ],
            'Employee Management' => [
                'employees.users' => 'Manage Users',
                'employees.roles' => 'Role Management',
                'employees.live_attendance' => 'Live Attendance',
                'employees.camera_attendance' => 'Camera Attendance',
                'employees.performance' => 'Employee Performance',
            ],
            'Account' => [
                'account.profile' => 'Profile',
                'account.settings' => 'Settings',
            ],
        ];
    }

    public static function permissionKeys(): array
    {
        $keys = [];
        foreach (self::permissionGroups() as $permissions) {
            $keys = array_merge($keys, array_keys($permissions));
        }

        return $keys;
    }

    public static function systemRoleLabels(): array
    {
        return [
            'admin' => 'Admin',
            'manager' => 'Manager',
            'employee' => 'Employee',
        ];
    }

    public static function defaultPermissionsForRole(string $role): array
    {
        $role = strtolower(trim($role));

        if ($role === 'admin') {
            return self::permissionKeys();
        }

        if ($role === 'manager') {
            return [
                'backend.dashboard',
                'work.definitions',
                'work.tasks',
                'work.attendance',
                'work.reports.performance',
                'work.reports.tasks',
                'work.reports.attendance',
                'work.leaderboard',
                'work.workflows',
                'employees.live_attendance',
                'employees.camera_attendance',
                'employees.performance',
                'account.profile',
                'account.settings',
            ];
        }

        if ($role === 'employee') {
            return [
                'work.tasks',
                'work.attendance',
                'work.leaderboard',
                'account.profile',
                'account.settings',
            ];
        }

        return [];
    }

    public static function pagePermission(string $page): ?string
    {
        $map = [
            'tasks' => 'work.tasks',
            'attendance' => 'work.attendance',
            'attendance-employee-detail' => 'work.attendance',
            'attendance-scan' => 'work.attendance',
            'reports-performance' => 'work.reports.performance',
            'reports-tasks' => 'work.reports.tasks',
            'reports-attendance' => 'work.reports.attendance',
            'leaderboard' => 'work.leaderboard',
            'workflows' => 'work.workflows',
            'profile' => 'account.profile',
            'settings' => 'account.settings',
            'admin-users' => 'employees.users',
            'admin-roles' => 'employees.roles',
            'admin-live-attendance' => 'employees.live_attendance',
            'admin-camera-attendance' => 'employees.camera_attendance',
            'employee-performance' => 'employees.performance',
        ];

        return $map[$page] ?? null;
    }
}
