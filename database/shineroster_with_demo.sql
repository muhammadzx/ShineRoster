-- ShineRoster marketplace - initial installation (not a migration).
-- Target: MySQL 8.0.16+ or MariaDB 10.4+ (including recent XAMPP).
-- Import once using phpMyAdmin > Import. UTF-8; prices in JOD.
-- No existing tables or databases are dropped. Reimport into a fresh database.
-- Local DATETIME values: Asia/Amman. Configure PHP to the same timezone.
-- Passwords: PHP password_hash()/password_verify(); never store plain text.
-- Includes synthetic development accounts; see demo-data section below.

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

-- Initial selectable skills, not verified certifications.
INSERT INTO skills (name) VALUES
('التنظيف المنزلي العادي'),
('التنظيف العميق'),
('تنظيف المطابخ'),
('تنظيف الحمامات'),
('تنظيف النوافذ'),
('تنظيف السجاد'),
('تنظيف الكنب والمفروشات'),
('تنظيف المنازل بعد الانتقال');

-- Public rating summary: NULL average and count=0 means no reviews yet.
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

-- Available slots; also recheck these conditions inside the booking transaction.
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

-- APPLICATION CONTRACT (PHP/PDO; essential cross-table rules):
-- 1. Register only customer/provider; create provider + profile in one transaction.
--    Admin is provisioned privately; role changes with related history are forbidden.
-- 2. Check session, is_active, role, ownership and CSRF on every mutation.
-- 3. Serialize provider operations: BEGIN; SELECT id FROM provider_profiles
--    WHERE id=:provider_id FOR UPDATE; then lock needed service/slot rows.
--    All slot additions/edits, bookings, cancellations and service changes must
--    take this same provider lock FIRST, in the same order, and COMMIT/ROLLBACK.
-- 4. When adding/editing a slot, require future start and duration matching service.
--    Reject overlap with ANY other slot for that provider (including closed slots):
--      existing.starts_at < new_end AND existing.ends_at > new_start
--    across ALL their services; exclude the edited slot itself. Adjacent is allowed.
--    Delete unused closed slots if that reserved interval must be reused.
-- 5. Before booking, verify active customer, active provider, active service,
--    open future slot and no occupied booking. Read price from services inside
--    the transaction; never trust a posted price. Catch duplicate-key error.
-- 6. Only confirmed -> cancelled (before start) or confirmed -> completed
--    (after end, by owner-provider/admin). Set matching timestamp in SAME UPDATE.
--    Customer can cancel only own booking. Terminal states cannot be reopened.
-- 7. Insert review only for current customer's completed booking; rating 1..5.
--    Provider cannot edit reviews. Admin may hide via is_visible.
-- 8. Do not change service ownership, booking user/slot or booked slot times.
--    Do not delete services/slots with ANY booking history, even cancelled ones.
--    Deactivate service/account instead. Delete unused child slots before service.
-- 9. Existing slots keep their duration when service duration changes. Apply the
--    new duration to new slots only; display actual booked start/end times.
-- 10. Validate nonempty names/address/city, normalize email, validate phone/email,
--     escape output, use prepared statements, regenerate session after login.
-- 11. Dashboard totals must exclude cancelled bookings where appropriate.
--     Sum completed total_price = completed service value, NOT collected revenue.
-- 12. The database enforces FKs, checks and uniqueness; PHP enforces rules above.

