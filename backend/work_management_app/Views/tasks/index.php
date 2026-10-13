<?php
$columns = ['pending' => 'Pending', 'in_progress' => 'In Progress', 'review' => 'Review', 'completed' => 'Completed'];
$taskSuccess = $_SESSION['tasks_success'] ?? null;
$taskError = $_SESSION['tasks_error'] ?? null;
unset($_SESSION['tasks_success'], $_SESSION['tasks_error']);
?>
<section class="page-grid">
    <article class="panel panel-span-2" style="min-width: 0;">
        <div class="panel-head">
            <div><p class="eyebrow">My tasks</p><h2>Workflow task board</h2></div>
            <?php if (!empty($canCreateTasks)): ?>
                <button class="btn btn-primary task-panel-toggle" type="button" data-target="taskCreatePanel">Create Task</button>
            <?php endif; ?>
        </div>
        <?php if ($taskSuccess): ?><p class="form-success"><?= htmlspecialchars($taskSuccess) ?></p><?php endif; ?>
        <?php if ($taskError): ?><p class="form-error"><?= htmlspecialchars($taskError) ?></p><?php endif; ?>
        <?php if (!empty($canCreateTasks)): ?>
            <form id="taskCreatePanel" class="stack task-create-form" action="<?= htmlspecialchars(($basePath ?? '') . '/api/tasks/create') ?>" method="POST" hidden>
                <div class="users-detail-grid">
                    <label class="field"><span>Task Title</span><input type="text" name="title" required></label>
                    <label class="field"><span>Client / Project Name</span><input type="text" name="client_name"></label>
                    <label class="field"><span>Task Type</span><input type="text" name="task_type" placeholder="Video / Design / Marketing"></label>
                    <label class="field"><span>Priority</span>
                        <select name="priority">
                            <option value="low">Low</option>
                            <option value="medium" selected>Medium</option>
                            <option value="high">High</option>
                            <option value="urgent">Urgent</option>
                        </select>
                    </label>
                    <label class="field"><span>Date</span><input type="date" name="task_date"></label>
                    <label class="field"><span>Time</span><input type="time" name="task_time"></label>
                    <label class="field field-span-2"><span>Location</span><input type="text" name="task_location" placeholder="Location"></label>
                    <label class="field field-span-2"><span>Notes / Instructions</span><textarea name="notes" rows="4" placeholder="Task notes and instructions"></textarea></label>
                    <label class="field"><span>Deadline</span><input type="date" name="deadline" required></label>
                </div>
                <div class="task-step-builder">
                    <div class="panel-head">
                        <div><p class="eyebrow">Workflow departments</p><h3>Ordered handoff steps</h3></div>
                        <button class="btn btn-light btn-small task-step-add" type="button">Add Step</button>
                    </div>
                    <div class="task-step-list task-step-sequence" data-departments='<?= htmlspecialchars(json_encode(array_values($workflowDepartments ?? [])), ENT_QUOTES, 'UTF-8') ?>'></div>
                    <p class="empty-copy task-step-empty">Add departments in the exact workflow order, for example Video Shooting -> Video Editor -> Digital Marketing -> Marketing Head.</p>
                </div>
                <div class="button-row">
                    <button class="btn btn-primary" type="submit">Create Workflow Task</button>
                </div>
            </form>
        <?php endif; ?>
        <div style="width: 100%; overflow-x: auto; padding-bottom: 10px;">
            <div class="kanban-grid" style="min-width: 880px;">
                <?php foreach ($columns as $statusKey => $statusLabel): ?>
                    <div class="kanban-column">
                        <h3><?= htmlspecialchars($statusLabel) ?></h3>
                        <?php foreach ($tasks as $task): ?>
                            <?php if (($task['board_status'] ?? '') !== $statusKey) { continue; } ?>
                            <article class="task-card workflow-task-card task-card-priority-<?= htmlspecialchars($task['priority'] ?? 'medium') ?>" data-task-id="<?= (int) $task['id'] ?>">
                                <div class="task-card-head">
                                    <strong><?= htmlspecialchars($task['title']) ?></strong>
                                    <span class="task-priority priority-<?= htmlspecialchars($task['priority'] ?? 'medium') ?>"><?= htmlspecialchars(ucfirst($task['priority'] ?? 'medium')) ?></span>
                                </div>
                                <span><?= htmlspecialchars($task['client_name'] ?: ($task['description'] ?: 'Workflow task')) ?></span>
                                <div class="task-meta">
                                    <small>Deadline: <?= htmlspecialchars($task['deadline']) ?></small>
                                    <small><?= htmlspecialchars($task['task_type'] ?: 'General') ?></small>
                                </div>
                                <div class="task-meta">
                                    <small>Current Dept: <?= htmlspecialchars($task['current_step_name'] ?? 'Department') ?></small>
                                    <small><?= htmlspecialchars($task['assigned_name'] ?? 'Unreserved') ?></small>
                                </div>
                                <div class="task-meta">
                                    <small>Total Points: <?= (int) ($task['points'] ?? 0) ?></small>
                                    <small>Step Points: <?= (int) ($task['current_step_points'] ?? 0) ?></small>
                                </div>
                                <div class="workflow-pill"><?= htmlspecialchars($task['status'] === 'completed' ? 'Final Completed' : ($task['current_step_name'] ?? 'Workflow Step')) ?></div>
                                <div class="task-card-actions">
                                    <?php if (!empty($task['can_reserve'])): ?>
                                        <button class="btn btn-light btn-small task-action-btn" type="button" data-task-action="reserve" data-task-id="<?= (int) $task['id'] ?>">Reserve Task</button>
                                    <?php endif; ?>
                                    <?php if (!empty($task['can_work'])): ?>
                                        <button class="btn btn-light btn-small task-action-btn" type="button" data-task-action="start" data-task-id="<?= (int) $task['id'] ?>">Start</button>
                                        <button class="btn btn-light btn-small task-action-btn" type="button" data-task-action="note" data-task-id="<?= (int) $task['id'] ?>">Add Note</button>
                                        <button class="btn btn-primary btn-small task-action-btn" type="button" data-task-action="complete-step" data-task-id="<?= (int) $task['id'] ?>">Complete Step</button>
                                    <?php endif; ?>
                                    <?php if (($task['board_status'] ?? '') === 'completed'): ?>
                                        <span class="badge">Completed</span>
                                    <?php endif; ?>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </article>

    <article class="panel">
        <div class="panel-head"><div><p class="eyebrow">Workflow progress</p><h3>Multi-step routing</h3></div></div>
        <?php foreach (array_slice($tasks, 0, 5) as $task): ?>
            <div class="workflow-track">
                <div><strong><?= htmlspecialchars($task['title']) ?></strong><span><?= htmlspecialchars($task['current_step_name'] ?? 'N/A') ?></span></div>
                <div class="progress-bar"><span style="width: <?= !empty($task['workflow_steps']) ? ((($task['current_step_index'] ?? 0) + (($task['status'] ?? '') === 'completed' ? 1 : 0)) / max(count($task['workflow_steps']), 1)) * 100 : 0 ?>%"></span></div>
            </div>
        <?php endforeach; ?>
    </article>
</section>

<div class="task-detail-modal" id="taskDetailModal" hidden>
    <div class="task-detail-backdrop" data-task-modal-close="true"></div>
    <div class="task-detail-panel">
        <div class="panel-head">
            <div><p class="eyebrow">Task timeline</p><h2 id="taskDetailTitle">Workflow task</h2></div>
            <button class="btn btn-light" type="button" data-task-modal-close="true">Close</button>
        </div>
        <div class="detail-grid task-detail-meta" id="taskDetailMeta"></div>
        <div class="task-timeline" id="taskDetailTimeline"></div>
    </div>
</div>

<template id="taskStepTemplate">
    <div class="task-step-row">
        <div class="task-step-order">Step 1</div>
        <label class="field task-step-field">
            <span>Department</span>
            <select class="task-step-select"></select>
        </label>
        <label class="field task-step-points-field">
            <span>Points</span>
            <input class="task-step-points" type="number" min="0" step="1" value="0">
        </label>
        <div class="task-step-actions">
            <button class="btn btn-light btn-small task-step-up" type="button">Up</button>
            <button class="btn btn-light btn-small task-step-down" type="button">Down</button>
            <button class="btn btn-light btn-small task-step-remove" type="button">Remove</button>
        </div>
    </div>
</template>
