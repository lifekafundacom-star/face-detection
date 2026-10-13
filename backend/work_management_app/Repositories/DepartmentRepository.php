<?php

namespace App\Repositories;

use RuntimeException;

class DepartmentRepository extends BaseRepository
{
    public function all(bool $includeInactive = true): array
    {
        $sql = "
            SELECT id, name, code, status, created_at, updated_at
            FROM departments
            WHERE deleted_at IS NULL
        ";

        if (!$includeInactive) {
            $sql .= " AND status = 'active'";
        }

        $sql .= " ORDER BY name ASC";

        return $this->db->query($sql)->fetchAll();
    }

    public function active(): array
    {
        return $this->all(false);
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare("
            SELECT id, name, code, status, created_at, updated_at
            FROM departments
            WHERE id = :id
              AND deleted_at IS NULL
            LIMIT 1
        ");
        $stmt->execute(['id' => $id]);

        return $stmt->fetch() ?: null;
    }

    public function create(array $payload): array
    {
        $name = trim((string) ($payload['name'] ?? ''));
        if ($name === '') {
            throw new RuntimeException('Department name is required.');
        }

        if ($this->findByName($name)) {
            throw new RuntimeException('Department already exists.');
        }

        $status = $this->normalizeStatus($payload['status'] ?? 'active');
        $code = trim((string) ($payload['code'] ?? ''));
        if ($code === '') {
            $code = $this->generateCode($name);
        }

        $stmt = $this->db->prepare("
            INSERT INTO departments (name, code, status)
            VALUES (:name, :code, :status)
        ");
        $stmt->execute([
            'name' => $name,
            'code' => strtoupper($code),
            'status' => $status,
        ]);

        return $this->findById((int) $this->db->lastInsertId());
    }

    public function update(int $departmentId, array $payload): array
    {
        $department = $this->findById($departmentId);
        if (!$department) {
            throw new RuntimeException('Department not found.');
        }

        $name = trim((string) ($payload['name'] ?? $department['name']));
        if ($name === '') {
            throw new RuntimeException('Department name is required.');
        }

        $duplicate = $this->findByName($name);
        if ($duplicate && (int) $duplicate['id'] !== $departmentId) {
            throw new RuntimeException('Department already exists.');
        }

        $status = $this->normalizeStatus($payload['status'] ?? $department['status']);
        $code = trim((string) ($payload['code'] ?? $department['code']));
        if ($code === '') {
            $code = $this->generateCode($name);
        }

        $stmt = $this->db->prepare("
            UPDATE departments
            SET name = :name,
                code = :code,
                status = :status
            WHERE id = :id
        ");
        $stmt->execute([
            'name' => $name,
            'code' => strtoupper($code),
            'status' => $status,
            'id' => $departmentId,
        ]);

        return $this->findById($departmentId);
    }

    public function delete(int $departmentId): void
    {
        $department = $this->findById($departmentId);
        if (!$department) {
            throw new RuntimeException('Department not found.');
        }

        $stmt = $this->db->prepare("
            SELECT COUNT(*) AS total
            FROM users
            WHERE department = :department
              AND deleted_at IS NULL
        ");
        $stmt->execute(['department' => $department['name']]);
        $inUse = (int) ($stmt->fetch()['total'] ?? 0);

        if ($inUse > 0) {
            throw new RuntimeException('Department is assigned to users. Set it inactive before removing assignments.');
        }

        $delete = $this->db->prepare("
            UPDATE departments
            SET deleted_at = NOW(),
                status = 'inactive'
            WHERE id = :id
        ");
        $delete->execute(['id' => $departmentId]);
    }

    private function findByName(string $name): ?array
    {
        $stmt = $this->db->prepare("
            SELECT id, name, code, status
            FROM departments
            WHERE LOWER(name) = LOWER(:name)
              AND deleted_at IS NULL
            LIMIT 1
        ");
        $stmt->execute(['name' => $name]);

        return $stmt->fetch() ?: null;
    }

    private function normalizeStatus($status): string
    {
        $status = strtolower(trim((string) $status));
        return $status === 'inactive' ? 'inactive' : 'active';
    }

    private function generateCode(string $name): string
    {
        $sanitized = preg_replace('/[^A-Za-z0-9]+/', ' ', strtoupper($name));
        $parts = array_values(array_filter(explode(' ', trim((string) $sanitized))));

        if (empty($parts)) {
            return 'DEPT';
        }

        $code = '';
        foreach ($parts as $part) {
            $code .= substr($part, 0, 1);
        }

        return substr($code, 0, 6);
    }
}
