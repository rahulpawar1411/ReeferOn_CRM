-- ReeferON CRM - Phase 2: Follow-ups
-- Run after schema.sql (database crm must exist)

USE crm;

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
