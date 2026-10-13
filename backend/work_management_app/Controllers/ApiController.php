<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\AttendanceRepository;
use App\Repositories\AwardRepository;
use App\Repositories\DepartmentRepository;
use App\Repositories\SettingsRepository;
use App\Repositories\RoleRepository;
use App\Repositories\TaskRepository;
use App\Repositories\TimeTrackingRepository;
use App\Repositories\UserRepository;
use App\Services\DashboardService;
use App\Services\FaceMatchService;
use App\Services\ProfileImageService;
use App\Services\TimeTrackingService;
use App\Services\WorkflowService;
use RuntimeException;

class ApiController
{
    private function expectsJsonRequest(): bool
    {
        $contentType = (string) ($_SERVER['CONTENT_TYPE'] ?? '');
        $accept = (string) ($_SERVER['HTTP_ACCEPT'] ?? '');

        return strpos($contentType, 'application/json') !== false
            || strpos($accept, 'application/json') !== false;
    }

    private function user(): array
    {
        $user = Auth::user();
        if (!$user) {
            Response::json(['message' => 'Unauthenticated'], 401);
            exit;
        }

        return $user;
    }

    public function dashboard(Request $request = null, array $params = []): void
    {
        Response::json((new DashboardService())->data($this->user()));
    }

    public function tasks(Request $request = null, array $params = []): void
    {
        $repository = new TaskRepository();
        Response::json([
            'data' => $repository->listForUser($this->user()),
            'departments' => $repository->workflowDepartments(),
        ]);
    }

    public function updateTaskStatus(Request $request, array $params): void
    {
        $status = (string) $request->input('status', 'pending');
        (new TaskRepository())->updateStatus((int) $params['id'], $status);
        Response::json(['message' => 'Task status updated.', 'status' => $status]);
    }

    public function taskDetail(Request $request, array $params): void
    {
        try {
            Response::json([
                'task' => (new TaskRepository())->detailForUser((int) $params['id'], $this->user()),
            ]);
        } catch (RuntimeException $exception) {
            Response::json(['message' => $exception->getMessage()], 422);
        }
    }

    public function createTask(Request $request, array $params = []): void
    {
        try {
            $task = (new TaskRepository())->createTask($request->all(), $this->user());
            if ($this->expectsJsonRequest()) {
                Response::json([
                    'message' => 'Workflow task created successfully.',
                    'task' => $task,
                ]);
                return;
            }

            $_SESSION['tasks_success'] = 'Workflow task created successfully.';
            Response::redirect('/tasks');
        } catch (RuntimeException $exception) {
            if ($this->expectsJsonRequest()) {
                Response::json(['message' => $exception->getMessage()], 422);
                return;
            }

            $_SESSION['tasks_error'] = $exception->getMessage();
            Response::redirect('/tasks');
        }
    }

    public function reserveTask(Request $request, array $params): void
    {
        $this->taskAction('Task reserved successfully.', 'reserveTask', (int) $params['id']);
    }

    public function startTask(Request $request, array $params): void
    {
        $this->taskAction('Task started successfully.', 'startTask', (int) $params['id']);
    }

    public function noteTask(Request $request, array $params): void
    {
        try {
            Response::json([
                'message' => 'Task note saved successfully.',
                'task' => (new TaskRepository())->addTaskNote((int) $params['id'], $this->user(), (string) $request->input('note', '')),
            ]);
        } catch (RuntimeException $exception) {
            Response::json(['message' => $exception->getMessage()], 422);
        }
    }

    public function completeTaskStep(Request $request, array $params): void
    {
        try {
            Response::json([
                'message' => 'Workflow step completed successfully.',
                'task' => (new TaskRepository())->completeStep((int) $params['id'], $this->user(), (string) $request->input('note', '')),
            ]);
        } catch (RuntimeException $exception) {
            Response::json(['message' => $exception->getMessage()], 422);
        }
    }

