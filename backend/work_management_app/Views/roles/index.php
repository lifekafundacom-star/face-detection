<?php
$systemRoles = $systemRoles ?? [];
$roles = $roles ?? [];
$activeRoles = $activeRoles ?? [];
$assignments = $assignments ?? [];
$permissionGroups = $permissionGroups ?? [];
$assignedCount = count(array_filter($assignments, static fn ($assignment) => !empty($assignment['custom_role_id'])));
$permissionCount = array_sum(array_map('count', $permissionGroups));
$allRoleCards = array_merge($systemRoles, $roles);
?>

<section class="stats-grid admin-stats-grid">
    <article class="stat-card"><span>System Roles</span><strong><?= count($systemRoles) ?></strong><small>Admin, manager, employee</small></article>
    <article class="stat-card"><span>Custom Roles</span><strong><?= count($roles) ?></strong><small>Extra menu access profiles</small></article>
    <article class="stat-card"><span>Assigned Employees</span><strong><?= $assignedCount ?></strong><small>With custom menu role</small></article>
    <article class="stat-card"><span>Menu Permissions</span><strong><?= $permissionCount ?></strong><small>Selectable page links</small></article>
</section>

<section class="page-grid role-management-grid">
    <article class="panel panel-span-2">
        <div class="panel-head">
            <div><p class="eyebrow">Role Setup</p><h2>Create or edit menu access</h2></div>
            <button class="btn btn-light btn-small" type="button" id="resetRoleForm">New Role</button>
        </div>

        <form class="role-form" id="roleForm" action="<?= htmlspecialchars(($basePath ?? '') . '/api/roles') ?>" method="POST">
            <input type="hidden" name="role_id" id="roleId" value="">
            <input type="hidden" name="role_scope" id="roleScope" value="custom">
            <input type="hidden" name="system_role" id="systemRole" value="">

            <div class="role-edit-mode" id="roleEditMode">Editing: New custom role</div>

            <div class="correction-grid role-meta-grid">
                <label class="field"><span>Role Name</span><input type="text" name="name" id="roleName" placeholder="Example: Attendance Team" required></label>
                <label class="field">
                    <span>Status</span>
                    <select name="status" id="roleStatus">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </label>
                <label class="field panel-span-2"><span>Description</span><textarea name="description" id="roleDescription" rows="3" placeholder="Short note for this role"></textarea></label>
            </div>

            <div class="permission-toolbar">
                <strong>Menu pages visible for this role</strong>
                <div class="action-row">
                    <button class="btn btn-light btn-small" type="button" id="selectAllPermissions">Select All</button>
                    <button class="btn btn-light btn-small" type="button" id="clearAllPermissions">Clear</button>
                </div>
            </div>

            <div class="permission-grid">
                <?php foreach ($permissionGroups as $groupLabel => $permissions): ?>
                    <fieldset class="permission-group">
                        <legend><?= htmlspecialchars($groupLabel) ?></legend>
                        <?php foreach ($permissions as $key => $label): ?>
                            <label class="permission-option">
                                <input type="checkbox" name="permissions[]" value="<?= htmlspecialchars($key) ?>">
                                <span><?= htmlspecialchars($label) ?></span>
                            </label>
                        <?php endforeach; ?>
                    </fieldset>
                <?php endforeach; ?>
            </div>

            <div class="filter-actions">
                <button class="btn btn-primary" type="submit" id="saveRoleButton">Save Role</button>
                <button class="btn btn-light" type="button" id="resetRoleFormBottom">Reset</button>
            </div>
        </form>
    </article>

    <article class="panel">
        <div class="panel-head">
            <div><p class="eyebrow">Existing Roles</p><h2>Role list</h2></div>
        </div>
        <div class="role-list">
            <?php if (empty($allRoleCards)): ?>
                <p class="empty-state">No roles found.</p>
            <?php endif; ?>
            <?php foreach ($allRoleCards as $role): ?>
                <?php
                $isSystemRole = ($role['scope'] ?? 'custom') === 'system';
                $roleJson = htmlspecialchars(json_encode($role, JSON_UNESCAPED_SLASHES), ENT_QUOTES, 'UTF-8');
                ?>
                <article class="role-card <?= $isSystemRole ? 'role-system-card' : '' ?>">
                    <div>
                        <strong><?= htmlspecialchars($role['name']) ?></strong>
                        <?php if ($isSystemRole): ?>
                            <span class="badge role-system-badge">System</span>
                        <?php else: ?>
                            <span class="badge status-<?= htmlspecialchars($role['status'] ?? 'active') ?>"><?= htmlspecialchars(ucfirst($role['status'] ?? 'active')) ?></span>
                        <?php endif; ?>
                    </div>
                    <p><?= htmlspecialchars($role['description'] ?: 'No description') ?></p>
                    <small><?= (int) ($role['employee_count'] ?? 0) ?> employees / <?= count($role['permissions'] ?? []) ?> permissions</small>
                    <div class="role-actions">
                        <button class="btn btn-primary btn-small role-edit-btn" type="button" data-role="<?= $roleJson ?>">Edit</button>
                        <?php if (!$isSystemRole): ?>
                            <button class="btn btn-danger btn-small role-delete-btn" type="button" data-url="<?= htmlspecialchars(($basePath ?? '') . '/api/roles/' . (int) $role['id'] . '/delete') ?>">Delete</button>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </article>