-- ShineRoster interconnected DEMO data. LOCAL DEVELOPMENT ONLY.
-- Import ONCE after shineroster.sql. Existing eight initial skills are reused.
-- Adds 21 users, 10 profiles, 10 skill links, 10 services, 20 slots,
-- 10 completed bookings and 10 reviews. Skills become 10 on a fresh schema.
-- All demo passwords: ShineDemo!2026 (bcrypt hashes, PHP password_verify compatible).
-- Synthetic phone numbers and .test emails; do not contact or deploy demo accounts.
-- Procedure handler rolls back the seed if any statement fails.
SET NAMES utf8mb4;
USE shineroster;
DELIMITER $$
CREATE PROCEDURE seed_shineroster_demo_20261022()
BEGIN
  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    ROLLBACK;
    RESIGNAL;
  END;
  START TRANSACTION;
  INSERT INTO skills (name) SELECT 'التنظيف المنزلي العادي' WHERE NOT EXISTS (SELECT 1 FROM skills WHERE name='التنظيف المنزلي العادي');
  SET @sr_skill_1 = (SELECT id FROM skills WHERE name='التنظيف المنزلي العادي');
  INSERT INTO skills (name) SELECT 'التنظيف العميق' WHERE NOT EXISTS (SELECT 1 FROM skills WHERE name='التنظيف العميق');
  SET @sr_skill_2 = (SELECT id FROM skills WHERE name='التنظيف العميق');
  INSERT INTO skills (name) SELECT 'تنظيف المطابخ' WHERE NOT EXISTS (SELECT 1 FROM skills WHERE name='تنظيف المطابخ');
  SET @sr_skill_3 = (SELECT id FROM skills WHERE name='تنظيف المطابخ');
  INSERT INTO skills (name) SELECT 'تنظيف الحمامات' WHERE NOT EXISTS (SELECT 1 FROM skills WHERE name='تنظيف الحمامات');
  SET @sr_skill_4 = (SELECT id FROM skills WHERE name='تنظيف الحمامات');
  INSERT INTO skills (name) SELECT 'تنظيف النوافذ' WHERE NOT EXISTS (SELECT 1 FROM skills WHERE name='تنظيف النوافذ');
  SET @sr_skill_5 = (SELECT id FROM skills WHERE name='تنظيف النوافذ');
  INSERT INTO skills (name) SELECT 'تنظيف السجاد' WHERE NOT EXISTS (SELECT 1 FROM skills WHERE name='تنظيف السجاد');
  SET @sr_skill_6 = (SELECT id FROM skills WHERE name='تنظيف السجاد');
  INSERT INTO skills (name) SELECT 'تنظيف الكنب والمفروشات' WHERE NOT EXISTS (SELECT 1 FROM skills WHERE name='تنظيف الكنب والمفروشات');
  SET @sr_skill_7 = (SELECT id FROM skills WHERE name='تنظيف الكنب والمفروشات');
  INSERT INTO skills (name) SELECT 'تنظيف المنازل بعد الانتقال' WHERE NOT EXISTS (SELECT 1 FROM skills WHERE name='تنظيف المنازل بعد الانتقال');
  SET @sr_skill_8 = (SELECT id FROM skills WHERE name='تنظيف المنازل بعد الانتقال');
  INSERT INTO skills (name) SELECT 'تنظيف الشرفات' WHERE NOT EXISTS (SELECT 1 FROM skills WHERE name='تنظيف الشرفات');
  SET @sr_skill_9 = (SELECT id FROM skills WHERE name='تنظيف الشرفات');
  INSERT INTO skills (name) SELECT 'تنظيف المنازل بعد الصيانة' WHERE NOT EXISTS (SELECT 1 FROM skills WHERE name='تنظيف المنازل بعد الصيانة');
  SET @sr_skill_10 = (SELECT id FROM skills WHERE name='تنظيف المنازل بعد الصيانة');
  INSERT INTO users (full_name,email,phone,password_hash,role) VALUES ('Demo Admin','admin@shineroster.test','0790000001','$2b$12$cL/JxpQFoYJVFJ5XfOp/QOuVZ3mU9bSuEtiNJo2X9QNm2X56Unan6','admin');
  SET @sr_admin = LAST_INSERT_ID();
  INSERT INTO users (full_name,email,phone,password_hash,role) VALUES ('Ahmad Khalil','provider1@shineroster.test','0790000002','$2b$12$n5rChVxiLIb6hiuqa.YRRuvRNz8ZMpoBWDxWK0np965.Ueac3vKrm','provider');
  SET @sr_provider1 = LAST_INSERT_ID();
  INSERT INTO users (full_name,email,phone,password_hash,role) VALUES ('Omar Hasan','provider2@shineroster.test','0790000003','$2b$12$7mnqF9rW91kss8GgwY4uVu4d4WIqRAIof7aGv30KdU5.9Rt2cUh/W','provider');
  SET @sr_provider2 = LAST_INSERT_ID();
  INSERT INTO users (full_name,email,phone,password_hash,role) VALUES ('Sara Ali','provider3@shineroster.test','0790000004','$2b$12$ldopdFJGtGaz9HeR3jkhTuEYRtWw0G2TfNTav/5Bj0I3HELaA7LAu','provider');
  SET @sr_provider3 = LAST_INSERT_ID();
  INSERT INTO users (full_name,email,phone,password_hash,role) VALUES ('Noor Samir','provider4@shineroster.test','0790000005','$2b$12$J6LvuVT3.TLn9jxE8kuyGe8rIwHOfWqfUeaTmYkwSxjroGhgkMsq.','provider');
  SET @sr_provider4 = LAST_INSERT_ID();
  INSERT INTO users (full_name,email,phone,password_hash,role) VALUES ('Yousef Adel','provider5@shineroster.test','0790000006','$2b$12$9QtXENcXPAJyt.DJFXNWE.iw4XWwsG6zu9aSVceZHDLzehzaOzIeW','provider');
  SET @sr_provider5 = LAST_INSERT_ID();
  INSERT INTO users (full_name,email,phone,password_hash,role) VALUES ('Lina Nasser','provider6@shineroster.test','0790000007','$2b$12$2mUmeQC5L/Mn9XwUKdSnE.oR3FcLRxhAdZvtXB7rtxYOQ58awSu6W','provider');
  SET @sr_provider6 = LAST_INSERT_ID();
  INSERT INTO users (full_name,email,phone,password_hash,role) VALUES ('Khaled Amin','provider7@shineroster.test','0790000008','$2b$12$YXLY7yEV6B9AJ0OVbPeA4ePf5VARt7LF09iLjlkwE32UHpcTAkaK6','provider');
  SET @sr_provider7 = LAST_INSERT_ID();
  INSERT INTO users (full_name,email,phone,password_hash,role) VALUES ('Huda Saleh','provider8@shineroster.test','0790000009','$2b$12$XNkODEdU7DgTXJa6CK4w1.HLTaB.67wwMXHcTFXJ6fg.e5BUts9Mu','provider');
  SET @sr_provider8 = LAST_INSERT_ID();
  INSERT INTO users (full_name,email,phone,password_hash,role) VALUES ('Rami Fares','provider9@shineroster.test','0790000010','$2b$12$Rx7WbnM/CNOTgpnD9KPB.OJeTw4GtELtGBAq5u6vF/UtzDN/RqcEa','provider');
  SET @sr_provider9 = LAST_INSERT_ID();
  INSERT INTO users (full_name,email,phone,password_hash,role) VALUES ('Mona Zaid','provider10@shineroster.test','0790000011','$2b$12$hA9ZvZrwYonp0oaAJGOp2.kgjdVXsqbqUP0g8bGfZUqyXx0UzZgT2','provider');
  SET @sr_provider10 = LAST_INSERT_ID();
  INSERT INTO users (full_name,email,phone,password_hash,role) VALUES ('Muhammad Sami','customer1@shineroster.test','0790000012','$2b$12$Gx7H26BmV4e4lO/W.eD3qe0RUuSA9STSrSnxk1ArFB9ThxB7ctha.','customer');
  SET @sr_customer1 = LAST_INSERT_ID();
  INSERT INTO users (full_name,email,phone,password_hash,role) VALUES ('Layla Omar','customer2@shineroster.test','0790000013','$2b$12$5RBqATY6ycgZg6p7w5vI2erz9FVXDAvDM29/bRg54PoPBTckwnTge','customer');
  SET @sr_customer2 = LAST_INSERT_ID();
  INSERT INTO users (full_name,email,phone,password_hash,role) VALUES ('Zaid Ahmad','customer3@shineroster.test','0790000014','$2b$12$ETX9J9vIzdJjBW08biM95.Of6JWvnSAVr3F2HZnvCJQAQMgx0Xpo2','customer');
  SET @sr_customer3 = LAST_INSERT_ID();
  INSERT INTO users (full_name,email,phone,password_hash,role) VALUES ('Dina Khalil','customer4@shineroster.test','0790000015','$2b$12$.VMZKtlUlW3lpwnyNEwrbO8/uzWaBT5yQvBF1bUG.nt02ba8PiIgO','customer');
  SET @sr_customer4 = LAST_INSERT_ID();
  INSERT INTO users (full_name,email,phone,password_hash,role) VALUES ('Ali Nasser','customer5@shineroster.test','0790000016','$2b$12$qdpTTNUMHi9Alx2mMgMcBuTgVEyWWUyoHxDIOA2yvKqqxHbeSEb2a','customer');
  SET @sr_customer5 = LAST_INSERT_ID();
  INSERT INTO users (full_name,email,phone,password_hash,role) VALUES ('Rana Hasan','customer6@shineroster.test','0790000017','$2b$12$gLWGH8oirxPr5.jOOqbJNeoXQpF6ET1OvTJxhizYbABv2lYkNkDHS','customer');
  SET @sr_customer6 = LAST_INSERT_ID();
  INSERT INTO users (full_name,email,phone,password_hash,role) VALUES ('Sami Adel','customer7@shineroster.test','0790000018','$2b$12$1tUsNDbth3Cz7usrNZYU7.2TLzf/xXkeliqP.AMYOTRR1FdaRLhUm','customer');
  SET @sr_customer7 = LAST_INSERT_ID();
  INSERT INTO users (full_name,email,phone,password_hash,role) VALUES ('Hala Amin','customer8@shineroster.test','0790000019','$2b$12$wFUjGUvMWED8oKAo1jrE0.fqJFKfFIpWktzoRjbEavfEUBb5b.U1q','customer');
  SET @sr_customer8 = LAST_INSERT_ID();
  INSERT INTO users (full_name,email,phone,password_hash,role) VALUES ('Fadi Saleh','customer9@shineroster.test','0790000020','$2b$12$ZCLeIJfPpmquZFpD8oSy4eQnqi4I4qddPocEb5.lxwd6D1XfOH38G','customer');
  SET @sr_customer9 = LAST_INSERT_ID();
  INSERT INTO users (full_name,email,phone,password_hash,role) VALUES ('Reem Fares','customer10@shineroster.test','0790000021','$2b$12$rO9tHk1JdpwkbIc9GX9c3.PbqM4ttC.Kf.ZlpDdz1oXky5/E4r1yy','customer');
  SET @sr_customer10 = LAST_INSERT_ID();
  INSERT INTO provider_profiles (user_id,bio,city) VALUES (@sr_provider1,'Demo cleaning provider specializing in التنظيف المنزلي العادي.','Amman');
  SET @sr_profile_1 = LAST_INSERT_ID();
  INSERT INTO provider_skills (provider_id,skill_id) VALUES (@sr_profile_1,@sr_skill_1);
  INSERT INTO services (provider_id,name,description,price,duration_minutes) VALUES (@sr_profile_1,'التنظيف المنزلي العادي','Scheduled home cleaning service. Price covers the stated visit duration.',25.000,120);
  SET @sr_service_1 = LAST_INSERT_ID();
  INSERT INTO provider_profiles (user_id,bio,city) VALUES (@sr_provider2,'Demo cleaning provider specializing in التنظيف العميق.','Madaba');
  SET @sr_profile_2 = LAST_INSERT_ID();
  INSERT INTO provider_skills (provider_id,skill_id) VALUES (@sr_profile_2,@sr_skill_2);
  INSERT INTO services (provider_id,name,description,price,duration_minutes) VALUES (@sr_profile_2,'التنظيف العميق','Scheduled home cleaning service. Price covers the stated visit duration.',45.000,180);
  SET @sr_service_2 = LAST_INSERT_ID();
  INSERT INTO provider_profiles (user_id,bio,city) VALUES (@sr_provider3,'Demo cleaning provider specializing in تنظيف المطابخ.','Irbid');
  SET @sr_profile_3 = LAST_INSERT_ID();
  INSERT INTO provider_skills (provider_id,skill_id) VALUES (@sr_profile_3,@sr_skill_3);
  INSERT INTO services (provider_id,name,description,price,duration_minutes) VALUES (@sr_profile_3,'تنظيف المطابخ','Scheduled home cleaning service. Price covers the stated visit duration.',20.000,90);
  SET @sr_service_3 = LAST_INSERT_ID();
  INSERT INTO provider_profiles (user_id,bio,city) VALUES (@sr_provider4,'Demo cleaning provider specializing in تنظيف الحمامات.','Zarqa');
  SET @sr_profile_4 = LAST_INSERT_ID();
  INSERT INTO provider_skills (provider_id,skill_id) VALUES (@sr_profile_4,@sr_skill_4);
  INSERT INTO services (provider_id,name,description,price,duration_minutes) VALUES (@sr_profile_4,'تنظيف الحمامات','Scheduled home cleaning service. Price covers the stated visit duration.',20.000,90);
  SET @sr_service_4 = LAST_INSERT_ID();
  INSERT INTO provider_profiles (user_id,bio,city) VALUES (@sr_provider5,'Demo cleaning provider specializing in تنظيف النوافذ.','Salt');
  SET @sr_profile_5 = LAST_INSERT_ID();
  INSERT INTO provider_skills (provider_id,skill_id) VALUES (@sr_profile_5,@sr_skill_5);
  INSERT INTO services (provider_id,name,description,price,duration_minutes) VALUES (@sr_profile_5,'تنظيف النوافذ','Scheduled home cleaning service. Price covers the stated visit duration.',18.000,60);
  SET @sr_service_5 = LAST_INSERT_ID();
  INSERT INTO provider_profiles (user_id,bio,city) VALUES (@sr_provider6,'Demo cleaning provider specializing in تنظيف السجاد.','Amman');
  SET @sr_profile_6 = LAST_INSERT_ID();
  INSERT INTO provider_skills (provider_id,skill_id) VALUES (@sr_profile_6,@sr_skill_6);
  INSERT INTO services (provider_id,name,description,price,duration_minutes) VALUES (@sr_profile_6,'تنظيف السجاد','Scheduled home cleaning service. Price covers the stated visit duration.',35.000,120);
  SET @sr_service_6 = LAST_INSERT_ID();
  INSERT INTO provider_profiles (user_id,bio,city) VALUES (@sr_provider7,'Demo cleaning provider specializing in تنظيف الكنب والمفروشات.','Madaba');
  SET @sr_profile_7 = LAST_INSERT_ID();
  INSERT INTO provider_skills (provider_id,skill_id) VALUES (@sr_profile_7,@sr_skill_7);
  INSERT INTO services (provider_id,name,description,price,duration_minutes) VALUES (@sr_profile_7,'تنظيف الكنب والمفروشات','Scheduled home cleaning service. Price covers the stated visit duration.',40.000,120);
  SET @sr_service_7 = LAST_INSERT_ID();
  INSERT INTO provider_profiles (user_id,bio,city) VALUES (@sr_provider8,'Demo cleaning provider specializing in تنظيف المنازل بعد الانتقال.','Aqaba');
  SET @sr_profile_8 = LAST_INSERT_ID();
  INSERT INTO provider_skills (provider_id,skill_id) VALUES (@sr_profile_8,@sr_skill_8);
  INSERT INTO services (provider_id,name,description,price,duration_minutes) VALUES (@sr_profile_8,'تنظيف المنازل بعد الانتقال','Scheduled home cleaning service. Price covers the stated visit duration.',55.000,180);
  SET @sr_service_8 = LAST_INSERT_ID();
  INSERT INTO provider_profiles (user_id,bio,city) VALUES (@sr_provider9,'Demo cleaning provider specializing in تنظيف الشرفات.','Jerash');
  SET @sr_profile_9 = LAST_INSERT_ID();
  INSERT INTO provider_skills (provider_id,skill_id) VALUES (@sr_profile_9,@sr_skill_9);
  INSERT INTO services (provider_id,name,description,price,duration_minutes) VALUES (@sr_profile_9,'تنظيف الشرفات','Scheduled home cleaning service. Price covers the stated visit duration.',15.000,60);
  SET @sr_service_9 = LAST_INSERT_ID();
  INSERT INTO provider_profiles (user_id,bio,city) VALUES (@sr_provider10,'Demo cleaning provider specializing in تنظيف المنازل بعد الصيانة.','Ajloun');
  SET @sr_profile_10 = LAST_INSERT_ID();
  INSERT INTO provider_skills (provider_id,skill_id) VALUES (@sr_profile_10,@sr_skill_10);
  INSERT INTO services (provider_id,name,description,price,duration_minutes) VALUES (@sr_profile_10,'تنظيف المنازل بعد الصيانة','Scheduled home cleaning service. Price covers the stated visit duration.',60.000,180);
  SET @sr_service_10 = LAST_INSERT_ID();
  INSERT INTO appointment_slots (service_id,starts_at,ends_at,created_at) VALUES (@sr_service_1,TIMESTAMP(DATE_SUB(CURRENT_DATE, INTERVAL 3 DAY), '10:00:00'),DATE_ADD(TIMESTAMP(DATE_SUB(CURRENT_DATE, INTERVAL 3 DAY), '10:00:00'), INTERVAL 120 MINUTE),DATE_SUB(TIMESTAMP(DATE_SUB(CURRENT_DATE, INTERVAL 3 DAY), '10:00:00'), INTERVAL 2 DAY));
  SET @sr_past_slot_1 = LAST_INSERT_ID();
  INSERT INTO bookings (user_id,slot_id,city,address,notes,total_price,status,created_at,completed_at) VALUES (@sr_customer1,@sr_past_slot_1,'Amman','Demo Street 1, Building 11, Floor 1','Fictional completed booking',25.000,'completed',DATE_SUB(TIMESTAMP(DATE_SUB(CURRENT_DATE, INTERVAL 3 DAY), '10:00:00'), INTERVAL 2 DAY),DATE_ADD(TIMESTAMP(DATE_SUB(CURRENT_DATE, INTERVAL 3 DAY), '10:00:00'), INTERVAL 120 MINUTE));
  SET @sr_booking_1 = LAST_INSERT_ID();
  INSERT INTO reviews (booking_id,rating,comment,created_at) VALUES (@sr_booking_1,5,'Excellent service and punctual arrival.',DATE_ADD(DATE_ADD(TIMESTAMP(DATE_SUB(CURRENT_DATE, INTERVAL 3 DAY), '10:00:00'), INTERVAL 120 MINUTE), INTERVAL 1 HOUR));
  INSERT INTO appointment_slots (service_id,starts_at,ends_at) VALUES (@sr_service_1,TIMESTAMP(DATE_ADD(CURRENT_DATE, INTERVAL 2 DAY),'10:00:00'),DATE_ADD(TIMESTAMP(DATE_ADD(CURRENT_DATE, INTERVAL 2 DAY),'10:00:00'), INTERVAL 120 MINUTE));
  INSERT INTO appointment_slots (service_id,starts_at,ends_at,created_at) VALUES (@sr_service_2,TIMESTAMP(DATE_SUB(CURRENT_DATE, INTERVAL 4 DAY), '10:00:00'),DATE_ADD(TIMESTAMP(DATE_SUB(CURRENT_DATE, INTERVAL 4 DAY), '10:00:00'), INTERVAL 180 MINUTE),DATE_SUB(TIMESTAMP(DATE_SUB(CURRENT_DATE, INTERVAL 4 DAY), '10:00:00'), INTERVAL 2 DAY));
  SET @sr_past_slot_2 = LAST_INSERT_ID();
  INSERT INTO bookings (user_id,slot_id,city,address,notes,total_price,status,created_at,completed_at) VALUES (@sr_customer2,@sr_past_slot_2,'Madaba','Demo Street 2, Building 12, Floor 1','Fictional completed booking',45.000,'completed',DATE_SUB(TIMESTAMP(DATE_SUB(CURRENT_DATE, INTERVAL 4 DAY), '10:00:00'), INTERVAL 2 DAY),DATE_ADD(TIMESTAMP(DATE_SUB(CURRENT_DATE, INTERVAL 4 DAY), '10:00:00'), INTERVAL 180 MINUTE));
  SET @sr_booking_2 = LAST_INSERT_ID();
  INSERT INTO reviews (booking_id,rating,comment,created_at) VALUES (@sr_booking_2,4,'Good cleaning and friendly communication.',DATE_ADD(DATE_ADD(TIMESTAMP(DATE_SUB(CURRENT_DATE, INTERVAL 4 DAY), '10:00:00'), INTERVAL 180 MINUTE), INTERVAL 1 HOUR));
  INSERT INTO appointment_slots (service_id,starts_at,ends_at) VALUES (@sr_service_2,TIMESTAMP(DATE_ADD(CURRENT_DATE, INTERVAL 3 DAY),'10:00:00'),DATE_ADD(TIMESTAMP(DATE_ADD(CURRENT_DATE, INTERVAL 3 DAY),'10:00:00'), INTERVAL 180 MINUTE));
  INSERT INTO appointment_slots (service_id,starts_at,ends_at,created_at) VALUES (@sr_service_3,TIMESTAMP(DATE_SUB(CURRENT_DATE, INTERVAL 5 DAY), '10:00:00'),DATE_ADD(TIMESTAMP(DATE_SUB(CURRENT_DATE, INTERVAL 5 DAY), '10:00:00'), INTERVAL 90 MINUTE),DATE_SUB(TIMESTAMP(DATE_SUB(CURRENT_DATE, INTERVAL 5 DAY), '10:00:00'), INTERVAL 2 DAY));
  SET @sr_past_slot_3 = LAST_INSERT_ID();
  INSERT INTO bookings (user_id,slot_id,city,address,notes,total_price,status,created_at,completed_at) VALUES (@sr_customer3,@sr_past_slot_3,'Irbid','Demo Street 3, Building 13, Floor 1','Fictional completed booking',20.000,'completed',DATE_SUB(TIMESTAMP(DATE_SUB(CURRENT_DATE, INTERVAL 5 DAY), '10:00:00'), INTERVAL 2 DAY),DATE_ADD(TIMESTAMP(DATE_SUB(CURRENT_DATE, INTERVAL 5 DAY), '10:00:00'), INTERVAL 90 MINUTE));
  SET @sr_booking_3 = LAST_INSERT_ID();
  INSERT INTO reviews (booking_id,rating,comment,created_at) VALUES (@sr_booking_3,5,'The home was cleaned thoroughly.',DATE_ADD(DATE_ADD(TIMESTAMP(DATE_SUB(CURRENT_DATE, INTERVAL 5 DAY), '10:00:00'), INTERVAL 90 MINUTE), INTERVAL 1 HOUR));
  INSERT INTO appointment_slots (service_id,starts_at,ends_at) VALUES (@sr_service_3,TIMESTAMP(DATE_ADD(CURRENT_DATE, INTERVAL 4 DAY),'10:00:00'),DATE_ADD(TIMESTAMP(DATE_ADD(CURRENT_DATE, INTERVAL 4 DAY),'10:00:00'), INTERVAL 90 MINUTE));
  INSERT INTO appointment_slots (service_id,starts_at,ends_at,created_at) VALUES (@sr_service_4,TIMESTAMP(DATE_SUB(CURRENT_DATE, INTERVAL 6 DAY), '10:00:00'),DATE_ADD(TIMESTAMP(DATE_SUB(CURRENT_DATE, INTERVAL 6 DAY), '10:00:00'), INTERVAL 90 MINUTE),DATE_SUB(TIMESTAMP(DATE_SUB(CURRENT_DATE, INTERVAL 6 DAY), '10:00:00'), INTERVAL 2 DAY));
  SET @sr_past_slot_4 = LAST_INSERT_ID();
  INSERT INTO bookings (user_id,slot_id,city,address,notes,total_price,status,created_at,completed_at) VALUES (@sr_customer4,@sr_past_slot_4,'Zarqa','Demo Street 4, Building 14, Floor 1','Fictional completed booking',20.000,'completed',DATE_SUB(TIMESTAMP(DATE_SUB(CURRENT_DATE, INTERVAL 6 DAY), '10:00:00'), INTERVAL 2 DAY),DATE_ADD(TIMESTAMP(DATE_SUB(CURRENT_DATE, INTERVAL 6 DAY), '10:00:00'), INTERVAL 90 MINUTE));
  SET @sr_booking_4 = LAST_INSERT_ID();
  INSERT INTO reviews (booking_id,rating,comment,created_at) VALUES (@sr_booking_4,4,'Satisfied with the cleaning visit.',DATE_ADD(DATE_ADD(TIMESTAMP(DATE_SUB(CURRENT_DATE, INTERVAL 6 DAY), '10:00:00'), INTERVAL 90 MINUTE), INTERVAL 1 HOUR));
  INSERT INTO appointment_slots (service_id,starts_at,ends_at) VALUES (@sr_service_4,TIMESTAMP(DATE_ADD(CURRENT_DATE, INTERVAL 5 DAY),'10:00:00'),DATE_ADD(TIMESTAMP(DATE_ADD(CURRENT_DATE, INTERVAL 5 DAY),'10:00:00'), INTERVAL 90 MINUTE));
  INSERT INTO appointment_slots (service_id,starts_at,ends_at,created_at) VALUES (@sr_service_5,TIMESTAMP(DATE_SUB(CURRENT_DATE, INTERVAL 7 DAY), '10:00:00'),DATE_ADD(TIMESTAMP(DATE_SUB(CURRENT_DATE, INTERVAL 7 DAY), '10:00:00'), INTERVAL 60 MINUTE),DATE_SUB(TIMESTAMP(DATE_SUB(CURRENT_DATE, INTERVAL 7 DAY), '10:00:00'), INTERVAL 2 DAY));
  SET @sr_past_slot_5 = LAST_INSERT_ID();
  INSERT INTO bookings (user_id,slot_id,city,address,notes,total_price,status,created_at,completed_at) VALUES (@sr_customer5,@sr_past_slot_5,'Salt','Demo Street 5, Building 15, Floor 1','Fictional completed booking',18.000,'completed',DATE_SUB(TIMESTAMP(DATE_SUB(CURRENT_DATE, INTERVAL 7 DAY), '10:00:00'), INTERVAL 2 DAY),DATE_ADD(TIMESTAMP(DATE_SUB(CURRENT_DATE, INTERVAL 7 DAY), '10:00:00'), INTERVAL 60 MINUTE));
  SET @sr_booking_5 = LAST_INSERT_ID();
  INSERT INTO reviews (booking_id,rating,comment,created_at) VALUES (@sr_booking_5,3,'Acceptable service with room for improvement.',DATE_ADD(DATE_ADD(TIMESTAMP(DATE_SUB(CURRENT_DATE, INTERVAL 7 DAY), '10:00:00'), INTERVAL 60 MINUTE), INTERVAL 1 HOUR));
  INSERT INTO appointment_slots (service_id,starts_at,ends_at) VALUES (@sr_service_5,TIMESTAMP(DATE_ADD(CURRENT_DATE, INTERVAL 6 DAY),'10:00:00'),DATE_ADD(TIMESTAMP(DATE_ADD(CURRENT_DATE, INTERVAL 6 DAY),'10:00:00'), INTERVAL 60 MINUTE));
  INSERT INTO appointment_slots (service_id,starts_at,ends_at,created_at) VALUES (@sr_service_6,TIMESTAMP(DATE_SUB(CURRENT_DATE, INTERVAL 8 DAY), '10:00:00'),DATE_ADD(TIMESTAMP(DATE_SUB(CURRENT_DATE, INTERVAL 8 DAY), '10:00:00'), INTERVAL 120 MINUTE),DATE_SUB(TIMESTAMP(DATE_SUB(CURRENT_DATE, INTERVAL 8 DAY), '10:00:00'), INTERVAL 2 DAY));
  SET @sr_past_slot_6 = LAST_INSERT_ID();
  INSERT INTO bookings (user_id,slot_id,city,address,notes,total_price,status,created_at,completed_at) VALUES (@sr_customer6,@sr_past_slot_6,'Amman','Demo Street 6, Building 16, Floor 1','Fictional completed booking',35.000,'completed',DATE_SUB(TIMESTAMP(DATE_SUB(CURRENT_DATE, INTERVAL 8 DAY), '10:00:00'), INTERVAL 2 DAY),DATE_ADD(TIMESTAMP(DATE_SUB(CURRENT_DATE, INTERVAL 8 DAY), '10:00:00'), INTERVAL 120 MINUTE));
  SET @sr_booking_6 = LAST_INSERT_ID();
  INSERT INTO reviews (booking_id,rating,comment,created_at) VALUES (@sr_booking_6,5,'Careful work and excellent results.',DATE_ADD(DATE_ADD(TIMESTAMP(DATE_SUB(CURRENT_DATE, INTERVAL 8 DAY), '10:00:00'), INTERVAL 120 MINUTE), INTERVAL 1 HOUR));
  INSERT INTO appointment_slots (service_id,starts_at,ends_at) VALUES (@sr_service_6,TIMESTAMP(DATE_ADD(CURRENT_DATE, INTERVAL 7 DAY),'10:00:00'),DATE_ADD(TIMESTAMP(DATE_ADD(CURRENT_DATE, INTERVAL 7 DAY),'10:00:00'), INTERVAL 120 MINUTE));
  INSERT INTO appointment_slots (service_id,starts_at,ends_at,created_at) VALUES (@sr_service_7,TIMESTAMP(DATE_SUB(CURRENT_DATE, INTERVAL 9 DAY), '10:00:00'),DATE_ADD(TIMESTAMP(DATE_SUB(CURRENT_DATE, INTERVAL 9 DAY), '10:00:00'), INTERVAL 120 MINUTE),DATE_SUB(TIMESTAMP(DATE_SUB(CURRENT_DATE, INTERVAL 9 DAY), '10:00:00'), INTERVAL 2 DAY));
  SET @sr_past_slot_7 = LAST_INSERT_ID();
  INSERT INTO bookings (user_id,slot_id,city,address,notes,total_price,status,created_at,completed_at) VALUES (@sr_customer7,@sr_past_slot_7,'Madaba','Demo Street 7, Building 17, Floor 1','Fictional completed booking',40.000,'completed',DATE_SUB(TIMESTAMP(DATE_SUB(CURRENT_DATE, INTERVAL 9 DAY), '10:00:00'), INTERVAL 2 DAY),DATE_ADD(TIMESTAMP(DATE_SUB(CURRENT_DATE, INTERVAL 9 DAY), '10:00:00'), INTERVAL 120 MINUTE));
  SET @sr_booking_7 = LAST_INSERT_ID();
  INSERT INTO reviews (booking_id,rating,comment,created_at) VALUES (@sr_booking_7,4,'A good experience overall.',DATE_ADD(DATE_ADD(TIMESTAMP(DATE_SUB(CURRENT_DATE, INTERVAL 9 DAY), '10:00:00'), INTERVAL 120 MINUTE), INTERVAL 1 HOUR));
  INSERT INTO appointment_slots (service_id,starts_at,ends_at) VALUES (@sr_service_7,TIMESTAMP(DATE_ADD(CURRENT_DATE, INTERVAL 8 DAY),'10:00:00'),DATE_ADD(TIMESTAMP(DATE_ADD(CURRENT_DATE, INTERVAL 8 DAY),'10:00:00'), INTERVAL 120 MINUTE));
  INSERT INTO appointment_slots (service_id,starts_at,ends_at,created_at) VALUES (@sr_service_8,TIMESTAMP(DATE_SUB(CURRENT_DATE, INTERVAL 10 DAY), '10:00:00'),DATE_ADD(TIMESTAMP(DATE_SUB(CURRENT_DATE, INTERVAL 10 DAY), '10:00:00'), INTERVAL 180 MINUTE),DATE_SUB(TIMESTAMP(DATE_SUB(CURRENT_DATE, INTERVAL 10 DAY), '10:00:00'), INTERVAL 2 DAY));
  SET @sr_past_slot_8 = LAST_INSERT_ID();
  INSERT INTO bookings (user_id,slot_id,city,address,notes,total_price,status,created_at,completed_at) VALUES (@sr_customer8,@sr_past_slot_8,'Aqaba','Demo Street 8, Building 18, Floor 1','Fictional completed booking',55.000,'completed',DATE_SUB(TIMESTAMP(DATE_SUB(CURRENT_DATE, INTERVAL 10 DAY), '10:00:00'), INTERVAL 2 DAY),DATE_ADD(TIMESTAMP(DATE_SUB(CURRENT_DATE, INTERVAL 10 DAY), '10:00:00'), INTERVAL 180 MINUTE));
  SET @sr_booking_8 = LAST_INSERT_ID();
  INSERT INTO reviews (booking_id,rating,comment,created_at) VALUES (@sr_booking_8,5,'Professional and well organized.',DATE_ADD(DATE_ADD(TIMESTAMP(DATE_SUB(CURRENT_DATE, INTERVAL 10 DAY), '10:00:00'), INTERVAL 180 MINUTE), INTERVAL 1 HOUR));
  INSERT INTO appointment_slots (service_id,starts_at,ends_at) VALUES (@sr_service_8,TIMESTAMP(DATE_ADD(CURRENT_DATE, INTERVAL 9 DAY),'10:00:00'),DATE_ADD(TIMESTAMP(DATE_ADD(CURRENT_DATE, INTERVAL 9 DAY),'10:00:00'), INTERVAL 180 MINUTE));
  INSERT INTO appointment_slots (service_id,starts_at,ends_at,created_at) VALUES (@sr_service_9,TIMESTAMP(DATE_SUB(CURRENT_DATE, INTERVAL 11 DAY), '10:00:00'),DATE_ADD(TIMESTAMP(DATE_SUB(CURRENT_DATE, INTERVAL 11 DAY), '10:00:00'), INTERVAL 60 MINUTE),DATE_SUB(TIMESTAMP(DATE_SUB(CURRENT_DATE, INTERVAL 11 DAY), '10:00:00'), INTERVAL 2 DAY));
  SET @sr_past_slot_9 = LAST_INSERT_ID();
  INSERT INTO bookings (user_id,slot_id,city,address,notes,total_price,status,created_at,completed_at) VALUES (@sr_customer9,@sr_past_slot_9,'Jerash','Demo Street 9, Building 19, Floor 1','Fictional completed booking',15.000,'completed',DATE_SUB(TIMESTAMP(DATE_SUB(CURRENT_DATE, INTERVAL 11 DAY), '10:00:00'), INTERVAL 2 DAY),DATE_ADD(TIMESTAMP(DATE_SUB(CURRENT_DATE, INTERVAL 11 DAY), '10:00:00'), INTERVAL 60 MINUTE));
  SET @sr_booking_9 = LAST_INSERT_ID();
  INSERT INTO reviews (booking_id,rating,comment,created_at) VALUES (@sr_booking_9,4,'Good results and clear communication.',DATE_ADD(DATE_ADD(TIMESTAMP(DATE_SUB(CURRENT_DATE, INTERVAL 11 DAY), '10:00:00'), INTERVAL 60 MINUTE), INTERVAL 1 HOUR));
  INSERT INTO appointment_slots (service_id,starts_at,ends_at) VALUES (@sr_service_9,TIMESTAMP(DATE_ADD(CURRENT_DATE, INTERVAL 10 DAY),'10:00:00'),DATE_ADD(TIMESTAMP(DATE_ADD(CURRENT_DATE, INTERVAL 10 DAY),'10:00:00'), INTERVAL 60 MINUTE));
  INSERT INTO appointment_slots (service_id,starts_at,ends_at,created_at) VALUES (@sr_service_10,TIMESTAMP(DATE_SUB(CURRENT_DATE, INTERVAL 12 DAY), '10:00:00'),DATE_ADD(TIMESTAMP(DATE_SUB(CURRENT_DATE, INTERVAL 12 DAY), '10:00:00'), INTERVAL 180 MINUTE),DATE_SUB(TIMESTAMP(DATE_SUB(CURRENT_DATE, INTERVAL 12 DAY), '10:00:00'), INTERVAL 2 DAY));
  SET @sr_past_slot_10 = LAST_INSERT_ID();
  INSERT INTO bookings (user_id,slot_id,city,address,notes,total_price,status,created_at,completed_at) VALUES (@sr_customer10,@sr_past_slot_10,'Ajloun','Demo Street 10, Building 20, Floor 1','Fictional completed booking',60.000,'completed',DATE_SUB(TIMESTAMP(DATE_SUB(CURRENT_DATE, INTERVAL 12 DAY), '10:00:00'), INTERVAL 2 DAY),DATE_ADD(TIMESTAMP(DATE_SUB(CURRENT_DATE, INTERVAL 12 DAY), '10:00:00'), INTERVAL 180 MINUTE));
  SET @sr_booking_10 = LAST_INSERT_ID();
  INSERT INTO reviews (booking_id,rating,comment,created_at) VALUES (@sr_booking_10,5,'Very happy with the completed service.',DATE_ADD(DATE_ADD(TIMESTAMP(DATE_SUB(CURRENT_DATE, INTERVAL 12 DAY), '10:00:00'), INTERVAL 180 MINUTE), INTERVAL 1 HOUR));
  INSERT INTO appointment_slots (service_id,starts_at,ends_at) VALUES (@sr_service_10,TIMESTAMP(DATE_ADD(CURRENT_DATE, INTERVAL 11 DAY),'10:00:00'),DATE_ADD(TIMESTAMP(DATE_ADD(CURRENT_DATE, INTERVAL 11 DAY),'10:00:00'), INTERVAL 180 MINUTE));
  COMMIT;
END$$
DELIMITER ;
CALL seed_shineroster_demo_20261022();
DROP PROCEDURE seed_shineroster_demo_20261022;

-- Run this once on an existing ShineRoster database; does not remove data.
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