    public function attendance(Request $request = null, array $params = []): void
    {
        $user = $this->user();
        $repository = new AttendanceRepository();
        $filters = [
            'date' => (string) ($request ? $request->query('date', date('Y-m-d')) : date('Y-m-d')),
            'status' => (string) ($request ? $request->query('status', '') : ''),
            'department' => (string) ($request ? $request->query('department', '') : ''),
        ];
        Response::json([
            'data' => $repository->listForUser($user, $filters),
            'metrics' => $repository->metrics($user),
            'overview' => $user['role'] === 'admin' ? $repository->todayOverview($filters) : [],
            'lateMonitoring' => $user['role'] === 'admin' ? $repository->lateMonitoring($filters) : [],
            'absentManagement' => $user['role'] === 'admin' ? $repository->absentManagement($filters) : [],
        ]);
    }

    public function performanceReport(Request $request = null, array $params = []): void
    {
        $payload = (new DashboardService())->data($this->user());
        Response::json(['stats' => $payload['stats'], 'trend' => $payload['performanceTrend'], 'leaderboard' => $payload['leaderboard']]);
    }

    public function taskReport(Request $request = null, array $params = []): void
    {
        $user = $this->user();
        Response::json(['stats' => (new TaskRepository())->metrics($user), 'tasks' => (new TaskRepository())->listForUser($user)]);
    }

    public function attendanceReport(Request $request = null, array $params = []): void
    {
        $user = $this->user();
        $repository = new AttendanceRepository();
        Response::json(['stats' => $repository->metrics($user), 'entries' => $repository->listForUser($user)]);
    }

    public function leaderboard(Request $request, array $params = []): void
    {
        $scope = (string) $request->query('scope', 'monthly');
        Response::json(['data' => (new UserRepository())->forLeaderboard($scope === 'weekly' ? 'weekly' : 'monthly')]);
    }

    public function timeTrackingStatus(Request $request = null, array $params = []): void
    {
        Response::json(['data' => (new TimeTrackingRepository())->summary((int) $this->user()['id'])]);
    }

    public function punchIn(Request $request = null, array $params = []): void
    {
        $this->runTimeTrackingAction('punchIn');
    }

    public function takeBreak(Request $request = null, array $params = []): void
    {
        $this->runTimeTrackingAction('takeBreak');
    }

    public function resume(Request $request = null, array $params = []): void
    {
        $this->runTimeTrackingAction('resume');
    }

    public function punchOut(Request $request = null, array $params = []): void
    {
        $this->runTimeTrackingAction('punchOut');
    }

    public function adminCameraPunch(Request $request, array $params = []): void
    {
        try {
            $payload = $request->all();
            if (($payload['recognition_mode'] ?? '') === 'faceapi-client') {
                Auth::requireRole(['admin']);
                $targetUser = (new UserRepository())->findById((int) ($payload['user_id'] ?? 0));
                if (!$targetUser || !in_array(($targetUser['role'] ?? ''), ['admin', 'manager', 'employee'], true)) {
                    throw new RuntimeException('Select a valid employee for camera attendance.');
                }
                if (empty($targetUser['face_reference_descriptor'])) {
                    throw new RuntimeException('Selected employee does not have a trained face reference descriptor.');
                }

                $match = [
                    'matched' => true,
                    'score' => (int) ($payload['match_score'] ?? 0),
                    'threshold' => 0,
                    'reference_field' => 'face_reference_descriptor',
                ];
            } else {
                [$targetUser, $match] = $this->resolveCameraAttendanceMatch($payload);
            }

            $attendance = (new TimeTrackingService())->punchIn((int) $targetUser['id']);
            Response::json([
                'message' => 'Attendance punched successfully by camera verification.',
                'match' => $match,
                'attendance' => $attendance,
                'employee' => [
                    'id' => (int) $targetUser['id'],
                    'name' => $targetUser['name'],
                    'designation' => $targetUser['designation'] ?? 'Team Member',
                    'department' => $targetUser['department'] ?? '',
                    'reference_source' => $this->cameraReferenceSourceLabel((string) ($match['reference_field'] ?? '')),
                ],
            ]);
        } catch (RuntimeException $exception) {
            Response::json(['message' => $exception->getMessage()], 422);
        }
    }

