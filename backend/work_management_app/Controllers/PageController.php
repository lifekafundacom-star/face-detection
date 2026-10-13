<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\MenuRegistry;
use App\Core\Request;
use App\Core\Response;
use App\Core\View;
use App\Repositories\AttendanceRepository;
use App\Repositories\AwardRepository;
use App\Repositories\DepartmentRepository;
use App\Repositories\SettingsRepository;
use App\Repositories\RoleRepository;
use App\Repositories\TaskRepository;
use App\Repositories\TimeTrackingRepository;
use App\Repositories\UserRepository;
use App\Repositories\WorkflowRepository;
use App\Services\DashboardService;

class PageController
{
    private function render(string $view, string $title, string $page, array $extra = []): void
    {
        $user = $this->currentUser();
        $permission = MenuRegistry::pagePermission($page);
        if ($permission && !Auth::canAccess($permission, $user)) {
            http_response_code(403);
            echo 'Forbidden';
            exit;
        }

        View::render($view, array_merge([
            'title' => $title,
            'page' => $page,
            'user' => $user,
            'navigation' => $this->navigation($user['role']),
            'timeTracking' => (new TimeTrackingRepository())->summary((int) $user['id']),
        ], $extra));
    }

    private function currentUser(): array
    {
        $user = Auth::user();
        if (!$user) {
            Response::redirect('/');
        }

        return $user;
    }

    public function dashboard(Request $request = null, array $params = []): void
    {
        $this->render('dashboard/index', 'Dashboard', 'dashboard', (new DashboardService())->data($this->currentUser()));
    }

    public function tasks(Request $request = null, array $params = []): void
    {
        $taskRepository = new TaskRepository();
        $user = $this->currentUser();
        $this->render('tasks/index', 'My Tasks', 'tasks', [
            'tasks' => $taskRepository->listForUser($user),
            'workflowDepartments' => in_array(($user['role'] ?? ''), ['admin', 'manager'], true) ? $taskRepository->workflowDepartments() : [],
            'canCreateTasks' => in_array(($user['role'] ?? ''), ['admin', 'manager'], true),
        ]);
    }

    public function attendance(Request $request = null, array $params = []): void
    {
        $attendanceRepository = new AttendanceRepository();
        $user = $this->currentUser();
        $filters = [
            'date' => (string) ($request ? $request->query('date', date('Y-m-d')) : date('Y-m-d')),
            'status' => (string) ($request ? $request->query('status', '') : ''),
            'department' => (string) ($request ? $request->query('department', '') : ''),
        ];

        $this->render('attendance/index', 'Attendance', 'attendance', [
            'attendance' => $attendanceRepository->listForUser($user, $filters),
            'attendanceCalendar' => $attendanceRepository->monthlyCalendar($user),
            'attendanceOverview' => $user['role'] === 'admin' ? $attendanceRepository->todayOverview($filters) : [],
            'adminAttendanceCards' => $user['role'] === 'admin' ? $attendanceRepository->adminEmployeeCards($filters) : [],
            'lateMonitoring' => $user['role'] === 'admin' ? $attendanceRepository->lateMonitoring($filters) : [],
            'absentManagement' => $user['role'] === 'admin' ? $attendanceRepository->absentManagement($filters) : [],
            'attendanceFilters' => $filters,
            'attendanceDepartments' => $user['role'] === 'admin' ? $attendanceRepository->departments() : [],
        ]);
    }

