CREATE DATABASE IF NOT EXISTS graduation_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE graduation_db;

CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(190) NOT NULL UNIQUE,
  pass_hash VARCHAR(255) NOT NULL,
  role ENUM('student','admin','superadmin') NOT NULL DEFAULT 'student',
  full_name VARCHAR(190) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE students (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL UNIQUE,
  faculty_no VARCHAR(50) NOT NULL UNIQUE,
  degree ENUM('Бакалавър','Магистър','Доктор') NOT NULL,
  program_name VARCHAR(190) NOT NULL,
  group_code VARCHAR(50) NOT NULL,  
  phone VARCHAR(50) NULL,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE grad_process (
  id INT AUTO_INCREMENT PRIMARY KEY,
  student_id INT NOT NULL UNIQUE,
  stage TINYINT NOT NULL DEFAULT 0,
  registered_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  confirmed_at TIMESTAMP NULL,
  ceremony_checked_in_at TIMESTAMP NULL,
  diploma_received_at TIMESTAMP NULL,
  gown_requested TINYINT NOT NULL DEFAULT 0,
  gown_taken TINYINT NOT NULL DEFAULT 0,
  gown_returned TINYINT NOT NULL DEFAULT 0,
  is_honors TINYINT NOT NULL DEFAULT 0,
  reward_badge TINYINT NOT NULL DEFAULT 0,
  reward_calendar TINYINT NOT NULL DEFAULT 0,
  notes TEXT NULL,
  FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
);

CREATE TABLE responsibilities (
  id INT AUTO_INCREMENT PRIMARY KEY,
  type ENUM('gowns','signatures','diplomas') NOT NULL,
  person_name VARCHAR(190) NOT NULL,
  email VARCHAR(190) NULL,
  phone VARCHAR(50) NULL,
  active TINYINT NOT NULL DEFAULT 1
);

CREATE TABLE qr_tokens (
  id INT AUTO_INCREMENT PRIMARY KEY,
  student_id INT NOT NULL,
  token VARCHAR(64) NOT NULL UNIQUE,
  purpose ENUM('checkin','gown_take','gown_return','diploma') NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  used_at TIMESTAMP NULL,
  FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
);

INSERT INTO users(email, pass_hash, role, full_name) VALUES
('admin@uni.test',  SHA2('admin123', 256), 'superadmin', 'System Admin');

INSERT INTO responsibilities(type, person_name, email, phone) VALUES
('gowns','Мария Георгиева','gowns@uni.test','0888000001'),
('signatures','Доц. Петров','sign@uni.test','0888000002'),
('diplomas','Гл. специалист Димитрова','diplomas@uni.test','0888000003');

CREATE TABLE IF NOT EXISTS guest_tickets (
  id INT AUTO_INCREMENT PRIMARY KEY,
  student_id INT NOT NULL,
  token VARCHAR(64) NOT NULL UNIQUE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  used_at TIMESTAMP NULL,
  used_by_user_id INT NULL,
  FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
  FOREIGN KEY (used_by_user_id) REFERENCES users(id) ON DELETE SET NULL
);

ALTER TABLE grad_process
  ADD COLUMN guests_allowed INT NOT NULL DEFAULT 2;

CREATE TABLE IF NOT EXISTS student_qr (
  id INT AUTO_INCREMENT PRIMARY KEY,
  student_id INT NOT NULL UNIQUE,
  token VARCHAR(64) NOT NULL UNIQUE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  used_at TIMESTAMP NULL,
  used_by_user_id INT NULL,
  FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
  FOREIGN KEY (used_by_user_id) REFERENCES users(id) ON DELETE SET NULL
);

ALTER TABLE students
  ADD COLUMN gpa DECIMAL(3,2) NULL;

ALTER TABLE grad_process
ADD agree_personal_data TINYINT DEFAULT 0,
ADD agree_public_name TINYINT DEFAULT 0,
ADD agree_photos TINYINT DEFAULT 0,
ADD declare_correct TINYINT DEFAULT 0;

ALTER TABLE students ADD COLUMN photo VARCHAR(255) NULL;

UPDATE grad_process gp
JOIN students s ON s.id = gp.student_id
SET gp.is_honors = (s.gpa >= 5.50);

ALTER TABLE grad_process 
ADD COLUMN application_submitted TINYINT(1) DEFAULT 0,
ADD COLUMN application_submitted_at DATETIME NULL;



