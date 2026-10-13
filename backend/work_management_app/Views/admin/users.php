<?php
$customRoleOptions = $customRoleOptions ?? [];
$customRoleAssignments = $customRoleAssignments ?? [];
$systemRoleOptions = ['admin' => 'Admin', 'manager' => 'Manager', 'employee' => 'Employee'];
?>
<section class="stats-grid admin-stats-grid">
    <article class="stat-card"><span>Total Users</span><strong><?= count($users) ?></strong><small>Visible after filters</small></article>
    <article class="stat-card"><span>Active</span><strong><?= count(array_filter($users, static fn ($userRow) => ($userRow['status'] ?? '') === 'active')) ?></strong><small>Allowed to login</small></article>
    <article class="stat-card"><span>Managers</span><strong><?= count(array_filter($users, static fn ($userRow) => ($userRow['role'] ?? '') === 'manager')) ?></strong><small>Reporting owners</small></article>
    <article class="stat-card"><span>Employees</span><strong><?= count(array_filter($users, static fn ($userRow) => ($userRow['role'] ?? '') === 'employee')) ?></strong><small>Execution team</small></article>
</section>

<section class="panel filter-panel">
    <div class="panel-head">
        <div><p class="eyebrow">Admin</p><h2>Manage users</h2></div>
    </div>
    <form class="filter-grid" method="GET" action="<?= htmlspecialchars(($basePath ?? '') . '/admin/users') ?>">
        <label class="field"><span>Search</span><input type="text" name="search" value="<?= htmlspecialchars($userFilters['search'] ?? '') ?>" placeholder="Name, email, employee ID"></label>
        <label class="field">
            <span>Role</span>
            <select name="role">
                <option value="">All roles</option>
                <?php foreach (['admin', 'manager', 'employee'] as $role): ?>
                    <option value="<?= htmlspecialchars($role) ?>" <?= ($userFilters['role'] ?? '') === $role ? 'selected' : '' ?>><?= htmlspecialchars(ucfirst($role)) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label class="field">
            <span>Department</span>
            <select name="department">
                <option value="">All departments</option>
                <?php foreach (($departmentOptions ?? []) as $department): ?>
                    <option value="<?= htmlspecialchars($department['name']) ?>" <?= ($userFilters['department'] ?? '') === $department['name'] ? 'selected' : '' ?>><?= htmlspecialchars($department['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label class="field">
            <span>Status</span>
            <select name="status">
                <option value="">All statuses</option>
                <?php foreach (($statusOptions ?? []) as $status): ?>
                    <option value="<?= htmlspecialchars($status) ?>" <?= ($userFilters['status'] ?? '') === $status ? 'selected' : '' ?>><?= htmlspecialchars(ucfirst($status)) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label class="field"><span>Created date</span><input type="date" name="created_date" value="<?= htmlspecialchars($userFilters['created_date'] ?? '') ?>"></label>
        <label class="field">
            <span>Sort</span>
            <select name="sort">
                <option value="newest" <?= ($userFilters['sort'] ?? 'newest') === 'newest' ? 'selected' : '' ?>>Newest first</option>
                <option value="oldest" <?= ($userFilters['sort'] ?? '') === 'oldest' ? 'selected' : '' ?>>Oldest first</option>
                <option value="name_asc" <?= ($userFilters['sort'] ?? '') === 'name_asc' ? 'selected' : '' ?>>Name A-Z</option>
                <option value="department_asc" <?= ($userFilters['sort'] ?? '') === 'department_asc' ? 'selected' : '' ?>>Department wise</option>
            </select>
        </label>
        <div class="filter-actions">
            <button class="btn btn-primary" type="submit">Apply Filters</button>
            <a class="btn btn-light" href="<?= htmlspecialchars(($basePath ?? '') . '/admin/users') ?>">Reset</a>
            <button class="btn btn-success users-panel-toggle" type="button" data-target="create-user-panel">Create User</button>
        </div>
    </form>
</section>

<section class="panel" id="create-user-panel" hidden>
    <div class="panel-head">
        <div><p class="eyebrow">Create User</p><h2>Add a new team member</h2></div>
    </div>
    <form class="user-form correction-grid" action="<?= htmlspecialchars(($basePath ?? '') . '/api/users') ?>" method="POST">
        <label class="field"><span>User ID (Auto Generated)</span><input type="text" name="employee_code" value="<?= htmlspecialchars($nextUserCode ?? '') ?>" placeholder="Auto-generated" readonly style="background: #f8fafc; font-weight: 600;"></label>
        <label class="field"><span>Password</span><input type="password" name="password" required></label>
        <label class="field"><span>Confirm Password</span><input type="password" name="confirm_password" required></label>
        <label class="field"><span>Full Name</span><input type="text" name="name" required></label>
        <label class="field"><span>Email</span><input type="email" name="email" placeholder="Optional (defaults to user ID)"></label>
        <label class="field"><span>Mobile</span><input type="text" name="mobile"></label>
        <label class="field">
            <span>Role</span>
            <select class="user-role-select" name="menu_role_choice" data-default-system-role="employee">
                <optgroup label="System Roles">
                    <?php foreach ($systemRoleOptions as $roleValue => $roleLabel): ?>
                        <option value="system:<?= htmlspecialchars($roleValue) ?>" <?= $roleValue === 'employee' ? 'selected' : '' ?>><?= htmlspecialchars($roleLabel) ?></option>
                    <?php endforeach; ?>
                </optgroup>
                <?php if (!empty($customRoleOptions)): ?>
                    <optgroup label="Custom Roles">
                        <?php foreach ($customRoleOptions as $customRole): ?>
                            <option value="custom:<?= (int) $customRole['id'] ?>"><?= htmlspecialchars($customRole['name']) ?></option>
                        <?php endforeach; ?>
                    </optgroup>
                <?php endif; ?>
            </select>
            <input type="hidden" name="role" value="employee">
            <input type="hidden" name="custom_role_id" value="">
        </label>
        <label class="field">
            <span>Department</span>
            <select name="department">
                <option value="">Select department</option>
                <?php foreach (($departmentOptions ?? []) as $department): ?>
                    <option value="<?= htmlspecialchars($department['name']) ?>"><?= htmlspecialchars($department['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label class="field"><span>Designation</span><input type="text" name="designation" placeholder="Executive"></label>
        <label class="field"><span>Shift</span><input type="text" name="shift_name" value="General Shift"></label>
        <label class="field"><span>Shift Start</span><input type="time" name="shift_start" value="09:00"></label>
        <label class="field"><span>Shift End</span><input type="time" name="shift_end" value="18:00"></label>
        <label class="field">
            <span>Status</span>
            <select name="status">
                <?php foreach (($statusOptions ?? []) as $status): ?>
                    <option value="<?= htmlspecialchars($status) ?>" <?= $status === 'active' ? 'selected' : '' ?>><?= htmlspecialchars(ucfirst($status)) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label class="field"><span>Joining Date</span><input type="date" name="joining_date" value="<?= date('Y-m-d') ?>"></label>
        <label class="field">
            <span>Reporting Manager</span>
            <select name="manager_id">
                <option value="">No manager</option>
                <?php foreach (($managerOptions ?? []) as $manager): ?>
                    <option value="<?= (int) $manager['id'] ?>"><?= htmlspecialchars($manager['name']) ?><?= !empty($manager['department']) ? ' • ' . htmlspecialchars($manager['department']) : '' ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label class="field checkbox-field"><span>Force password change</span><input type="checkbox" name="force_password_change" value="1"></label>
        
        <!-- Face Scan Integration -->
        <div class="field face-scan-section" style="grid-column: 1 / -1; margin-top: 15px; border-top: 1px solid var(--border); padding-top: 15px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                <span style="font-weight: 600; font-size: 0.95rem;"><i class="fa-solid fa-face-smile" style="color: var(--primary); margin-right: 6px;"></i> Face Recognition Reference</span>
                <span class="badge" style="background: rgba(59,130,246,0.12); color: #3b82f6; font-size: 0.75rem; padding: 2px 8px; border-radius: 4px;">HD Multi-Angle Support</span>
            </div>

            <div class="face-scan-widget">
                <input type="hidden" name="face_reference_descriptor" class="form-face-descriptor">
                <input type="hidden" name="captured_face_reference" class="form-captured-image">
                <input type="file" class="face-file-input" accept="image/jpeg,image/png,image/webp" style="display: none;">

                <!-- Camera & Source Toolbar -->
                <div class="face-widget-toolbar" style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap; margin-bottom: 12px; background: rgba(255,255,255,0.02); padding: 8px 12px; border-radius: 8px; border: 1px solid var(--border);">
                    <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                        <label style="font-size: 0.8rem; color: var(--text-muted); margin: 0;"><i class="fa-solid fa-video"></i> Camera:</label>
                        <select class="camera-device-select" style="min-width: 190px; max-width: 270px; font-size: 0.82rem; padding: 4px 8px; border-radius: 5px; border: 1px solid var(--border); background: var(--bg-card, #1c1c24); color: var(--text);">
                            <option value="">Detecting cameras...</option>
                        </select>
                        <button type="button" class="btn btn-outline btn-small switch-camera-btn" title="Switch to next camera" style="padding: 4px 8px; font-size: 0.78rem;"><i class="fa-solid fa-arrows-rotate"></i> Switch Camera</button>
                    </div>

                    <div style="display: flex; gap: 8px; margin-left: auto;">
                        <button type="button" class="btn btn-accent btn-small start-widget-webcam"><i class="fa-solid fa-camera"></i> Open Webcam</button>
                        <button type="button" class="btn btn-secondary btn-small trigger-upload-btn"><i class="fa-solid fa-cloud-arrow-up"></i> Upload Photo File</button>
                    </div>
                </div>

                <!-- OBS Virtual Camera Warning Banner -->
                <div class="obs-warning-banner" style="display: none; margin-bottom: 12px; background: rgba(239, 68, 68, 0.15); border: 1px solid rgba(239, 68, 68, 0.4); border-radius: 6px; padding: 8px 12px; font-size: 0.82rem; color: #fca5a5; align-items: center; justify-content: space-between; gap: 10px;">
                    <span><i class="fa-solid fa-triangle-exclamation"></i> <strong>OBS Virtual Camera is selected</strong> (Inactive logo screen). Switch to your real webcam.</span>
                    <button type="button" class="btn btn-warning btn-small auto-switch-real-cam" style="padding: 3px 8px; font-size: 0.78rem; white-space: nowrap;"><i class="fa-solid fa-arrows-rotate"></i> Switch to Real Webcam</button>
                </div>

                <div class="face-scan-row" style="display: flex; gap: 20px; align-items: flex-start; flex-wrap: wrap;">
                    <!-- Webcam view container -->
                    <div class="webcam-box-container" style="display: none; position: relative; width: 340px; height: 255px; background: #000; border-radius: 8px; overflow: hidden; border: 2px solid var(--primary); box-shadow: 0 4px 15px rgba(0,0,0,0.3);">
                        <video class="webcam-feed-preview" style="width: 100%; height: 100%; object-fit: cover;" autoplay muted playsinline></video>
                        <canvas class="webcam-overlay-canvas" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; pointer-events: none;"></canvas>
                        <!-- Oval framing guide -->
                        <div class="face-guide-oval" style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); width: 170px; height: 215px; border: 2px dashed rgba(255,255,255,0.45); border-radius: 50%; pointer-events: none; transition: border-color 0.2s;"></div>
                        
                        <!-- Direction Arrow Indicator Overlay -->
                        <div class="webcam-direction-overlay" style="display: none; position: absolute; inset: 0; pointer-events: none; z-index: 5;">
                            <!-- Direction Badge Top -->
                            <div class="direction-badge" style="position: absolute; top: 8px; left: 50%; transform: translateX(-50%); background: rgba(15, 23, 42, 0.92); backdrop-filter: blur(4px); border: 1px solid rgba(255,255,255,0.25); color: #fff; font-weight: 600; font-size: 0.78rem; padding: 4px 12px; border-radius: 20px; box-shadow: 0 4px 12px rgba(0,0,0,0.5); display: flex; align-items: center; gap: 6px; white-space: nowrap;">
                                <span class="direction-step-tag" style="background: var(--primary, #3b82f6); color: #fff; font-size: 0.68rem; padding: 2px 7px; border-radius: 10px; font-weight: 700;">Step 1/3</span>
                                <span class="direction-text">Look Straight Ahead</span>
                            </div>

                            <!-- Left Arrow prompt -->
                            <div class="direction-arrow-prompt prompt-left" style="display: none; position: absolute; top: 48%; left: 10px; transform: translateY(-50%);">
                                <div class="arrow-bubble-pulse arrow-bubble-left">
                                    <i class="fa-solid fa-arrow-left" style="font-size: 2rem;"></i>
                                    <span style="font-size: 0.72rem; font-weight: 700; text-transform: uppercase; margin-top: 4px; letter-spacing: 0.5px;">Turn Left</span>
                                </div>
                            </div>

                            <!-- Right Arrow prompt -->
                            <div class="direction-arrow-prompt prompt-right" style="display: none; position: absolute; top: 48%; right: 10px; transform: translateY(-50%);">
                                <div class="arrow-bubble-pulse arrow-bubble-right">
                                    <i class="fa-solid fa-arrow-right" style="font-size: 2rem;"></i>
                                    <span style="font-size: 0.72rem; font-weight: 700; text-transform: uppercase; margin-top: 4px; letter-spacing: 0.5px;">Turn Right</span>
                                </div>
                            </div>

                            <!-- Front prompt -->
                            <div class="direction-arrow-prompt prompt-front" style="display: none; position: absolute; top: 48%; left: 50%; transform: translate(-50%, -50%);">
                                <div class="front-target-pulse">
                                    <i class="fa-solid fa-arrows-to-dot" style="font-size: 2rem;"></i>
                                    <span style="font-size: 0.72rem; font-weight: 700; text-transform: uppercase; margin-top: 4px; letter-spacing: 0.5px;">Look Straight</span>
                                </div>
                            </div>
                        </div>

                        <div class="webcam-angle-instruction" style="position: absolute; bottom: 8px; left: 8px; right: 8px; background: rgba(0,0,0,0.85); color: #fff; font-size: 0.78rem; text-align: center; padding: 5px 8px; border-radius: 4px; pointer-events: none; border: 1px solid rgba(255,255,255,0.15);">Center face in oval</div>
                    </div>

                    <!-- Saved thumbnail preview -->
                    <div class="captured-snapshot-preview" style="display: none; flex-direction: column; align-items: center; gap: 8px;">
                        <div style="width: 130px; height: 130px; border-radius: 8px; overflow: hidden; border: 2px solid #10b981; background: #222; box-shadow: 0 4px 12px rgba(16,185,129,0.25);">
                            <img class="captured-snapshot-img" style="width: 100%; height: 100%; object-fit: cover;" src="">
                        </div>
                        <span class="badge face-captured-badge" style="background: rgba(16,185,129,0.15); color: #10b981; font-size: 0.75rem; padding: 2px 8px; border-radius: 4px;"><i class="fa-solid fa-circle-check"></i> Face Saved</span>
                    </div>

                    <div class="face-scan-controls" style="display: flex; flex-direction: column; gap: 10px; justify-content: center; min-height: 120px; flex: 1;">
                        <div class="action-btn-group" style="display: flex; gap: 8px; flex-wrap: wrap;">
                            <button type="button" class="btn btn-primary btn-small capture-single-face" style="display: none;"><i class="fa-solid fa-camera"></i> Capture Face (Front)</button>
                            <button type="button" class="btn btn-accent btn-small capture-multi-face" style="display: none;"><i class="fa-solid fa-layer-group"></i> Multi-Angle Scan (3 Angles)</button>
                            
                            <!-- Interactive Multi-Step Scan Buttons (Manual click next next) -->
                            <button type="button" class="btn btn-primary btn-small multi-step-capture-btn" style="display: none;"><i class="fa-solid fa-camera"></i> <span class="step-btn-text">Capture Front Face (1/3)</span></button>
                            <button type="button" class="btn btn-secondary btn-small cancel-multi-scan" style="display: none;"><i class="fa-solid fa-ban"></i> Cancel Scan</button>

                            <button type="button" class="btn btn-secondary btn-small retake-widget-face" style="display: none;"><i class="fa-solid fa-rotate"></i> Retake / Clear</button>
                            <button type="button" class="btn btn-light btn-small close-widget-cam" style="display: none;"><i class="fa-solid fa-xmark"></i> Close Camera</button>
                        </div>

                        <!-- Multi-angle progress indicators -->
                        <div class="multi-angle-progress" style="display: none; align-items: center; gap: 8px; background: rgba(255,255,255,0.03); padding: 8px 12px; border-radius: 6px; border: 1px solid var(--border); max-width: 440px; flex-wrap: wrap;">
                            <div class="angle-step step-front" style="font-size: 0.75rem; color: var(--text-muted); display: flex; align-items: center; gap: 4px;"><i class="fa-regular fa-circle"></i> 1. Front</div>
                            <div style="color: var(--border);">→</div>
                            <div class="angle-step step-left" style="font-size: 0.75rem; color: var(--text-muted); display: flex; align-items: center; gap: 4px;"><i class="fa-regular fa-circle"></i> 2. Left</div>
                            <div style="color: var(--border);">→</div>
                            <div class="angle-step step-right" style="font-size: 0.75rem; color: var(--text-muted); display: flex; align-items: center; gap: 4px;"><i class="fa-regular fa-circle"></i> 3. Right</div>
                        </div>

                        <span class="face-widget-status-msg" style="font-size: 0.85rem; color: var(--text-muted);">Webcam inactive. Open camera or upload a clear photo.</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="filter-actions">
            <button class="btn btn-primary" type="submit">Create User</button>
        </div>
    </form>
</section>

<section class="panel">
    <div class="panel-head">
        <div><p class="eyebrow">Users Directory</p><h2>Search, filter, update, and control access</h2></div>
    </div>

    <form class="bulk-users-form bulk-bar" action="<?= htmlspecialchars(($basePath ?? '') . '/api/users/bulk') ?>" method="POST">
        <label class="field">
            <span>Bulk Action</span>
            <select name="action">
                <option value="activate">Bulk activate</option>
                <option value="deactivate">Bulk deactivate</option>
                <option value="delete">Bulk deactivate/delete</option>
                <option value="assign_department">Bulk department assign</option>
                <option value="assign_shift">Bulk shift assign</option>
            </select>
        </label>
        <label class="field">
            <span>Department</span>
            <select name="department">
                <option value="">Select department</option>
                <?php foreach (($departmentOptions ?? []) as $department): ?>
                    <option value="<?= htmlspecialchars($department['name']) ?>"><?= htmlspecialchars($department['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label class="field"><span>Shift Name</span><input type="text" name="shift_name" placeholder="For shift assignment"></label>
        <label class="field"><span>Shift Start</span><input type="time" name="shift_start" value="09:00"></label>
        <label class="field"><span>Shift End</span><input type="time" name="shift_end" value="18:00"></label>
        <div class="filter-actions">
            <button class="btn btn-warning" type="submit">Apply Bulk Action</button>
        </div>
    </form>

    <div class="table-wrap">
        <table>
            <thead>
            <tr>
                <th><input type="checkbox" id="selectAllUsers"></th>
                <th>User ID</th>
                <th>Name</th>
                <th>Role</th>
                <th>Department</th>
                <th>Status</th>
                <th>Created Date</th>
                <th>Action</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($users as $member): ?>
                <?php
                $userCode = !empty($member['employee_code'])
                    ? $member['employee_code']
                    : ('EMP-' . str_pad((string) $member['id'], 4, '0', STR_PAD_LEFT));
                ?>
                <tr>
                    <td><input type="checkbox" class="bulk-user-checkbox" value="<?= (int) $member['id'] ?>"></td>
                    <td><strong><?= htmlspecialchars($userCode) ?></strong></td>
                    <td><?= htmlspecialchars($member['name']) ?></td>
                    <td><span class="badge"><?= htmlspecialchars($customRoleAssignments[(int) $member['id']]['role_name'] ?? ucfirst($member['role'])) ?></span></td>
                    <td><?= htmlspecialchars($member['department'] ?? 'Unassigned') ?></td>
                    <td><span class="badge status-<?= htmlspecialchars($member['status'] ?? 'active') ?>"><?= htmlspecialchars(ucfirst($member['status'] ?? 'active')) ?></span></td>
                    <td><?= htmlspecialchars($member['created_at']) ?></td>
                    <td class="action-row">
                        <button class="btn btn-light btn-small users-panel-toggle" type="button" data-target="user-detail-<?= (int) $member['id'] ?>">View</button>
                        <button class="btn btn-primary btn-small users-panel-toggle" type="button" data-target="user-edit-<?= (int) $member['id'] ?>">Edit</button>
                        <button class="btn btn-warning btn-small users-panel-toggle" type="button" data-target="user-reset-<?= (int) $member['id'] ?>">Reset Password</button>
                    </td>
                </tr>
                <tr id="user-detail-<?= (int) $member['id'] ?>" class="attendance-edit-row" hidden>
                    <td colspan="8">
                        <div class="detail-grid users-detail-grid">
                            <div><span>User ID</span><strong><?= htmlspecialchars($userCode) ?></strong></div>
                            <div><span>Name</span><strong><?= htmlspecialchars($member['name']) ?></strong></div>
                            <div><span>Email</span><strong><?= htmlspecialchars($member['email']) ?></strong></div>
                            <div><span>Mobile</span><strong><?= htmlspecialchars($member['mobile'] ?? '--') ?></strong></div>
                            <div><span>Role</span><strong><?= htmlspecialchars($customRoleAssignments[(int) $member['id']]['role_name'] ?? ucfirst($member['role'])) ?></strong></div>
                            <div><span>Department</span><strong><?= htmlspecialchars($member['department'] ?? 'Unassigned') ?></strong></div>
                            <div><span>Designation</span><strong><?= htmlspecialchars($member['designation'] ?? '--') ?></strong></div>
                            <div><span>Shift</span><strong><?= htmlspecialchars($member['shift_name'] ?? 'General Shift') ?><?= !empty($member['shift_start']) && !empty($member['shift_end']) ? ' (' . htmlspecialchars(substr($member['shift_start'], 0, 5)) . ' - ' . htmlspecialchars(substr($member['shift_end'], 0, 5)) . ')' : '' ?></strong></div>
                            <div><span>Joining date</span><strong><?= htmlspecialchars($member['joining_date'] ?? '--') ?></strong></div>
                            <div><span>Status</span><strong><?= htmlspecialchars(ucfirst($member['status'] ?? 'active')) ?></strong></div>
                            <div><span>Reporting Manager</span><strong><?= htmlspecialchars($member['manager_name'] ?? 'Not assigned') ?></strong></div>
                            <div><span>Created at</span><strong><?= htmlspecialchars($member['created_at']) ?></strong></div>
                        </div>
                    </td>
                </tr>
                <tr id="user-edit-<?= (int) $member['id'] ?>" class="attendance-edit-row" hidden>
                    <td colspan="8">
                        <form class="user-form correction-grid" action="<?= htmlspecialchars(($basePath ?? '') . '/api/users/' . (int) $member['id'] . '/update') ?>" method="POST">
                            <label class="field"><span>User ID</span><input type="text" name="employee_code" value="<?= htmlspecialchars($userCode) ?>"></label>
                            <label class="field"><span>Full Name</span><input type="text" name="name" value="<?= htmlspecialchars($member['name']) ?>"></label>
                            <label class="field"><span>Email</span><input type="email" name="email" value="<?= htmlspecialchars($member['email']) ?>"></label>
                            <label class="field"><span>New Password</span><input type="password" name="password" placeholder="Leave blank to keep current"></label>
                            <label class="field"><span>Confirm Password</span><input type="password" name="confirm_password" placeholder="Leave blank to keep current"></label>
                            <label class="field"><span>Mobile</span><input type="text" name="mobile" value="<?= htmlspecialchars($member['mobile'] ?? '') ?>"></label>
                            <label class="field">
                                <span>Role</span>
                                <?php
                                $assignedCustomRoleId = (int) ($customRoleAssignments[(int) $member['id']]['role_id'] ?? 0);
                                $selectedRoleValue = $assignedCustomRoleId > 0 ? 'custom:' . $assignedCustomRoleId : 'system:' . ($member['role'] ?? 'employee');
                                ?>
                                <select class="user-role-select" name="menu_role_choice" data-default-system-role="<?= htmlspecialchars(in_array(($member['role'] ?? ''), ['manager', 'employee'], true) ? $member['role'] : 'employee') ?>">
                                    <optgroup label="System Roles">
                                        <?php foreach ($systemRoleOptions as $roleValue => $roleLabel): ?>
                                            <option value="system:<?= htmlspecialchars($roleValue) ?>" <?= $selectedRoleValue === 'system:' . $roleValue ? 'selected' : '' ?>><?= htmlspecialchars($roleLabel) ?></option>
                                        <?php endforeach; ?>
                                    </optgroup>
                                    <?php if (!empty($customRoleOptions)): ?>
                                        <optgroup label="Custom Roles">
                                            <?php foreach ($customRoleOptions as $customRole): ?>
                                                <option value="custom:<?= (int) $customRole['id'] ?>" <?= $selectedRoleValue === 'custom:' . (int) $customRole['id'] ? 'selected' : '' ?>><?= htmlspecialchars($customRole['name']) ?></option>
                                            <?php endforeach; ?>
                                        </optgroup>
                                    <?php endif; ?>
                                </select>
                                <input type="hidden" name="role" value="<?= htmlspecialchars($member['role'] ?? 'employee') ?>">
                                <input type="hidden" name="custom_role_id" value="<?= $assignedCustomRoleId > 0 ? (int) $assignedCustomRoleId : '' ?>">
                            </label>
                            <label class="field">
                                <span>Department</span>
                                <select name="department">
                                    <option value="">Select department</option>
                                    <?php foreach (($departmentOptions ?? []) as $department): ?>
                                        <option value="<?= htmlspecialchars($department['name']) ?>" <?= ($member['department'] ?? '') === $department['name'] ? 'selected' : '' ?>><?= htmlspecialchars($department['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </label>
                            <label class="field"><span>Designation</span><input type="text" name="designation" value="<?= htmlspecialchars($member['designation'] ?? '') ?>"></label>
                            <label class="field"><span>Shift</span><input type="text" name="shift_name" value="<?= htmlspecialchars($member['shift_name'] ?? 'General Shift') ?>"></label>
                            <label class="field"><span>Shift Start</span><input type="time" name="shift_start" value="<?= htmlspecialchars(substr((string) ($member['shift_start'] ?? '09:00:00'), 0, 5)) ?>"></label>
                            <label class="field"><span>Shift End</span><input type="time" name="shift_end" value="<?= htmlspecialchars(substr((string) ($member['shift_end'] ?? '18:00:00'), 0, 5)) ?>"></label>
                            <label class="field">
                                <span>Status</span>
                                <select name="status">
                                    <?php foreach (($statusOptions ?? []) as $status): ?>
                                        <option value="<?= htmlspecialchars($status) ?>" <?= ($member['status'] ?? '') === $status ? 'selected' : '' ?>><?= htmlspecialchars(ucfirst($status)) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </label>
                            <label class="field"><span>Joining Date</span><input type="date" name="joining_date" value="<?= htmlspecialchars(substr((string) ($member['joining_date'] ?? date('Y-m-d')), 0, 10)) ?>"></label>
                            <label class="field">
                                <span>Reporting Manager</span>
                                <select name="manager_id">
                                    <option value="">No manager</option>
                                    <?php foreach (($managerOptions ?? []) as $manager): ?>
                                        <option value="<?= (int) $manager['id'] ?>" <?= (int) ($member['manager_id'] ?? 0) === (int) $manager['id'] ? 'selected' : '' ?>><?= htmlspecialchars($manager['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </label>
                             <label class="field checkbox-field"><span>Force password change</span><input type="checkbox" name="force_password_change" value="1" <?= !empty($member['force_password_change']) ? 'checked' : '' ?>></label>

                             <!-- Face Scan Integration -->
                             <div class="field face-scan-section" style="grid-column: 1 / -1; margin-top: 15px; border-top: 1px solid var(--border); padding-top: 15px;">
                                 <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                                     <span style="font-weight: 600; font-size: 0.95rem;"><i class="fa-solid fa-face-smile" style="color: var(--primary); margin-right: 6px;"></i> Face Recognition Reference</span>
                                     <span class="badge" style="background: rgba(59,130,246,0.12); color: #3b82f6; font-size: 0.75rem; padding: 2px 8px; border-radius: 4px;">HD Multi-Angle Support</span>
                                 </div>

                                 <div class="face-scan-widget">
                                     <input type="hidden" name="face_reference_descriptor" class="form-face-descriptor">
                                     <input type="hidden" name="captured_face_reference" class="form-captured-image">
                                     <input type="file" class="face-file-input" accept="image/jpeg,image/png,image/webp" style="display: none;">

                                     <!-- Camera & Source Toolbar -->
                                     <div class="face-widget-toolbar" style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap; margin-bottom: 12px; background: rgba(255,255,255,0.02); padding: 8px 12px; border-radius: 8px; border: 1px solid var(--border);">
                                         <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                                             <label style="font-size: 0.8rem; color: var(--text-muted); margin: 0;"><i class="fa-solid fa-video"></i> Camera:</label>
                                             <select class="camera-device-select" style="min-width: 190px; max-width: 270px; font-size: 0.82rem; padding: 4px 8px; border-radius: 5px; border: 1px solid var(--border); background: var(--bg-card, #1c1c24); color: var(--text);">
                                                 <option value="">Detecting cameras...</option>
                                             </select>
                                             <button type="button" class="btn btn-outline btn-small switch-camera-btn" title="Switch to next camera" style="padding: 4px 8px; font-size: 0.78rem;"><i class="fa-solid fa-arrows-rotate"></i> Switch Camera</button>
                                         </div>

                                         <div style="display: flex; gap: 8px; margin-left: auto;">
                                             <button type="button" class="btn btn-accent btn-small start-widget-webcam"><i class="fa-solid fa-camera"></i> Open Webcam</button>
                                             <button type="button" class="btn btn-secondary btn-small trigger-upload-btn"><i class="fa-solid fa-cloud-arrow-up"></i> Upload Photo File</button>
                                         </div>
                                     </div>

                                     <!-- OBS Virtual Camera Warning Banner -->
                                     <div class="obs-warning-banner" style="display: none; margin-bottom: 12px; background: rgba(239, 68, 68, 0.15); border: 1px solid rgba(239, 68, 68, 0.4); border-radius: 6px; padding: 8px 12px; font-size: 0.82rem; color: #fca5a5; align-items: center; justify-content: space-between; gap: 10px;">
                                         <span><i class="fa-solid fa-triangle-exclamation"></i> <strong>OBS Virtual Camera is selected</strong> (Inactive logo screen). Switch to your real webcam.</span>
                                         <button type="button" class="btn btn-warning btn-small auto-switch-real-cam" style="padding: 3px 8px; font-size: 0.78rem; white-space: nowrap;"><i class="fa-solid fa-arrows-rotate"></i> Switch to Real Webcam</button>
                                     </div>

                                     <div class="face-scan-row" style="display: flex; gap: 20px; align-items: flex-start; flex-wrap: wrap;">
                                         <!-- Webcam view container -->
                                         <div class="webcam-box-container" style="display: none; position: relative; width: 340px; height: 255px; background: #000; border-radius: 8px; overflow: hidden; border: 2px solid var(--primary); box-shadow: 0 4px 15px rgba(0,0,0,0.3);">
                                             <video class="webcam-feed-preview" style="width: 100%; height: 100%; object-fit: cover;" autoplay muted playsinline></video>
                                             <canvas class="webcam-overlay-canvas" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; pointer-events: none;"></canvas>
                                             <!-- Oval framing guide -->
                                             <div class="face-guide-oval" style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); width: 170px; height: 215px; border: 2px dashed rgba(255,255,255,0.45); border-radius: 50%; pointer-events: none; transition: border-color 0.2s;"></div>

                                             <!-- Direction Arrow Indicator Overlay -->
                                             <div class="webcam-direction-overlay" style="display: none; position: absolute; inset: 0; pointer-events: none; z-index: 5;">
                                                 <!-- Direction Badge Top -->
                                                 <div class="direction-badge" style="position: absolute; top: 8px; left: 50%; transform: translateX(-50%); background: rgba(15, 23, 42, 0.92); backdrop-filter: blur(4px); border: 1px solid rgba(255,255,255,0.25); color: #fff; font-weight: 600; font-size: 0.78rem; padding: 4px 12px; border-radius: 20px; box-shadow: 0 4px 12px rgba(0,0,0,0.5); display: flex; align-items: center; gap: 6px; white-space: nowrap;">
                                                     <span class="direction-step-tag" style="background: var(--primary, #3b82f6); color: #fff; font-size: 0.68rem; padding: 2px 7px; border-radius: 10px; font-weight: 700;">Step 1/3</span>
                                                     <span class="direction-text">Look Straight Ahead</span>
                                                 </div>

                                                 <!-- Left Arrow prompt -->
                                                 <div class="direction-arrow-prompt prompt-left" style="display: none; position: absolute; top: 48%; left: 10px; transform: translateY(-50%);">
                                                     <div class="arrow-bubble-pulse arrow-bubble-left">
                                                         <i class="fa-solid fa-arrow-left" style="font-size: 2rem;"></i>
                                                         <span style="font-size: 0.72rem; font-weight: 700; text-transform: uppercase; margin-top: 4px; letter-spacing: 0.5px;">Turn Left</span>
                                                     </div>
                                                 </div>

                                                 <!-- Right Arrow prompt -->
                                                 <div class="direction-arrow-prompt prompt-right" style="display: none; position: absolute; top: 48%; right: 10px; transform: translateY(-50%);">
                                                     <div class="arrow-bubble-pulse arrow-bubble-right">
                                                         <i class="fa-solid fa-arrow-right" style="font-size: 2rem;"></i>
                                                         <span style="font-size: 0.72rem; font-weight: 700; text-transform: uppercase; margin-top: 4px; letter-spacing: 0.5px;">Turn Right</span>
                                                     </div>
                                                 </div>

                                                 <!-- Front prompt -->
                                                 <div class="direction-arrow-prompt prompt-front" style="display: none; position: absolute; top: 48%; left: 50%; transform: translate(-50%, -50%);">
                                                     <div class="front-target-pulse">
                                                         <i class="fa-solid fa-arrows-to-dot" style="font-size: 2rem;"></i>
                                                         <span style="font-size: 0.72rem; font-weight: 700; text-transform: uppercase; margin-top: 4px; letter-spacing: 0.5px;">Look Straight</span>
                                                     </div>
                                                 </div>
                                             </div>

                                             <div class="webcam-angle-instruction" style="position: absolute; bottom: 8px; left: 8px; right: 8px; background: rgba(0,0,0,0.85); color: #fff; font-size: 0.78rem; text-align: center; padding: 5px 8px; border-radius: 4px; pointer-events: none; border: 1px solid rgba(255,255,255,0.15);">Center face in oval</div>
                                         </div>

                                         <!-- Saved thumbnail preview -->
                                         <div class="captured-snapshot-preview" style="display: none; flex-direction: column; align-items: center; gap: 8px;">
                                             <div style="width: 130px; height: 130px; border-radius: 8px; overflow: hidden; border: 2px solid #10b981; background: #222; box-shadow: 0 4px 12px rgba(16,185,129,0.25);">
                                                 <img class="captured-snapshot-img" style="width: 100%; height: 100%; object-fit: cover;" src="">
                                             </div>
                                             <span class="badge face-captured-badge" style="background: rgba(16,185,129,0.15); color: #10b981; font-size: 0.75rem; padding: 2px 8px; border-radius: 4px;"><i class="fa-solid fa-circle-check"></i> Face Saved</span>
                                         </div>

                                         <!-- Show existing image if available -->
                                         <?php if (!empty($member['face_reference_image'])): ?>
                                             <div class="existing-snapshot-preview" style="display: flex; flex-direction: column; align-items: center; gap: 8px;">
                                                 <div style="width: 130px; height: 130px; border-radius: 8px; overflow: hidden; border: 1px solid var(--border); background: #222;">
                                                     <img class="lazy-face-image" style="width: 100%; height: 100%; object-fit: cover;" src="data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='1' height='1'></svg>" data-src="<?= htmlspecialchars($basePath . $member['face_reference_image']) ?>">
                                                 </div>
                                                 <small style="color: var(--text-muted); font-size: 0.78rem;">Current Reference</small>
                                             </div>
                                         <?php endif; ?>

                                         <div class="face-scan-controls" style="display: flex; flex-direction: column; gap: 10px; justify-content: center; min-height: 120px; flex: 1;">
                                             <div class="action-btn-group" style="display: flex; gap: 8px; flex-wrap: wrap;">
                                                 <button type="button" class="btn btn-primary btn-small capture-single-face" style="display: none;"><i class="fa-solid fa-camera"></i> Capture Face (Front)</button>
                                                 <button type="button" class="btn btn-accent btn-small capture-multi-face" style="display: none;"><i class="fa-solid fa-layer-group"></i> Multi-Angle Scan (3 Angles)</button>
                                                 
                                                 <!-- Interactive Multi-Step Scan Buttons (Manual click next next) -->
                                                 <button type="button" class="btn btn-primary btn-small multi-step-capture-btn" style="display: none;"><i class="fa-solid fa-camera"></i> <span class="step-btn-text">Capture Front Face (1/3)</span></button>
                                                 <button type="button" class="btn btn-secondary btn-small cancel-multi-scan" style="display: none;"><i class="fa-solid fa-ban"></i> Cancel Scan</button>

                                                 <button type="button" class="btn btn-secondary btn-small retake-widget-face" style="display: none;"><i class="fa-solid fa-rotate"></i> Retake / Clear</button>
                                                 <button type="button" class="btn btn-light btn-small close-widget-cam" style="display: none;"><i class="fa-solid fa-xmark"></i> Close Camera</button>
                                             </div>

                                             <!-- Multi-angle progress indicators -->
                                             <div class="multi-angle-progress" style="display: none; align-items: center; gap: 8px; background: rgba(255,255,255,0.03); padding: 8px 12px; border-radius: 6px; border: 1px solid var(--border); max-width: 440px; flex-wrap: wrap;">
                                                 <div class="angle-step step-front" style="font-size: 0.75rem; color: var(--text-muted); display: flex; align-items: center; gap: 4px;"><i class="fa-regular fa-circle"></i> 1. Front</div>
                                                 <div style="color: var(--border);">→</div>
                                                 <div class="angle-step step-left" style="font-size: 0.75rem; color: var(--text-muted); display: flex; align-items: center; gap: 4px;"><i class="fa-regular fa-circle"></i> 2. Left</div>
                                                 <div style="color: var(--border);">→</div>
                                                 <div class="angle-step step-right" style="font-size: 0.75rem; color: var(--text-muted); display: flex; align-items: center; gap: 4px;"><i class="fa-regular fa-circle"></i> 3. Right</div>
                                             </div>

                                             <span class="face-widget-status-msg" style="font-size: 0.85rem; color: var(--text-muted);">Webcam inactive. Open camera or upload a clear photo.</span>
                                         </div>
                                     </div>
                                 </div>
                             </div>

                             <div class="filter-actions">
                                 <button class="btn btn-primary btn-small" type="submit">Save User</button>
                                 <button class="btn btn-danger btn-small user-delete-btn" type="button" data-action="<?= htmlspecialchars(($basePath ?? '') . '/api/users/' . (int) $member['id'] . '/delete') ?>">Deactivate</button>
                             </div>
                        </form>
                    </td>
                </tr>
                <tr id="user-reset-<?= (int) $member['id'] ?>" class="attendance-edit-row" hidden>
                    <td colspan="8">
                        <form class="user-password-reset-form correction-grid" action="<?= htmlspecialchars(($basePath ?? '') . '/api/users/' . (int) $member['id'] . '/reset-password') ?>" method="POST">
                            <label class="field"><span>New Password</span><input type="text" name="password" placeholder="Leave blank for Temp@1234"></label>
                            <label class="field checkbox-field"><span>Force change on next login</span><input type="checkbox" name="force_password_change" value="1" checked></label>
                            <div class="filter-actions">
                                <button class="btn btn-warning btn-small" type="submit">Reset Password</button>
                            </div>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<style>
/* Modern Face Direction Guides & Multi-Angle UI */
.webcam-direction-overlay {
    transition: opacity 0.25s ease-in-out;
}
.arrow-bubble-pulse {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    background: rgba(16, 185, 129, 0.95);
    color: #ffffff;
    padding: 8px 14px;
    border-radius: 12px;
    box-shadow: 0 0 20px rgba(16, 185, 129, 0.7), 0 4px 12px rgba(0,0,0,0.6);
    border: 2px solid #ffffff;
    pointer-events: none;
    user-select: none;
}
.arrow-bubble-left {
    animation: arrow-bounce-left 0.8s infinite alternate ease-in-out;
}
.arrow-bubble-right {
    animation: arrow-bounce-right 0.8s infinite alternate ease-in-out;
}
.front-target-pulse {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    background: rgba(59, 130, 246, 0.95);
    color: #ffffff;
    padding: 8px 14px;
    border-radius: 12px;
    box-shadow: 0 0 20px rgba(59, 130, 246, 0.7), 0 4px 12px rgba(0,0,0,0.6);
    border: 2px solid #ffffff;
    animation: pulse-scale 1.1s infinite alternate ease-in-out;
    pointer-events: none;
    user-select: none;
}

@keyframes arrow-bounce-left {
    0% { transform: translateX(0); }
    100% { transform: translateX(-10px); }
}
@keyframes arrow-bounce-right {
    0% { transform: translateX(0); }
    100% { transform: translateX(10px); }
}
@keyframes pulse-scale {
    0% { transform: scale(1); }
    100% { transform: scale(1.08); }
}

.angle-step {
    transition: all 0.2s ease-in-out;
    padding: 3px 8px;
    border-radius: 4px;
}
.angle-step.active-step {
    color: #3b82f6 !important;
    font-weight: 700 !important;
    background: rgba(59, 130, 246, 0.18) !important;
    border: 1px solid rgba(59, 130, 246, 0.5) !important;
}
.angle-step.completed-step {
    color: #10b981 !important;
    font-weight: 600 !important;
    background: rgba(16, 185, 129, 0.12) !important;
    border: 1px solid rgba(16, 185, 129, 0.4) !important;
}
</style>

<script>
(function() {
    let modelsLoaded = false;
    let activeStream = null;
    let activeVideo = null;
    let activeInterval = null;
    let activeWidget = null;
    let availableVideoDevices = [];
    let multiAngleSession = {
        active: false,
        step: 0,
        descriptors: [],
        frontPhoto: '',
        widget: null
    };

    function startMultiAngleSession(widget) {
        multiAngleSession = {
            active: true,
            step: 1,
            descriptors: [],
            frontPhoto: '',
            widget: widget
        };

        const singleBtn = widget.querySelector('.capture-single-face');
        const multiBtn = widget.querySelector('.capture-multi-face');
        const stepBtn = widget.querySelector('.multi-step-capture-btn');
        const cancelBtn = widget.querySelector('.cancel-multi-scan');
        const progress = widget.querySelector('.multi-angle-progress');
        const statusMsg = widget.querySelector('.face-widget-status-msg');

        if (singleBtn) singleBtn.style.display = 'none';
        if (multiBtn) multiBtn.style.display = 'none';
        if (stepBtn) stepBtn.style.display = 'inline-flex';
        if (cancelBtn) cancelBtn.style.display = 'inline-flex';
        if (progress) progress.style.display = 'flex';

        updateStepUI(widget, 1);
        if (statusMsg) {
            statusMsg.textContent = "Step 1 of 3: Look straight at camera and click 'Capture Front Face (1/3)'.";
        }
    }

    function updateStepUI(widget, step) {
        if (!widget) return;
        const dirOverlay = widget.querySelector('.webcam-direction-overlay');
        const badgeTag = widget.querySelector('.direction-step-tag');
        const badgeText = widget.querySelector('.direction-text');
        const promptFront = widget.querySelector('.prompt-front');
        const promptLeft = widget.querySelector('.prompt-left');
        const promptRight = widget.querySelector('.prompt-right');
        const angleInstruction = widget.querySelector('.webcam-angle-instruction');
        const stepBtn = widget.querySelector('.multi-step-capture-btn');
        const stepFront = widget.querySelector('.step-front');
        const stepLeft = widget.querySelector('.step-left');
        const stepRight = widget.querySelector('.step-right');

        if (dirOverlay) dirOverlay.style.display = 'block';

        if (step === 1) {
            if (badgeTag) {
                badgeTag.textContent = "Step 1/3";
                badgeTag.style.background = "var(--primary, #3b82f6)";
            }
            if (badgeText) badgeText.innerHTML = "Look Straight Ahead 🎯";
            if (promptFront) promptFront.style.display = 'flex';
            if (promptLeft) promptLeft.style.display = 'none';
            if (promptRight) promptRight.style.display = 'none';

            if (angleInstruction) {
                angleInstruction.innerHTML = '⬆️ <strong>STEP 1/3:</strong> Look straight at camera, then click <strong>Capture Front Face</strong>';
                angleInstruction.style.borderColor = '#3b82f6';
            }

            if (stepBtn) {
                stepBtn.disabled = false;
                stepBtn.className = 'btn btn-primary btn-small multi-step-capture-btn';
                stepBtn.innerHTML = '<i class="fa-solid fa-camera"></i> <span class="step-btn-text">Capture Front Face (1/3)</span>';
            }

            if (stepFront) {
                stepFront.className = 'angle-step step-front active-step';
                stepFront.innerHTML = '<i class="fa-solid fa-circle-dot"></i> 1. Front (Click to save)';
            }
            if (stepLeft) {
                stepLeft.className = 'angle-step step-left';
                stepLeft.innerHTML = '<i class="fa-regular fa-circle"></i> 2. Left';
                stepLeft.style.color = 'var(--text-muted)';
            }
            if (stepRight) {
                stepRight.className = 'angle-step step-right';
                stepRight.innerHTML = '<i class="fa-regular fa-circle"></i> 3. Right';
                stepRight.style.color = 'var(--text-muted)';
            }
        } else if (step === 2) {
            if (badgeTag) {
                badgeTag.textContent = "Step 2/3";
                badgeTag.style.background = "#10b981";
            }
            if (badgeText) badgeText.innerHTML = "Turn Head to LEFT ⬅️";
            if (promptFront) promptFront.style.display = 'none';
            if (promptLeft) promptLeft.style.display = 'flex';
            if (promptRight) promptRight.style.display = 'none';

            if (angleInstruction) {
                angleInstruction.innerHTML = '⬅️ <strong>STEP 2/3:</strong> Turn head to the <strong>LEFT</strong> (follow ⬅️ arrow), then click <strong>Capture Left Face</strong>';
                angleInstruction.style.borderColor = '#10b981';
            }

            if (stepBtn) {
                stepBtn.disabled = false;
                stepBtn.className = 'btn btn-success btn-small multi-step-capture-btn';
                stepBtn.innerHTML = '<i class="fa-solid fa-arrow-left"></i> <span class="step-btn-text">Next: Capture Left Face (2/3) →</span>';
            }

            if (stepFront) {
                stepFront.className = 'angle-step step-front completed-step';
                stepFront.innerHTML = '<i class="fa-solid fa-circle-check"></i> 1. Front ✓';
            }
            if (stepLeft) {
                stepLeft.className = 'angle-step step-left active-step';
                stepLeft.innerHTML = '<i class="fa-solid fa-circle-dot"></i> 2. Left (Click to save)';
            }
            if (stepRight) {
                stepRight.className = 'angle-step step-right';
                stepRight.innerHTML = '<i class="fa-regular fa-circle"></i> 3. Right';
                stepRight.style.color = 'var(--text-muted)';
            }
        } else if (step === 3) {
            if (badgeTag) {
                badgeTag.textContent = "Step 3/3";
                badgeTag.style.background = "#10b981";
            }
            if (badgeText) badgeText.innerHTML = "Turn Head to RIGHT ➡️";
            if (promptFront) promptFront.style.display = 'none';
            if (promptLeft) promptLeft.style.display = 'none';
            if (promptRight) promptRight.style.display = 'flex';

            if (angleInstruction) {
                angleInstruction.innerHTML = '➡️ <strong>STEP 3/3:</strong> Turn head to the <strong>RIGHT</strong> (follow ➡️ arrow), then click <strong>Capture Right Face & Save</strong>';
                angleInstruction.style.borderColor = '#10b981';
            }

            if (stepBtn) {
                stepBtn.disabled = false;
                stepBtn.className = 'btn btn-accent btn-small multi-step-capture-btn';
                stepBtn.innerHTML = '<i class="fa-solid fa-arrow-right"></i> <span class="step-btn-text">Next: Capture Right Face (3/3) & Save ✓</span>';
            }

            if (stepFront) {
                stepFront.className = 'angle-step step-front completed-step';
                stepFront.innerHTML = '<i class="fa-solid fa-circle-check"></i> 1. Front ✓';
            }
            if (stepLeft) {
                stepLeft.className = 'angle-step step-left completed-step';
                stepLeft.innerHTML = '<i class="fa-solid fa-circle-check"></i> 2. Left ✓';
            }
            if (stepRight) {
                stepRight.className = 'angle-step step-right active-step';
                stepRight.innerHTML = '<i class="fa-solid fa-circle-dot"></i> 3. Right (Click to save)';
            }
        }
    }

    function resetMultiAngleSession(widget) {
        multiAngleSession = {
            active: false,
            step: 0,
            descriptors: [],
            frontPhoto: '',
            widget: null
        };

        if (!widget) return;

        const singleBtn = widget.querySelector('.capture-single-face');
        const multiBtn = widget.querySelector('.capture-multi-face');
        const stepBtn = widget.querySelector('.multi-step-capture-btn');
        const cancelBtn = widget.querySelector('.cancel-multi-scan');
        const progress = widget.querySelector('.multi-angle-progress');
        const dirOverlay = widget.querySelector('.webcam-direction-overlay');
        const angleInstruction = widget.querySelector('.webcam-angle-instruction');
        const stepFront = widget.querySelector('.step-front');
        const stepLeft = widget.querySelector('.step-left');
        const stepRight = widget.querySelector('.step-right');

        if (singleBtn) singleBtn.style.display = 'inline-flex';
        if (multiBtn) multiBtn.style.display = 'inline-flex';
        if (stepBtn) stepBtn.style.display = 'none';
        if (cancelBtn) cancelBtn.style.display = 'none';
        if (progress) progress.style.display = 'none';
        if (dirOverlay) dirOverlay.style.display = 'none';

        if (angleInstruction) {
            angleInstruction.textContent = "Center face inside oval guide";
            angleInstruction.style.borderColor = 'rgba(255,255,255,0.15)';
        }

        if (stepFront) {
            stepFront.className = 'angle-step step-front';
            stepFront.innerHTML = '<i class="fa-regular fa-circle"></i> 1. Front';
            stepFront.style.color = 'var(--text-muted)';
        }
        if (stepLeft) {
            stepLeft.className = 'angle-step step-left';
            stepLeft.innerHTML = '<i class="fa-regular fa-circle"></i> 2. Left';
            stepLeft.style.color = 'var(--text-muted)';
        }
        if (stepRight) {
            stepRight.className = 'angle-step step-right';
            stepRight.innerHTML = '<i class="fa-regular fa-circle"></i> 3. Right';
            stepRight.style.color = 'var(--text-muted)';
        }
    }

    function isVirtualCam(label) {
        const l = (label || '').toLowerCase();
        return l.includes('obs') || l.includes('virtual') || l.includes('v-cam') || l.includes('unity') || l.includes('droidcam') || l.includes('ivcam') || l.includes('screen');
    }

    function playBeep(freq, type = 'sine', duration = 0.15) {
        try {
            const audioCtx = new (window.AudioContext || window.webkitAudioContext)();
            const osc = audioCtx.createOscillator();
            const gain = audioCtx.createGain();
            osc.type = type;
            osc.frequency.value = freq;
            gain.gain.setValueAtTime(0.12, audioCtx.currentTime);
            gain.gain.exponentialRampToValueAtTime(0.0001, audioCtx.currentTime + duration);
            osc.connect(gain);
            gain.connect(audioCtx.destination);
            osc.start();
            osc.stop(audioCtx.currentTime + duration);
        } catch (e) {}
    }

    async function loadFaceApiScript() {
        if (typeof faceapi !== 'undefined') return;
        return new Promise((resolve, reject) => {
            const script = document.createElement('script');
            script.src = '<?= htmlspecialchars(($basePath ?? '')) ?>/assets/vendor/face-api/face-api.min.js';
            script.onload = () => resolve();
            script.onerror = () => reject(new Error("Failed to load face-api script."));
            document.head.appendChild(script);
        });
    }

    async function loadModels() {
        await loadFaceApiScript();
        if (modelsLoaded) return;
        const modelsUrl = '<?= htmlspecialchars(($basePath ?? '')) ?>/assets/vendor/face-api/models';
        await Promise.all([
            faceapi.nets.tinyFaceDetector.loadFromUri(modelsUrl),
            faceapi.nets.faceLandmark68TinyNet.loadFromUri(modelsUrl),
            faceapi.nets.faceRecognitionNet.loadFromUri(modelsUrl)
        ]);
        try {
            await faceapi.nets.ssdMobilenetv1.loadFromUri(modelsUrl);
        } catch (e) {
            console.warn("SSD model note:", e);
        }
        modelsLoaded = true;
    }

    async function enumerateCameras() {
        try {
            if (!navigator.mediaDevices || !navigator.mediaDevices.enumerateDevices) return [];
            const devices = await navigator.mediaDevices.enumerateDevices();
            availableVideoDevices = devices.filter(d => d.kind === 'videoinput');
            updateCameraDropdowns();
            return availableVideoDevices;
        } catch (err) {
            console.warn("Camera enum error:", err);
            return [];
        }
    }

    function updateCameraDropdowns() {
        document.querySelectorAll('.camera-device-select').forEach(select => {
            const currentVal = select.value;
            select.innerHTML = '';
            if (availableVideoDevices.length === 0) {
                const opt = document.createElement('option');
                opt.value = '';
                opt.textContent = 'Default Webcam';
                select.appendChild(opt);
                return;
            }

            const realDevices = [];
            const virtualDevices = [];

            availableVideoDevices.forEach((dev, idx) => {
                const label = dev.label || `Camera ${idx + 1}`;
                if (isVirtualCam(label)) {
                    virtualDevices.push({ dev, label });
                } else {
                    realDevices.push({ dev, label });
                }
            });

            // Physical webcams first
            if (realDevices.length > 0) {
                const grp = document.createElement('optgroup');
                grp.label = "Physical Webcams (Recommended)";
                realDevices.forEach(({ dev, label }) => {
                    const opt = document.createElement('option');
                    opt.value = dev.deviceId;
                    opt.textContent = `📷 ${label}`;
                    grp.appendChild(opt);
                });
                select.appendChild(grp);
            }

            // Virtual cameras second
            if (virtualDevices.length > 0) {
                const grp = document.createElement('optgroup');
                grp.label = "Virtual Cameras (OBS / Software)";
                virtualDevices.forEach(({ dev, label }) => {
                    const opt = document.createElement('option');
                    opt.value = dev.deviceId;
                    opt.textContent = `⚠️ ${label} (Virtual)`;
                    grp.appendChild(opt);
                });
                select.appendChild(grp);
            }

            // Select best camera: ALWAYS prefer physical camera over OBS!
            let targetId = '';
            if (currentVal && realDevices.some(r => r.dev.deviceId === currentVal)) {
                targetId = currentVal;
            } else if (realDevices.length > 0) {
                targetId = realDevices[0].dev.deviceId;
            } else if (virtualDevices.length > 0) {
                targetId = virtualDevices[0].dev.deviceId;
            }
            if (targetId) {
                select.value = targetId;
            }
        });
    }

    function stopActiveCamera() {
        if (activeInterval) {
            clearInterval(activeInterval);
            activeInterval = null;
        }
        if (activeStream) {
            activeStream.getTracks().forEach(track => track.stop());
            activeStream = null;
        }
        if (activeVideo) {
            activeVideo.srcObject = null;
            activeVideo = null;
        }
        if (activeWidget) {
            resetMultiAngleSession(activeWidget);
            const container = activeWidget.querySelector('.webcam-box-container');
            const singleBtn = activeWidget.querySelector('.capture-single-face');
            const multiBtn = activeWidget.querySelector('.capture-multi-face');
            const stepBtn = activeWidget.querySelector('.multi-step-capture-btn');
            const cancelBtn = activeWidget.querySelector('.cancel-multi-scan');
            const closeBtn = activeWidget.querySelector('.close-widget-cam');
            const progress = activeWidget.querySelector('.multi-angle-progress');
            const dirOverlay = activeWidget.querySelector('.webcam-direction-overlay');
            const obsBanner = activeWidget.querySelector('.obs-warning-banner');
            const startBtn = activeWidget.querySelector('.start-widget-webcam');
            if (container) container.style.display = 'none';
            if (singleBtn) singleBtn.style.display = 'none';
            if (multiBtn) multiBtn.style.display = 'none';
            if (stepBtn) stepBtn.style.display = 'none';
            if (cancelBtn) cancelBtn.style.display = 'none';
            if (closeBtn) closeBtn.style.display = 'none';
            if (progress) progress.style.display = 'none';
            if (dirOverlay) dirOverlay.style.display = 'none';
            if (obsBanner) obsBanner.style.display = 'none';
            if (startBtn) startBtn.style.display = 'inline-flex';
            activeWidget = null;
        }
    }

    async function startCameraStream(widget, preferredDeviceId = null) {
        const video = widget.querySelector('.webcam-feed-preview');
        const canvas = widget.querySelector('.webcam-overlay-canvas');
        const container = widget.querySelector('.webcam-box-container');
        const singleBtn = widget.querySelector('.capture-single-face');
        const multiBtn = widget.querySelector('.capture-multi-face');
        const closeBtn = widget.querySelector('.close-widget-cam');
        const startBtn = widget.querySelector('.start-widget-webcam');
        const retakeBtn = widget.querySelector('.retake-widget-face');
        const statusMsg = widget.querySelector('.face-widget-status-msg');
        const snapshotPreview = widget.querySelector('.captured-snapshot-preview');
        const existingPreview = widget.querySelector('.existing-snapshot-preview');
        const angleInstruction = widget.querySelector('.webcam-angle-instruction');
        const select = widget.querySelector('.camera-device-select');
        const obsBanner = widget.querySelector('.obs-warning-banner');

        // Stop current active stream
        if (activeStream) {
            activeStream.getTracks().forEach(t => t.stop());
            activeStream = null;
        }
        if (activeInterval) {
            clearInterval(activeInterval);
            activeInterval = null;
        }

        activeWidget = widget;
        statusMsg.textContent = "Loading Face AI models & initializing camera...";

        try {
            await loadModels();
        } catch (err) {
            console.error(err);
            statusMsg.textContent = "Error: Face AI models failed to load.";
            return;
        }

        // If labels are still empty, request a quick stream to unlock labels
        if (availableVideoDevices.length === 0 || availableVideoDevices.every(d => !d.label)) {
            try {
                const initPromptStream = await navigator.mediaDevices.getUserMedia({ video: true });
                initPromptStream.getTracks().forEach(t => t.stop());
                await enumerateCameras();
            } catch (e) {
                console.warn("Initial prompt error", e);
            }
        }

        // Determine device to open
        let chosenId = preferredDeviceId || (select ? select.value : '');
        const realDevs = availableVideoDevices.filter(d => !isVirtualCam(d.label));
        const chosenDev = availableVideoDevices.find(d => d.deviceId === chosenId);

        // If chosenId is OBS Virtual Camera and we have real webcams, switch to real webcam!
        if ((!chosenId || (chosenDev && isVirtualCam(chosenDev.label))) && realDevs.length > 0) {
            chosenId = realDevs[0].deviceId;
            if (select) select.value = chosenId;
        }

        statusMsg.textContent = "Starting camera feed...";

        let stream = null;
        try {
            stream = await navigator.mediaDevices.getUserMedia({
                video: {
                    deviceId: chosenId ? { ideal: chosenId } : undefined,
                    width: { ideal: 1280, ideal: 640 },
                    height: { ideal: 720, ideal: 480 }
                }
            });
        } catch (err1) {
            console.warn("Ideal constraint fallback:", err1);
            try {
                stream = await navigator.mediaDevices.getUserMedia({
                    video: chosenId ? { deviceId: { ideal: chosenId } } : true
                });
            } catch (err2) {
                console.error("Camera access failed:", err2);
                statusMsg.textContent = "Camera access denied or device busy. Try switching camera.";
                return;
            }
        }

        activeStream = stream;
        activeVideo = video;
        video.srcObject = stream;

        await enumerateCameras();

        const activeTrack = stream.getVideoTracks()[0];
        const activeLabel = activeTrack ? (activeTrack.label || '') : '';
        const currentDevId = activeTrack ? activeTrack.getSettings().deviceId : chosenId;
        if (select && currentDevId) {
            select.value = currentDevId;
        }

        // Check if stream is OBS
        if (isVirtualCam(activeLabel)) {
            if (obsBanner) obsBanner.style.display = 'flex';
            statusMsg.textContent = "OBS Virtual Camera active. Click 'Switch Camera' to use your hardware webcam.";
        } else {
            if (obsBanner) obsBanner.style.display = 'none';
            statusMsg.textContent = `Active: ${activeLabel || 'Hardware Webcam'}. Position face in oval.`;
        }

        video.onloadedmetadata = () => {
            video.play();
            resetMultiAngleSession(widget);
            container.style.display = 'block';
            singleBtn.style.display = 'inline-flex';
            multiBtn.style.display = 'inline-flex';
            closeBtn.style.display = 'inline-flex';
            startBtn.style.display = 'none';
            retakeBtn.style.display = 'none';
            snapshotPreview.style.display = 'none';
            if (existingPreview) existingPreview.style.display = 'none';
            if (angleInstruction) angleInstruction.textContent = "Center face inside oval guide";

            const displaySize = { width: video.offsetWidth, height: video.offsetHeight };
            canvas.width = displaySize.width;
            canvas.height = displaySize.height;
            faceapi.matchDimensions(canvas, displaySize);

            activeInterval = setInterval(async () => {
                if (video.paused || video.ended) return;
                try {
                    const detections = await faceapi.detectAllFaces(video, new faceapi.TinyFaceDetectorOptions({ inputSize: 224, scoreThreshold: 0.45 }));
                    const resized = faceapi.resizeResults(detections, displaySize);
                    const ctx = canvas.getContext('2d');
                    ctx.clearRect(0, 0, canvas.width, canvas.height);
                    const ovalGuide = widget.querySelector('.face-guide-oval');
                    if (resized.length > 0) {
                        if (ovalGuide) ovalGuide.style.borderColor = '#10b981';
                        resized.forEach(det => {
                            const box = det.box;
                            ctx.strokeStyle = '#10b981';
                            ctx.lineWidth = 2;
                            ctx.strokeRect(box.x, box.y, box.width, box.height);
                        });
                    } else {
                        if (ovalGuide) ovalGuide.style.borderColor = 'rgba(255,255,255,0.45)';
                    }
                } catch (err) {}
            }, 220);
        };
    }

    function cropFaceSnapshot(sourceMedia, box) {
        const canvas = document.createElement('canvas');
        canvas.width = 500;
        canvas.height = 500;
        const ctx = canvas.getContext('2d');

        const padX = (box.width || 200) * 0.28;
        const padY = (box.height || 200) * 0.28;

        let sx = (box.x || 0) - padX;
        let sy = (box.y || 0) - padY;
        let sw = (box.width || 200) + padX * 2;
        let sh = (box.height || 200) + padY * 2;

        const maxDim = Math.max(sw, sh);
        sx -= (maxDim - sw) / 2;
        sy -= (maxDim - sh) / 2;
        sw = maxDim;
        sh = maxDim;

        const srcW = sourceMedia.videoWidth || sourceMedia.naturalWidth || sourceMedia.width;
        const srcH = sourceMedia.videoHeight || sourceMedia.naturalHeight || sourceMedia.height;

        if (sx < 0) sx = 0;
        if (sy < 0) sy = 0;
        if (sx + sw > srcW) sw = srcW - sx;
        if (sy + sh > srcH) sh = srcH - sy;

        ctx.drawImage(sourceMedia, sx, sy, sw, sh, 0, 0, 500, 500);
        return canvas.toDataURL('image/jpeg', 0.95);
    }

    async function detectFaceWithFallbacks(input) {
        let detection = null;
        if (faceapi.nets.ssdMobilenetv1.params) {
            try {
                detection = await faceapi.detectSingleFace(input, new faceapi.SsdMobilenetv1Options({ minConfidence: 0.45 }))
                    .withFaceLandmarks(true)
                    .withFaceDescriptor();
            } catch (e) {}
        }
        if (!detection) {
            detection = await faceapi.detectSingleFace(input, new faceapi.TinyFaceDetectorOptions({ inputSize: 320, scoreThreshold: 0.4 }))
                .withFaceLandmarks(true)
                .withFaceDescriptor();
        }
        return detection;
    }

    // Initialize camera listing on load
    if (navigator.mediaDevices && navigator.mediaDevices.enumerateDevices) {
        navigator.mediaDevices.enumerateDevices().then(() => enumerateCameras());
    }

    document.addEventListener('click', async function(e) {
        // 1. OPEN WEBCAM
        const startBtn = e.target.closest('.start-widget-webcam');
        if (startBtn) {
            e.preventDefault();
            const widget = startBtn.closest('.face-scan-widget');
            await startCameraStream(widget);
            return;
        }

        // 2. SWITCH TO NEXT CAMERA
        const switchBtn = e.target.closest('.switch-camera-btn');
        if (switchBtn) {
            e.preventDefault();
            const widget = switchBtn.closest('.face-scan-widget');
            const select = widget.querySelector('.camera-device-select');
            if (select && select.options.length > 1) {
                const nextIdx = (select.selectedIndex + 1) % select.options.length;
                select.selectedIndex = nextIdx;
                await startCameraStream(widget, select.value);
            }
            return;
        }

        // 3. AUTO SWITCH FROM OBS TO REAL WEBCAM
        const autoSwitchBtn = e.target.closest('.auto-switch-real-cam');
        if (autoSwitchBtn) {
            e.preventDefault();
            const widget = autoSwitchBtn.closest('.face-scan-widget');
            const realDevs = availableVideoDevices.filter(d => !isVirtualCam(d.label));
            if (realDevs.length > 0) {
                await startCameraStream(widget, realDevs[0].deviceId);
            }
            return;
        }

        // 4. CLOSE WEBCAM
        const closeBtn = e.target.closest('.close-widget-cam');
        if (closeBtn) {
            e.preventDefault();
            const widget = closeBtn.closest('.face-scan-widget');
            const statusMsg = widget.querySelector('.face-widget-status-msg');
            stopActiveCamera();
            statusMsg.textContent = "Webcam closed.";
            return;
        }

        // 5. CAPTURE SINGLE FRONT FACE
        const singleBtn = e.target.closest('.capture-single-face');
        if (singleBtn) {
            e.preventDefault();
            const widget = singleBtn.closest('.face-scan-widget');
            const video = widget.querySelector('.webcam-feed-preview');
            const statusMsg = widget.querySelector('.face-widget-status-msg');
            const snapshotPreview = widget.querySelector('.captured-snapshot-preview');
            const snapshotImg = widget.querySelector('.captured-snapshot-img');
            const badge = widget.querySelector('.face-captured-badge');
            const descriptorInput = widget.querySelector('.form-face-descriptor');
            const imageInput = widget.querySelector('.form-captured-image');
            const retakeBtn = widget.querySelector('.retake-widget-face');

            statusMsg.textContent = "Analyzing face portrait...";
            singleBtn.disabled = true;

            try {
                const detection = await detectFaceWithFallbacks(video);
                if (!detection) {
                    statusMsg.textContent = "No face detected. Please ensure your real camera is active and face the lens.";
                    playBeep(220, 'sawtooth', 0.25);
                    singleBtn.disabled = false;
                    return;
                }

                const descArray = Array.from(detection.descriptor);
                descriptorInput.value = JSON.stringify([descArray]);

                const croppedDataUrl = cropFaceSnapshot(video, detection.detection.box);
                imageInput.value = croppedDataUrl;
                snapshotImg.src = croppedDataUrl;

                playBeep(659.25, 'sine', 0.2);
                stopActiveCamera();

                retakeBtn.style.display = 'inline-flex';
                snapshotPreview.style.display = 'flex';
                if (badge) badge.innerHTML = '<i class="fa-solid fa-circle-check"></i> High-Quality Front Face Saved';
                statusMsg.textContent = "Face portrait captured and reference saved successfully.";
            } catch (err) {
                console.error(err);
                statusMsg.textContent = "Face capture failed: " + err.message;
            } finally {
                singleBtn.disabled = false;
            }
            return;
        }

        // 6. MULTI-ANGLE SCAN START
        const multiBtn = e.target.closest('.capture-multi-face');
        if (multiBtn) {
            e.preventDefault();
            const widget = multiBtn.closest('.face-scan-widget');
            startMultiAngleSession(widget);
            return;
        }

        // 7. MULTI-ANGLE INTERACTIVE STEP CAPTURE (Manual click next next)
        const stepBtn = e.target.closest('.multi-step-capture-btn');
        if (stepBtn) {
            e.preventDefault();
            const widget = stepBtn.closest('.face-scan-widget');
            const video = widget.querySelector('.webcam-feed-preview');
            const statusMsg = widget.querySelector('.face-widget-status-msg');
            const descriptorInput = widget.querySelector('.form-face-descriptor');
            const imageInput = widget.querySelector('.form-captured-image');
            const snapshotPreview = widget.querySelector('.captured-snapshot-preview');
            const snapshotImg = widget.querySelector('.captured-snapshot-img');
            const badge = widget.querySelector('.face-captured-badge');
            const retakeBtn = widget.querySelector('.retake-widget-face');

            if (!multiAngleSession.active) {
                startMultiAngleSession(widget);
                return;
            }

            const currentStep = multiAngleSession.step;
            stepBtn.disabled = true;

            if (currentStep === 1) {
                stepBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Scanning Front Face...';
                statusMsg.textContent = "Detecting front face portrait...";

                try {
                    const d1 = await detectFaceWithFallbacks(video);
                    if (!d1) {
                        playBeep(220, 'sawtooth', 0.25);
                        statusMsg.textContent = "⚠️ No face detected. Please face the camera lens straight and click 'Capture Front Face' again.";
                        updateStepUI(widget, 1);
                        return;
                    }

                    playBeep(523.25, 'sine', 0.15);
                    multiAngleSession.descriptors.push(Array.from(d1.descriptor));
                    multiAngleSession.frontPhoto = cropFaceSnapshot(video, d1.detection.box);
                    multiAngleSession.step = 2;

                    updateStepUI(widget, 2);
                    statusMsg.textContent = "Front face saved! Now turn your head slightly to the LEFT (follow ⬅️ arrow) and click 'Capture Left Face'.";
                } catch (err) {
                    console.error(err);
                    playBeep(220, 'sawtooth', 0.25);
                    statusMsg.textContent = "Capture error: " + err.message;
                    updateStepUI(widget, 1);
                }
                return;
            }

            if (currentStep === 2) {
                stepBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Scanning Left Angle...';
                statusMsg.textContent = "Detecting left angle face...";

                try {
                    const d2 = await detectFaceWithFallbacks(video);
                    if (!d2) {
                        playBeep(220, 'sawtooth', 0.25);
                        statusMsg.textContent = "⚠️ Face not detected at left angle. Turn head slightly (around 15°-20°) and click again.";
                        updateStepUI(widget, 2);
                        return;
                    }

                    playBeep(659.25, 'sine', 0.15);
                    multiAngleSession.descriptors.push(Array.from(d2.descriptor));
                    multiAngleSession.step = 3;

                    updateStepUI(widget, 3);
                    statusMsg.textContent = "Left angle saved! Now turn your head slightly to the RIGHT (follow ➡️ arrow) and click 'Capture Right Face'.";
                } catch (err) {
                    console.error(err);
                    playBeep(220, 'sawtooth', 0.25);
                    statusMsg.textContent = "Capture error: " + err.message;
                    updateStepUI(widget, 2);
                }
                return;
            }

            if (currentStep === 3) {
                stepBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Saving Face Profile...';
                statusMsg.textContent = "Detecting right angle & finalizing face profile...";

                try {
                    const d3 = await detectFaceWithFallbacks(video);
                    if (!d3) {
                        playBeep(220, 'sawtooth', 0.25);
                        statusMsg.textContent = "⚠️ Face not detected at right angle. Turn head slightly (around 15°-20°) and click again.";
                        updateStepUI(widget, 3);
                        return;
                    }

                    playBeep(783.99, 'sine', 0.25);
                    multiAngleSession.descriptors.push(Array.from(d3.descriptor));

                    const stepRight = widget.querySelector('.step-right');
                    if (stepRight) {
                        stepRight.className = 'angle-step step-right completed-step';
                        stepRight.innerHTML = '<i class="fa-solid fa-circle-check"></i> 3. Right ✓';
                    }

                    // Save all 3 descriptors into hidden input
                    descriptorInput.value = JSON.stringify(multiAngleSession.descriptors);
                    imageInput.value = multiAngleSession.frontPhoto;
                    snapshotImg.src = multiAngleSession.frontPhoto;

                    stopActiveCamera();

                    retakeBtn.style.display = 'inline-flex';
                    snapshotPreview.style.display = 'flex';
                    if (badge) badge.innerHTML = '<i class="fa-solid fa-circle-check"></i> 3-Angle Face Profile Saved (High Accuracy)';
                    statusMsg.textContent = "Multi-angle face profile saved successfully! (Front, Left, Right). Ready to save user.";
                } catch (err) {
                    console.error(err);
                    playBeep(220, 'sawtooth', 0.25);
                    statusMsg.textContent = "Finalize error: " + err.message;
                    updateStepUI(widget, 3);
                }
                return;
            }
        }

        // 8. CANCEL MULTI-SCAN
        const cancelMultiBtn = e.target.closest('.cancel-multi-scan');
        if (cancelMultiBtn) {
            e.preventDefault();
            const widget = cancelMultiBtn.closest('.face-scan-widget');
            resetMultiAngleSession(widget);
            const statusMsg = widget.querySelector('.face-widget-status-msg');
            if (statusMsg) statusMsg.textContent = "Multi-angle scan cancelled. You can try again or capture a single photo.";
            return;
        }

        // 9. TRIGGER PHOTO UPLOAD
        const uploadTrigger = e.target.closest('.trigger-upload-btn');
        if (uploadTrigger) {
            e.preventDefault();
            const widget = uploadTrigger.closest('.face-scan-widget');
            const fileInput = widget.querySelector('.face-file-input');
            stopActiveCamera();
            fileInput.click();
            return;
        }

        // 10. RETAKE / RESET
        const retakeBtn = e.target.closest('.retake-widget-face');
        if (retakeBtn) {
            e.preventDefault();
            const widget = retakeBtn.closest('.face-scan-widget');
            const descriptorInput = widget.querySelector('.form-face-descriptor');
            const imageInput = widget.querySelector('.form-captured-image');
            const snapshotPreview = widget.querySelector('.captured-snapshot-preview');
            const statusMsg = widget.querySelector('.face-widget-status-msg');

            descriptorInput.value = "";
            imageInput.value = "";
            snapshotPreview.style.display = 'none';

            resetMultiAngleSession(widget);

            statusMsg.textContent = "Face cleared. Open webcam or upload photo.";
            await startCameraStream(widget);
            return;
        }
    });

    // Handle Camera Dropdown Change
    document.addEventListener('change', async function(e) {
        const select = e.target.closest('.camera-device-select');
        if (select) {
            const widget = select.closest('.face-scan-widget');
            const container = widget.querySelector('.webcam-box-container');
            if (container && container.style.display !== 'none') {
                await startCameraStream(widget, select.value);
            }
            return;
        }

        // Handle Photo File Upload Input
        const fileInput = e.target.closest('.face-file-input');
        if (fileInput && fileInput.files && fileInput.files[0]) {
            const file = fileInput.files[0];
            const widget = fileInput.closest('.face-scan-widget');
            const statusMsg = widget.querySelector('.face-widget-status-msg');
            const snapshotPreview = widget.querySelector('.captured-snapshot-preview');
            const snapshotImg = widget.querySelector('.captured-snapshot-img');
            const badge = widget.querySelector('.face-captured-badge');
            const descriptorInput = widget.querySelector('.form-face-descriptor');
            const imageInput = widget.querySelector('.form-captured-image');
            const retakeBtn = widget.querySelector('.retake-widget-face');

            statusMsg.textContent = "Reading uploaded photo & detecting face...";

            try {
                await loadModels();
                const reader = new FileReader();
                reader.onload = async (re) => {
                    const img = new Image();
                    img.onload = async () => {
                        try {
                            const detection = await detectFaceWithFallbacks(img);
                            if (!detection) {
                                playBeep(220, 'sawtooth', 0.3);
                                statusMsg.textContent = "No face detected in uploaded photo. Please upload a clear portrait image.";
                                return;
                            }

                            const descArray = Array.from(detection.descriptor);
                            descriptorInput.value = JSON.stringify([descArray]);

                            const croppedDataUrl = cropFaceSnapshot(img, detection.detection.box);
                            imageInput.value = croppedDataUrl;
                            snapshotImg.src = croppedDataUrl;

                            playBeep(659.25, 'sine', 0.2);
                            retakeBtn.style.display = 'inline-flex';
                            snapshotPreview.style.display = 'flex';
                            if (badge) badge.innerHTML = '<i class="fa-solid fa-circle-check"></i> Photo Uploaded & Face Verified';
                            statusMsg.textContent = "Photo uploaded and face descriptor verified successfully!";
                        } catch (err) {
                            console.error(err);
                            statusMsg.textContent = "Analysis failed: " + err.message;
                        }
                    };
                    img.src = re.target.result;
                };
                reader.readAsDataURL(file);
            } catch (err) {
                console.error(err);
                statusMsg.textContent = "Failed to load models for photo analysis.";
            }
            fileInput.value = '';
        }
    });

    document.querySelectorAll('.users-panel-toggle').forEach(btn => {
        btn.addEventListener('click', () => {
            stopActiveCamera();
            const targetId = btn.getAttribute('data-target');
            if (targetId) {
                const targetPanel = document.getElementById(targetId);
                setTimeout(() => {
                    if (targetPanel && !targetPanel.hidden) {
                        targetPanel.querySelectorAll('.lazy-face-image').forEach(img => {
                            if (img.dataset.src && img.src !== img.dataset.src) {
                                img.src = img.dataset.src;
                            }
                        });
                        enumerateCameras();
                    }
                }, 50);
            }
        });
    });
})();
</script>

