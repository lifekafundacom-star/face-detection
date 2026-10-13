<?php

namespace App\Core;

use App\Repositories\UserRepository;
use App\Repositories\RoleRepository;

class Auth
{
    public static function verifyPassword(string $password, string $storedPassword): bool
    {
        return password_verify($password, $storedPassword) || hash_equals($storedPassword, $password);
    }

    public static function user(): ?array
    {
        $userId = $_SESSION['__id'] ?? $_SESSION['user_id'] ?? null;

        if (!$userId) {
            return null;
        }

        return (new UserRepository())->findById((int) $userId);
    }

    public static function attempt(string $email, string $password): bool
    {
        $user = (new UserRepository())->findByEmailOrCode($email);

        if (
            !$user ||
            ($user['status'] ?? 'active') !== 'active' ||
            !self::verifyPassword($password, (string) $user['password'])
        ) {
            return false;
        }

        $_SESSION['user_id'] = (int) $user['id'];
        $_SESSION['__id'] = (int) $user['id'];
        return true;
    }

    public static function logout(): void
    {
        unset($_SESSION['user_id']);
        unset($_SESSION['__id'], $_SESSION['__authKey'], $_SESSION['__expire']);
        session_regenerate_id(true);
    }

    public static function requireRole(array $roles): void
    {
        $user = self::user();

        if (!$user) {
            Response::redirect('/');
        }

        if (!in_array($user['role'], $roles, true)) {
            http_response_code(403);
            echo 'Forbidden';
            exit;
        }
    }

    public static function canAccess(string $permissionKey, ?array $user = null): bool
    {
        $user = $user ?: self::user();
        if (!$user) {
            return false;
        }

        $permissions = (new RoleRepository())->permissionsForUser((int) $user['id']);
        if ($permissions === null) {
            return ($user['role'] ?? '') === 'admin';
        }

        return in_array($permissionKey, $permissions, true);
    }
}
