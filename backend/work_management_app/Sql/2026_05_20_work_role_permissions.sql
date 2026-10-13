CREATE TABLE IF NOT EXISTS work_roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    slug VARCHAR(140) NOT NULL UNIQUE,
    description TEXT NULL,
    status ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS work_role_permissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    role_id INT NOT NULL,
    permission_key VARCHAR(160) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_work_role_permission (role_id, permission_key),
    KEY idx_work_role_permissions_key (permission_key),
    CONSTRAINT fk_work_role_permissions_role FOREIGN KEY (role_id) REFERENCES work_roles(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS work_user_roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    role_id INT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_work_user_roles_user (user_id),
    KEY idx_work_user_roles_role (role_id),
    CONSTRAINT fk_work_user_roles_role FOREIGN KEY (role_id) REFERENCES work_roles(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS work_system_role_permissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    system_role ENUM('admin','manager','employee') NOT NULL,
    permission_key VARCHAR(160) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_work_system_role_permission (system_role, permission_key),
    KEY idx_work_system_role_permissions_key (permission_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
