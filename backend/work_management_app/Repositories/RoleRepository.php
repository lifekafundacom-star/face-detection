<?php

namespace App\Repositories;

use App\Core\MenuRegistry;
use RuntimeException;

class RoleRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct();
        $this->ensureTables();
    }

    public function all(): array
    {
        $roles = $this->db->query("
            SELECT
                r.*,
                COUNT(ur.user_id) AS employee_count
            FROM work_roles r
            LEFT JOIN work_user_roles ur ON ur.role_id = r.id
            GROUP BY r.id
            ORDER BY r.created_at DESC, r.name ASC
        ")->fetchAll();

        foreach ($roles as &$role) {
            $role['permissions'] = $this->permissionsForRole((int) $role['id']);
        }

        return $roles;
    }

    public function active(): array
    {
        $stmt = $this->db->query("
            SELECT id, name, slug
            FROM work_roles
            WHERE status = 'active'
            ORDER BY name ASC
        ");

        return $stmt->fetchAll();
    }

    public function systemRoles(): array
    {
        $countStmt = $this->db->query("
            SELECT role, COUNT(*) AS employee_count
            FROM users
            WHERE role IN ('admin', 'manager', 'employee')
              AND deleted_at IS NULL
            GROUP BY role
        ");
        $counts = [];
        foreach ($countStmt->fetchAll() as $row) {
            $counts[(string) $row['role']] = (int) $row['employee_count'];
        }

        $roles = [];
        foreach (MenuRegistry::systemRoleLabels() as $roleKey => $roleName) {
            $roles[] = [
                'scope' => 'system',
                'id' => 'system-' . $roleKey,
                'system_role' => $roleKey,
                'name' => $roleName,
                'status' => 'active',
                'description' => 'Built-in system role access.',
                'employee_count' => $counts[$roleKey] ?? 0,
                'permissions' => $this->permissionsForSystemRole($roleKey),
            ];
        }

        return $roles;
    }

    public function saveSystemRole(array $payload): array
    {
        $role = strtolower(trim((string) ($payload['system_role'] ?? '')));
        if (!array_key_exists($role, MenuRegistry::systemRoleLabels())) {
            throw new RuntimeException('Select a valid system role.');
        }

        $permissions = $this->normalizePermissions($payload['permissions'] ?? []);
        if (empty($permissions)) {
            throw new RuntimeException('Select at least one menu permission for this role.');
        }

        $delete = $this->db->prepare("DELETE FROM work_system_role_permissions WHERE system_role = :system_role");
        $delete->execute(['system_role' => $role]);

        $insert = $this->db->prepare("
            INSERT INTO work_system_role_permissions (system_role, permission_key, created_at)
            VALUES (:system_role, :permission_key, NOW())
        ");
        foreach ($permissions as $permission) {
            $insert->execute([
                'system_role' => $role,
                'permission_key' => $permission,
            ]);
        }

        foreach ($this->systemRoles() as $systemRole) {
            if (($systemRole['system_role'] ?? '') === $role) {
                return $systemRole;
            }
        }

        throw new RuntimeException('System role could not be saved.');
    }

    public function find(int $roleId): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM work_roles WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $roleId]);
        $role = $stmt->fetch() ?: null;

        if ($role) {
            $role['permissions'] = $this->permissionsForRole($roleId);
        }

        return $role;
    }

    public function save(array $payload): array
    {
        $roleId = (int) ($payload['role_id'] ?? $payload['id'] ?? 0);
        $name = trim((string) ($payload['name'] ?? ''));
        if ($name === '') {
            throw new RuntimeException('Role name is required.');
        }

        $status = (string) ($payload['status'] ?? 'active');
        if (!in_array($status, ['active', 'inactive'], true)) {
            $status = 'active';
        }

        $permissions = $this->normalizePermissions($payload['permissions'] ?? []);
        if (empty($permissions)) {
            throw new RuntimeException('Select at least one menu permission for this role.');
        }

        $description = trim((string) ($payload['description'] ?? ''));
        $slug = $this->uniqueSlug($name, $roleId);

        if ($roleId > 0) {
            if (!$this->find($roleId)) {
                throw new RuntimeException('Role not found.');
            }

            $stmt = $this->db->prepare("
                UPDATE work_roles
                SET name = :name,
                    slug = :slug,
                    description = :description,
                    status = :status,
                    updated_at = NOW()
                WHERE id = :id
            ");
            $stmt->execute([
                'id' => $roleId,
                'name' => $name,
                'slug' => $slug,
                'description' => $description,
                'status' => $status,
            ]);
        } else {
            $stmt = $this->db->prepare("
                INSERT INTO work_roles (name, slug, description, status, created_at, updated_at)
                VALUES (:name, :slug, :description, :status, NOW(), NOW())
            ");
            $stmt->execute([
                'name' => $name,
                'slug' => $slug,
                'description' => $description,
                'status' => $status,
            ]);
            $roleId = (int) $this->db->lastInsertId();
        }

        $this->replacePermissions($roleId, $permissions);

        return $this->find($roleId);
    }

    public function delete(int $roleId): void
    {
        $role = $this->find($roleId);
        if (!$role) {
            throw new RuntimeException('Role not found.');
        }

        $stmt = $this->db->prepare("SELECT COUNT(*) FROM work_user_roles WHERE role_id = :role_id");
        $stmt->execute(['role_id' => $roleId]);
        if ((int) $stmt->fetchColumn() > 0) {
            throw new RuntimeException('Remove this role from employees before deleting it.');
        }

        $delete = $this->db->prepare("DELETE FROM work_roles WHERE id = :id");
        $delete->execute(['id' => $roleId]);
    }

    public function assignToUser(int $userId, int $roleId = 0): void
    {
        if ($roleId <= 0) {
            $stmt = $this->db->prepare("DELETE FROM work_user_roles WHERE user_id = :user_id");
            $stmt->execute(['user_id' => $userId]);
            return;
        }

        $userStmt = $this->db->prepare("
            SELECT id
            FROM users
            WHERE id = :id
              AND role IN ('manager', 'employee')
              AND deleted_at IS NULL
            LIMIT 1
        ");
        $userStmt->execute(['id' => $userId]);
        if (!$userStmt->fetch()) {
            throw new RuntimeException('Select a valid employee or manager.');
        }

        $role = $this->find($roleId);
        if (!$role || ($role['status'] ?? '') !== 'active') {
            throw new RuntimeException('Select an active role.');
        }

        $stmt = $this->db->prepare("
            INSERT INTO work_user_roles (user_id, role_id, created_at, updated_at)
            VALUES (:user_id, :role_id, NOW(), NOW())
            ON DUPLICATE KEY UPDATE role_id = VALUES(role_id), updated_at = NOW()
        ");
        $stmt->execute([
            'user_id' => $userId,
            'role_id' => $roleId,
        ]);
    }

    public function userAssignments(): array
    {
        $stmt = $this->db->query("
            SELECT
                u.id,
                u.name,
                u.email,
                u.role AS system_role,
                u.department,
                u.designation,
                wr.id AS custom_role_id,
                wr.name AS custom_role_name,
                wr.status AS custom_role_status
            FROM users u
            LEFT JOIN work_user_roles wur ON wur.user_id = u.id
            LEFT JOIN work_roles wr ON wr.id = wur.role_id
            WHERE u.role IN ('manager', 'employee')
              AND u.deleted_at IS NULL
            ORDER BY u.name ASC
        ");

        return $stmt->fetchAll();
    }

    public function userAssignmentMap(): array
    {
        $stmt = $this->db->query("
            SELECT wur.user_id, wur.role_id, wr.name AS role_name
            FROM work_user_roles wur
            INNER JOIN work_roles wr ON wr.id = wur.role_id
        ");

        $map = [];
        foreach ($stmt->fetchAll() as $row) {
            $map[(int) $row['user_id']] = [
                'role_id' => (int) $row['role_id'],
                'role_name' => (string) $row['role_name'],
            ];
        }

        return $map;
    }

    public function permissionsForUser(int $userId): ?array
    {
        $stmt = $this->db->prepare("
            SELECT wr.id
            FROM work_user_roles wur
            INNER JOIN work_roles wr ON wr.id = wur.role_id AND wr.status = 'active'
            WHERE wur.user_id = :user_id
            LIMIT 1
        ");
        $stmt->execute(['user_id' => $userId]);
        $roleId = (int) ($stmt->fetchColumn() ?: 0);

        if ($roleId <= 0) {
            $userStmt = $this->db->prepare("
                SELECT role
                FROM users
                WHERE id = :user_id
                  AND deleted_at IS NULL
                LIMIT 1
            ");
            $userStmt->execute(['user_id' => $userId]);
            $systemRole = (string) ($userStmt->fetchColumn() ?: '');

            return $this->permissionsForSystemRole($systemRole);
        }

        return $this->permissionsForRole($roleId);
    }

    public function permissionsForSystemRole(string $role): array
    {
        $role = strtolower(trim($role));
        if (!array_key_exists($role, MenuRegistry::systemRoleLabels())) {
            return [];
        }

        $stmt = $this->db->prepare("
            SELECT permission_key
            FROM work_system_role_permissions
            WHERE system_role = :system_role
            ORDER BY permission_key ASC
        ");
        $stmt->execute(['system_role' => $role]);
        $permissions = array_map(static fn ($row) => (string) $row['permission_key'], $stmt->fetchAll());

        return $permissions ?: MenuRegistry::defaultPermissionsForRole($role);
    }

    private function permissionsForRole(int $roleId): array
    {
        $stmt = $this->db->prepare("
            SELECT permission_key
            FROM work_role_permissions
            WHERE role_id = :role_id
            ORDER BY permission_key ASC
        ");
        $stmt->execute(['role_id' => $roleId]);

        return array_map(static fn ($row) => (string) $row['permission_key'], $stmt->fetchAll());
    }

    private function replacePermissions(int $roleId, array $permissions): void
    {
        $delete = $this->db->prepare("DELETE FROM work_role_permissions WHERE role_id = :role_id");
        $delete->execute(['role_id' => $roleId]);

        $insert = $this->db->prepare("
            INSERT INTO work_role_permissions (role_id, permission_key, created_at)
            VALUES (:role_id, :permission_key, NOW())
        ");

        foreach ($permissions as $permission) {
            $insert->execute([
                'role_id' => $roleId,
                'permission_key' => $permission,
            ]);
        }
    }

    private function normalizePermissions($permissions): array
    {
        if (is_string($permissions)) {
            $decoded = json_decode($permissions, true);
            $permissions = is_array($decoded) ? $decoded : [$permissions];
        }

        if (!is_array($permissions)) {
            $permissions = [];
        }

        $allowed = array_flip(MenuRegistry::permissionKeys());
        $normalized = [];
        foreach ($permissions as $permission) {
            $key = trim((string) $permission);
            if (isset($allowed[$key])) {
                $normalized[$key] = $key;
            }
        }

        return array_values($normalized);
    }

    private function uniqueSlug(string $name, int $ignoreRoleId = 0): string
    {
        $base = strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', $name), '-'));
        $base = $base !== '' ? $base : 'role';
        $slug = $base;
        $suffix = 2;

        while ($this->slugExists($slug, $ignoreRoleId)) {
            $slug = $base . '-' . $suffix;
            $suffix++;
        }

        return $slug;
    }

    private function slugExists(string $slug, int $ignoreRoleId = 0): bool
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*)
            FROM work_roles
            WHERE slug = :slug
              AND id <> :ignore_id
        ");
        $stmt->execute([
            'slug' => $slug,
            'ignore_id' => $ignoreRoleId,
        ]);

        return (int) $stmt->fetchColumn() > 0;
    }

    private function ensureTables(): void
    {
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS work_roles (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(120) NOT NULL,
                slug VARCHAR(140) NOT NULL UNIQUE,
                description TEXT NULL,
                status ENUM('active','inactive') NOT NULL DEFAULT 'active',
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        $this->db->exec("
            CREATE TABLE IF NOT EXISTS work_role_permissions (
                id INT AUTO_INCREMENT PRIMARY KEY,
                role_id INT NOT NULL,
                permission_key VARCHAR(160) NOT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY uq_work_role_permission (role_id, permission_key),
                KEY idx_work_role_permissions_key (permission_key),
                CONSTRAINT fk_work_role_permissions_role FOREIGN KEY (role_id) REFERENCES work_roles(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        $this->db->exec("
            CREATE TABLE IF NOT EXISTS work_user_roles (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                role_id INT NOT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY uq_work_user_roles_user (user_id),
                KEY idx_work_user_roles_role (role_id),
                CONSTRAINT fk_work_user_roles_role FOREIGN KEY (role_id) REFERENCES work_roles(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        $this->db->exec("
            CREATE TABLE IF NOT EXISTS work_system_role_permissions (
                id INT AUTO_INCREMENT PRIMARY KEY,
                system_role ENUM('admin','manager','employee') NOT NULL,
                permission_key VARCHAR(160) NOT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY uq_work_system_role_permission (system_role, permission_key),
                KEY idx_work_system_role_permissions_key (permission_key)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
    }
}
