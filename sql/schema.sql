-- ReeferON CRM - Phase 0 + Phase 1 Schema
-- Import this file in phpMyAdmin or MySQL CLI

CREATE DATABASE IF NOT EXISTS crm
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE crm;

-- -----------------------------------------------------
-- Users (structure ready; login wired in Phase 7)
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  employee_id VARCHAR(50) NOT NULL,
  employee_name VARCHAR(150) NOT NULL,
  email VARCHAR(150) NOT NULL,
  mobile VARCHAR(20) DEFAULT NULL,
  user_id VARCHAR(80) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  department ENUM('Sales','Business Development','Operations','Finance','Admin','Management') NOT NULL DEFAULT 'Sales',
  role ENUM('Super Admin','Admin','Salesperson','Finance','Operations','Approver','Management','Viewer') NOT NULL DEFAULT 'Salesperson',
  status ENUM('Active','Inactive') NOT NULL DEFAULT 'Active',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_users_employee_id (employee_id),
  UNIQUE KEY uq_users_email (email),
  UNIQUE KEY uq_users_user_id (user_id)
) ENGINE=InnoDB;

INSERT INTO users (id, employee_id, employee_name, email, mobile, user_id, password_hash, department, role, status)
VALUES (
  1,
  'EMP-001',
  'System User',
  'system@reeferon.local',
  '9999999999',
  'system',
  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- password: password (temp)
  'Admin',
  'Super Admin',
  'Active'
) ON DUPLICATE KEY UPDATE employee_name = VALUES(employee_name);

-- -----------------------------------------------------
-- ID sequences (Prospect / Onboarding / Customer codes)
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS id_sequences (
  seq_name VARCHAR(50) NOT NULL PRIMARY KEY,
  prefix VARCHAR(30) NOT NULL,
  next_number INT UNSIGNED NOT NULL DEFAULT 1,
  pad_length TINYINT UNSIGNED NOT NULL DEFAULT 4
) ENGINE=InnoDB;

INSERT INTO id_sequences (seq_name, prefix, next_number, pad_length) VALUES
  ('prospect', 'REO-LEAD-', 1, 4),
  ('onboarding', 'REO-ONB-', 1, 4),
  ('customer', 'REO-CUS-', 1, 4)
ON DUPLICATE KEY UPDATE prefix = VALUES(prefix);

-- -----------------------------------------------------
-- Prospects (Phase 1)
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS prospects (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  prospect_id VARCHAR(30) NOT NULL,
  lead_creation_date DATE NOT NULL,
  company_name VARCHAR(200) NOT NULL,
  contact_person VARCHAR(150) NOT NULL,
  designation VARCHAR(100) DEFAULT NULL,
  mobile VARCHAR(20) NOT NULL,
  email VARCHAR(150) DEFAULT NULL,
  business_type ENUM('QSR','FMCG','Ice Cream','Retail','Other') NOT NULL,
  industry VARCHAR(100) DEFAULT NULL,
  city VARCHAR(100) DEFAULT NULL,
  service_required ENUM('Transportation','Warehousing','EV','Integrated Supply Chain','Other') NOT NULL,
  temperature_requirement ENUM('Ambient','Chiller','Frozen') NOT NULL,
  requirement_details TEXT DEFAULT NULL,
  estimated_monthly_business DECIMAL(14,2) DEFAULT NULL,
  lead_source ENUM('Reference','Website','Direct','Other','LinkedIn') NOT NULL,
  salesperson_id INT UNSIGNED NOT NULL,
  salesperson_name VARCHAR(150) NOT NULL,
  pipeline_stage ENUM(
    'New Prospect',
    'Calling',
    'Meeting',
    'Proposal Sent',
    'Negotiation',
    'Commercial Sent',
    'Commercial Approved',
    'Onboarding',
    'Customer Activated',
    'Lost',
    'On Hold',
    'Cancelled'
  ) NOT NULL DEFAULT 'New Prospect',
  next_followup_date DATE DEFAULT NULL,
  remarks TEXT DEFAULT NULL,
  status ENUM('Active','Inactive') NOT NULL DEFAULT 'Active',
  created_by INT UNSIGNED NOT NULL,
  updated_by INT UNSIGNED DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_prospects_prospect_id (prospect_id),
  KEY idx_prospects_company (company_name),
  KEY idx_prospects_stage (pipeline_stage),
  KEY idx_prospects_salesperson (salesperson_id),
  KEY idx_prospects_mobile (mobile),
  KEY idx_prospects_followup (next_followup_date),
  CONSTRAINT fk_prospects_salesperson FOREIGN KEY (salesperson_id) REFERENCES users(id),
  CONSTRAINT fk_prospects_created_by FOREIGN KEY (created_by) REFERENCES users(id)
) ENGINE=InnoDB;

