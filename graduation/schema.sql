CREATE DATABASE IF NOT EXISTS graduation_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE graduation_db;

-- USERS (roles: student/admin/superadmin)
CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(190) NOT NULL UNIQUE,
  pass_hash VARCHAR(255) NOT NULL,
  role ENUM('student','admin','superadmin') NOT NULL DEFAULT 'student',
  full_name VARCHAR(190) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- STUDENTS (като профил/академични данни)
CREATE TABLE students (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL UNIQUE,
  faculty_no VARCHAR(50) NOT NULL UNIQUE,
  degree ENUM('bachelor','master','phd') NOT NULL,
  program_name VARCHAR(190) NOT NULL,
  group_code VARCHAR(50) NOT NULL,   -- напр. "БАК-2026-1"
  phone VARCHAR(50) NULL,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- GRADUATION PROCESS (етапи + тоги + церемония + отличия)
-- stage: 0=Регистриран, 1=Потвърден, 2=На церемония, 3=Завършен
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

-- RESPONSIBILITIES (отговорници: тоги/подписи/дипломи)
CREATE TABLE responsibilities (
  id INT AUTO_INCREMENT PRIMARY KEY,
  type ENUM('gowns','signatures','diplomas') NOT NULL,
  person_name VARCHAR(190) NOT NULL,
  email VARCHAR(190) NULL,
  phone VARCHAR(50) NULL,
  active TINYINT NOT NULL DEFAULT 1
);

-- CITATIONS dictionary
/*CREATE TABLE citations (
  id INT AUTO_INCREMENT PRIMARY KEY,
  key_code VARCHAR(50) NOT NULL UNIQUE, -- напр. CIT-001
  quote_text TEXT NOT NULL,
  source_text VARCHAR(255) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);*/

-- Who cited what (статистика)
/*CREATE TABLE citation_uses (
  id INT AUTO_INCREMENT PRIMARY KEY,
  citation_id INT NOT NULL,
  user_id INT NOT NULL,
  context VARCHAR(190) NOT NULL, -- напр. "student_register_note", "report"
  used_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (citation_id) REFERENCES citations(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);*/

-- QR tokens for checklists (simple)
CREATE TABLE qr_tokens (
  id INT AUTO_INCREMENT PRIMARY KEY,
  student_id INT NOT NULL,
  token VARCHAR(64) NOT NULL UNIQUE,
  purpose ENUM('checkin','gown_take','gown_return','diploma') NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  used_at TIMESTAMP NULL,
  FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
);

-- Seed accounts (пароли: admin123 / student123)
INSERT INTO users(email, pass_hash, role, full_name) VALUES
('admin@uni.test',  '$2y$10$QpQ6pQ8t4iW3oQxYf7f9/OVgW7xVfDgGkYpK0E9p8mVfQvQv2pE6G', 'superadmin', 'System Admin'),
('stud1@uni.test',  '$2y$10$1mXG1nQyP0sK7a1mKqYw9e8QwM7d8j8qf8m2oO2k2bq7s8kqvQb6W', 'student', 'Иван Иванов');

-- Create student profile for stud1
INSERT INTO students(user_id, faculty_no, degree, program_name, group_code, phone)
SELECT id, 'F12345', 'bachelor', 'Информатика', 'БАК-2026-1', '0888123456'
FROM users WHERE email='stud1@uni.test';

INSERT INTO grad_process(student_id) SELECT id FROM students WHERE faculty_no='F12345';

-- Seed responsibilities
INSERT INTO responsibilities(type, person_name, email, phone) VALUES
('gowns','Мария Георгиева','gowns@uni.test','0888000001'),
('signatures','Доц. Петров','sign@uni.test','0888000002'),
('diplomas','Гл. специалист Димитрова','diplomas@uni.test','0888000003');

-- Seed citations
/*INSERT INTO citations(key_code, quote_text, source_text) VALUES
('CIT-001','Образованието е най-мощното оръжие, което можеш да използваш, за да промениш света.','(приписвано на Н. Мандела)'),
('CIT-002','Науката е организирано знание. Мъдростта е организиран живот.','У. Дюрант');*/


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

-- лимит билети на студент (по подразбиране 2)
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


