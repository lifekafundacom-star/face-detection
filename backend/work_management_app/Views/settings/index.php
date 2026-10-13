<?php $isAdminSettings = ($user['role'] ?? '') === 'admin'; ?>
<?php
$settingsSuccess = $_SESSION['settings_success'] ?? '';
$settingsError = $_SESSION['settings_error'] ?? '';
unset($_SESSION['settings_success'], $_SESSION['settings_error']);
?>

<section class="page-grid">
    <?php if ($settingsSuccess !== ''): ?>
    <article class="panel panel-span-2">
        <div class="alert alert-success"><?= htmlspecialchars($settingsSuccess) ?></div>
    </article>
    <?php endif; ?>

    <?php if ($settingsError !== ''): ?>
    <article class="panel panel-span-2">
        <div class="alert alert-error"><?= htmlspecialchars($settingsError) ?></div>
    </article>
    <?php endif; ?>

    <?php if ($isAdminSettings): ?>
    <article class="panel panel-span-2">
        <div class="panel-head">
            <div>
                <p class="eyebrow">Attendance Rules Settings</p>
                <h2>Configure office timings, grace, minimum work hours, half day, overtime, and break rules</h2>
            </div>
        </div>
        <form class="attendance-rules-form correction-grid" action="<?= htmlspecialchars(($basePath ?? '') . '/api/settings/attendance-rules') ?>" method="POST">
            <label class="field">
                <span>Office start time</span>
                <input type="time" name="office_start_time" value="<?= htmlspecialchars(substr((string) ($attendanceRules['office_start_time'] ?? '09:00:00'), 0, 5)) ?>">
            </label>
            <label class="field">
                <span>Grace time (minutes)</span>
                <input type="number" min="0" name="grace_minutes" value="<?= (int) ($attendanceRules['grace_minutes'] ?? 15) ?>">
            </label>
            <label class="field">
                <span>Minimum work hours</span>
                <input type="text" name="minimum_work_hours" value="<?= htmlspecialchars($attendanceRules['minimum_work_hours'] ?? '08:00:00') ?>" placeholder="08:00:00">
            </label>
            <label class="field">
                <span>Half day rule</span>
                <input type="text" name="half_day_hours" value="<?= htmlspecialchars($attendanceRules['half_day_hours'] ?? '04:00:00') ?>" placeholder="04:00:00">
            </label>
            <label class="field">
                <span>Standard duty hours</span>
                <input type="text" name="standard_duty_hours" value="<?= htmlspecialchars($attendanceRules['standard_duty_hours'] ?? '09:00:00') ?>" placeholder="09:00:00">
            </label>
            <label class="field">
                <span>Break Time</span>
                <input type="number" min="0" name="break_time" value="<?= (int) ($attendanceRules['break_time'] ?? 60) ?>">
            </label>
            <label class="field checkbox-field">
                <span>Enable overtime rules</span>
                <input type="checkbox" name="overtime_enabled" value="1" <?= !empty($attendanceRules['overtime_enabled']) ? 'checked' : '' ?>>
            </label>
            <div class="filter-actions">
                <button class="btn btn-primary" type="submit">Save Attendance Rules</button>
            </div>
        </form>
    </article>

    <article class="panel panel-span-2">
        <div class="panel-head">
            <div>
                <p class="eyebrow">Department Management</p>
                <h2>Add, edit, activate, deactivate, and control available departments</h2>
            </div>
        </div>
        <form class="department-form correction-grid" action="<?= htmlspecialchars(($basePath ?? '') . '/api/departments') ?>" method="POST">
            <label class="field">
                <span>Department name</span>
                <input type="text" name="name" placeholder="Operations" required>
            </label>
            <label class="field">
                <span>Department code</span>
                <input type="text" name="code" placeholder="OPS">
            </label>
            <label class="field">
                <span>Status</span>
                <select name="status">
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </label>
            <div class="filter-actions">
                <button class="btn btn-primary" type="submit">Add Department</button>
            </div>
        </form>

        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th>Department</th>
                    <th>Code</th>
                    <th>Status</th>
                    <th>Created</th>
                    <th>Action</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach (($departments ?? []) as $department): ?>
                    <tr>
                        <td><?= htmlspecialchars($department['name']) ?></td>
                        <td><?= htmlspecialchars($department['code'] ?? '--') ?></td>
                        <td><span class="badge status-<?= htmlspecialchars($department['status']) ?>"><?= htmlspecialchars(ucfirst($department['status'])) ?></span></td>
                        <td><?= htmlspecialchars($department['created_at']) ?></td>
                        <td class="action-row">
                            <button class="btn btn-primary btn-small users-panel-toggle" type="button" data-target="department-edit-<?= (int) $department['id'] ?>">Edit</button>
                            <button class="btn btn-danger btn-small department-delete-btn" type="button" data-action="<?= htmlspecialchars(($basePath ?? '') . '/api/departments/' . (int) $department['id'] . '/delete') ?>">Delete</button>
                        </td>
                    </tr>
                    <tr id="department-edit-<?= (int) $department['id'] ?>" class="attendance-edit-row" hidden>
                        <td colspan="5">
                            <form class="department-form correction-grid" action="<?= htmlspecialchars(($basePath ?? '') . '/api/departments/' . (int) $department['id'] . '/update') ?>" method="POST">
                                <label class="field">
                                    <span>Department name</span>
                                    <input type="text" name="name" value="<?= htmlspecialchars($department['name']) ?>" required>
                                </label>
                                <label class="field">
                                    <span>Department code</span>
                                    <input type="text" name="code" value="<?= htmlspecialchars($department['code'] ?? '') ?>">
                                </label>
                                <label class="field">
                                    <span>Status</span>
                                    <select name="status">
                                        <option value="active" <?= ($department['status'] ?? '') === 'active' ? 'selected' : '' ?>>Active</option>
                                        <option value="inactive" <?= ($department['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                                    </select>
                                </label>
                                <div class="filter-actions">
                                    <button class="btn btn-primary btn-small" type="submit">Save Department</button>
                                </div>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </article>

    <article class="panel panel-span-2">
        <div class="panel-head">
            <div>
                <p class="eyebrow">Awards Control</p>
                <h2>Set each dashboard award to auto-calculate or use an Admin-selected winner</h2>
            </div>
        </div>

        <div class="stack">
            <?php foreach (($awardSettings ?? []) as $awardSetting): ?>
                <form class="award-settings-form correction-grid" action="<?= htmlspecialchars(($basePath ?? '') . '/api/settings/awards') ?>" method="POST">
                    <input type="hidden" name="award_type" value="<?= htmlspecialchars($awardSetting['award_type']) ?>">
                    <input type="hidden" name="award_year" value="<?= (int) $awardSetting['award_year'] ?>">
                    <input type="hidden" name="award_month" value="<?= htmlspecialchars((string) ($awardSetting['award_month'] ?? '')) ?>">

                    <label class="field">
                        <span>Award type</span>
                        <input type="text" value="<?= htmlspecialchars($awardSetting['heading']) ?>" disabled>
                    </label>
                    <label class="field">
                        <span>Period</span>
                        <input type="text" value="<?= htmlspecialchars($awardSetting['period_label']) ?>" disabled>
                    </label>
                    <label class="field">
                        <span>Mode</span>
                        <?php if (!empty($awardSetting['locked_mode'])): ?>
                            <input type="hidden" name="display_mode" value="manual">
                            <input type="text" value="Manual Select" disabled>
                        <?php else: ?>
                            <select name="display_mode">
                                <option value="auto" <?= ($awardSetting['display_mode'] ?? 'auto') === 'auto' ? 'selected' : '' ?>>Auto Calculate</option>
                                <option value="manual" <?= ($awardSetting['display_mode'] ?? '') === 'manual' ? 'selected' : '' ?>>Manual Select</option>
                            </select>
                        <?php endif; ?>
                    </label>
                    <label class="field">
                        <span>Employee</span>
                        <select name="user_id">
                            <option value="">Select employee</option>
                            <?php foreach (($awardEmployeeOptions ?? []) as $employeeOption): ?>
                                <option value="<?= (int) $employeeOption['id'] ?>" <?= (int) ($awardSetting['selected_user_id'] ?? 0) === (int) $employeeOption['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($employeeOption['name'] . ' - ' . ($employeeOption['designation'] ?: 'Team Member')) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label class="field">
                        <span>Award title</span>
                        <input type="text" name="award_title" value="<?= htmlspecialchars($awardSetting['award_title'] ?? '') ?>" placeholder="Award title">
                    </label>
                    <label class="field field-span-2">
                        <span>Optional note</span>
                        <input type="text" name="award_note" value="<?= htmlspecialchars($awardSetting['note'] ?? '') ?>" placeholder="Optional note">
                    </label>
                    <div class="filter-actions">
                        <button class="btn btn-primary" type="submit">Save Award</button>
                    </div>
                </form>
            <?php endforeach; ?>
        </div>

        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th>Award</th>
                    <th>Period</th>
                    <th>Mode</th>
                    <th>Employee</th>
                    <th>Title</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach (($awardSelections ?? []) as $selection): ?>
                    <tr>
                        <td><?= htmlspecialchars(ucwords(str_replace('_', ' ', (string) $selection['award_type']))) ?></td>
                        <td>
                            <?= (int) $selection['award_year'] ?>
                            <?= !empty($selection['award_month']) ? ' / ' . htmlspecialchars(date('F', mktime(0, 0, 0, (int) $selection['award_month'], 1))) : '' ?>
                        </td>
                        <td><span class="badge"><?= htmlspecialchars(ucfirst((string) ($selection['display_mode'] ?? 'manual'))) ?></span></td>
                        <td><?= htmlspecialchars($selection['name'] ?: '--') ?></td>
                        <td><?= htmlspecialchars($selection['award_title'] ?: '--') ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </article>
    <?php endif; ?>

    <article class="panel">
        <div class="panel-head"><div><p class="eyebrow">Preferences</p><h2>Workspace settings</h2></div></div>
        <form class="stack">
            <label class="field"><span>Theme accent</span><input type="text" value="Sunrise gold"></label>
            <label class="field"><span>Timezone</span><input type="text" value="Asia/Calcutta"></label>
            <label class="field"><span>Default report range</span><input type="text" value="Monthly"></label>
            <button class="btn btn-primary" type="button">Save Preferences</button>
        </form>
    </article>

    <article class="panel">
        <div class="panel-head"><div><p class="eyebrow">Notifications</p><h2>Alert controls</h2></div></div>
        <div class="toggle-list">
            <div class="toggle-row"><span>Task assignment alerts</span><label class="switch"><input type="checkbox" checked><i></i></label></div>
            <div class="toggle-row"><span>Workflow handoff alerts</span><label class="switch"><input type="checkbox" checked><i></i></label></div>
            <div class="toggle-row"><span>Attendance reminders</span><label class="switch"><input type="checkbox"><i></i></label></div>
        </div>
    </article>
</section>
