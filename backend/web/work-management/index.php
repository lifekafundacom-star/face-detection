<?php

declare(strict_types=1);

session_name('advanced-backend');
session_start();

$config = require __DIR__ . '/../../work_management_app/Config/config.php';
date_default_timezone_set($config['app']['timezone']);

require __DIR__ . '/../../work_management_app/Core/Autoload.php';

use App\Controllers\ApiController;
use App\Controllers\AuthController;
use App\Controllers\PageController;
use App\Core\Request;
use App\Core\Router;

$router = new Router();
$request = new Request();

$router->get('/', [AuthController::class, 'showLogin']);
$router->post('/login', [AuthController::class, 'login']);
$router->get('/logout', [AuthController::class, 'logout']);
$router->get('/dashboard', [PageController::class, 'dashboard']);
$router->get('/tasks', [PageController::class, 'tasks']);
$router->get('/attendance', [PageController::class, 'attendance']);
$router->get('/attendance/scan', [PageController::class, 'scanAttendance']);
$router->get('/attendance/employee/{id}', [PageController::class, 'attendanceEmployeeDetail']);
$router->get('/reports/performance', [PageController::class, 'performanceReport']);
$router->get('/reports/tasks', [PageController::class, 'taskReport']);
$router->get('/reports/attendance', [PageController::class, 'attendanceReport']);
$router->get('/employee-performance', [PageController::class, 'employeePerformance']);
$router->get('/leaderboard', [PageController::class, 'leaderboard']);
$router->get('/profile', [PageController::class, 'profile']);
$router->get('/settings', [PageController::class, 'settings']);
$router->get('/admin/users', [PageController::class, 'users']);
$router->get('/admin/roles', [PageController::class, 'roles']);
$router->get('/admin_live_attendance', [PageController::class, 'adminLiveAttendance']);
$router->get('/admin/camera-attendance', [PageController::class, 'adminCameraAttendance']);
$router->get('/workflows', [PageController::class, 'workflows']);

$router->get('/api/dashboard', [ApiController::class, 'dashboard']);
$router->get('/api/tasks', [ApiController::class, 'tasks']);
$router->get('/api/tasks/{id}', [ApiController::class, 'taskDetail']);
$router->post('/api/tasks/create', [ApiController::class, 'createTask']);
$router->post('/api/tasks/{id}/status', [ApiController::class, 'updateTaskStatus']);
$router->post('/api/tasks/{id}/reserve', [ApiController::class, 'reserveTask']);
$router->post('/api/tasks/{id}/start', [ApiController::class, 'startTask']);
$router->post('/api/tasks/{id}/note', [ApiController::class, 'noteTask']);
$router->post('/api/tasks/{id}/complete-step', [ApiController::class, 'completeTaskStep']);
$router->post('/api/admin/camera-attendance/recognize', [ApiController::class, 'adminCameraRecognize']);
$router->post('/api/admin/camera-attendance/punch', [ApiController::class, 'adminCameraPunch']);
$router->get('/api/attendance', [ApiController::class, 'attendance']);
$router->get('/api/attendance/scan-candidates', [ApiController::class, 'scanCandidates']);
$router->get('/api/attendance/scan-status/{id}', [ApiController::class, 'scanEmployeeStatus']);
$router->post('/api/attendance/scan-punch', [ApiController::class, 'scanPunch']);
$router->post('/api/attendance/{id}/update', [ApiController::class, 'updateAttendance']);
$router->post('/api/profile/change-password', [ApiController::class, 'changePassword']);
$router->post('/api/profile/image', [ApiController::class, 'uploadProfileImage']);
$router->post('/api/profile/face-reference', [ApiController::class, 'uploadFaceReference']);
$router->post('/api/users', [ApiController::class, 'createUser']);
$router->post('/api/users/{id}/update', [ApiController::class, 'updateUser']);
$router->post('/api/users/{id}/reset-password', [ApiController::class, 'resetUserPassword']);
$router->post('/api/users/{id}/delete', [ApiController::class, 'deleteUser']);
$router->post('/api/users/bulk', [ApiController::class, 'bulkUsers']);
$router->post('/api/roles', [ApiController::class, 'saveRole']);
$router->post('/api/system-roles', [ApiController::class, 'saveSystemRole']);
$router->post('/api/roles/{id}/delete', [ApiController::class, 'deleteRole']);
$router->post('/api/roles/assign', [ApiController::class, 'assignRole']);
$router->post('/api/departments', [ApiController::class, 'createDepartment']);
$router->post('/api/departments/{id}/update', [ApiController::class, 'updateDepartment']);
$router->post('/api/departments/{id}/delete', [ApiController::class, 'deleteDepartment']);
$router->post('/api/settings/attendance-rules', [ApiController::class, 'updateAttendanceRules']);
$router->post('/api/settings/employee-of-year', [ApiController::class, 'saveAwardSetting']);
$router->post('/api/settings/awards', [ApiController::class, 'saveAwardSetting']);
$router->get('/api/reports/performance', [ApiController::class, 'performanceReport']);
$router->get('/api/reports/tasks', [ApiController::class, 'taskReport']);
$router->get('/api/reports/attendance', [ApiController::class, 'attendanceReport']);
$router->get('/api/leaderboard', [ApiController::class, 'leaderboard']);
$router->get('/api/time-tracking', [ApiController::class, 'timeTrackingStatus']);
$router->post('/api/time-tracking/punch-in', [ApiController::class, 'punchIn']);
$router->post('/api/time-tracking/break', [ApiController::class, 'takeBreak']);
$router->post('/api/time-tracking/resume', [ApiController::class, 'resume']);
$router->post('/api/time-tracking/punch-out', [ApiController::class, 'punchOut']);
$router->post('/api/workflows/{taskId}/advance', [ApiController::class, 'advanceWorkflow']);

$router->dispatch($request);