    public function adminCameraRecognize(Request $request, array $params = []): void
    {
        try {
            [$targetUser, $match] = $this->resolveCameraAttendanceMatch($request->all());
            Response::json([
                'message' => 'Employee recognized from saved face reference.',
                'match' => $match,
                'employee' => [
                    'id' => (int) $targetUser['id'],
                    'name' => $targetUser['name'],
                    'designation' => $targetUser['designation'] ?? 'Team Member',
                    'department' => $targetUser['department'] ?? '',
                    'reference_source' => $this->cameraReferenceSourceLabel((string) ($match['reference_field'] ?? '')),
                ],
            ]);
        } catch (RuntimeException $exception) {
            Response::json(['message' => $exception->getMessage()], 422);
        }
    }

    public function advanceWorkflow(Request $request, array $params): void
    {
        try {
            Response::json((new WorkflowService())->advance((int) $params['taskId'], $this->user()));
        } catch (RuntimeException $exception) {
            Response::json(['message' => $exception->getMessage()], 422);
        }
    }

    public function updateAttendance(Request $request, array $params): void
    {
        try {
            $record = (new AttendanceRepository())->updateByAdmin((int) $params['id'], $request->all(), $this->user());
            $timeTracking = (new TimeTrackingRepository())->summary((int) $record['user_id']);
            Response::json([
                'message' => 'Attendance record updated successfully.',
                'record' => $record,
                'timeTracking' => $timeTracking,
            ]);
        } catch (RuntimeException $exception) {
            Response::json(['message' => $exception->getMessage()], 422);
        }
    }

    public function updateAttendanceRules(Request $request, array $params = []): void
    {
        $user = $this->user();
        if (($user['role'] ?? '') !== 'admin') {
            Response::json(['message' => 'Only admin can update attendance rules.'], 403);
            return;
        }

        $rules = (new SettingsRepository())->updateAttendanceRules($request->all());
        Response::json([
            'message' => 'Attendance rules saved successfully.',
            'rules' => $rules,
        ]);
    }

    public function saveAwardSetting(Request $request, array $params = []): void
    {
        try {
            Auth::requireRole(['admin']);
            $award = (new AwardRepository())->saveAwardSetting($request->all());
            if ($this->expectsJsonRequest()) {
                Response::json([
                    'message' => 'Award settings saved successfully.',
                    'award' => $award,
                ]);
                return;
            }

            $_SESSION['settings_success'] = 'Award settings saved successfully.';
            Response::redirect('/settings');
        } catch (RuntimeException $exception) {
            if ($this->expectsJsonRequest()) {
                Response::json(['message' => $exception->getMessage()], 422);
                return;
            }

            $_SESSION['settings_error'] = $exception->getMessage();
            Response::redirect('/settings');
        }
    }

    public function createUser(Request $request, array $params = []): void
    {
        try {
            Auth::requireRole(['admin']);
            $payload = $this->normalizeUserRolePayload($request->all());
            $user = (new UserRepository())->create($payload);
            $this->syncUserMenuRole((int) $user['id'], $payload, (string) ($user['role'] ?? ''));

            $capturedImage = (string) $request->input('captured_face_reference', '');
            $faceDescriptor = $request->input('face_reference_descriptor', null);
            if (trim($capturedImage) !== '' && $faceDescriptor !== null) {
                $paths = (new ProfileImageService())->saveFaceReferenceForUser(
                    (int) $user['id'],
                    $capturedImage,
                    $user
                );
                $paths['face_reference_descriptor'] = $this->normalizeFaceDescriptor($faceDescriptor);
                $user = (new UserRepository())->updateFaceReferenceImages((int) $user['id'], $paths);
            }

            Response::json(['message' => 'User created successfully.', 'user' => $user]);
        } catch (RuntimeException $exception) {
            Response::json(['message' => $exception->getMessage()], 422);
        }
    }