    public function scanAttendance(Request $request = null, array $params = []): void
    {
        $config = require __DIR__ . '/../Config/config.php';
        $db = \App\Core\Database::connection($config['db']);
        $stmt = $db->query("
            SELECT a.user_id, u.name, u.designation, a.clock_in 
            FROM attendance a 
            INNER JOIN users u ON a.user_id = u.id 
            WHERE a.date = CURDATE() AND a.clock_in IS NOT NULL 
            ORDER BY a.clock_in DESC 
            LIMIT 6
        ");
        $recentLogs = $stmt->fetchAll();

        $this->render('attendance/scan', 'Scan Attendance', 'attendance-scan', [
            'recentLogs' => $recentLogs
        ]);
    }

    public function attendanceEmployeeDetail(Request $request, array $params = []): void
    {
        $user = $this->currentUser();
        Auth::requireRole(['admin', 'manager', 'employee']);

        $targetUser = (new UserRepository())->findById((int) $params['id']);
        if (!$targetUser || !in_array(($targetUser['role'] ?? ''), ['manager', 'employee'], true)) {
            http_response_code(404);
            echo 'Employee not found.';
            exit;
        }

        $canView = false;
        if (($user['role'] ?? '') === 'admin') {
            $canView = true;
        } elseif (($user['role'] ?? '') === 'manager') {
            $canView = ((int) $targetUser['id'] === (int) $user['id']) || ((int) ($targetUser['manager_id'] ?? 0) === (int) $user['id']);
        } elseif (($user['role'] ?? '') === 'employee') {
            $canView = (int) $targetUser['id'] === (int) $user['id'];
        }

        if (!$canView) {
            http_response_code(403);
            echo 'Forbidden';
            exit;
        }

        $attendanceRepository = new AttendanceRepository();
        $month = (int) ($request ? $request->query('month', date('n')) : date('n'));
        $year = (int) ($request ? $request->query('year', date('Y')) : date('Y'));

        $detail = $attendanceRepository->adminEmployeeDetail((int) $params['id'], $month, $year);
        $detail['canEditAttendance'] = ($user['role'] ?? '') === 'admin';

        $this->render('attendance/employee-detail', 'Employee Attendance Detail', 'attendance-employee-detail', $detail);
    }

    public function adminLiveAttendance(Request $request = null, array $params = []): void
    {
        Auth::requireRole(['admin']);
        $attendanceRepository = new AttendanceRepository();

        $this->render('attendance/live-admin', 'Live Attendance Tracking', 'admin-live-attendance', [
            'liveAttendanceCards' => $attendanceRepository->liveAttendanceCards(),
        ]);
    }

    public function adminCameraAttendance(Request $request = null, array $params = []): void
    {
        Auth::requireRole(['admin']);
        $this->render('attendance/camera-admin', 'Camera Attendance Punch', 'admin-camera-attendance', [
            'attendanceCandidates' => (new UserRepository())->attendanceCandidates(),
        ]);
    }

    public function performanceReport(Request $request = null, array $params = []): void
    {
        $this->render('reports/performance', 'Performance Report', 'reports-performance', (new DashboardService())->data($this->currentUser()));
    }

    public function employeePerformance(Request $request = null, array $params = []): void
    {
        $this->render('reports/performance', 'Employee Performance', 'employee-performance', (new DashboardService())->data($this->currentUser()));
    }

    public function taskReport(Request $request = null, array $params = []): void
    {
        $this->render('reports/tasks', 'Task Report', 'reports-tasks', (new DashboardService())->data($this->currentUser()));
    }

    public function attendanceReport(Request $request = null, array $params = []): void
    {
        $this->render('reports/attendance', 'Attendance Report', 'reports-attendance', (new DashboardService())->data($this->currentUser()));
    }

    public function leaderboard(Request $request, array $params = []): void
    {
        $scope = (string) $request->query('scope', 'monthly');
        $this->render('leaderboard/index', 'Leaderboard', 'leaderboard', [
            'ranking' => (new UserRepository())->forLeaderboard($scope === 'weekly' ? 'weekly' : 'monthly'),
            'scope' => $scope,
        ]);
    }

    public function profile(Request $request = null, array $params = []): void
    {
        $this->render('profile/index', 'Profile', 'profile');
    }

    public function settings(Request $request = null, array $params = []): void
    {
        $user = $this->currentUser();
        $this->render('settings/index', 'Settings', 'settings', [
            'attendanceRules' => $user['role'] === 'admin' ? (new SettingsRepository())->attendanceRules() : [],
            'departments' => $user['role'] === 'admin' ? (new DepartmentRepository())->all() : [],
            'awardEmployeeOptions' => $user['role'] === 'admin' ? (new AwardRepository())->employeeOptions() : [],
            'awardSettings' => $user['role'] === 'admin' ? (new AwardRepository())->settingsCards() : [],
            'awardSelections' => $user['role'] === 'admin' ? (new AwardRepository())->awardSelections() : [],
        ]);
    }

    public function users(Request $request = null, array $params = []): void
    {
        Auth::requireRole(['admin']);
        $repository = new UserRepository();
        $filters = [
            'search' => (string) ($request ? $request->query('search', '') : ''),
            'role' => (string) ($request ? $request->query('role', '') : ''),
            'department' => (string) ($request ? $request->query('department', '') : ''),
            'status' => (string) ($request ? $request->query('status', '') : ''),
            'created_date' => (string) ($request ? $request->query('created_date', '') : ''),
            'sort' => (string) ($request ? $request->query('sort', 'newest') : 'newest'),
        ];
        $roleRepository = new RoleRepository();

        $this->render('admin/users', 'Manage Users', 'admin-users', [
            'users' => $repository->all($filters),
            'userFilters' => $filters,
            'managerOptions' => $repository->managers(),
            'departmentOptions' => $repository->departments(),
            'statusOptions' => $repository->statuses(),
            'customRoleOptions' => $roleRepository->active(),
            'customRoleAssignments' => $roleRepository->userAssignmentMap(),
            'nextUserCode' => $repository->nextEmployeeCode(),
        ]);
    }

    public function roles(Request $request = null, array $params = []): void
    {
        Auth::requireRole(['admin']);
        $repository = new RoleRepository();

        $this->render('roles/index', 'Role Management', 'admin-roles', [
            'systemRoles' => $repository->systemRoles(),
            'roles' => $repository->all(),
            'activeRoles' => $repository->active(),
            'assignments' => $repository->userAssignments(),
            'permissionGroups' => MenuRegistry::permissionGroups(),
        ]);
    }

    public function workflows(Request $request = null, array $params = []): void
    {
        Auth::requireRole(['admin', 'manager']);
        $taskRepository = new TaskRepository();
        $this->render('workflows/index', 'Workflow Engine', 'workflows', [
            'workflows' => (new WorkflowRepository())->all(),
            'tasks' => $taskRepository->listForUser($this->currentUser()),
            'workflowDepartments' => $taskRepository->workflowDepartments(),
        ]);
    }

    private function navigation(string $role): array
    {
        $items = [
            ['label' => 'Dashboard', 'icon' => 'grid', 'path' => '/dashboard', 'page' => 'dashboard'],
            ['label' => 'My Tasks', 'icon' => 'tasks', 'path' => '/tasks', 'page' => 'tasks'],
            ['label' => 'Attendance', 'icon' => 'calendar', 'path' => '/attendance', 'page' => 'attendance'],
            ['label' => 'Scan Attendance', 'icon' => 'camera', 'path' => '/attendance/scan', 'page' => 'attendance-scan'],
            ['label' => 'Reports', 'icon' => 'reports', 'path' => '#', 'page' => 'reports', 'children' => [
                ['label' => 'Performance Report', 'path' => '/reports/performance', 'page' => 'reports-performance'],
                ['label' => 'Task Report', 'path' => '/reports/tasks', 'page' => 'reports-tasks'],
                ['label' => 'Attendance Report', 'path' => '/reports/attendance', 'page' => 'reports-attendance'],
            ]],
            ['label' => 'Leaderboard', 'icon' => 'trophy', 'path' => '/leaderboard', 'page' => 'leaderboard'],
            ['label' => 'Profile', 'icon' => 'profile', 'path' => '/profile', 'page' => 'profile'],
            ['label' => 'Settings', 'icon' => 'settings', 'path' => '/settings', 'page' => 'settings'],
        ];

        if ($role === 'admin') {
            $items[] = ['label' => 'Manage Users', 'icon' => 'users', 'path' => '/admin/users', 'page' => 'admin-users'];
            $items[] = ['label' => 'Live Attendance', 'icon' => 'pulse', 'path' => '/admin_live_attendance', 'page' => 'admin-live-attendance'];
            $items[] = ['label' => 'Camera Attendance', 'icon' => 'camera', 'path' => '/admin/camera-attendance', 'page' => 'admin-camera-attendance'];
            $items[] = ['label' => 'Workflows', 'icon' => 'flow', 'path' => '/workflows', 'page' => 'workflows'];
        } elseif ($role === 'manager') {
            $items[] = ['label' => 'Workflows', 'icon' => 'flow', 'path' => '/workflows', 'page' => 'workflows'];
        }

        $items[] = ['label' => 'Logout', 'icon' => 'logout', 'path' => '/logout', 'page' => 'logout'];
        return $items;
    }
}
