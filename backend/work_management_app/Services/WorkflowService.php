<?php

namespace App\Services;

use App\Repositories\TaskRepository;

class WorkflowService
{
    public function advance(int $taskId, array $user): array
    {
        $task = (new TaskRepository())->completeStep($taskId, $user, 'Advanced from workflow engine.');

        return [
            'message' => $task['status'] === 'completed'
                ? 'Workflow finished and task marked completed.'
                : 'Task advanced to the next workflow department.',
            'status' => $task['status'],
            'task' => $task,
        ];
    }
}
