ALTER TABLE users ADD COLUMN avatar_url VARCHAR(500) NULL AFTER role;
ALTER TABLE users ADD COLUMN phone VARCHAR(80) NULL AFTER avatar_url;
ALTER TABLE users ADD COLUMN job_title VARCHAR(140) NULL AFTER phone;
ALTER TABLE users ADD COLUMN company VARCHAR(180) NULL AFTER job_title;
ALTER TABLE users ADD COLUMN timezone VARCHAR(80) NULL AFTER company;
ALTER TABLE settings ADD COLUMN dkim_selector VARCHAR(80) NULL AFTER default_from_email;
ALTER TABLE settings ADD COLUMN reply_to_email VARCHAR(190) NULL AFTER dkim_selector;
