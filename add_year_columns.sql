-- Add year column to all relevant tables
-- Run this SQL in phpMyAdmin or MySQL command line

-- For stock module tables (if they exist)
ALTER TABLE stock_products ADD COLUMN IF NOT EXISTS year INT NOT NULL DEFAULT 2026 AFTER company_id;
ALTER TABLE stock_transactions ADD COLUMN IF NOT EXISTS year INT NOT NULL DEFAULT 2026 AFTER company_id;
ALTER TABLE stock_action_logs ADD COLUMN IF NOT EXISTS year INT NOT NULL DEFAULT 2026 AFTER company_id;

-- For companytransaction module
ALTER TABLE quotations ADD COLUMN IF NOT EXISTS year INT NOT NULL DEFAULT 2026 AFTER company_id;

-- For projects module (if tables exist)
ALTER TABLE projects_list ADD COLUMN IF NOT EXISTS year INT NOT NULL DEFAULT 2026 AFTER company_id;
ALTER TABLE transactions ADD COLUMN IF NOT EXISTS year INT NOT NULL DEFAULT 2026 AFTER company_id;

-- Add indexes for better performance
CREATE INDEX IF NOT EXISTS idx_year ON stock_products(year);
CREATE INDEX IF NOT EXISTS idx_year ON stock_transactions(year);
CREATE INDEX IF NOT EXISTS idx_year ON quotations(year);
CREATE INDEX IF NOT EXISTS idx_year ON projects_list(year);
CREATE INDEX IF NOT EXISTS idx_year ON transactions(year);