    public function updateUser(Request $request, array $params): void
    {
        try {
            Auth::requireRole(['admin']);
            $payload = $this->normalizeUserRolePayload($request->all());
            $user = (new UserRepository())->update((int) $params['id'], $payload);
            $this->syncUserMenuRole((int) $user['id'], $payload, (string) ($user['role'] ?? ''));

            $capturedImage = (string) $request->input('captured_face_reference', '');
            $faceDescriptor = $request->input('face_reference_descriptor', null);
            if (trim($capturedImage) !== '' && $faceDescriptor !== null) {
                $paths = (new ProfileImageService())->saveFaceReferenceForUser(
                    (int) $user['id'],
                    $capturedImage,
                    $user
                );
                $paths['face_reference_descriptor'] = $this->normalizeFaceDescriptor($faceDescriptor);
                $user = (new UserRepository())->updateFaceReferenceImages((int) $user['id'], $paths);
            }

            Response::json(['message' => 'User updated successfully.', 'user' => $user]);
        } catch (RuntimeException $exception) {
            Response::json(['message' => $exception->getMessage()], 422);
        }
    }

    public function resetUserPassword(Request $request, array $params): void
    {
        try {
            Auth::requireRole(['admin']);
            $password = (string) $request->input('password', '');
            if ($password === '') {
                $password = 'Temp@1234';
            }
            $user = (new UserRepository())->resetPassword((int) $params['id'], $password, !empty($request->input('force_password_change', 1)));
            Response::json(['message' => 'Password reset successfully.', 'user' => $user, 'temporary_password' => $password]);
        } catch (RuntimeException $exception) {
            Response::json(['message' => $exception->getMessage()], 422);
        }
    }

    public function bulkUsers(Request $request, array $params = []): void
    {
        try {
            Auth::requireRole(['admin']);
            $count = (new UserRepository())->bulkAction((string) $request->input('action', ''), (array) $request->input('user_ids', []), $request->all());
            Response::json(['message' => 'Bulk action completed.', 'affected' => $count]);
        } catch (RuntimeException $exception) {
            Response::json(['message' => $exception->getMessage()], 422);
        }
    }

    public function deleteUser(Request $request, array $params): void
    {
        try {
            Auth::requireRole(['admin']);
            (new UserRepository())->softDelete((int) $params['id']);
            Response::json(['message' => 'User deactivated successfully.']);
        } catch (RuntimeException $exception) {
            Response::json(['message' => $exception->getMessage()], 422);
        }
    }

    public function changePassword(Request $request, array $params = []): void
    {
        try {
            $user = $this->user();
            (new UserRepository())->changePassword(
                (int) $user['id'],
                (string) $request->input('current_password', ''),
                (string) $request->input('new_password', ''),
                (string) $request->input('confirm_password', '')
            );
            Response::json(['message' => 'Password updated successfully.']);
        } catch (RuntimeException $exception) {
            Response::json(['message' => $exception->getMessage()], 422);
        }
    }

    public function uploadProfileImage(Request $request, array $params = []): void
    {
        try {
            $user = $this->user();
            if (empty($_FILES['profile_image'])) {
                throw new RuntimeException('Please choose a profile image to upload.');
            }

            $paths = (new ProfileImageService())->saveForUser(
                (int) $user['id'],
                $_FILES['profile_image'],
                (string) $request->input('cropped_image_data', ''),
                $user
            );
            $updatedUser = (new UserRepository())->updateProfileImages((int) $user['id'], $paths);

            Response::json([
                'message' => 'Profile image updated successfully.',
                'image_path' => $paths['profile_image'],
                'thumbnail_path' => $paths['profile_thumbnail'],
                'user' => $updatedUser,
            ]);
        } catch (RuntimeException $exception) {
            Response::json(['message' => $exception->getMessage()], 422);
        }
    }

