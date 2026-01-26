-- Add notes column to quotations table
-- Run this SQL in phpMyAdmin or MySQL command line

ALTER TABLE quotations ADD COLUMN notes TEXT AFTER grand_total;