-- -----------------------------------------------------
-- Prospect stage history (Phase 2 will use heavily; ready now)
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS prospect_stage_history (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  prospect_pk INT UNSIGNED NOT NULL,
  from_stage VARCHAR(50) DEFAULT NULL,
  to_stage VARCHAR(50) NOT NULL,
  remarks TEXT DEFAULT NULL,
  updated_by INT UNSIGNED NOT NULL,
  updated_by_name VARCHAR(150) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_stage_history_prospect (prospect_pk),
  CONSTRAINT fk_stage_history_prospect FOREIGN KEY (prospect_pk) REFERENCES prospects(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- -----------------------------------------------------
-- Follow-ups (Phase 2)
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS followups (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  prospect_pk INT UNSIGNED NOT NULL,
  prospect_id VARCHAR(30) NOT NULL,
  company_name VARCHAR(200) NOT NULL,
  contact_person VARCHAR(150) NOT NULL,
  followup_date DATE NOT NULL,
  interaction_type ENUM('Phone Call','Email','Meeting','WhatsApp','Other') NOT NULL,
  discussion_summary TEXT DEFAULT NULL,
  next_followup_date DATE DEFAULT NULL,
  remarks TEXT DEFAULT NULL,
  status ENUM('Open','Completed','Cancelled') NOT NULL DEFAULT 'Open',
  created_by INT UNSIGNED NOT NULL,
  created_by_name VARCHAR(150) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_followups_prospect (prospect_pk),
  KEY idx_followups_date (followup_date),
  KEY idx_followups_status (status),
  KEY idx_followups_next (next_followup_date),
  CONSTRAINT fk_followups_prospect FOREIGN KEY (prospect_pk) REFERENCES prospects(id) ON DELETE CASCADE,
  CONSTRAINT fk_followups_created_by FOREIGN KEY (created_by) REFERENCES users(id)
) ENGINE=InnoDB;

-- -----------------------------------------------------
-- Audit log (basic)
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS audit_logs (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  module VARCHAR(50) NOT NULL,
  record_id VARCHAR(50) NOT NULL,
  action VARCHAR(50) NOT NULL,
  old_value TEXT DEFAULT NULL,
  new_value TEXT DEFAULT NULL,
  performed_by INT UNSIGNED NOT NULL,
  performed_by_name VARCHAR(150) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_audit_module_record (module, record_id)
) ENGINE=InnoDB;
-- ReeferON CRM - Phase 3: Onboarding + Documents (+ approval rows ready for Phase 4)
USE crm;

CREATE TABLE IF NOT EXISTS onboarding (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  onboarding_id VARCHAR(30) NOT NULL,
  prospect_pk INT UNSIGNED NOT NULL,
  prospect_id VARCHAR(30) NOT NULL,
  customer_name VARCHAR(200) NOT NULL,
  brand_name VARCHAR(200) DEFAULT NULL,
  business_type ENUM('QSR','FMCG','Ice Cream','Retail','Other') NOT NULL,
  contact_person VARCHAR(150) NOT NULL,
  mobile VARCHAR(20) NOT NULL,
  email VARCHAR(150) DEFAULT NULL,
  registered_address TEXT DEFAULT NULL,
  billing_address TEXT DEFAULT NULL,
  operating_address TEXT DEFAULT NULL,
  gstin VARCHAR(20) DEFAULT NULL,
  pan VARCHAR(20) DEFAULT NULL,
  service_required ENUM('Transportation','Warehousing','EV','Integrated Supply Chain','Other') NOT NULL,
  temperature_requirement ENUM('Ambient','Chiller','Frozen') NOT NULL,
  vehicle_requirement ENUM('8ft','10ft','14ft','20ft','Other') DEFAULT NULL,
  storage_requirement ENUM('Ambient','Chiller','Frozen') DEFAULT NULL,
  origin VARCHAR(200) DEFAULT NULL,
  destination VARCHAR(200) DEFAULT NULL,
  estimated_monthly_volume VARCHAR(100) DEFAULT NULL,
  business_start_date DATE DEFAULT NULL,
  commercial_reference VARCHAR(200) DEFAULT NULL,
  payment_terms VARCHAR(100) DEFAULT NULL,
  credit_limit DECIMAL(14,2) DEFAULT NULL,
  overall_status ENUM('Draft','Submitted','In Approval','Approved','Rejected','Cancelled') NOT NULL DEFAULT 'Draft',
  created_by INT UNSIGNED NOT NULL,
  updated_by INT UNSIGNED DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_onboarding_id (onboarding_id),
  UNIQUE KEY uq_onboarding_prospect (prospect_pk),
  KEY idx_onboarding_status (overall_status),
  KEY idx_onboarding_gstin (gstin),
  CONSTRAINT fk_onboarding_prospect FOREIGN KEY (prospect_pk) REFERENCES prospects(id),
  CONSTRAINT fk_onboarding_created_by FOREIGN KEY (created_by) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS onboarding_documents (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  onboarding_pk INT UNSIGNED NOT NULL,
  doc_type ENUM(
    'GST Certificate',
    'PAN Card',
    'Company KYC',
    'Customer Agreement',
    'Purchase Order / Work Order',
    'Commercial Approval'
  ) NOT NULL,
  file_name VARCHAR(255) DEFAULT NULL,
  file_path VARCHAR(500) DEFAULT NULL,
  file_size INT UNSIGNED DEFAULT NULL,
  mime_type VARCHAR(100) DEFAULT NULL,
  status ENUM('Pending','Received','Rejected','Not Applicable') NOT NULL DEFAULT 'Pending',
  uploaded_by INT UNSIGNED DEFAULT NULL,
  uploaded_by_name VARCHAR(150) DEFAULT NULL,
  remarks TEXT DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_onboarding_doc_type (onboarding_pk, doc_type),
  CONSTRAINT fk_ondoc_onboarding FOREIGN KEY (onboarding_pk) REFERENCES onboarding(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS onboarding_approvals (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  onboarding_pk INT UNSIGNED NOT NULL,
  department ENUM('Finance','Operations','Commercial','Admin') NOT NULL,
  status ENUM('Pending','Approved','Rejected') NOT NULL DEFAULT 'Pending',
  remarks TEXT DEFAULT NULL,
  approver_id INT UNSIGNED DEFAULT NULL,
  approver_name VARCHAR(150) DEFAULT NULL,
  decided_at DATETIME DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_onboarding_dept (onboarding_pk, department),
  CONSTRAINT fk_onappr_onboarding FOREIGN KEY (onboarding_pk) REFERENCES onboarding(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ReeferON CRM - Phase 4: Customer Master (after final approval)
USE crm;

CREATE TABLE IF NOT EXISTS customers (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  customer_code VARCHAR(30) NOT NULL,
  source ENUM('New Onboarding','Existing Customer') NOT NULL DEFAULT 'New Onboarding',
  onboarding_pk INT UNSIGNED DEFAULT NULL,
  prospect_pk INT UNSIGNED DEFAULT NULL,
  customer_name VARCHAR(200) NOT NULL,
  brand_name VARCHAR(200) DEFAULT NULL,
  business_type ENUM('QSR','FMCG','Ice Cream','Retail','Other') NOT NULL,
  contact_person VARCHAR(150) NOT NULL,
  mobile VARCHAR(20) NOT NULL,
  email VARCHAR(150) DEFAULT NULL,
  gstin VARCHAR(20) DEFAULT NULL,
  registered_address TEXT DEFAULT NULL,
  billing_address TEXT DEFAULT NULL,
  operating_location TEXT DEFAULT NULL,
  services VARCHAR(200) DEFAULT NULL,
  temperature_requirement ENUM('Ambient','Chiller','Frozen') DEFAULT NULL,
  commercial_details VARCHAR(255) DEFAULT NULL,
  payment_terms VARCHAR(100) DEFAULT NULL,
  credit_limit DECIMAL(14,2) DEFAULT NULL,
  agreement_start_date DATE DEFAULT NULL,
  agreement_end_date DATE DEFAULT NULL,
  account_owner_id INT UNSIGNED DEFAULT NULL,
  account_owner_name VARCHAR(150) DEFAULT NULL,
  status ENUM('Active','Inactive','On Hold') NOT NULL DEFAULT 'Active',
  created_by INT UNSIGNED NOT NULL,
  updated_by INT UNSIGNED DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_customers_code (customer_code),
  UNIQUE KEY uq_customers_onboarding (onboarding_pk),
  KEY idx_customers_name (customer_name),
  KEY idx_customers_gstin (gstin),
  KEY idx_customers_status (status),
  CONSTRAINT fk_customers_onboarding FOREIGN KEY (onboarding_pk) REFERENCES onboarding(id),
  CONSTRAINT fk_customers_prospect FOREIGN KEY (prospect_pk) REFERENCES prospects(id),
  CONSTRAINT fk_customers_owner FOREIGN KEY (account_owner_id) REFERENCES users(id),
  CONSTRAINT fk_customers_created_by FOREIGN KEY (created_by) REFERENCES users(id)
) ENGINE=InnoDB;


-- ReeferON CRM - Phase 6: Notifications
USE crm;

CREATE TABLE IF NOT EXISTS notifications (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED DEFAULT NULL,
  event_type VARCHAR(80) NOT NULL,
  title VARCHAR(200) NOT NULL,
  message VARCHAR(500) NOT NULL,
  link_url VARCHAR(255) DEFAULT NULL,
  related_module VARCHAR(50) DEFAULT NULL,
  related_id VARCHAR(50) DEFAULT NULL,
  is_read TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_notifications_user_read (user_id, is_read),
  KEY idx_notifications_created (created_at)
) ENGINE=InnoDB;
