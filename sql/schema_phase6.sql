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
