-- Fresh installation without demo accounts.
SET NAMES utf8mb4;

CREATE DATABASE IF NOT EXISTS shineroster
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE shineroster;

CREATE TABLE users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  full_name VARCHAR(100) NOT NULL,
  email VARCHAR(190) NOT NULL,
  phone VARCHAR(20) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('customer','provider','admin') NOT NULL DEFAULT 'customer',
  is_active BOOLEAN NOT NULL DEFAULT TRUE,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_users_email (email),
  KEY idx_users_role_active (role, is_active),
  CONSTRAINT chk_user_active CHECK (is_active IN (0,1))
) ENGINE=InnoDB;

CREATE TABLE provider_profiles (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  bio TEXT NULL,
  city VARCHAR(100) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_provider_user (user_id),
  KEY idx_provider_city (city),
  CONSTRAINT fk_provider_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE skills (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  UNIQUE KEY uq_skill_name (name)
) ENGINE=InnoDB;

CREATE TABLE provider_skills (
  provider_id INT UNSIGNED NOT NULL,
  skill_id INT UNSIGNED NOT NULL,
  PRIMARY KEY (provider_id, skill_id),
  KEY idx_provider_skill (skill_id),
  CONSTRAINT fk_ps_provider FOREIGN KEY (provider_id) REFERENCES provider_profiles(id) ON DELETE CASCADE,
  CONSTRAINT fk_ps_skill FOREIGN KEY (skill_id) REFERENCES skills(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE services (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  provider_id INT UNSIGNED NOT NULL,
  name VARCHAR(120) NOT NULL,
  description TEXT NOT NULL,
  price DECIMAL(10,3) NOT NULL COMMENT 'JOD; three decimal places',
  duration_minutes SMALLINT UNSIGNED NOT NULL,
  is_active BOOLEAN NOT NULL DEFAULT TRUE,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_service_provider_active (provider_id, is_active),
  CONSTRAINT fk_service_provider FOREIGN KEY (provider_id) REFERENCES provider_profiles(id) ON DELETE RESTRICT,
  CONSTRAINT chk_service_price CHECK (price >= 0),
  CONSTRAINT chk_service_duration CHECK (duration_minutes > 0),
  CONSTRAINT chk_service_active CHECK (is_active IN (0,1))
) ENGINE=InnoDB;

CREATE TABLE appointment_slots (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  service_id INT UNSIGNED NOT NULL,
  starts_at DATETIME NOT NULL,
  ends_at DATETIME NOT NULL,
  is_open BOOLEAN NOT NULL DEFAULT TRUE,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_slot_service_start (service_id, starts_at),
  KEY idx_slot_open_start (is_open, starts_at),
  CONSTRAINT fk_slot_service FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE RESTRICT,
  CONSTRAINT chk_slot_times CHECK (ends_at > starts_at),
  CONSTRAINT chk_slot_open CHECK (is_open IN (0,1))
) ENGINE=InnoDB;

CREATE TABLE bookings (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  slot_id INT UNSIGNED NOT NULL,
  city VARCHAR(100) NOT NULL,
  address VARCHAR(255) NOT NULL,
  notes TEXT NULL,
  total_price DECIMAL(10,3) NOT NULL COMMENT 'Server-side price snapshot in JOD',
  status ENUM('confirmed','completed','cancelled') NOT NULL DEFAULT 'confirmed',
  active_slot_guard TINYINT GENERATED ALWAYS AS (
    CASE WHEN status IN ('confirmed','completed') THEN 1 ELSE NULL END
  ) STORED,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  cancelled_at DATETIME NULL,
  completed_at DATETIME NULL,
  UNIQUE KEY uq_booking_occupied_slot (slot_id, active_slot_guard),
  KEY idx_booking_customer_status (user_id, status),
  KEY idx_booking_status_created (status, created_at),
  CONSTRAINT fk_booking_customer FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE RESTRICT,
  CONSTRAINT fk_booking_slot FOREIGN KEY (slot_id) REFERENCES appointment_slots(id) ON DELETE RESTRICT,
  CONSTRAINT chk_booking_price CHECK (total_price >= 0),
  CONSTRAINT chk_booking_state_dates CHECK (
    (status='confirmed' AND cancelled_at IS NULL AND completed_at IS NULL)
    OR (status='completed' AND completed_at IS NOT NULL AND cancelled_at IS NULL AND completed_at >= created_at)
    OR (status='cancelled' AND cancelled_at IS NOT NULL AND completed_at IS NULL AND cancelled_at >= created_at)
  )
) ENGINE=InnoDB;

CREATE TABLE reviews (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  booking_id INT UNSIGNED NOT NULL,
  rating TINYINT UNSIGNED NOT NULL,
  comment TEXT NULL,
  is_visible BOOLEAN NOT NULL DEFAULT TRUE,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_review_booking (booking_id),
  CONSTRAINT fk_review_booking FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE RESTRICT,
  CONSTRAINT chk_review_rating CHECK (rating BETWEEN 1 AND 5),
  CONSTRAINT chk_review_visible CHECK (is_visible IN (0,1))
) ENGINE=InnoDB;
INSERT INTO skills (name) VALUES
('التنظيف المنزلي العادي'),
('التنظيف العميق'),
('تنظيف المطابخ'),
('تنظيف الحمامات'),
('تنظيف النوافذ'),
('تنظيف السجاد'),
('تنظيف الكنب والمفروشات'),
('تنظيف المنازل بعد الانتقال');
CREATE VIEW provider_rating_summary AS
SELECT p.id AS provider_id,
       ROUND(AVG(r.rating),2) AS average_rating,
       COUNT(r.id) AS review_count
FROM provider_profiles p
LEFT JOIN services s ON s.provider_id=p.id
LEFT JOIN appointment_slots a ON a.service_id=s.id
LEFT JOIN bookings b ON b.slot_id=a.id AND b.status='completed'
LEFT JOIN reviews r ON r.booking_id=b.id AND r.is_visible=1
GROUP BY p.id;
CREATE VIEW available_appointment_slots AS
SELECT a.id AS slot_id, s.id AS service_id, s.provider_id,
       s.name AS service_name, s.price, s.duration_minutes,
       a.starts_at, a.ends_at, p.city
FROM appointment_slots a
JOIN services s ON s.id=a.service_id
JOIN provider_profiles p ON p.id=s.provider_id
JOIN users u ON u.id=p.user_id
WHERE a.is_open=1 AND s.is_active=1 AND u.is_active=1 AND u.role='provider'
  AND a.starts_at > CURRENT_TIMESTAMP
  AND NOT EXISTS (
    SELECT 1 FROM bookings b
    WHERE b.slot_id=a.id AND b.status IN ('confirmed','completed')
  );
USE shineroster;
CREATE TABLE IF NOT EXISTS auth_limits (
 account_key CHAR(64) CHARACTER SET ascii COLLATE ascii_bin PRIMARY KEY,
 attempts INT UNSIGNED NOT NULL DEFAULT 0,
 window_started DATETIME NOT NULL,
 KEY idx_auth_window (window_started)
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS contact_messages (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 full_name VARCHAR(100) NOT NULL,
 email VARCHAR(190) NOT NULL,
 phone VARCHAR(20) NOT NULL DEFAULT '',
 subject VARCHAR(30) NOT NULL,
 message TEXT NOT NULL,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
