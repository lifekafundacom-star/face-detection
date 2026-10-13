<?php

namespace App\Repositories;

class WorkflowRepository extends BaseRepository
{
    public function all(): array
    {
        $workflows = $this->db->query('SELECT * FROM workflows ORDER BY name')->fetchAll();

        foreach ($workflows as &$workflow) {
            $stmt = $this->db->prepare('SELECT * FROM workflow_stages WHERE workflow_id = :workflow_id ORDER BY stage_order');
            $stmt->execute(['workflow_id' => $workflow['id']]);
            $workflow['stages'] = $stmt->fetchAll();
        }

        return $workflows;
    }

    public function nextStage(int $workflowId, int $currentStageId): ?array
    {
        $stmt = $this->db->prepare('SELECT stage_order FROM workflow_stages WHERE id = :id AND workflow_id = :workflow_id');
        $stmt->execute(['id' => $currentStageId, 'workflow_id' => $workflowId]);
        $currentStage = $stmt->fetch();

        if (!$currentStage) {
            return null;
        }

        $nextStmt = $this->db->prepare("
            SELECT *
            FROM workflow_stages
            WHERE workflow_id = :workflow_id
              AND stage_order = :stage_order
            LIMIT 1
        ");
        $nextStmt->execute([
            'workflow_id' => $workflowId,
            'stage_order' => ((int) $currentStage['stage_order']) + 1,
        ]);

        return $nextStmt->fetch() ?: null;
    }

    public function assigneeForRole(string $role): ?int
    {
        $stmt = $this->db->prepare("
            SELECT id
            FROM users
            WHERE role = :role
            ORDER BY id
            LIMIT 1
        ");
        $stmt->execute(['role' => $role]);
        $user = $stmt->fetch();
        return $user ? (int) $user['id'] : null;
    }
}
