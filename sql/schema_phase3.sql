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
