-- EMAILSEND ONE v0.8.8 · ficha de contactos ampliada
ALTER TABLE contacts ADD COLUMN company_name VARCHAR(220) NULL AFTER name;
ALTER TABLE contacts ADD COLUMN sales_band VARCHAR(120) NULL AFTER company_name;
ALTER TABLE contacts ADD COLUMN company_type VARCHAR(120) NULL AFTER sales_band;
ALTER TABLE contacts ADD COLUMN employees VARCHAR(60) NULL AFTER company_type;
ALTER TABLE contacts ADD COLUMN annual_income VARCHAR(140) NULL AFTER employees;
ALTER TABLE contacts ADD COLUMN economic_activity VARCHAR(255) NULL AFTER annual_income;
ALTER TABLE contacts ADD COLUMN region VARCHAR(180) NULL AFTER economic_activity;
ALTER TABLE contacts ADD COLUMN province VARCHAR(160) NULL AFTER region;
ALTER TABLE contacts ADD COLUMN commune VARCHAR(160) NULL AFTER province;
ALTER TABLE contacts ADD COLUMN legal_representative VARCHAR(220) NULL AFTER commune;
ALTER TABLE contacts ADD COLUMN phone_fixed VARCHAR(180) NULL AFTER legal_representative;
ALTER TABLE contacts ADD COLUMN phone_mobile VARCHAR(220) NULL AFTER phone_fixed;
ALTER TABLE contacts ADD COLUMN source_data_json LONGTEXT NULL AFTER phone_mobile;