    public function uploadFaceReference(Request $request, array $params = []): void
    {
        try {
            $user = $this->user();
            $capturedImage = (string) $request->input('captured_face_reference', '');
            if (trim($capturedImage) === '') {
                throw new RuntimeException('Capture a face photo before saving face reference.');
            }

            $paths = (new ProfileImageService())->saveFaceReferenceForUser(
                (int) $user['id'],
                $capturedImage,
                $user
            );
            $paths['face_reference_descriptor'] = $this->normalizeFaceDescriptor($request->input('face_reference_descriptor', null));
            $updatedUser = (new UserRepository())->updateFaceReferenceImages((int) $user['id'], $paths);

            Response::json([
                'message' => 'Face reference saved successfully.',
                'face_reference_image' => $paths['face_reference_image'],
                'face_reference_thumbnail' => $paths['face_reference_thumbnail'],
                'user' => $updatedUser,
            ]);
        } catch (RuntimeException $exception) {
            Response::json(['message' => $exception->getMessage()], 422);
        }
    }

    public function createDepartment(Request $request, array $params = []): void
    {
        try {
            Auth::requireRole(['admin']);
            $department = (new DepartmentRepository())->create($request->all());
            Response::json(['message' => 'Department created successfully.', 'department' => $department]);
        } catch (RuntimeException $exception) {
            Response::json(['message' => $exception->getMessage()], 422);
        }
    }

    public function updateDepartment(Request $request, array $params): void
    {
        try {
            Auth::requireRole(['admin']);
            $department = (new DepartmentRepository())->update((int) $params['id'], $request->all());
            Response::json(['message' => 'Department updated successfully.', 'department' => $department]);
        } catch (RuntimeException $exception) {
            Response::json(['message' => $exception->getMessage()], 422);
        }
    }

    public function deleteDepartment(Request $request, array $params): void
    {
        try {
            Auth::requireRole(['admin']);
            (new DepartmentRepository())->delete((int) $params['id']);
            Response::json(['message' => 'Department deleted successfully.']);
        } catch (RuntimeException $exception) {
            Response::json(['message' => $exception->getMessage()], 422);
        }
    }

    public function saveRole(Request $request, array $params = []): void
    {
        try {
            Auth::requireRole(['admin']);
            $role = (new RoleRepository())->save($request->all());
            Response::json(['message' => 'Role saved successfully.', 'role' => $role]);
        } catch (RuntimeException $exception) {
            Response::json(['message' => $exception->getMessage()], 422);
        }
    }

    public function saveSystemRole(Request $request, array $params = []): void
    {
        try {
            Auth::requireRole(['admin']);
            $role = (new RoleRepository())->saveSystemRole($request->all());
            Response::json(['message' => 'System role saved successfully.', 'role' => $role]);
        } catch (RuntimeException $exception) {
            Response::json(['message' => $exception->getMessage()], 422);
        }
    }

    public function deleteRole(Request $request, array $params): void
    {
        try {
            Auth::requireRole(['admin']);
            (new RoleRepository())->delete((int) $params['id']);
            Response::json(['message' => 'Role deleted successfully.']);
        } catch (RuntimeException $exception) {
            Response::json(['message' => $exception->getMessage()], 422);
        }
    }

    public function assignRole(Request $request, array $params = []): void
    {
        try {
            Auth::requireRole(['admin']);
            (new RoleRepository())->assignToUser(
                (int) $request->input('user_id', 0),
                (int) $request->input('role_id', 0)
            );
            Response::json(['message' => 'Employee role updated successfully.']);
        } catch (RuntimeException $exception) {
            Response::json(['message' => $exception->getMessage()], 422);
        }
    }

