<?php

namespace App\Repositories;

class ReportRepository extends BaseRepository
{
    public function performanceTrend(array $user): array
    {
        $condition = 't.assigned_to = :user_id';
        $params = ['user_id' => $user['id']];

        if ($user['role'] === 'admin') {
            $condition = '1=1';
            $params = [];
        } elseif ($user['role'] === 'manager') {
            $condition = '(u.manager_id = :manager_id OR t.created_by = :manager_id)';
            $params = ['manager_id' => $user['id']];
        }

        $stmt = $this->db->prepare("
            SELECT DATE_FORMAT(t.completed_at, '%b') AS label, COALESCE(SUM(t.points), 0) AS points
            FROM tasks t
            LEFT JOIN users u ON u.id = t.assigned_to
            WHERE t.status = 'completed'
              AND t.completed_at IS NOT NULL
              AND {$condition}
              AND t.completed_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
            GROUP BY YEAR(t.completed_at), MONTH(t.completed_at), DATE_FORMAT(t.completed_at, '%b')
            ORDER BY YEAR(t.completed_at), MONTH(t.completed_at)
        ");
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function workflowSummary(): array
    {
        return $this->db->query("
            SELECT w.name, COUNT(t.id) AS task_count
            FROM workflows w
            LEFT JOIN tasks t ON t.workflow_id = w.id
            GROUP BY w.id, w.name
            ORDER BY task_count DESC, w.name ASC
        ")->fetchAll();
    }
}
