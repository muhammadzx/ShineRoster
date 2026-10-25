-- Fresh installation with synthetic demo data.
CREATE DATABASE IF NOT EXISTS shineroster CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE shineroster;

CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    phone VARCHAR(20) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role VARCHAR(20) NOT NULL DEFAULT 'customer',
    is_active TINYINT NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE provider_profiles (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL UNIQUE,
    bio TEXT,
    city VARCHAR(100) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

CREATE TABLE skills (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE
);

CREATE TABLE provider_skills (
    provider_id INT UNSIGNED NOT NULL,
    skill_id INT UNSIGNED NOT NULL,
    PRIMARY KEY (provider_id, skill_id),
    FOREIGN KEY (provider_id) REFERENCES provider_profiles(id),
    FOREIGN KEY (skill_id) REFERENCES skills(id)
);

CREATE TABLE services (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    provider_id INT UNSIGNED NOT NULL,
    name VARCHAR(120) NOT NULL,
    description TEXT NOT NULL,
    price DECIMAL(10,3) NOT NULL,
    duration_minutes SMALLINT UNSIGNED NOT NULL,
    is_active TINYINT NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (provider_id) REFERENCES provider_profiles(id)
);

CREATE TABLE appointment_slots (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    service_id INT UNSIGNED NOT NULL,
    starts_at DATETIME NOT NULL,
    ends_at DATETIME NOT NULL,
    is_open TINYINT NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE (service_id, starts_at),
    FOREIGN KEY (service_id) REFERENCES services(id)
);

CREATE TABLE bookings (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    slot_id INT UNSIGNED NOT NULL,
    city VARCHAR(100) NOT NULL,
    address VARCHAR(255) NOT NULL,
    notes TEXT,
    total_price DECIMAL(10,3) NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'confirmed',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    cancelled_at DATETIME,
    completed_at DATETIME,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (slot_id) REFERENCES appointment_slots(id)
);

CREATE TABLE reviews (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    booking_id INT UNSIGNED NOT NULL UNIQUE,
    rating TINYINT UNSIGNED NOT NULL,
    comment TEXT,
    is_visible TINYINT NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (booking_id) REFERENCES bookings(id)
);

CREATE TABLE contact_messages (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(190) NOT NULL,
    phone VARCHAR(20) NOT NULL DEFAULT '',
    subject VARCHAR(30) NOT NULL,
    message TEXT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO skills (name) VALUES
('التنظيف المنزلي العادي'),
('التنظيف العميق'),
('تنظيف المطابخ'),
('تنظيف الحمامات'),
('تنظيف النوافذ'),
('تنظيف السجاد'),
('تنظيف الكنب والمفروشات'),
('تنظيف المنازل بعد الانتقال'),
('تنظيف الشرفات'),
('تنظيف المنازل بعد الصيانة');

INSERT INTO users (full_name,email,phone,password_hash,role) VALUES ('Demo Admin','admin@shineroster.test','0790000001','$2b$12$cL/JxpQFoYJVFJ5XfOp/QOuVZ3mU9bSuEtiNJo2X9QNm2X56Unan6','admin');
INSERT INTO users (full_name,email,phone,password_hash,role) VALUES ('Ahmad Khalil','provider1@shineroster.test','0790000002','$2b$12$n5rChVxiLIb6hiuqa.YRRuvRNz8ZMpoBWDxWK0np965.Ueac3vKrm','provider');
INSERT INTO users (full_name,email,phone,password_hash,role) VALUES ('Omar Hasan','provider2@shineroster.test','0790000003','$2b$12$7mnqF9rW91kss8GgwY4uVu4d4WIqRAIof7aGv30KdU5.9Rt2cUh/W','provider');
INSERT INTO users (full_name,email,phone,password_hash,role) VALUES ('Sara Ali','provider3@shineroster.test','0790000004','$2b$12$ldopdFJGtGaz9HeR3jkhTuEYRtWw0G2TfNTav/5Bj0I3HELaA7LAu','provider');
INSERT INTO users (full_name,email,phone,password_hash,role) VALUES ('Noor Samir','provider4@shineroster.test','0790000005','$2b$12$J6LvuVT3.TLn9jxE8kuyGe8rIwHOfWqfUeaTmYkwSxjroGhgkMsq.','provider');
INSERT INTO users (full_name,email,phone,password_hash,role) VALUES ('Yousef Adel','provider5@shineroster.test','0790000006','$2b$12$9QtXENcXPAJyt.DJFXNWE.iw4XWwsG6zu9aSVceZHDLzehzaOzIeW','provider');
INSERT INTO users (full_name,email,phone,password_hash,role) VALUES ('Lina Nasser','provider6@shineroster.test','0790000007','$2b$12$2mUmeQC5L/Mn9XwUKdSnE.oR3FcLRxhAdZvtXB7rtxYOQ58awSu6W','provider');
INSERT INTO users (full_name,email,phone,password_hash,role) VALUES ('Khaled Amin','provider7@shineroster.test','0790000008','$2b$12$YXLY7yEV6B9AJ0OVbPeA4ePf5VARt7LF09iLjlkwE32UHpcTAkaK6','provider');
INSERT INTO users (full_name,email,phone,password_hash,role) VALUES ('Huda Saleh','provider8@shineroster.test','0790000009','$2b$12$XNkODEdU7DgTXJa6CK4w1.HLTaB.67wwMXHcTFXJ6fg.e5BUts9Mu','provider');
INSERT INTO users (full_name,email,phone,password_hash,role) VALUES ('Rami Fares','provider9@shineroster.test','0790000010','$2b$12$Rx7WbnM/CNOTgpnD9KPB.OJeTw4GtELtGBAq5u6vF/UtzDN/RqcEa','provider');
INSERT INTO users (full_name,email,phone,password_hash,role) VALUES ('Mona Zaid','provider10@shineroster.test','0790000011','$2b$12$hA9ZvZrwYonp0oaAJGOp2.kgjdVXsqbqUP0g8bGfZUqyXx0UzZgT2','provider');
INSERT INTO users (full_name,email,phone,password_hash,role) VALUES ('Muhammad Sami','customer1@shineroster.test','0790000012','$2b$12$Gx7H26BmV4e4lO/W.eD3qe0RUuSA9STSrSnxk1ArFB9ThxB7ctha.','customer');
INSERT INTO users (full_name,email,phone,password_hash,role) VALUES ('Layla Omar','customer2@shineroster.test','0790000013','$2b$12$5RBqATY6ycgZg6p7w5vI2erz9FVXDAvDM29/bRg54PoPBTckwnTge','customer');
INSERT INTO users (full_name,email,phone,password_hash,role) VALUES ('Zaid Ahmad','customer3@shineroster.test','0790000014','$2b$12$ETX9J9vIzdJjBW08biM95.Of6JWvnSAVr3F2HZnvCJQAQMgx0Xpo2','customer');
INSERT INTO users (full_name,email,phone,password_hash,role) VALUES ('Dina Khalil','customer4@shineroster.test','0790000015','$2b$12$.VMZKtlUlW3lpwnyNEwrbO8/uzWaBT5yQvBF1bUG.nt02ba8PiIgO','customer');
INSERT INTO users (full_name,email,phone,password_hash,role) VALUES ('Ali Nasser','customer5@shineroster.test','0790000016','$2b$12$qdpTTNUMHi9Alx2mMgMcBuTgVEyWWUyoHxDIOA2yvKqqxHbeSEb2a','customer');
INSERT INTO users (full_name,email,phone,password_hash,role) VALUES ('Rana Hasan','customer6@shineroster.test','0790000017','$2b$12$gLWGH8oirxPr5.jOOqbJNeoXQpF6ET1OvTJxhizYbABv2lYkNkDHS','customer');
INSERT INTO users (full_name,email,phone,password_hash,role) VALUES ('Sami Adel','customer7@shineroster.test','0790000018','$2b$12$1tUsNDbth3Cz7usrNZYU7.2TLzf/xXkeliqP.AMYOTRR1FdaRLhUm','customer');
INSERT INTO users (full_name,email,phone,password_hash,role) VALUES ('Hala Amin','customer8@shineroster.test','0790000019','$2b$12$wFUjGUvMWED8oKAo1jrE0.fqJFKfFIpWktzoRjbEavfEUBb5b.U1q','customer');
INSERT INTO users (full_name,email,phone,password_hash,role) VALUES ('Fadi Saleh','customer9@shineroster.test','0790000020','$2b$12$ZCLeIJfPpmquZFpD8oSy4eQnqi4I4qddPocEb5.lxwd6D1XfOH38G','customer');
INSERT INTO users (full_name,email,phone,password_hash,role) VALUES ('Reem Fares','customer10@shineroster.test','0790000021','$2b$12$rO9tHk1JdpwkbIc9GX9c3.PbqM4ttC.Kf.ZlpDdz1oXky5/E4r1yy','customer');
INSERT INTO provider_profiles (user_id,bio,city) VALUES (2,'Demo cleaning provider specializing in التنظيف المنزلي العادي.','Amman');
INSERT INTO provider_skills (provider_id,skill_id) VALUES (1,1);
INSERT INTO services (provider_id,name,description,price,duration_minutes) VALUES (1,'التنظيف المنزلي العادي','Scheduled home cleaning service. Price covers the stated visit duration.',25.000,120);
INSERT INTO provider_profiles (user_id,bio,city) VALUES (3,'Demo cleaning provider specializing in التنظيف العميق.','Madaba');
INSERT INTO provider_skills (provider_id,skill_id) VALUES (2,2);
INSERT INTO services (provider_id,name,description,price,duration_minutes) VALUES (2,'التنظيف العميق','Scheduled home cleaning service. Price covers the stated visit duration.',45.000,180);
INSERT INTO provider_profiles (user_id,bio,city) VALUES (4,'Demo cleaning provider specializing in تنظيف المطابخ.','Irbid');
INSERT INTO provider_skills (provider_id,skill_id) VALUES (3,3);
INSERT INTO services (provider_id,name,description,price,duration_minutes) VALUES (3,'تنظيف المطابخ','Scheduled home cleaning service. Price covers the stated visit duration.',20.000,90);
INSERT INTO provider_profiles (user_id,bio,city) VALUES (5,'Demo cleaning provider specializing in تنظيف الحمامات.','Zarqa');
INSERT INTO provider_skills (provider_id,skill_id) VALUES (4,4);
INSERT INTO services (provider_id,name,description,price,duration_minutes) VALUES (4,'تنظيف الحمامات','Scheduled home cleaning service. Price covers the stated visit duration.',20.000,90);
INSERT INTO provider_profiles (user_id,bio,city) VALUES (6,'Demo cleaning provider specializing in تنظيف النوافذ.','Salt');
INSERT INTO provider_skills (provider_id,skill_id) VALUES (5,5);
INSERT INTO services (provider_id,name,description,price,duration_minutes) VALUES (5,'تنظيف النوافذ','Scheduled home cleaning service. Price covers the stated visit duration.',18.000,60);
INSERT INTO provider_profiles (user_id,bio,city) VALUES (7,'Demo cleaning provider specializing in تنظيف السجاد.','Amman');
INSERT INTO provider_skills (provider_id,skill_id) VALUES (6,6);
INSERT INTO services (provider_id,name,description,price,duration_minutes) VALUES (6,'تنظيف السجاد','Scheduled home cleaning service. Price covers the stated visit duration.',35.000,120);
INSERT INTO provider_profiles (user_id,bio,city) VALUES (8,'Demo cleaning provider specializing in تنظيف الكنب والمفروشات.','Madaba');
INSERT INTO provider_skills (provider_id,skill_id) VALUES (7,7);
INSERT INTO services (provider_id,name,description,price,duration_minutes) VALUES (7,'تنظيف الكنب والمفروشات','Scheduled home cleaning service. Price covers the stated visit duration.',40.000,120);
INSERT INTO provider_profiles (user_id,bio,city) VALUES (9,'Demo cleaning provider specializing in تنظيف المنازل بعد الانتقال.','Aqaba');
INSERT INTO provider_skills (provider_id,skill_id) VALUES (8,8);
INSERT INTO services (provider_id,name,description,price,duration_minutes) VALUES (8,'تنظيف المنازل بعد الانتقال','Scheduled home cleaning service. Price covers the stated visit duration.',55.000,180);
INSERT INTO provider_profiles (user_id,bio,city) VALUES (10,'Demo cleaning provider specializing in تنظيف الشرفات.','Jerash');
INSERT INTO provider_skills (provider_id,skill_id) VALUES (9,9);
INSERT INTO services (provider_id,name,description,price,duration_minutes) VALUES (9,'تنظيف الشرفات','Scheduled home cleaning service. Price covers the stated visit duration.',15.000,60);
INSERT INTO provider_profiles (user_id,bio,city) VALUES (11,'Demo cleaning provider specializing in تنظيف المنازل بعد الصيانة.','Ajloun');
INSERT INTO provider_skills (provider_id,skill_id) VALUES (10,10);
INSERT INTO services (provider_id,name,description,price,duration_minutes) VALUES (10,'تنظيف المنازل بعد الصيانة','Scheduled home cleaning service. Price covers the stated visit duration.',60.000,180);
INSERT INTO appointment_slots (service_id,starts_at,ends_at) VALUES (1,'2026-10-14 10:00:00','2026-10-14 12:00:00');
INSERT INTO appointment_slots (service_id,starts_at,ends_at) VALUES (1,'2026-11-25 10:00:00','2026-11-25 12:00:00');
INSERT INTO bookings (user_id,slot_id,city,address,notes,total_price,status,created_at,completed_at) VALUES (12,1,'Amman','Demo street 1, building 1','Synthetic demo booking',25.000,'completed','2026-10-13 10:00:00','2026-10-14 12:00:00');
INSERT INTO reviews (booking_id,rating,comment,created_at) VALUES (1,5,'A good cleaning experience.','2026-10-14 12:00:00');
INSERT INTO contact_messages (full_name,email,phone,subject,message) VALUES ('Demo Contact 1','contact1@shineroster.test','0790000001','other','This is a synthetic demonstration message.');
INSERT INTO appointment_slots (service_id,starts_at,ends_at) VALUES (2,'2026-10-13 10:00:00','2026-10-13 13:00:00');
INSERT INTO appointment_slots (service_id,starts_at,ends_at) VALUES (2,'2026-11-26 10:00:00','2026-11-26 13:00:00');
INSERT INTO bookings (user_id,slot_id,city,address,notes,total_price,status,created_at,completed_at) VALUES (13,3,'Amman','Demo street 2, building 2','Synthetic demo booking',45.000,'completed','2026-10-12 10:00:00','2026-10-13 13:00:00');
INSERT INTO reviews (booking_id,rating,comment,created_at) VALUES (2,4,'A good cleaning experience.','2026-10-13 13:00:00');
INSERT INTO contact_messages (full_name,email,phone,subject,message) VALUES ('Demo Contact 2','contact2@shineroster.test','0790000002','other','This is a synthetic demonstration message.');
INSERT INTO appointment_slots (service_id,starts_at,ends_at) VALUES (3,'2026-10-12 10:00:00','2026-10-12 11:30:00');
INSERT INTO appointment_slots (service_id,starts_at,ends_at) VALUES (3,'2026-11-27 10:00:00','2026-11-27 11:30:00');
INSERT INTO bookings (user_id,slot_id,city,address,notes,total_price,status,created_at,completed_at) VALUES (14,5,'Amman','Demo street 3, building 3','Synthetic demo booking',20.000,'completed','2026-10-11 10:00:00','2026-10-12 11:30:00');
INSERT INTO reviews (booking_id,rating,comment,created_at) VALUES (3,5,'A good cleaning experience.','2026-10-12 11:30:00');
INSERT INTO contact_messages (full_name,email,phone,subject,message) VALUES ('Demo Contact 3','contact3@shineroster.test','0790000003','other','This is a synthetic demonstration message.');
INSERT INTO appointment_slots (service_id,starts_at,ends_at) VALUES (4,'2026-10-11 10:00:00','2026-10-11 11:30:00');
INSERT INTO appointment_slots (service_id,starts_at,ends_at) VALUES (4,'2026-11-28 10:00:00','2026-11-28 11:30:00');
INSERT INTO bookings (user_id,slot_id,city,address,notes,total_price,status,created_at,completed_at) VALUES (15,7,'Amman','Demo street 4, building 4','Synthetic demo booking',20.000,'completed','2026-10-10 10:00:00','2026-10-11 11:30:00');
INSERT INTO reviews (booking_id,rating,comment,created_at) VALUES (4,4,'A good cleaning experience.','2026-10-11 11:30:00');
INSERT INTO contact_messages (full_name,email,phone,subject,message) VALUES ('Demo Contact 4','contact4@shineroster.test','0790000004','other','This is a synthetic demonstration message.');
INSERT INTO appointment_slots (service_id,starts_at,ends_at) VALUES (5,'2026-10-10 10:00:00','2026-10-10 11:00:00');
INSERT INTO appointment_slots (service_id,starts_at,ends_at) VALUES (5,'2026-11-29 10:00:00','2026-11-29 11:00:00');
INSERT INTO bookings (user_id,slot_id,city,address,notes,total_price,status,created_at,completed_at) VALUES (16,9,'Amman','Demo street 5, building 5','Synthetic demo booking',18.000,'completed','2026-10-09 10:00:00','2026-10-10 11:00:00');
INSERT INTO reviews (booking_id,rating,comment,created_at) VALUES (5,5,'A good cleaning experience.','2026-10-10 11:00:00');
INSERT INTO contact_messages (full_name,email,phone,subject,message) VALUES ('Demo Contact 5','contact5@shineroster.test','0790000005','other','This is a synthetic demonstration message.');
INSERT INTO appointment_slots (service_id,starts_at,ends_at) VALUES (6,'2026-10-09 10:00:00','2026-10-09 12:00:00');
INSERT INTO appointment_slots (service_id,starts_at,ends_at) VALUES (6,'2026-11-30 10:00:00','2026-11-30 12:00:00');
INSERT INTO bookings (user_id,slot_id,city,address,notes,total_price,status,created_at,completed_at) VALUES (17,11,'Amman','Demo street 6, building 6','Synthetic demo booking',35.000,'completed','2026-10-08 10:00:00','2026-10-09 12:00:00');
INSERT INTO reviews (booking_id,rating,comment,created_at) VALUES (6,4,'A good cleaning experience.','2026-10-09 12:00:00');
INSERT INTO contact_messages (full_name,email,phone,subject,message) VALUES ('Demo Contact 6','contact6@shineroster.test','0790000006','other','This is a synthetic demonstration message.');
INSERT INTO appointment_slots (service_id,starts_at,ends_at) VALUES (7,'2026-10-08 10:00:00','2026-10-08 12:00:00');
INSERT INTO appointment_slots (service_id,starts_at,ends_at) VALUES (7,'2026-12-01 10:00:00','2026-12-01 12:00:00');
INSERT INTO bookings (user_id,slot_id,city,address,notes,total_price,status,created_at,completed_at) VALUES (18,13,'Amman','Demo street 7, building 7','Synthetic demo booking',40.000,'completed','2026-10-07 10:00:00','2026-10-08 12:00:00');
INSERT INTO reviews (booking_id,rating,comment,created_at) VALUES (7,5,'A good cleaning experience.','2026-10-08 12:00:00');
INSERT INTO contact_messages (full_name,email,phone,subject,message) VALUES ('Demo Contact 7','contact7@shineroster.test','0790000007','other','This is a synthetic demonstration message.');
INSERT INTO appointment_slots (service_id,starts_at,ends_at) VALUES (8,'2026-10-07 10:00:00','2026-10-07 13:00:00');
INSERT INTO appointment_slots (service_id,starts_at,ends_at) VALUES (8,'2026-12-02 10:00:00','2026-12-02 13:00:00');
INSERT INTO bookings (user_id,slot_id,city,address,notes,total_price,status,created_at,completed_at) VALUES (19,15,'Amman','Demo street 8, building 8','Synthetic demo booking',55.000,'completed','2026-10-06 10:00:00','2026-10-07 13:00:00');
INSERT INTO reviews (booking_id,rating,comment,created_at) VALUES (8,4,'A good cleaning experience.','2026-10-07 13:00:00');
INSERT INTO contact_messages (full_name,email,phone,subject,message) VALUES ('Demo Contact 8','contact8@shineroster.test','0790000008','other','This is a synthetic demonstration message.');
INSERT INTO appointment_slots (service_id,starts_at,ends_at) VALUES (9,'2026-10-06 10:00:00','2026-10-06 11:00:00');
INSERT INTO appointment_slots (service_id,starts_at,ends_at) VALUES (9,'2026-12-03 10:00:00','2026-12-03 11:00:00');
INSERT INTO bookings (user_id,slot_id,city,address,notes,total_price,status,created_at,completed_at) VALUES (20,17,'Amman','Demo street 9, building 9','Synthetic demo booking',15.000,'completed','2026-10-05 10:00:00','2026-10-06 11:00:00');
INSERT INTO reviews (booking_id,rating,comment,created_at) VALUES (9,5,'A good cleaning experience.','2026-10-06 11:00:00');
INSERT INTO contact_messages (full_name,email,phone,subject,message) VALUES ('Demo Contact 9','contact9@shineroster.test','0790000009','other','This is a synthetic demonstration message.');
INSERT INTO appointment_slots (service_id,starts_at,ends_at) VALUES (10,'2026-10-05 10:00:00','2026-10-05 13:00:00');
INSERT INTO appointment_slots (service_id,starts_at,ends_at) VALUES (10,'2026-12-04 10:00:00','2026-12-04 13:00:00');
INSERT INTO bookings (user_id,slot_id,city,address,notes,total_price,status,created_at,completed_at) VALUES (21,19,'Amman','Demo street 10, building 10','Synthetic demo booking',60.000,'completed','2026-10-04 10:00:00','2026-10-05 13:00:00');
INSERT INTO reviews (booking_id,rating,comment,created_at) VALUES (10,4,'A good cleaning experience.','2026-10-05 13:00:00');
INSERT INTO contact_messages (full_name,email,phone,subject,message) VALUES ('Demo Contact 10','contact10@shineroster.test','0790000010','other','This is a synthetic demonstration message.');
