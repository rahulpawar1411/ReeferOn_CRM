-- ReeferON CRM - Phase 7: Roles (checkbox permissions) + Auth prep
USE crm;

CREATE TABLE IF NOT EXISTS roles (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(80) NOT NULL,
  description VARCHAR(255) DEFAULT NULL,
  permissions_json TEXT NOT NULL,
  is_system TINYINT(1) NOT NULL DEFAULT 0,
  status ENUM('Active','Inactive') NOT NULL DEFAULT 'Active',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_roles_name (name)
) ENGINE=InnoDB;

-- Link users to roles (keep legacy role ENUM for display until fully migrated)
ALTER TABLE users
  ADD COLUMN role_id INT UNSIGNED NULL AFTER role;

-- Safe if column already exists: ignore duplicate error when re-run manually

ALTER TABLE users
  ADD CONSTRAINT fk_users_role FOREIGN KEY (role_id) REFERENCES roles(id);