    private function runTimeTrackingAction(string $method): void
    {
        try {
            $service = new TimeTrackingService();
            Response::json($service->{$method}((int) $this->user()['id']));
        } catch (RuntimeException $exception) {
            Response::json(['message' => $exception->getMessage()], 422);
        }
    }

    private function taskAction(string $message, string $method, int $taskId): void
    {
        try {
            Response::json([
                'message' => $message,
                'task' => (new TaskRepository())->{$method}($taskId, $this->user()),
            ]);
        } catch (RuntimeException $exception) {
            Response::json(['message' => $exception->getMessage()], 422);
        }
    }

    private function resolveCameraAttendanceMatch(array $payload): array
    {
        Auth::requireRole(['admin']);

        $userRepository = new UserRepository();
        $faceMatchService = new FaceMatchService();
        $targetUserId = (int) ($payload['user_id'] ?? 0);
        $capturedImage = (string) ($payload['captured_image'] ?? '');

        if ($targetUserId > 0) {
            $targetUser = $userRepository->findById($targetUserId);
            if (!$targetUser || !in_array(($targetUser['role'] ?? ''), ['admin', 'manager', 'employee'], true)) {
                throw new RuntimeException('Select a valid employee for camera attendance.');
            }

            $match = $faceMatchService->compareDataUrlToProfile($capturedImage, $targetUser);
        } else {
            $match = $faceMatchService->compareDataUrlToCandidates($capturedImage, $userRepository->attendanceCandidates());
            $targetUser = $match['user'];
        }

        if (empty($match['matched'])) {
            $bestName = !empty($targetUser['name']) ? $targetUser['name'] : 'No employee';
            throw new RuntimeException('Face did not match the saved face reference strongly enough. Best match: ' . $bestName . ' (' . (int) ($match['score'] ?? 0) . '%).');
        }

        return [$targetUser, $match];
    }

    private function cameraReferenceSourceLabel(string $field): string
    {
        if (strpos($field, 'face_reference') === 0) {
            return 'Face Reference';
        }

        return 'Saved Photo';
    }

    private function normalizeFaceDescriptor($descriptor): ?string
    {
        if ($descriptor === null || $descriptor === '') {
            return null;
        }

        if (is_string($descriptor)) {
            $decoded = json_decode($descriptor, true);
        } else {
            $decoded = $descriptor;
        }

        if (!is_array($decoded)) {
            throw new RuntimeException('Face descriptor is invalid. Please capture a clear face reference again.');
        }

        // Single descriptor (128 floats)
        if (count($decoded) === 128 && !is_array(reset($decoded))) {
            $normalized = array_map(static function ($value) {
                return round((float) $value, 10);
            }, array_values($decoded));

            return json_encode([$normalized], JSON_UNESCAPED_SLASHES);
        }

        // Array of descriptors (multi-angle / multi-sample: [[128 floats], ...])
        if (!empty($decoded) && is_array(reset($decoded))) {
            $allNormalized = [];
            foreach ($decoded as $sample) {
                if (is_array($sample) && count($sample) === 128) {
                    $allNormalized[] = array_map(static function ($value) {
                        return round((float) $value, 10);
                    }, array_values($sample));
                }
            }

            if (!empty($allNormalized)) {
                return json_encode($allNormalized, JSON_UNESCAPED_SLASHES);
            }
        }

        throw new RuntimeException('Face descriptor is invalid. Please capture a clear face reference again.');
    }

    private function syncUserMenuRole(int $userId, array $payload, string $systemRole): void
    {
        if (!array_key_exists('custom_role_id', $payload)) {
            return;
        }

        $customRoleId = (int) ($payload['custom_role_id'] ?? 0);
        if ($systemRole === 'admin') {
            $customRoleId = 0;
        }

        (new RoleRepository())->assignToUser($userId, $customRoleId);
    }

