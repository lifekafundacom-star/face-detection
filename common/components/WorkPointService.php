<?php

namespace common\components;

use common\models\Product;
use common\models\Template;
use Yii;
use yii\db\Expression;

class WorkPointService
{
    public const TEMPLATE_SAVE_KEY = 'template_save';
    public const TEMPLATE_PHOTO_KEY = 'template_photo_upload';
    public const TEMPLATE_CONTENT_KEY = 'template_content_entry';
    public const TEMPLATE_VERIFY_KEY = 'template_verification';
    public const TEMPLATE_MAPPING_KEY = 'template_mapping';

    private static bool $schemaEnsured = false;
    private static array $workflowCache = [];

    public function ensureSchema(bool $force = false): void
    {
        if (self::$schemaEnsured && !$force) {
            return;
        }
        self::$schemaEnsured = true;

        $db = Yii::$app->dbEmployee;

        $db->createCommand("
            CREATE TABLE IF NOT EXISTS work_definitions (
                id INT AUTO_INCREMENT PRIMARY KEY,
                work_key VARCHAR(80) NOT NULL UNIQUE,
                work_name VARCHAR(160) NOT NULL,
                description TEXT NULL,
                completion_points INT NOT NULL DEFAULT 0,
                is_active TINYINT(1) NOT NULL DEFAULT 1,
                created_by INT NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ")->execute();
        $this->ensureColumn('work_definitions', 'allowed_user_ids', 'TEXT NULL');

        $db->createCommand("
            CREATE TABLE IF NOT EXISTS completed_work_logs (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                work_definition_id INT NULL,
                task_id INT NULL,
                source_type VARCHAR(60) NOT NULL,
                source_id INT NULL,
                product_id INT NULL,
                template_name VARCHAR(160) NOT NULL,
                image_file VARCHAR(255) NULL,
                show_image_file VARCHAR(255) NULL,
                points INT NOT NULL DEFAULT 0,
                action_type VARCHAR(40) NOT NULL DEFAULT 'save',
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_completed_work_user (user_id),
                INDEX idx_completed_work_source (source_type, source_id),
                INDEX idx_completed_work_definition (work_definition_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ")->execute();

        $db->createCommand("
            CREATE TABLE IF NOT EXISTS template_workflows (
                id INT AUTO_INCREMENT PRIMARY KEY,
                template_id INT NOT NULL,
                product_id INT NULL,
                template_name VARCHAR(160) NOT NULL,
                image_file VARCHAR(255) NULL,
                show_image_file VARCHAR(255) NULL,
                status ENUM('photo_pending','content_pending','verification_pending','verified','rejected') NOT NULL DEFAULT 'photo_pending',
                photo_user_id INT NULL,
                content_user_id INT NULL,
                verified_by INT NULL,
                photo_completed_at DATETIME NULL,
                content_completed_at DATETIME NULL,
                verified_at DATETIME NULL,
                verification_note TEXT NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY uniq_template_workflow_template (template_id),
                INDEX idx_template_workflow_status (status),
                INDEX idx_template_workflow_product (product_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ")->execute();

        $this->ensureDefinition(self::TEMPLATE_SAVE_KEY, 'Template Save', 'Template name with main image and show image saved from backend.', 10);
        $this->ensureDefinition(self::TEMPLATE_PHOTO_KEY, 'website photo update', 'Stage 1: template name, template image, and show image upload.', 10);
        $this->ensureDefinition(self::TEMPLATE_CONTENT_KEY, 'website content writing', 'Stage 2: product title, description, SEO, and tag entry.', 8);
        $this->ensureDefinition(self::TEMPLATE_VERIFY_KEY, 'templete varification', 'Stage 3: admin/manager verification before product goes live.', 5);
        $this->ensureDefinition(self::TEMPLATE_MAPPING_KEY, 'Template Mappings', 'Stage 4: Mapping template to category/subcategory.', 10);
    }

    public function recordTemplateSave(Template $template, Product $product, string $actionType = 'save'): void
    {
        $this->recordTemplateWork(self::TEMPLATE_SAVE_KEY, $template, $product, $actionType);
    }

    public function recordTemplateWork(string $workKey, Template $template, Product $product, string $actionType, ?int $userId = null): void
    {
        $userId = $userId !== null ? $userId : (Yii::$app->user->isGuest ? 0 : (int) Yii::$app->user->id);
        if ($userId <= 0 || !$product->id) {
            return;
        }

        $this->ensureSchema();
        $db = Yii::$app->dbEmployee;
        $definition = (new \yii\db\Query())
            ->from('work_definitions')
            ->where(['work_key' => $workKey, 'is_active' => 1])
            ->one($db);

        if (!$definition) {
            return;
        }

        $points = max(0, (int) ($definition['completion_points'] ?? 0));
        $templateName = (string) $template->template_name;
        $imageFile = trim((string) $product->images);
        $showImageFile = trim((string) $product->show_image);

        $transaction = $db->beginTransaction();

        try {
            $db->createCommand()->insert('tasks', [
                'created_by' => $userId,
                'assigned_to' => $userId,
                'assigned_user_id' => $userId,
                'title' => 'Template saved: ' . $templateName,
                'description' => 'Template image: ' . $imageFile . "\nShow image: " . $showImageFile,
                'task_type' => (string) $definition['work_key'],
                'task_date' => new Expression('CURDATE()'),
                'status' => 'completed',
                'deadline' => new Expression('CURDATE()'),
                'completed_at' => new Expression('NOW()'),
                'started_at' => new Expression('NOW()'),
                'points' => $points,
            ])->execute();
            $taskId = (int) $db->getLastInsertID();

            $db->createCommand()->insert('completed_work_logs', [
                'user_id' => $userId,
                'work_definition_id' => (int) $definition['id'],
                'task_id' => $taskId,
                'source_type' => 'template',
                'source_id' => (int) $template->id,
                'product_id' => (int) $product->id,
                'template_name' => $templateName,
                'image_file' => $imageFile !== '' ? $imageFile : null,
                'show_image_file' => $showImageFile !== '' ? $showImageFile : null,
                'points' => $points,
                'action_type' => $actionType,
            ])->execute();

            $transaction->commit();
        } catch (\Throwable $exception) {
            $transaction->rollBack();
            Yii::error($exception->getMessage(), __METHOD__);
        }
    }

    public function syncTemplateWorkflow(Template $template, Product $product, string $status, array $extra = []): void
    {
        $this->ensureSchema();
        $db = Yii::$app->dbEmployee;
        $now = new Expression('NOW()');
        $baseData = [
            'template_id' => (int) $template->id,
            'product_id' => $product->id ? (int) $product->id : null,
            'template_name' => (string) $template->template_name,
            'image_file' => trim((string) $product->images) !== '' ? trim((string) $product->images) : null,
            'show_image_file' => trim((string) $product->show_image) !== '' ? trim((string) $product->show_image) : null,
            'status' => $status,
        ];
        $data = array_merge($baseData, $extra);

        $exists = (new \yii\db\Query())
            ->from('template_workflows')
            ->where(['template_id' => (int) $template->id])
            ->exists($db);

        if ($exists) {
            $db->createCommand()->update('template_workflows', $data, ['template_id' => (int) $template->id])->execute();
            return;
        }

        if (!isset($data['photo_completed_at']) && $status !== 'photo_pending') {
            $data['photo_completed_at'] = $now;
        }

        $db->createCommand()->insert('template_workflows', $data)->execute();
    }

    public function workflowForTemplate(int $templateId): ?array
    {
        if (isset(self::$workflowCache[$templateId])) {
            return self::$workflowCache[$templateId];
        }

        $this->ensureSchema();

        $row = (new \yii\db\Query())
            ->from('template_workflows')
            ->where(['template_id' => $templateId])
            ->one(Yii::$app->dbEmployee) ?: null;

        self::$workflowCache[$templateId] = $row;
        return $row;
    }

    public function canVerifyCurrentUser(): bool
    {
        if (Yii::$app->user->isGuest) {
            return false;
        }

        $role = strtolower((string) (Yii::$app->user->identity->role ?? ''));
        if (in_array($role, ['admin', 'manager'], true)) {
            return true;
        }

        $this->ensureSchema();
        $definition = (new \yii\db\Query())
            ->from('work_definitions')
            ->where(['work_key' => self::TEMPLATE_VERIFY_KEY, 'is_active' => 1])
            ->one(Yii::$app->dbEmployee);

        $allowedUserIds = array_filter(array_map('intval', preg_split('/\s*,\s*/', (string)($definition['allowed_user_ids'] ?? ''), -1, PREG_SPLIT_NO_EMPTY)));

        return in_array((int) Yii::$app->user->id, $allowedUserIds, true);
    }

    private function ensureDefinition(string $key, string $name, string $description, int $points): void
    {
        $db = Yii::$app->dbEmployee;
        $row = (new \yii\db\Query())
            ->from('work_definitions')
            ->where(['work_key' => $key])
            ->one($db);

        if ($row) {
            if ($row['work_name'] !== $name) {
                $db->createCommand()->update('work_definitions', ['work_name' => $name], ['id' => $row['id']])->execute();
            }
            return;
        }

        $db->createCommand()->insert('work_definitions', [
            'work_key' => $key,
            'work_name' => $name,
            'description' => $description,
            'completion_points' => $points,
            'is_active' => 1,
            'created_by' => Yii::$app->user->isGuest ? null : Yii::$app->user->id,
        ])->execute();
    }

    private function ensureColumn(string $table, string $column, string $definition): void
    {
        $db = Yii::$app->dbEmployee;
        $exists = $db->createCommand("SHOW COLUMNS FROM {$table} LIKE :column")
            ->bindValue(':column', $column)
            ->queryOne();

        if (!$exists) {
            $db->createCommand("ALTER TABLE {$table} ADD {$column} {$definition}")->execute();
        }
    }

    public function recordTemplateMappingWork(\common\models\TemplateMapping $mapping): void
    {
        $userId = Yii::$app->user->isGuest ? 0 : (int) Yii::$app->user->id;
        if ($userId <= 0 || !$mapping->template_id) {
            return;
        }

        $this->ensureSchema();
        $db = Yii::$app->dbEmployee;
        $definition = (new \yii\db\Query())
            ->from('work_definitions')
            ->where(['work_key' => self::TEMPLATE_MAPPING_KEY, 'is_active' => 1])
            ->one($db);

        if (!$definition) {
            return;
        }

        $points = max(0, (int) ($definition['completion_points'] ?? 0));
        
        $template = Template::findOne($mapping->template_id);
        $templateName = $template ? (string) $template->template_name : 'Template ID ' . $mapping->template_id;

        // Fetch associated product images if available
        $product = Product::find()->where(['template_id' => $mapping->template_id])->one();
        $imageFile = $product ? trim((string) $product->images) : null;
        $showImageFile = $product ? trim((string) $product->show_image) : null;
        $productId = $product ? (int) $product->id : null;

        $transaction = $db->beginTransaction();

        try {
            $db->createCommand()->insert('tasks', [
                'created_by' => $userId,
                'assigned_to' => $userId,
                'assigned_user_id' => $userId,
                'title' => 'Template mapped: ' . $templateName,
                'description' => 'Template ID: ' . $mapping->template_id . "\nCategory: " . $mapping->category_id . "\nSubcategory: " . $mapping->subcategory_id,
                'task_type' => (string) $definition['work_key'],
                'task_date' => new Expression('CURDATE()'),
                'status' => 'completed',
                'deadline' => new Expression('CURDATE()'),
                'completed_at' => new Expression('NOW()'),
                'started_at' => new Expression('NOW()'),
                'points' => $points,
            ])->execute();
            $taskId = (int) $db->getLastInsertID();

            $db->createCommand()->insert('completed_work_logs', [
                'user_id' => $userId,
                'work_definition_id' => (int) $definition['id'],
                'task_id' => $taskId,
                'source_type' => 'template_mapping',
                'source_id' => (int) $mapping->id,
                'product_id' => $productId,
                'template_name' => $templateName,
                'image_file' => $imageFile !== '' ? $imageFile : null,
                'show_image_file' => $showImageFile !== '' ? $showImageFile : null,
                'points' => $points,
                'action_type' => 'mapping',
            ])->execute();

            $transaction->commit();
        } catch (\Throwable $exception) {
            $transaction->rollBack();
            Yii::error($exception->getMessage(), __METHOD__);
        }
    }
}
