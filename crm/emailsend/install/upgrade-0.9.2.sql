CREATE TABLE IF NOT EXISTS user_permissions (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 user_id INT UNSIGNED NOT NULL,
 module VARCHAR(50) NOT NULL,
 action VARCHAR(50) NOT NULL,
 allowed TINYINT(1) NOT NULL DEFAULT 0,
 created_at DATETIME NULL,
 UNIQUE KEY uq_user_permission(user_id,module,action),
 CONSTRAINT fk_user_permissions_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