    private function normalizeUserRolePayload(array $payload): array
    {
        $selectedRole = (string) ($payload['menu_role_choice'] ?? '');

        if (strpos($selectedRole, 'system:') === 0) {
            $systemRole = substr($selectedRole, 7);
            if (in_array($systemRole, ['admin', 'manager', 'employee'], true)) {
                $payload['role'] = $systemRole;
                $payload['custom_role_id'] = '';
            }

            return $payload;
        }

        if (strpos($selectedRole, 'custom:') === 0) {
            $payload['custom_role_id'] = (int) substr($selectedRole, 7);
            if (empty($payload['role']) || !in_array((string) $payload['role'], ['manager', 'employee'], true)) {
                $payload['role'] = 'employee';
            }
        }

        return $payload;
    }

    public function scanCandidates(Request $request, array $params = []): void
    {
        try {
            $this->user();
            $candidates = (new UserRepository())->attendanceCandidates();
            $formatted = [];
            foreach ($candidates as $c) {
                if (!empty($c['face_reference_descriptor'])) {
                    $desc = json_decode((string) $c['face_reference_descriptor'], true);
                    if (is_array($desc)) {
                        $descriptors = [];
                        if (count($desc) === 128 && !is_array(reset($desc))) {
                            $descriptors[] = $desc;
                        } elseif (is_array(reset($desc))) {
                            foreach ($desc as $single) {
                                if (is_array($single) && count($single) === 128) {
                                    $descriptors[] = $single;
                                }
                            }
                        }

                        if (!empty($descriptors)) {
                            $photoUrl = '';
                            if (!empty($c['face_reference_image'])) {
                                $photoUrl = $c['face_reference_image'];
                            } elseif (!empty($c['profile_image'])) {
                                $photoUrl = $c['profile_image'];
                            } elseif (!empty($c['profile_image_path'])) {
                                $photoUrl = $c['profile_image_path'];
                            }

                            $formatted[] = [
                                'employee_id' => (int) $c['id'],
                                'employee_code' => $c['employee_code'] ?? ('EMP-' . str_pad((string) $c['id'], 4, '0', STR_PAD_LEFT)),
                                'name' => $c['name'],
                                'department' => $c['department'] ?? '',
                                'designation' => $c['designation'] ?: 'Team Member',
                                'photo' => $photoUrl,
                                'descriptors' => $descriptors,
                                'face_descriptor' => $descriptors[0],
                            ];
                        }
                    }
                }
            }
            Response::json([
                'success' => true,
                'employees' => $formatted
            ]);
        } catch (\Throwable $exception) {
            Response::json([
                'success' => false,
                'message' => $exception->getMessage()
            ], 422);
        }
    }

    public function scanEmployeeStatus(Request $request, array $params): void
    {
        try {
            $this->user();
            $userId = (int) ($params['id'] ?? 0);
            if ($userId <= 0) {
                throw new RuntimeException('Invalid employee ID.');
            }

            $userRepo = new UserRepository();
            $targetUser = $userRepo->findById($userId);
            if (!$targetUser) {
                throw new RuntimeException('Employee not found.');
            }

            $timeTrackingRepo = new TimeTrackingRepository();
            $summary = $timeTrackingRepo->summary($userId);

            $photoUrl = '';
            if (!empty($targetUser['face_reference_image'])) {
                $photoUrl = $targetUser['face_reference_image'];
            } elseif (!empty($targetUser['profile_image'])) {
                $photoUrl = $targetUser['profile_image'];
            } elseif (!empty($targetUser['profile_image_path'])) {
                $photoUrl = $targetUser['profile_image_path'];
            }

            Response::json([
                'success' => true,
                'employee_id' => $userId,
                'employee_code' => $targetUser['employee_code'] ?? ('EMP-' . str_pad((string) $userId, 4, '0', STR_PAD_LEFT)),
                'name' => $targetUser['name'],
                'department' => $targetUser['department'] ?: '',
                'designation' => $targetUser['designation'] ?: 'Team Member',
                'photo' => $photoUrl,
                'summary' => $summary,
            ]);
        } catch (\Throwable $exception) {
            Response::json([
                'success' => false,
                'message' => $exception->getMessage()
            ], 422);
        }
    }

