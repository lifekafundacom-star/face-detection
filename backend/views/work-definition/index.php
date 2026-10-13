<?php

use yii\helpers\Html;
use yii\helpers\Url;
use common\components\WorkPointService;

/** @var array $definitions */
/** @var array|null $editing */

$this->title = 'Work Points Definition';
$isEdit = !empty($editing);
?>

<style>
.wd-container {
    max-width: 1200px;
    margin: 20px auto;
    padding: 0 15px;
    font-family: 'Inter', system-ui, -apple-system, sans-serif;
}

.wd-header {
    background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%);
    color: white;
    padding: 30px 40px;
    border-radius: 16px;
    margin-bottom: 30px;
    box-shadow: 0 10px 25px -5px rgba(59, 130, 246, 0.2);
    position: relative;
    overflow: hidden;
}

.wd-header::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -10%;
    width: 250px;
    height: 250px;
    background: rgba(255, 255, 255, 0.08);
    border-radius: 50%;
    pointer-events: none;
}

.wd-header h1 {
    font-size: 2.2rem;
    font-weight: 800;
    margin: 0 0 10px 0;
    letter-spacing: -0.025em;
    color: #ffffff;
}

.wd-header p {
    font-size: 1rem;
    opacity: 0.9;
    margin: 0;
    font-weight: 400;
}

.wd-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 30px;
    align-items: start;
}

@media (min-width: 992px) {
    .wd-grid.has-edit {
        grid-template-columns: 1.6fr 1fr;
    }
}

.wd-card {
    background: white;
    border-radius: 16px;
    padding: 28px;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
    border: 1px solid #e5e7eb;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.wd-card:hover {
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.05), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
}

.wd-card h2 {
    font-size: 1.4rem;
    font-weight: 700;
    color: #1f2937;
    margin: 0 0 24px 0;
    display: flex;
    align-items: center;
    gap: 10px;
    border-bottom: 1px solid #f3f4f6;
    padding-bottom: 12px;
}

.wd-table-wrapper {
    overflow-x: auto;
}

.wd-table {
    width: 100%;
    border-collapse: collapse;
    text-align: left;
}

.wd-table th {
    background-color: #f9fafb;
    padding: 14px 16px;
    font-size: 0.85rem;
    font-weight: 600;
    color: #4b5563;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    border-bottom: 2px solid #e5e7eb;
}

.wd-table td {
    padding: 18px 16px;
    border-bottom: 1px solid #f3f4f6;
    vertical-align: middle;
}

.wd-table tr:hover {
    background-color: #f9fafb;
}

.wd-slno-badge {
    background-color: #eff6ff;
    color: #1e40af;
    padding: 4px 12px;
    border-radius: 9999px;
    font-size: 0.85rem;
    font-weight: 700;
    display: inline-block;
    border: 1px solid #bfdbfe;
}

.wd-work-title {
    font-size: 1.05rem;
    font-weight: 600;
    color: #111827;
}

.wd-work-desc {
    font-size: 0.85rem;
    color: #6b7280;
    margin-top: 4px;
    line-height: 1.4;
}

.wd-points-badge {
    background-color: #f0fdf4;
    color: #166534;
    padding: 6px 14px;
    border-radius: 8px;
    font-size: 1.15rem;
    font-weight: 700;
    border: 1px solid #bbf7d0;
    display: inline-flex;
    align-items: center;
    gap: 4px;
}

.btn-edit-wd {
    background-color: white;
    color: #2563eb;
    border: 1px solid #d1d5db;
    padding: 8px 16px;
    border-radius: 8px;
    font-size: 0.9rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

.btn-edit-wd:hover {
    background-color: #2563eb;
    color: white;
    border-color: #2563eb;
    box-shadow: 0 4px 6px -1px rgba(37, 99, 235, 0.2);
}

/* Edit form styles */
.wd-form-group {
    margin-bottom: 20px;
}

.wd-form-group label {
    display: block;
    font-size: 0.9rem;
    font-weight: 600;
    color: #374151;
    margin-bottom: 8px;
}

.wd-form-control {
    width: 100%;
    padding: 10px 14px;
    border-radius: 8px;
    border: 1px solid #d1d5db;
    font-size: 0.95rem;
    color: #1f2937;
    transition: border-color 0.2s ease, box-shadow 0.2s ease;
}

.wd-form-control:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
}

.wd-form-control[readonly] {
    background-color: #f3f4f6;
    color: #6b7280;
    cursor: not-allowed;
}

.wd-form-actions {
    display: flex;
    gap: 12px;
    margin-top: 24px;
}

.btn-submit-wd {
    background-color: #2563eb;
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 8px;
    font-size: 0.95rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
    flex: 1;
}

.btn-submit-wd:hover {
    background-color: #1d4ed8;
    box-shadow: 0 4px 6px -1px rgba(37, 99, 235, 0.2);
}

.btn-cancel-wd {
    background-color: #f3f4f6;
    color: #4b5563;
    border: 1px solid #d1d5db;
    padding: 10px 20px;
    border-radius: 8px;
    font-size: 0.95rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
    text-decoration: none;
    text-align: center;
    flex: 1;
}

.btn-cancel-wd:hover {
    background-color: #e5e7eb;
    color: #1f2937;
}

.wd-help-card {
    background-color: #faf5ff;
    border: 1px solid #f3e8ff;
    padding: 20px;
    border-radius: 12px;
    margin-top: 24px;
    font-size: 0.9rem;
    line-height: 1.5;
    color: #5b21b6;
}

