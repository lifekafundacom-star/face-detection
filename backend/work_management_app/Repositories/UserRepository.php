<?php

namespace App\Repositories;

use App\Core\Auth;
use RuntimeException;

class UserRepository extends BaseRepository
{
    public function all(array $filters = []): array
    {
        $conditions = ['u.deleted_at IS NULL'];
        $params = [];

        if (!empty($filters['search'])) {
            $conditions[] = '(u.name LIKE :search OR u.email LIKE :search OR u.employee_code LIKE :search)';
            $params['search'] = '%' . $filters['search'] . '%';
        }

        if (!empty($filters['role'])) {
            $conditions[] = 'u.role = :role';
            $params['role'] = $filters['role'];
        }

        if (!empty($filters['department'])) {
            $conditions[] = 'u.department = :department';
            $params['department'] = $filters['department'];
        }

        if (!empty($filters['status'])) {
            $conditions[] = 'u.status = :status';
            $params['status'] = $filters['status'];
        }

        if (!empty($filters['created_date'])) {
            $conditions[] = 'DATE(u.created_at) = :created_date';
            $params['created_date'] = $filters['created_date'];
        }

        $sortMap = [
            'newest' => 'u.created_at DESC',
            'oldest' => 'u.created_at ASC',
            'name_asc' => 'u.name ASC',
            'department_asc' => 'u.department ASC, u.name ASC',
        ];
        $orderBy = $sortMap[$filters['sort'] ?? 'newest'] ?? $sortMap['newest'];

        $stmt = $this->db->prepare("
            SELECT
                u.*,
                m.name AS manager_name
            FROM users u
            LEFT JOIN users m ON m.id = u.manager_id
            WHERE " . implode(' AND ', $conditions) . "
            ORDER BY {$orderBy}
        ");
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare("
            SELECT
                u.*,
                m.name AS manager_name,
                wr.name AS custom_role_name
            FROM users u
            LEFT JOIN users m ON m.id = u.manager_id
            LEFT JOIN work_user_roles wur ON wur.user_id = u.id
            LEFT JOIN work_roles wr ON wr.id = wur.role_id AND wr.status = 'active'
            WHERE u.id = :id
              AND u.deleted_at IS NULL
            LIMIT 1
        ");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public function findByEmail(string $email): ?array
    {
        $stmt = $this->db->prepare("
            SELECT *
            FROM users
            WHERE email = :email
              AND deleted_at IS NULL
            LIMIT 1
        ");
        $stmt->execute(['email' => $email]);
        return $stmt->fetch() ?: null;
    }

    public function findByEmailOrCode(string $identifier): ?array
    {
        $identifier = trim($identifier);
        $stmt = $this->db->prepare("
            SELECT *
            FROM users
            WHERE (email = :id OR employee_code = :id OR name = :id)
              AND deleted_at IS NULL
            LIMIT 1
        ");
        $stmt->execute(['id' => $identifier]);
        return $stmt->fetch() ?: null;
    }

    public function managers(): array
    {
        return $this->db->query("
            SELECT id, name, department
            FROM users
            WHERE role IN ('admin', 'manager')
              AND status = 'active'
              AND deleted_at IS NULL
            ORDER BY name ASC
        ")->fetchAll();
    }

    public function departments(): array
    {
        return (new DepartmentRepository())->active();
    }

    public function statuses(): array
    {
        return ['active', 'inactive', 'suspended'];
    }

    public function attendanceCandidates(): array
    {
        return $this->db->query("
            SELECT
                id,
                name,
                email,
                employee_code,
                department,
                designation,
                role,
                status,
                face_reference_descriptor,
                face_reference_image,
                face_reference_thumbnail,
                profile_image,
                profile_thumbnail,
                profile_image_path
            FROM users
            WHERE role IN ('admin', 'manager', 'employee')
              AND status = 'active'
              AND deleted_at IS NULL
            ORDER BY name ASC
        ")->fetchAll();
    }

    public function create(array $payload): array
    {
        $password = (string) ($payload['password'] ?? '');
        $confirmPassword = (string) ($payload['confirm_password'] ?? '');

        if ($password === '' || $password !== $confirmPassword) {
            throw new RuntimeException('Password and confirm password must match.');
        }

        $employeeCode = trim((string) ($payload['employee_code'] ?? ''));
        if ($employeeCode === '') {
            $employeeCode = $this->nextEmployeeCode();
        }

        $name = trim((string) ($payload['name'] ?? ''));
        if ($name === '') {
            throw new RuntimeException('Full Name is required.');
        }

        $email = trim((string) ($payload['email'] ?? ''));
        if ($email === '') {
            $cleanCode = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $employeeCode));
            $email = $cleanCode . '@lifekafunda.local';
            $baseEmail = $email;
            $counter = 1;
            while ($this->findByEmail($email)) {
                $email = str_replace('@', $counter . '@', $baseEmail);
                $counter++;
            }
        } elseif ($this->findByEmail($email)) {
            throw new RuntimeException('Email already exists.');
        }

        $stmt = $this->db->prepare("
            INSERT INTO users
                (manager_id, name, email, password, role, department, designation, shift_name, shift_start, shift_end, status, employee_code, mobile, joining_date, force_password_change)
            VALUES
                (:manager_id, :name, :email, :password, :role, :department, :designation, :shift_name, :shift_start, :shift_end, :status, :employee_code, :mobile, :joining_date, :force_password_change)
        ");
        $stmt->execute([
            'manager_id' => $this->nullableInt($payload['manager_id'] ?? null),
            'name' => trim((string) ($payload['name'] ?? '')),
            'email' => $email,
            'password' => password_hash($password, PASSWORD_BCRYPT),
            'role' => $payload['role'] ?? 'employee',
            'department' => $this->normalizeDepartment($payload['department'] ?? ''),
            'designation' => trim((string) ($payload['designation'] ?? '')),
            'shift_name' => trim((string) ($payload['shift_name'] ?? 'General Shift')),
            'shift_start' => $this->normalizeTime($payload['shift_start'] ?? '09:00'),
            'shift_end' => $this->normalizeTime($payload['shift_end'] ?? '18:00'),
            'status' => $payload['status'] ?? 'active',
            'employee_code' => $employeeCode,
            'mobile' => trim((string) ($payload['mobile'] ?? '')),
            'joining_date' => $payload['joining_date'] ?? date('Y-m-d'),
            'force_password_change' => !empty($payload['force_password_change']) ? 1 : 0,
        ]);

        return $this->findById((int) $this->db->lastInsertId());
    }

    public function update(int $userId, array $payload): array
    {
        $existing = $this->findById($userId);
        if (!$existing) {
            throw new RuntimeException('User not found.');
        }

        $email = trim((string) ($payload['email'] ?? $existing['email']));
        $duplicate = $this->findByEmail($email);
        if ($duplicate && (int) $duplicate['id'] !== $userId) {
            throw new RuntimeException('Email already exists.');
        }

        $password = (string) ($payload['password'] ?? '');
        $confirmPassword = (string) ($payload['confirm_password'] ?? '');

        if ($password !== '' && $password !== $confirmPassword) {
            throw new RuntimeException('Password and confirm password must match.');
        }

        $sql = "
            UPDATE users
            SET manager_id = :manager_id,
                name = :name,
                email = :email,
                role = :role,
                department = :department,
                designation = :designation,
                shift_name = :shift_name,
                shift_start = :shift_start,
                shift_end = :shift_end,
                status = :status,
                employee_code = :employee_code,
                mobile = :mobile,
                joining_date = :joining_date,
                force_password_change = :force_password_change
        ";

        $params = [
            'manager_id' => $this->nullableInt($payload['manager_id'] ?? $existing['manager_id']),
            'name' => trim((string) ($payload['name'] ?? $existing['name'])),
            'email' => $email,
            'role' => $payload['role'] ?? $existing['role'],
            'department' => $this->normalizeDepartment($payload['department'] ?? $existing['department']),
            'designation' => trim((string) ($payload['designation'] ?? $existing['designation'])),
            'shift_name' => trim((string) ($payload['shift_name'] ?? $existing['shift_name'])),
            'shift_start' => $this->normalizeTime($payload['shift_start'] ?? $existing['shift_start']),
            'shift_end' => $this->normalizeTime($payload['shift_end'] ?? $existing['shift_end']),
            'status' => $payload['status'] ?? $existing['status'],
            'employee_code' => trim((string) ($payload['employee_code'] ?? $existing['employee_code'])),
            'mobile' => trim((string) ($payload['mobile'] ?? $existing['mobile'])),
            'joining_date' => $payload['joining_date'] ?? $existing['joining_date'],
            'force_password_change' => !empty($payload['force_password_change']) ? 1 : 0,
            'id' => $userId,
        ];

        if ($password !== '') {
            $sql .= ", password = :password";
            $params['password'] = password_hash($password, PASSWORD_BCRYPT);
        }

        $sql .= " WHERE id = :id";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $this->findById($userId);
    }

    public function resetPassword(int $userId, string $password, bool $forcePasswordChange = true): array
    {
        $user = $this->findById($userId);
        if (!$user) {
            throw new RuntimeException('User not found.');
        }

        $stmt = $this->db->prepare("
            UPDATE users
            SET password = :password,
                force_password_change = :force_password_change
            WHERE id = :id
        ");
        $stmt->execute([
            'password' => password_hash($password, PASSWORD_BCRYPT),
            'force_password_change' => $forcePasswordChange ? 1 : 0,
            'id' => $userId,
        ]);

        return $this->findById($userId);
    }

    public function changePassword(int $userId, string $currentPassword, string $newPassword, string $confirmPassword): void
    {
        $user = $this->findById($userId);
        if (!$user) {
            throw new RuntimeException('User not found.');
        }

        if (trim($currentPassword) === '') {
            throw new RuntimeException('Current password is required.');
        }

        if (!Auth::verifyPassword($currentPassword, (string) $user['password'])) {
            throw new RuntimeException('Current password is incorrect.');
        }

        if (trim($newPassword) === '') {
            throw new RuntimeException('New password is required.');
        }

        if (strlen($newPassword) < 8) {
            throw new RuntimeException('New password must be at least 8 characters.');
        }

        if ($newPassword !== $confirmPassword) {
            throw new RuntimeException('New password and confirm password must match.');
        }

        if (hash_equals($currentPassword, $newPassword)) {
            throw new RuntimeException('New password must be different from the current password.');
        }

        $stmt = $this->db->prepare("
            UPDATE users
            SET password = :password,
                force_password_change = 0
            WHERE id = :id
        ");
        $stmt->execute([
            'password' => password_hash($newPassword, PASSWORD_BCRYPT),
            'id' => $userId,
        ]);
    }

    public function updateProfileImages(int $userId, array $paths): array
    {
        $user = $this->findById($userId);
        if (!$user) {
            throw new RuntimeException('User not found.');
        }

        $stmt = $this->db->prepare("
            UPDATE users
            SET profile_image = :profile_image,
                profile_thumbnail = :profile_thumbnail,
                profile_image_path = :profile_image_path
            WHERE id = :id
        ");
        $stmt->execute([
            'profile_image' => $paths['profile_image'] ?? null,
            'profile_thumbnail' => $paths['profile_thumbnail'] ?? null,
            'profile_image_path' => $paths['profile_image_path'] ?? ($paths['profile_image'] ?? null),
            'id' => $userId,
        ]);

        return $this->findById($userId);
    }

    public function updateFaceReferenceImages(int $userId, array $paths): array
    {
        $user = $this->findById($userId);
        if (!$user) {
            throw new RuntimeException('User not found.');
        }

        $stmt = $this->db->prepare("
            UPDATE users
            SET face_reference_image = :face_reference_image,
                face_reference_thumbnail = :face_reference_thumbnail,
                face_reference_descriptor = :face_reference_descriptor
            WHERE id = :id
        ");
        $stmt->execute([
            'face_reference_image' => $paths['face_reference_image'] ?? null,
            'face_reference_thumbnail' => $paths['face_reference_thumbnail'] ?? null,
            'face_reference_descriptor' => $paths['face_reference_descriptor'] ?? null,
            'id' => $userId,
        ]);

        return $this->findById($userId);
    }

    public function softDelete(int $userId): void
    {
        $stmt = $this->db->prepare("
            UPDATE users
            SET deleted_at = NOW(),
                status = 'inactive'
            WHERE id = :id
        ");
        $stmt->execute(['id' => $userId]);
    }

    public function bulkAction(string $action, array $userIds, array $payload = []): int
    {
        $userIds = array_values(array_filter(array_map('intval', $userIds)));
        if (empty($userIds)) {
            return 0;
        }

        $placeholders = implode(',', array_fill(0, count($userIds), '?'));

        switch ($action) {
            case 'activate':
                $stmt = $this->db->prepare("UPDATE users SET status = 'active' WHERE id IN ({$placeholders})");
                $stmt->execute($userIds);
                return $stmt->rowCount();
            case 'deactivate':
                $stmt = $this->db->prepare("UPDATE users SET status = 'inactive' WHERE id IN ({$placeholders})");
                $stmt->execute($userIds);
                return $stmt->rowCount();
            case 'delete':
                $stmt = $this->db->prepare("UPDATE users SET status = 'inactive', deleted_at = NOW() WHERE id IN ({$placeholders})");
                $stmt->execute($userIds);
                return $stmt->rowCount();
            case 'assign_department':
                $department = $this->normalizeDepartment($payload['department'] ?? '');
                $params = array_merge([$department], $userIds);
                $stmt = $this->db->prepare("UPDATE users SET department = ? WHERE id IN ({$placeholders})");
                $stmt->execute($params);
                return $stmt->rowCount();
            case 'assign_shift':
                $shiftName = trim((string) ($payload['shift_name'] ?? 'General Shift'));
                $shiftStart = $this->normalizeTime($payload['shift_start'] ?? '09:00');
                $shiftEnd = $this->normalizeTime($payload['shift_end'] ?? '18:00');
                $params = array_merge([$shiftName, $shiftStart, $shiftEnd], $userIds);
                $stmt = $this->db->prepare("UPDATE users SET shift_name = ?, shift_start = ?, shift_end = ? WHERE id IN ({$placeholders})");
                $stmt->execute($params);
                return $stmt->rowCount();
            default:
                throw new RuntimeException('Unsupported bulk action.');
        }
    }

    public function forLeaderboard(string $scope = 'monthly'): array
    {
        $dateFilter = $scope === 'weekly'
            ? 'DATE_SUB(CURDATE(), INTERVAL 7 DAY)'
            : 'DATE_SUB(CURDATE(), INTERVAL 30 DAY)';

        $sql = "
            SELECT u.id, u.name, u.role, COALESCE(SUM(t.points), 0) AS score, COUNT(t.id) AS completed_tasks
            FROM users u
            LEFT JOIN tasks t
                ON t.assigned_to = u.id
                AND t.status = 'completed'
                AND t.completed_at >= {$dateFilter}
            WHERE u.deleted_at IS NULL
            GROUP BY u.id, u.name, u.role
            ORDER BY score DESC, completed_tasks DESC, u.name ASC
        ";

        return $this->db->query($sql)->fetchAll();
    }

    public function nextEmployeeCode(): string
    {
        $stmt = $this->db->query("SELECT employee_code FROM users WHERE employee_code IS NOT NULL AND employee_code != ''");
        $codes = $stmt->fetchAll(\PDO::FETCH_COLUMN);
        $maxNum = 0;
        foreach ($codes as $code) {
            if (preg_match('/^EMP-(\d+)$/', (string) $code, $matches)) {
                $num = (int) $matches[1];
                if ($num > $maxNum) {
                    $maxNum = $num;
                }
            }
        }
        $next = max($maxNum + 1, count($codes) + 1);

        return 'EMP-' . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }

    private function generateEmployeeCode(): string
    {
        return $this->nextEmployeeCode();
    }

    private function normalizeTime($time): ?string
    {
        $time = trim((string) $time);
        if ($time === '') {
            return null;
        }

        if (preg_match('/^\d{2}:\d{2}$/', $time)) {
            return $time . ':00';
        }

        return preg_match('/^\d{2}:\d{2}:\d{2}$/', $time) ? $time : null;
    }

    private function nullableInt($value): ?int
    {
        if ($value === null || $value === '' || (int) $value === 0) {
            return null;
        }

        return (int) $value;
    }

    private function normalizeDepartment($department): ?string
    {
        $department = trim((string) $department);
        if ($department === '') {
            return null;
        }

        $repository = new DepartmentRepository();
        foreach ($repository->active() as $option) {
            if (strcasecmp($option['name'], $department) === 0) {
                return $option['name'];
            }
        }

        throw new RuntimeException('Select a valid active department.');
    }
}
