-- EMAILSEND ONE v0.8.1
-- Listas por cliente + múltiples listas por campaña

ALTER TABLE contact_lists ADD COLUMN client_id INT UNSIGNED NULL AFTER tenant_id;
ALTER TABLE contact_lists ADD INDEX idx_contact_lists_client_id (client_id);

CREATE TABLE IF NOT EXISTS campaign_lists (
 campaign_id BIGINT UNSIGNED NOT NULL,
 list_id INT UNSIGNED NOT NULL,
 PRIMARY KEY(campaign_id,list_id),
 INDEX(list_id),
 FOREIGN KEY(campaign_id) REFERENCES campaigns(id) ON DELETE CASCADE,
 FOREIGN KEY(list_id) REFERENCES contact_lists(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Conserva la audiencia de campañas existentes.
INSERT IGNORE INTO campaign_lists(campaign_id,list_id)
SELECT id,list_id FROM campaigns WHERE list_id IS NOT NULL;

-- Crea una lista dedicada vacía para clientes ya existentes que aún no tengan una.
INSERT INTO contact_lists(tenant_id,client_id,name,created_at)
SELECT rc.tenant_id,rc.id,CONCAT(rc.company,' · Contactos'),NOW()
FROM report_clients rc
WHERE NOT EXISTS (
  SELECT 1 FROM contact_lists l WHERE l.tenant_id=rc.tenant_id AND l.client_id=rc.id
);
