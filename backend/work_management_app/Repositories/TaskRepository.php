<?php

namespace App\Repositories;

use RuntimeException;

class TaskRepository extends BaseRepository
{
    public function listForUser(array $user): array
    {
        $departmentId = $this->userDepartmentId($user);
        $tasks = $this->fetchTasks();
        $logsByTask = $this->logsByTaskIds(array_column($tasks, 'id'));

        $visible = [];
        foreach ($tasks as $task) {
            $task = $this->decorateTask($task, $user, $departmentId, $logsByTask[(int) $task['id']] ?? []);
            if ($task['board_status'] === null) {
                continue;
            }
            $visible[] = $task;
        }

        usort($visible, static function (array $left, array $right): int {
            return strcmp((string) ($left['deadline'] ?? ''), (string) ($right['deadline'] ?? ''));
        });

        return $visible;
    }

    public function metrics(array $user): array
    {
        $tasks = $this->listForUser($user);

        $metrics = [
            'total_tasks' => count($tasks),
            'completed_tasks' => 0,
            'pending_tasks' => 0,
            'overdue_tasks' => 0,
            'total_points' => 0,
        ];

        foreach ($tasks as $task) {
            if (($task['board_status'] ?? '') === 'completed') {
                $metrics['completed_tasks']++;
                $metrics['total_points'] += (int) ($task['points'] ?? 0);
            } elseif (in_array((string) ($task['board_status'] ?? ''), ['pending', 'in_progress', 'review'], true)) {
                $metrics['pending_tasks']++;
            }

            if (!empty($task['deadline']) && $task['deadline'] < date('Y-m-d') && ($task['board_status'] ?? '') !== 'completed') {
                $metrics['overdue_tasks']++;
            }
        }

        return $metrics;
    }