    public function scanPunch(Request $request, array $params = []): void
    {
        try {
            $this->user();
            $payload = $request->all();
            $userId = (int) ($payload['employee_id'] ?? 0);
            if ($userId <= 0) {
                throw new RuntimeException('Invalid employee ID.');
            }

            $action = trim((string) ($payload['action'] ?? 'punchIn'));

            $userRepo = new UserRepository();
            $targetUser = $userRepo->findById($userId);
            if (!$targetUser || !in_array(($targetUser['role'] ?? ''), ['admin', 'manager', 'employee'], true)) {
                throw new RuntimeException('Employee not found or invalid role.');
            }

            $timeTrackingService = new TimeTrackingService();
            $timeTrackingRepo = new TimeTrackingRepository();

            $actionNorm = strtolower(str_replace(['_', '-'], '', $action));
            $resultMessage = '';
            $statusLabel = 'Recorded';

            switch ($actionNorm) {
                case 'punchin':
                case 'clockin':
                case 'in':
                    $existingAttendance = $timeTrackingRepo->todayAttendance($userId);
                    if ($existingAttendance && !empty($existingAttendance['clock_in'])) {
                        $clockInTime = date('h:i A', strtotime($existingAttendance['clock_in']));
                        $summary = $timeTrackingRepo->summary($userId);
                        Response::json([
                            'success' => true,
                            'already_marked' => true,
                            'action' => 'punchIn',
                            'employee_id' => $userId,
                            'employee_name' => $targetUser['name'],
                            'designation' => $targetUser['designation'] ?: 'Team Member',
                            'department' => $targetUser['department'] ?: '',
                            'status' => $summary['status'],
                            'summary' => $summary,
                            'time' => $clockInTime,
                            'message' => "Already punched in today at {$clockInTime}."
                        ]);
                        return;
                    }
                    $timeTrackingService->punchIn($userId);
                    $resultMessage = 'Punch In recorded successfully!';
                    $statusLabel = 'Punched In';
                    break;

                case 'takebreak':
                case 'startbreak':
                case 'break':
                    $timeTrackingService->takeBreak($userId);
                    $resultMessage = 'Break started successfully.';
                    $statusLabel = 'On Break';
                    break;

                case 'resume':
                case 'resumework':
                case 'endbreak':
                    $timeTrackingService->resume($userId);
                    $resultMessage = 'Work resumed successfully.';
                    $statusLabel = 'Resumed Work';
                    break;

                case 'punchout':
                case 'clockout':
                case 'out':
                    $timeTrackingService->punchOut($userId);
                    $resultMessage = 'Shift completed. Punch Out recorded successfully!';
                    $statusLabel = 'Punched Out';
                    break;

                default:
                    throw new RuntimeException("Unknown attendance action: {$action}");
            }

            $summary = $timeTrackingRepo->summary($userId);
            $currentTime = date('h:i A');

            Response::json([
                'success' => true,
                'already_marked' => false,
                'action' => $action,
                'status_label' => $statusLabel,
                'employee_id' => $userId,
                'employee_name' => $targetUser['name'],
                'designation' => $targetUser['designation'] ?: 'Team Member',
                'department' => $targetUser['department'] ?: '',
                'time' => $currentTime,
                'status' => $summary['status'],
                'summary' => $summary,
                'message' => $resultMessage . " ({$currentTime})"
            ]);
        } catch (\Throwable $exception) {
            Response::json([
                'success' => false,
                'message' => $exception->getMessage()
            ], 422);
        }
    }
}
