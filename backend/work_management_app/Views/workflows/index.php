<section class="page-grid">
    <article class="panel">
        <div class="panel-head"><div><p class="eyebrow">Workflow engine</p><h2>Configured stages</h2></div></div>
        <?php foreach ($workflows as $workflow): ?>
            <div class="workflow-definition">
                <strong><?= htmlspecialchars($workflow['name']) ?></strong>
                <div class="workflow-stages">
                    <?php foreach ($workflow['stages'] as $stage): ?>
                        <span><?= htmlspecialchars(ucfirst($stage['role'])) ?></span>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </article>

    <article class="panel panel-span-2">
        <div class="panel-head"><div><p class="eyebrow">In-flight tasks</p><h2>Advance workflow stages</h2></div></div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Task</th><th>Assigned To</th><th>Status</th><th>Current Stage</th><th>Action</th></tr></thead>
                <tbody>
                <?php foreach ($tasks as $task): ?>
                    <tr>
                        <td><?= htmlspecialchars($task['title']) ?></td>
                        <td><?= htmlspecialchars($task['assigned_name'] ?? 'Unassigned') ?></td>
                        <td><span class="badge"><?= htmlspecialchars(ucwords(str_replace('_', ' ', $task['status']))) ?></span></td>
                        <td><?= htmlspecialchars($task['current_stage_role'] ?? 'N/A') ?></td>
                        <td><button class="btn btn-light workflow-advance" type="button" data-task-id="<?= (int) $task['id'] ?>">Advance</button></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </article>
</section>