    public function workflowDepartments(): array
    {
        return $this->db->query("
            SELECT id, name
            FROM departments
            WHERE status = 'active'
              AND deleted_at IS NULL
            ORDER BY name ASC
        ")->fetchAll();
    }

    public function createTask(array $payload, array $actor): array
    {
        if (!in_array(($actor['role'] ?? ''), ['admin', 'manager'], true)) {
            throw new RuntimeException('Only admin or manager can create workflow tasks.');
        }

        $title = trim((string) ($payload['title'] ?? ''));
        $clientName = trim((string) ($payload['client_name'] ?? ''));
        $taskType = trim((string) ($payload['task_type'] ?? ''));
        $taskDate = trim((string) ($payload['task_date'] ?? ''));
        $taskTime = trim((string) ($payload['task_time'] ?? ''));
        $taskLocation = trim((string) ($payload['task_location'] ?? ''));
        $notes = trim((string) ($payload['notes'] ?? ''));
        $priority = trim((string) ($payload['priority'] ?? 'medium'));
        $deadline = trim((string) ($payload['deadline'] ?? ''));
        $workflowDepartments = $payload['workflow_departments'] ?? [];

        if ($title === '') {
            throw new RuntimeException('Task title is required.');
        }

        if ($deadline === '') {
            throw new RuntimeException('Deadline is required.');
        }

        if (empty($workflowDepartments)) {
            throw new RuntimeException('Select at least one workflow department.');
        }

        $steps = $this->buildWorkflowSteps($workflowDepartments);
        $currentDepartmentId = (int) $steps[0]['department_id'];
        $totalPoints = array_sum(array_map(static function (array $step): int {
            return (int) ($step['points'] ?? 0);
        }, $steps));

        $stmt = $this->db->prepare("
            INSERT INTO tasks (
                workflow_id,
                current_stage_id,
                created_by,
                assigned_to,
                assigned_user_id,
                title,
                description,
                client_name,
                task_type,
                task_date,
                task_time,
                task_location,
                notes,
                priority,
                status,
                deadline,
                points,
                workflow_json,
                current_step_index,
                current_department_id
            ) VALUES (
                NULL,
                NULL,
                :created_by,
                NULL,
                NULL,
                :title,
                :description,
                :client_name,
                :task_type,
                :task_date,
                :task_time,
                :task_location,
                :notes,
                :priority,
                'pending',
                :deadline,
                :points,
                :workflow_json,
                0,
                :current_department_id
            )
        ");
        $stmt->execute([
            'created_by' => (int) $actor['id'],
            'title' => $title,
            'description' => $notes !== '' ? $notes : null,
            'client_name' => $clientName !== '' ? $clientName : null,
            'task_type' => $taskType !== '' ? $taskType : null,
            'task_date' => $taskDate !== '' ? $taskDate : null,
            'task_time' => $taskTime !== '' ? $taskTime : null,
            'task_location' => $taskLocation !== '' ? $taskLocation : null,
            'notes' => $notes !== '' ? $notes : null,
            'priority' => $priority !== '' ? $priority : 'medium',
            'deadline' => $deadline,
            'points' => $totalPoints,
            'workflow_json' => json_encode($steps),
            'current_department_id' => $currentDepartmentId,
        ]);

        return $this->detailForUser((int) $this->db->lastInsertId(), $actor);
    }

    public function detailForUser(int $taskId, array $user): array
    {
        $task = $this->find($taskId);
        if (!$task) {
            throw new RuntimeException('Task not found.');
        }

        $departmentId = $this->userDepartmentId($user);
        $logs = $this->logsByTaskIds([$taskId]);
        $decorated = $this->decorateTask($task, $user, $departmentId, $logs[$taskId] ?? []);

        if ($decorated['board_status'] === null && !in_array(($user['role'] ?? ''), ['admin', 'manager'], true)) {
            throw new RuntimeException('You do not have access to this task.');
        }

        return $decorated;
    }

    public function reserveTask(int $taskId, array $user): array
    {
        $task = $this->find($taskId);
        if (!$task) {
            throw new RuntimeException('Task not found.');
        }

        $this->assertCanWorkCurrentDepartment($task, $user);

        if ((int) ($task['assigned_user_id'] ?? 0) > 0 || (int) ($task['assigned_to'] ?? 0) > 0) {
            throw new RuntimeException('This task is already reserved for the current step.');
        }

        $stmt = $this->db->prepare("
            UPDATE tasks
            SET assigned_to = :user_id,
                assigned_user_id = :user_id,
                status = 'in_progress',
                updated_at = CURRENT_TIMESTAMP
            WHERE id = :id
        ");
        $stmt->execute([
            'user_id' => (int) $user['id'],
            'id' => $taskId,
        ]);

        $this->createLog($taskId, (int) ($task['current_department_id'] ?? 0), (int) $user['id'], 'reserved', '');

        return $this->detailForUser($taskId, $user);
    }

    public function startTask(int $taskId, array $user): array
    {
        $task = $this->find($taskId);
        if (!$task) {
            throw new RuntimeException('Task not found.');
        }

        $this->assertAssignedUser($task, $user);

        $stmt = $this->db->prepare("
            UPDATE tasks
            SET status = 'in_progress',
                started_at = COALESCE(started_at, NOW()),
                updated_at = CURRENT_TIMESTAMP
            WHERE id = :id
        ");
        $stmt->execute(['id' => $taskId]);

        $this->createLog($taskId, (int) ($task['current_department_id'] ?? 0), (int) $user['id'], 'started', '');

        return $this->detailForUser($taskId, $user);
    }

    public function addTaskNote(int $taskId, array $user, string $note): array
    {
        $task = $this->find($taskId);
        if (!$task) {
            throw new RuntimeException('Task not found.');
        }

        $this->assertAssignedUser($task, $user);

        $note = trim($note);
        if ($note === '') {
            throw new RuntimeException('Note is required.');
        }

        $stmt = $this->db->prepare("
            UPDATE tasks
            SET notes = :notes,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = :id
        ");
        $stmt->execute([
            'notes' => $note,
            'id' => $taskId,
        ]);

        $this->createLog($taskId, (int) ($task['current_department_id'] ?? 0), (int) $user['id'], 'noted', $note);

        return $this->detailForUser($taskId, $user);
    }

    public function completeStep(int $taskId, array $user, string $note = ''): array
    {
        $task = $this->find($taskId);
        if (!$task) {
            throw new RuntimeException('Task not found.');
        }

        if (($user['role'] ?? '') !== 'admin') {
            $this->assertAssignedUser($task, $user);
        }

        $steps = $this->decodeWorkflowSteps($task['workflow_json'] ?? '[]');
        $currentIndex = (int) ($task['current_step_index'] ?? 0);
        $currentDepartmentId = (int) ($task['current_department_id'] ?? 0);

        $this->createLog($taskId, $currentDepartmentId, (int) $user['id'], 'completed', trim($note));

        $nextIndex = $currentIndex + 1;
        $isFinal = !isset($steps[$nextIndex]);

        if ($isFinal) {
            $stmt = $this->db->prepare("
                UPDATE tasks
                SET status = 'completed',
                    assigned_to = NULL,
                    assigned_user_id = NULL,
                    completed_at = NOW(),
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = :id
            ");
            $stmt->execute(['id' => $taskId]);
        } else {
            $nextDepartmentId = (int) ($steps[$nextIndex]['department_id'] ?? 0);
            $stmt = $this->db->prepare("
                UPDATE tasks
                SET status = 'pending',
                    current_step_index = :current_step_index,
                    current_department_id = :current_department_id,
                    assigned_to = NULL,
                    assigned_user_id = NULL,
                    started_at = NULL,
                    notes = :notes,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = :id
            ");
            $stmt->execute([
                'current_step_index' => $nextIndex,
                'current_department_id' => $nextDepartmentId,
                'notes' => trim($note) !== '' ? trim($note) : ($task['notes'] ?? null),
                'id' => $taskId,
            ]);
        }

        return $this->detailForUser($taskId, $user);
    }

    public function find(int $taskId): ?array
    {
        $stmt = $this->db->prepare("
            SELECT
                t.*,
                creator.name AS created_by_name,
                assignee.name AS assigned_name,
                current_department.name AS current_department_name
            FROM tasks t
            LEFT JOIN users creator ON creator.id = t.created_by
            LEFT JOIN users assignee ON assignee.id = COALESCE(t.assigned_user_id, t.assigned_to)
            LEFT JOIN departments current_department ON current_department.id = t.current_department_id
            WHERE t.id = :id
            LIMIT 1
        ");
        $stmt->execute(['id' => $taskId]);

        return $stmt->fetch() ?: null;
    }

    public function updateStatus(int $taskId, string $status): void
    {
        $stmt = $this->db->prepare("
            UPDATE tasks
            SET status = :status,
                completed_at = CASE WHEN :status = 'completed' THEN NOW() ELSE completed_at END
            WHERE id = :id
        ");
        $stmt->execute(['status' => $status, 'id' => $taskId]);
    }

    public function advanceStage(int $taskId, int $nextStageId, ?int $assignedTo, string $status): void
    {
        $stmt = $this->db->prepare("
            UPDATE tasks
            SET current_stage_id = :current_stage_id,
                assigned_to = :assigned_to,
                assigned_user_id = :assigned_to,
                status = :status,
                completed_at = CASE WHEN :status = 'completed' THEN NOW() ELSE completed_at END
            WHERE id = :id
        ");
        $stmt->bindValue(':current_stage_id', $nextStageId);
        $stmt->bindValue(':assigned_to', $assignedTo);
        $stmt->bindValue(':status', $status);
        $stmt->bindValue(':id', $taskId);
        $stmt->execute();
    }

    private function fetchTasks(): array
    {
        return $this->db->query("
            SELECT
                t.*,
                creator.name AS created_by_name,
                assignee.name AS assigned_name,
                current_department.name AS current_department_name
            FROM tasks t
            LEFT JOIN users creator ON creator.id = t.created_by
            LEFT JOIN users assignee ON assignee.id = COALESCE(t.assigned_user_id, t.assigned_to)
            LEFT JOIN departments current_department ON current_department.id = t.current_department_id
            ORDER BY t.updated_at DESC, t.deadline ASC
        ")->fetchAll();
    }

    private function logsByTaskIds(array $taskIds): array
    {
        $taskIds = array_values(array_filter(array_map('intval', $taskIds)));
        if (empty($taskIds)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($taskIds), '?'));
        $stmt = $this->db->prepare("
            SELECT
                l.*,
                u.name AS user_name,
                d.name AS department_name
            FROM task_logs l
            LEFT JOIN users u ON u.id = l.user_id
            LEFT JOIN departments d ON d.id = l.department_id
            WHERE l.task_id IN ({$placeholders})
            ORDER BY l.created_at ASC, l.id ASC
        ");
        $stmt->execute($taskIds);

        $grouped = [];
        foreach ($stmt->fetchAll() as $log) {
            $grouped[(int) $log['task_id']][] = $log;
        }

        return $grouped;
    }

    private function decorateTask(array $task, array $user, ?int $departmentId, array $logs): array
    {
        $steps = $this->decodeWorkflowSteps($task['workflow_json'] ?? '[]');
        $task['workflow_steps'] = $steps;
        $task['current_step_index'] = (int) ($task['current_step_index'] ?? 0);
        $task['current_department_id'] = (int) ($task['current_department_id'] ?? 0);
        $task['assigned_user_id'] = (int) (($task['assigned_user_id'] ?? $task['assigned_to'] ?? 0));
        $task['priority'] = $task['priority'] ?? 'medium';
        $task['logs'] = $logs;
        $task['timeline'] = $this->timelineFromLogs($steps, $logs);
        $task['last_action'] = !empty($logs) ? (string) ($logs[count($logs) - 1]['action'] ?? '') : '';
        $task['last_completed_department_id'] = $this->lastCompletedDepartmentId($logs);
        $task['current_step_name'] = $steps[$task['current_step_index']]['department_name'] ?? ($task['current_department_name'] ?? 'Department');
        $task['current_step_points'] = (int) ($steps[$task['current_step_index']]['points'] ?? 0);
        $task['board_status'] = $this->boardStatusForUser($task, $user, $departmentId);
        $task['can_reserve'] = $task['board_status'] === 'pending' && (($user['role'] ?? '') === 'admin' || empty($task['assigned_user_id']) || $task['assigned_user_id'] === 0);
        $task['can_work'] = $task['board_status'] === 'in_progress' && (int) ($task['assigned_user_id'] ?? 0) === (int) ($user['id'] ?? 0);
        $task['is_completed'] = $task['board_status'] === 'completed';

        return $task;
    }

    private function boardStatusForUser(array $task, array $user, ?int $departmentId): ?string
    {
        $role = (string) ($user['role'] ?? 'employee');
        $taskStatus = (string) ($task['status'] ?? 'pending');
        $assignedUserId = (int) ($task['assigned_user_id'] ?? 0);
        $currentDepartmentId = (int) ($task['current_department_id'] ?? 0);
        $userId = (int) ($user['id'] ?? 0);
        $userInWorkflow = $this->workflowContainsDepartment($task['workflow_steps'] ?? [], $departmentId);

        if (in_array($role, ['admin', 'manager'], true)) {
            if ($taskStatus === 'completed') {
                return 'completed';
            }
            if ($task['last_action'] === 'completed' && $assignedUserId === 0) {
                return 'review';
            }
            if ($assignedUserId === 0) {
                return 'pending';
            }
            return 'in_progress';
        }

        if ($taskStatus === 'completed') {
            return $userInWorkflow ? 'completed' : null;
        }

        if ($currentDepartmentId === (int) $departmentId) {
            if ($assignedUserId === 0) {
                return 'pending';
            }

            if ($assignedUserId === $userId) {
                return 'in_progress';
            }

            return null;
        }

        if ((int) ($task['last_completed_department_id'] ?? 0) === (int) $departmentId) {
            return 'review';
        }

        return null;
    }

    private function buildWorkflowSteps(array $workflowDepartments): array
    {
        $normalizedSteps = [];
        foreach ((array) $workflowDepartments as $entry) {
            if (is_array($entry)) {
                $departmentId = (int) ($entry['department_id'] ?? 0);
                $points = max(0, (int) ($entry['points'] ?? 0));
            } else {
                $departmentId = (int) $entry;
                $points = 0;
            }

            if ($departmentId > 0) {
                $normalizedSteps[] = [
                    'department_id' => $departmentId,
                    'points' => $points,
                ];
            }
        }

        $departmentIds = array_values(array_unique(array_map(static function (array $step): int {
            return (int) $step['department_id'];
        }, $normalizedSteps)));
        if (empty($departmentIds)) {
            throw new RuntimeException('Workflow departments are required.');
        }

        $placeholders = implode(',', array_fill(0, count($departmentIds), '?'));
        $stmt = $this->db->prepare("
            SELECT id, name
            FROM departments
            WHERE id IN ({$placeholders})
              AND status = 'active'
              AND deleted_at IS NULL
        ");
        $stmt->execute($departmentIds);

        $rows = [];
        foreach ($stmt->fetchAll() as $department) {
            $rows[(int) $department['id']] = $department['name'];
        }

        $steps = [];
        foreach ($normalizedSteps as $index => $step) {
            $departmentId = (int) $step['department_id'];
            if (!isset($rows[$departmentId])) {
                continue;
            }
            $steps[] = [
                'department_id' => $departmentId,
                'department_name' => $rows[$departmentId],
                'order' => $index,
                'points' => max(0, (int) ($step['points'] ?? 0)),
            ];
        }

        if (empty($steps)) {
            throw new RuntimeException('No valid workflow departments found.');
        }

        return $steps;
    }

    private function decodeWorkflowSteps(string $workflowJson): array
    {
        $steps = json_decode($workflowJson, true);
        return is_array($steps) ? $steps : [];
    }

    private function timelineFromLogs(array $steps, array $logs): array
    {
        $timeline = [];
        foreach ($steps as $step) {
            $timeline[(int) $step['department_id']] = [
                'department_id' => (int) $step['department_id'],
                'department_name' => $step['department_name'],
                'reserved_by' => null,
                'reserved_at' => null,
                'started_by' => null,
                'started_at' => null,
                'completed_by' => null,
                'completed_at' => null,
                'points' => (int) ($step['points'] ?? 0),
                'notes' => [],
            ];
        }

        foreach ($logs as $log) {
            $departmentId = (int) ($log['department_id'] ?? 0);
            if (!isset($timeline[$departmentId])) {
                continue;
            }

            if (($log['action'] ?? '') === 'reserved') {
                $timeline[$departmentId]['reserved_by'] = $log['user_name'] ?? null;
                $timeline[$departmentId]['reserved_at'] = $log['created_at'] ?? null;
            } elseif (($log['action'] ?? '') === 'started') {
                $timeline[$departmentId]['started_by'] = $log['user_name'] ?? null;
                $timeline[$departmentId]['started_at'] = $log['created_at'] ?? null;
            } elseif (($log['action'] ?? '') === 'completed') {
                $timeline[$departmentId]['completed_by'] = $log['user_name'] ?? null;
                $timeline[$departmentId]['completed_at'] = $log['created_at'] ?? null;
                if (!empty($log['note'])) {
                    $timeline[$departmentId]['notes'][] = $log['note'];
                }
            } elseif (!empty($log['note'])) {
                $timeline[$departmentId]['notes'][] = $log['note'];
            }
        }

        return array_values($timeline);
    }

    private function lastCompletedDepartmentId(array $logs): int
    {
        for ($index = count($logs) - 1; $index >= 0; $index--) {
            if (($logs[$index]['action'] ?? '') === 'completed') {
                return (int) ($logs[$index]['department_id'] ?? 0);
            }
        }

        return 0;
    }

    private function workflowContainsDepartment(array $steps, ?int $departmentId): bool
    {
        if (!$departmentId) {
            return false;
        }

        foreach ($steps as $step) {
            if ((int) ($step['department_id'] ?? 0) === (int) $departmentId) {
                return true;
            }
        }

        return false;
    }

    private function userDepartmentId(array $user): ?int
    {
        $departmentName = trim((string) ($user['department'] ?? ''));
        if ($departmentName === '') {
            return null;
        }

        $stmt = $this->db->prepare("
            SELECT id
            FROM departments
            WHERE name = :name
              AND deleted_at IS NULL
            LIMIT 1
        ");
        $stmt->execute(['name' => $departmentName]);
        $row = $stmt->fetch();

        return $row ? (int) $row['id'] : null;
    }

    private function assertCanWorkCurrentDepartment(array $task, array $user): void
    {
        if (in_array(($user['role'] ?? ''), ['admin', 'manager'], true)) {
            return;
        }

        $departmentId = $this->userDepartmentId($user);
        if (!$departmentId || (int) ($task['current_department_id'] ?? 0) !== $departmentId) {
            throw new RuntimeException('This task is not available for your department.');
        }
    }

    private function assertAssignedUser(array $task, array $user): void
    {
        if (($user['role'] ?? '') === 'admin') {
            return;
        }

        $this->assertCanWorkCurrentDepartment($task, $user);

        $assignedUserId = (int) (($task['assigned_user_id'] ?? $task['assigned_to'] ?? 0));
        if ($assignedUserId !== (int) $user['id']) {
            throw new RuntimeException('Only the assigned user can update this workflow step.');
        }
    }

    private function createLog(int $taskId, int $departmentId, int $userId, string $action, string $note): void
    {
        $stmt = $this->db->prepare("
            INSERT INTO task_logs (task_id, department_id, user_id, action, note, created_at)
            VALUES (:task_id, :department_id, :user_id, :action, :note, NOW())
        ");
        $stmt->execute([
            'task_id' => $taskId,
            'department_id' => $departmentId,
            'user_id' => $userId,
            'action' => $action,
            'note' => $note !== '' ? $note : null,
        ]);
    }
}
