-- EMAILSEND ONE v0.9.0
-- Importador inteligente + campos personalizados + permisos de listas por usuario.

ALTER TABLE users
  MODIFY COLUMN role ENUM('admin','user','client') NOT NULL DEFAULT 'user';

ALTER TABLE users
  ADD COLUMN list_access_mode ENUM('all','assigned') NOT NULL DEFAULT 'all' AFTER role;

CREATE TABLE IF NOT EXISTS user_contact_lists (
 user_id INT UNSIGNED NOT NULL,
 list_id INT UNSIGNED NOT NULL,
 assigned_at DATETIME NOT NULL,
 PRIMARY KEY(user_id,list_id),
 INDEX(list_id),
 CONSTRAINT fk_ucl_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
 CONSTRAINT fk_ucl_list FOREIGN KEY(list_id) REFERENCES contact_lists(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS contact_custom_fields (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 tenant_id INT UNSIGNED NOT NULL,
 name VARCHAR(180) NOT NULL,
 field_key VARCHAR(190) NOT NULL,
 active TINYINT(1) NOT NULL DEFAULT 1,
 sort_order INT NOT NULL DEFAULT 0,
 created_at DATETIME NOT NULL,
 UNIQUE KEY uq_contact_custom_field (tenant_id,field_key),
 INDEX(tenant_id),
 CONSTRAINT fk_ccf_tenant FOREIGN KEY(tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS contact_custom_values (
 contact_id BIGINT UNSIGNED NOT NULL,
 field_id INT UNSIGNED NOT NULL,
 value TEXT NULL,
 PRIMARY KEY(contact_id,field_id),
 INDEX(field_id),
 CONSTRAINT fk_ccv_contact FOREIGN KEY(contact_id) REFERENCES contacts(id) ON DELETE CASCADE,
 CONSTRAINT fk_ccv_field FOREIGN KEY(field_id) REFERENCES contact_custom_fields(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS contact_import_aliases (
 tenant_id INT UNSIGNED NOT NULL,
 normalized_header VARCHAR(190) NOT NULL,
 source_header VARCHAR(255) NULL,
 target_type ENUM('standard','custom','ignore') NOT NULL,
 target_key VARCHAR(190) NULL,
 updated_at DATETIME NOT NULL,
 PRIMARY KEY(tenant_id,normalized_header),
 INDEX(tenant_id),
 CONSTRAINT fk_cia_tenant FOREIGN KEY(tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
