-- Add voucher fields to students table
ALTER TABLE students 
ADD COLUMN has_voucher TINYINT(1) NOT NULL DEFAULT 0 AFTER enrollment_status, 
ADD COLUMN voucher_number VARCHAR(50) DEFAULT NULL AFTER has_voucher; 