.wd-help-card h3 {
    margin: 0 0 8px 0;
    font-size: 1rem;
    font-weight: 700;
    color: #6b21a8;
}

.wd-help-card ul {
    margin: 0;
    padding-left: 20px;
}

.wd-help-card li {
    margin-bottom: 6px;
}
</style>

<div class="wd-container">
    <div class="wd-header">
        <span style="font-weight: bold; text-transform: uppercase; font-size: 0.8rem; letter-spacing: 0.05em; display: inline-block; margin-bottom: 8px; opacity: 0.85;">Work Management</span>
        <h1>Work Point Definitions</h1>
        <p>Configure points award values for website photo updates and content writing. These configurations are retrieved dynamically at runtime.</p>
    </div>

    <div class="wd-grid <?= $isEdit ? 'has-edit' : '' ?>">
        <!-- Definitions List Card -->
        <div class="wd-card">
            <h2>
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="color: #2563eb;"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                Defined Work Points
            </h2>
            <div class="wd-table-wrapper">
                <table class="wd-table">
                    <thead>
                        <tr>
                            <th style="width: 120px;">Sl No</th>
                            <th>Work Name</th>
                            <th style="width: 150px; text-align: center;">Points</th>
                            <th style="width: 140px; text-align: right;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($definitions as $index => $definition): ?>
                            <?php 
                                $slno = $index + 1;
                                $isActiveEditRow = $isEdit && ($editing['id'] == $definition['id']);
                            ?>
                            <tr style="<?= $isActiveEditRow ? 'background-color: #eff6ff;' : '' ?>">
                                <td>
                                    <span class="wd-slno-badge">Slno-<?= $slno ?></span>
                                </td>
                                <td>
                                    <div class="wd-work-title"><?= Html::encode($definition['work_name']) ?></div>
                                    <div class="wd-work-desc">
                                        <?php if ($definition['work_key'] === WorkPointService::TEMPLATE_PHOTO_KEY): ?>
                                            Points are awarded when the main template photo is uploaded/saved.
                                        <?php elseif ($definition['work_key'] === WorkPointService::TEMPLATE_CONTENT_KEY): ?>
                                            Points are awarded to the content writer upon admin verification of the template.
                                        <?php elseif ($definition['work_key'] === WorkPointService::TEMPLATE_VERIFY_KEY): ?>
                                            Points are awarded to the admin/manager who verifies the template.
                                        <?php elseif ($definition['work_key'] === WorkPointService::TEMPLATE_MAPPING_KEY): ?>
                                            Points are awarded when a template mapping is saved on the template mappings page.
                                        <?php else: ?>
                                            <?= Html::encode($definition['description'] ?: 'Points awarded dynamically.') ?>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td style="text-align: center;">
                                    <span class="wd-points-badge">
                                        <?= (int) $definition['completion_points'] ?>
                                    </span>
                                </td>
                                <td style="text-align: right;">
                                    <?= Html::a('Edit Points', ['index', 'id' => $definition['id']], [
                                        'class' => 'btn-edit-wd' . ($isActiveEditRow ? ' active' : '')
                                    ]) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="wd-help-card">
                <h3>💡 Dynamic Rules Engine</h3>
                <ul>
                    <li><strong>website photo update (Slno-1):</strong> Logic awards this point value to the active user immediately when template main photo and show image are saved in the Create Template form.</li>
                    <li><strong>website content writing (Slno-2):</strong> Logic awards this point value to the user identified as the content writer (<code>content_user_id</code>) ONLY after the template status is officially updated to verified.</li>
                    <li><strong>templete varification (Slno-3):</strong> Logic awards this point value to the admin/manager who performs the verification.</li>
                    <li><strong>Template Mappings (Slno-4):</strong> Logic awards this point value to the active user when they create and save a new template mapping.</li>
                </ul>
            </div>
        </div>

        <!-- Edit Form Card -->
        <?php if ($isEdit): ?>
            <div class="wd-card" style="border-color: #bfdbfe; box-shadow: 0 10px 15px -3px rgba(59, 130, 246, 0.1);">
                <h2>
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="color: #16a34a;"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                    Edit Points Configuration
                </h2>
                
                <form method="post" action="<?= Url::to(['index']) ?>">
                    <?= Html::hiddenInput(Yii::$app->request->csrfParam, Yii::$app->request->csrfToken) ?>
                    <?= Html::hiddenInput('id', $editing['id'] ?? 0) ?>

                    <div class="wd-form-group">
                        <label>Work Key</label>
                        <input type="text" value="<?= Html::encode($editing['work_key'] ?? '') ?>" readonly class="wd-form-control">
                    </div>

                    <div class="wd-form-group">
                        <label>Work Name</label>
                        <input type="text" value="<?= Html::encode($editing['work_name'] ?? '') ?>" readonly class="wd-form-control" style="font-weight: 600;">
                    </div>

                    <div class="wd-form-group">
                        <label for="completion_points_input">Points (Award Value)</label>
                        <input type="text" id="completion_points_input" name="completion_points" value="<?= Html::encode($editing['completion_points'] ?? 0) ?>" required autofocus class="wd-form-control" placeholder="Enter point value (e.g. 10)">
                    </div>

                    <div class="wd-form-actions">
                        <button type="submit" class="btn-submit-wd">Save Changes</button>
                        <?= Html::a('Cancel', ['index'], ['class' => 'btn-cancel-wd']) ?>
                    </div>
                </form>
            </div>
        <?php endif; ?>
    </div>
</div>
