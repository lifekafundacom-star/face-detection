<?php

namespace common\models;

use Yii;
use yii\base\NotSupportedException;
use yii\base\InvalidArgumentException;
use yii\db\ActiveRecord;
use yii\web\IdentityInterface;

/**
 * Identity model for backend login from face_detection_ice_emp.users.
 */
class EmployeeUser extends ActiveRecord implements IdentityInterface
{
    public static function tableName()
    {
        return '{{%users}}';
    }

    public static function getDb()
    {
        return Yii::$app->has('dbEmployee') ? Yii::$app->dbEmployee : Yii::$app->db;
    }

    public static function findIdentity($id)
    {
        return static::find()
            ->where(['id' => $id, 'status' => 'active'])
            ->andWhere(['deleted_at' => null])
            ->one();
    }

    public static function findIdentityByAccessToken($token, $type = null)
    {
        throw new NotSupportedException('"findIdentityByAccessToken" is not implemented.');
    }

    public static function findByUsername($username)
    {
        $username = trim((string) $username);

        return static::find()
            ->where(['status' => 'active'])
            ->andWhere(['deleted_at' => null])
            ->andWhere([
                'or',
                ['email' => $username],
                ['name' => $username],
                ['employee_code' => $username],
            ])
            ->one();
    }

    public function getId()
    {
        return $this->getPrimaryKey();
    }

    public function getAuthKey()
    {
        return hash('sha256', implode('|', [
            $this->id,
            $this->email,
            $this->password,
            $this->updated_at,
            Yii::$app->request->cookieValidationKey,
        ]));
    }

    public function validateAuthKey($authKey)
    {
        return hash_equals($this->getAuthKey(), (string) $authKey);
    }

    public function validatePassword($password)
    {
        $storedPassword = (string) $this->password;

        try {
            if (Yii::$app->security->validatePassword($password, $storedPassword)) {
                return true;
            }
        } catch (InvalidArgumentException $exception) {
            // Some imported employee users still store plain demo passwords.
        }

        return hash_equals($storedPassword, (string) $password);
    }

    public function getUsername()
    {
        return $this->name ?: $this->email;
    }

    private $_permissions = null;

    public function getPermissions()
    {
        if ($this->_permissions !== null) {
            return $this->_permissions;
        }

        $db = static::getDb();
        $roleId = (int)$db->createCommand("
            SELECT wr.id
            FROM work_user_roles wur
            INNER JOIN work_roles wr ON wr.id = wur.role_id AND wr.status = 'active'
            WHERE wur.user_id = :user_id
            LIMIT 1
        ", [':user_id' => $this->id])->queryScalar();

        if ($roleId <= 0) {
            $systemRole = $this->role ?: '';
            $this->_permissions = $this->getSystemRolePermissions($systemRole);
        } else {
            $this->_permissions = $db->createCommand("
                SELECT permission_key
                FROM work_role_permissions
                WHERE role_id = :role_id
                ORDER BY permission_key ASC
            ", [':role_id' => $roleId])->queryColumn();
        }

        return $this->_permissions;
    }

    protected function getSystemRolePermissions($role)
    {
        $role = strtolower(trim((string)$role));
        $db = static::getDb();
        $permissions = $db->createCommand("
            SELECT permission_key
            FROM work_system_role_permissions
            WHERE system_role = :system_role
            ORDER BY permission_key ASC
        ", [':system_role' => $role])->queryColumn();

        if (!empty($permissions)) {
            return $permissions;
        }

        if ($role === 'admin') {
            return [
                'backend.dashboard', 'backend.contact',
                'website.language', 'website.category', 'website.subcategory',
                'cards.template', 'cards.product', 'cards.template_mapping',
                'analytics.google', 'analytics.internal', 'analytics.search',
                'work.definitions', 'work.tasks', 'work.attendance',
                'work.reports.performance', 'work.reports.tasks', 'work.reports.attendance',
                'work.leaderboard', 'work.workflows',
                'employees.users', 'employees.roles', 'employees.live_attendance', 'employees.camera_attendance', 'employees.performance',
                'account.profile', 'account.settings',
            ];
        }

        if ($role === 'manager') {
            return [
                'backend.dashboard',
                'work.definitions',
                'work.tasks',
                'work.attendance',
                'work.reports.performance',
                'work.reports.tasks',
                'work.reports.attendance',
                'work.leaderboard',
                'work.workflows',
                'employees.live_attendance',
                'employees.camera_attendance',
                'employees.performance',
                'account.profile',
                'account.settings',
            ];
        }

        if ($role === 'employee') {
            return [
                'work.tasks',
                'work.attendance',
                'work.leaderboard',
                'account.profile',
                'account.settings',
            ];
        }

        return [];
    }

    public function canAccess($permissionKey)
    {
        $permissions = $this->getPermissions();
        if ($permissions === null) {
            return $this->role === 'admin';
        }
        return in_array($permissionKey, $permissions, true);
    }

    public function getCustomRoleName()
    {
        $db = static::getDb();
        return $db->createCommand("
            SELECT wr.name
            FROM work_user_roles wur
            INNER JOIN work_roles wr ON wr.id = wur.role_id AND wr.status = 'active'
            WHERE wur.user_id = :user_id
            LIMIT 1
        ", [':user_id' => $this->id])->queryScalar() ?: null;
    }
}