</section>

<section class="panel">
    <div class="panel-head">
        <div><p class="eyebrow">Employee Access</p><h2>Assign or remove role from employee</h2></div>
    </div>

    <div class="table-wrap">
        <table>
            <thead>
            <tr>
                <th>Employee</th>
                <th>System Role</th>
                <th>Department</th>
                <th>Current Custom Role</th>
                <th>Assign Role</th>
                <th>Action</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($assignments as $assignment): ?>
                <tr>
                    <td>
                        <strong><?= htmlspecialchars($assignment['name']) ?></strong><br>
                        <small><?= htmlspecialchars($assignment['email'] ?? '') ?></small>
                    </td>
                    <td><span class="badge"><?= htmlspecialchars(ucfirst($assignment['system_role'] ?? 'employee')) ?></span></td>
                    <td><?= htmlspecialchars($assignment['department'] ?: 'Unassigned') ?></td>
                    <td>
                        <?php if (!empty($assignment['custom_role_name'])): ?>
                            <span class="role-pill"><?= htmlspecialchars($assignment['custom_role_name']) ?></span>
                        <?php else: ?>
                            <span class="muted">Default <?= htmlspecialchars(ucfirst($assignment['system_role'] ?? 'employee')) ?> access</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <select class="assignment-role-select" data-user-id="<?= (int) $assignment['id'] ?>">
                            <option value="">No custom role</option>
                            <?php foreach ($activeRoles as $roleOption): ?>
                                <option value="<?= (int) $roleOption['id'] ?>" <?= (int) ($assignment['custom_role_id'] ?? 0) === (int) $roleOption['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($roleOption['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                    <td class="action-row">
                        <button class="btn btn-primary btn-small role-assign-btn" type="button" data-user-id="<?= (int) $assignment['id'] ?>">Save</button>
                        <button class="btn btn-light btn-small role-remove-btn" type="button" data-user-id="<?= (int) $assignment['id'] ?>">Remove</button>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($assignments)): ?>
                <tr><td colspan="6">No employees found.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<script>
(function () {
    const form = document.getElementById('roleForm');
    if (!form) {
        return;
    }

    const customRoleUrl = form.getAttribute('action');
    const systemRoleUrl = <?= json_encode(($basePath ?? '') . '/api/system-roles', JSON_UNESCAPED_SLASHES) ?>;
    const apiAssignUrl = <?= json_encode(($basePath ?? '') . '/api/roles/assign', JSON_UNESCAPED_SLASHES) ?>;
    const roleId = document.getElementById('roleId');
    const roleScope = document.getElementById('roleScope');
    const systemRole = document.getElementById('systemRole');
    const roleName = document.getElementById('roleName');
    const roleStatus = document.getElementById('roleStatus');
    const roleDescription = document.getElementById('roleDescription');
    const roleEditMode = document.getElementById('roleEditMode');
    const saveRoleButton = document.getElementById('saveRoleButton');
    const permissionInputs = Array.prototype.slice.call(form.querySelectorAll('input[name="permissions[]"]'));

    async function postJson(url, payload) {
        const response = await fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload || {})
        });
        const data = await response.json();
        if (!response.ok) {
            throw new Error(data.message || 'Request failed');
        }
        return data;
    }

    function setCustomMode() {
        roleScope.value = 'custom';
        systemRole.value = '';
        roleName.disabled = false;
        roleStatus.disabled = false;
        roleDescription.disabled = false;
        roleEditMode.textContent = 'Editing: New custom role';
        saveRoleButton.textContent = 'Save Role';
    }

    function resetForm() {
        setCustomMode();
        roleId.value = '';
        roleName.value = '';
        roleStatus.value = 'active';
        roleDescription.value = '';
        permissionInputs.forEach(function (input) {
            input.checked = false;
        });
        roleName.focus();
    }

    function selectedPermissions() {
        return permissionInputs
            .filter(function (input) { return input.checked; })
            .map(function (input) { return input.value; });
    }

    form.addEventListener('submit', async function (event) {
        event.preventDefault();
        try {
            if (roleScope.value === 'system') {
                await postJson(systemRoleUrl, {
                    system_role: systemRole.value,
                    permissions: selectedPermissions()
                });
            } else {
                await postJson(customRoleUrl, {
                    role_id: roleId.value,
                    name: roleName.value,
                    status: roleStatus.value,
                    description: roleDescription.value,
                    permissions: selectedPermissions()
                });
            }
            window.location.reload();
        } catch (error) {
            window.alert(error.message || 'Role save failed.');
        }
    });

    document.querySelectorAll('.role-edit-btn').forEach(function (button) {
        button.addEventListener('click', function () {
            const role = JSON.parse(button.getAttribute('data-role') || '{}');
            const permissions = Array.isArray(role.permissions) ? role.permissions : [];

            roleScope.value = role.scope || 'custom';
            systemRole.value = role.system_role || '';
            roleId.value = role.scope === 'system' ? '' : (role.id || '');
            roleName.value = role.name || '';
            roleStatus.value = role.status || 'active';
            roleDescription.value = role.description || '';

            const isSystemRole = role.scope === 'system';
            roleName.disabled = isSystemRole;
            roleStatus.disabled = isSystemRole;
            roleDescription.disabled = isSystemRole;
            roleEditMode.textContent = isSystemRole
                ? 'Editing built-in role: ' + (role.name || '')
                : 'Editing custom role: ' + (role.name || '');
            saveRoleButton.textContent = isSystemRole ? 'Save System Role' : 'Save Role';

            permissionInputs.forEach(function (input) {
                input.checked = permissions.indexOf(input.value) !== -1;
            });
            window.scrollTo({ top: form.getBoundingClientRect().top + window.scrollY - 120, behavior: 'smooth' });
        });
    });

    document.querySelectorAll('.role-delete-btn').forEach(function (button) {
        button.addEventListener('click', async function () {
            if (!window.confirm('Delete this role? Assigned employees must be removed first.')) {
                return;
            }
            try {
                await postJson(button.getAttribute('data-url'), {});
                window.location.reload();
            } catch (error) {
                window.alert(error.message || 'Role delete failed.');
            }
        });
    });

    function roleSelectFor(userId) {
        return document.querySelector('.assignment-role-select[data-user-id="' + userId + '"]');
    }

    document.querySelectorAll('.role-assign-btn').forEach(function (button) {
        button.addEventListener('click', async function () {
            const userId = button.getAttribute('data-user-id');
            const select = roleSelectFor(userId);
            try {
                await postJson(apiAssignUrl, { user_id: userId, role_id: select ? select.value : '' });
                window.location.reload();
            } catch (error) {
                window.alert(error.message || 'Role assignment failed.');
            }
        });
    });

    document.querySelectorAll('.role-remove-btn').forEach(function (button) {
        button.addEventListener('click', async function () {
            try {
                await postJson(apiAssignUrl, { user_id: button.getAttribute('data-user-id'), role_id: 0 });
                window.location.reload();
            } catch (error) {
                window.alert(error.message || 'Role remove failed.');
            }
        });
    });

    document.getElementById('selectAllPermissions').addEventListener('click', function () {
        permissionInputs.forEach(function (input) { input.checked = true; });
    });
    document.getElementById('clearAllPermissions').addEventListener('click', function () {
        permissionInputs.forEach(function (input) { input.checked = false; });
    });
    document.getElementById('resetRoleForm').addEventListener('click', resetForm);
    document.getElementById('resetRoleFormBottom').addEventListener('click', resetForm);
})();
</script>
