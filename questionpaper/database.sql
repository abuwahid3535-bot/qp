-- =====================================================================
--  Question Paper Management System - Database Schema & Seed Data
--  Import this file into phpMyAdmin (or `mysql -u root < database.sql`)
-- =====================================================================

CREATE DATABASE IF NOT EXISTS question_paper_db
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE question_paper_db;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS papers;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS subjects;
DROP TABLE IF EXISTS subcourses;
DROP TABLE IF EXISTS courses;
DROP TABLE IF EXISTS academic_years;
SET FOREIGN_KEY_CHECKS = 1;

-- ---------------------------------------------------------------------
-- Courses (e.g. B.Sc, B.C.A, B.Com, B.B.A)
-- ---------------------------------------------------------------------
CREATE TABLE courses (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  code        VARCHAR(10)  NOT NULL UNIQUE,
  name        VARCHAR(100) NOT NULL,
  description VARCHAR(300) DEFAULT NULL,
  created_at  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Sub-courses (specialisation / stream inside a course)
-- ---------------------------------------------------------------------
CREATE TABLE subcourses (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  course_id   INT UNSIGNED NOT NULL,
  name        VARCHAR(100) NOT NULL,
  description VARCHAR(300) DEFAULT NULL,
  created_at  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_sub_course FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
  UNIQUE KEY uq_subcourse (course_id, name)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Subjects (belongs to a sub-course)
-- ---------------------------------------------------------------------
CREATE TABLE subjects (
  id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  subcourse_id INT UNSIGNED NOT NULL,
  code         VARCHAR(20)  DEFAULT NULL,
  name         VARCHAR(100) NOT NULL,
  created_at   TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_subject_subcourse FOREIGN KEY (subcourse_id) REFERENCES subcourses(id) ON DELETE CASCADE,
  UNIQUE KEY uq_subject (subcourse_id, name)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Academic years (of the question papers)
-- ---------------------------------------------------------------------
CREATE TABLE academic_years (
  id   INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(20) NOT NULL UNIQUE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Users (Students + Staff). One table, role decides permissions.
-- ---------------------------------------------------------------------
CREATE TABLE users (
  id                 INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name               VARCHAR(100) NOT NULL,
  email              VARCHAR(150) NOT NULL UNIQUE,
  mobile             VARCHAR(15)  NOT NULL UNIQUE,
  password           VARCHAR(255) NOT NULL,
  role               ENUM('student','staff') NOT NULL DEFAULT 'student',
  email_verified     TINYINT(1)   NOT NULL DEFAULT 0,
  mobile_verified    TINYINT(1)   NOT NULL DEFAULT 0,
  email_otp          VARCHAR(6)   DEFAULT NULL,
  email_otp_expiry   DATETIME     DEFAULT NULL,
  email_otp_sent_at  DATETIME     DEFAULT NULL,
  mobile_otp         VARCHAR(6)   DEFAULT NULL,
  mobile_otp_expiry  DATETIME     DEFAULT NULL,
  mobile_otp_sent_at DATETIME     DEFAULT NULL,
  status             ENUM('active','blocked') NOT NULL DEFAULT 'active',
  created_at         TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_role (role)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Papers (question papers uploaded by staff)
-- ---------------------------------------------------------------------
CREATE TABLE papers (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  course_id     INT UNSIGNED NOT NULL,
  subcourse_id  INT UNSIGNED NOT NULL,
  subject_id    INT UNSIGNED NOT NULL,
  year_id       INT UNSIGNED NOT NULL,
  semester      TINYINT      NOT NULL DEFAULT 1,
  title         VARCHAR(200) NOT NULL,
  description   VARCHAR(500) DEFAULT NULL,
  file_path     VARCHAR(255) NOT NULL,
  original_name VARCHAR(255) NOT NULL,
  file_size     INT UNSIGNED NOT NULL DEFAULT 0,
  downloads     INT UNSIGNED NOT NULL DEFAULT 0,
  uploaded_by   INT UNSIGNED NOT NULL,
  created_at    TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_paper_course    FOREIGN KEY (course_id)    REFERENCES courses(id)        ON DELETE CASCADE,
  CONSTRAINT fk_paper_subcourse FOREIGN KEY (subcourse_id) REFERENCES subcourses(id)     ON DELETE CASCADE,
  CONSTRAINT fk_paper_subject   FOREIGN KEY (subject_id)   REFERENCES subjects(id)       ON DELETE CASCADE,
  CONSTRAINT fk_paper_year      FOREIGN KEY (year_id)      REFERENCES academic_years(id) ON DELETE CASCADE,
  CONSTRAINT fk_paper_uploader  FOREIGN KEY (uploaded_by)  REFERENCES users(id)          ON DELETE CASCADE,
  INDEX idx_filter (course_id, subcourse_id, subject_id, year_id, semester)
) ENGINE=InnoDB;

-- =====================================================================
--  SEED DATA
-- =====================================================================

-- Courses -----------------------------------------------------------------
INSERT INTO courses (id, code, name, description) VALUES
(1, 'BSC',  'Bachelor of Science', 'Undergraduate science programme with multiple specialisations.'),
(2, 'BCA',  'Bachelor of Computer Applications', 'Undergraduate programme focused on computer applications and software development.'),
(3, 'BCOM', 'Bachelor of Commerce', 'Undergraduate programme covering accounting, finance and business studies.'),
(4, 'BBA',  'Bachelor of Business Administration', 'Undergraduate programme in business and management.');

-- Sub-courses ---------------------------------------------------------------
INSERT INTO subcourses (id, course_id, name, description) VALUES
(1, 1, 'Physics',              'B.Sc with Physics as the main specialisation'),
(2, 1, 'Chemistry',            'B.Sc with Chemistry as the main specialisation'),
(3, 1, 'Mathematics',          'B.Sc with Mathematics as the main specialisation'),
(4, 1, 'Computer Science',     'B.Sc with Computer Science as the main specialisation'),
(5, 1, 'Botany',               'B.Sc with Botany as the main specialisation'),
(6, 1, 'Statistics',           'B.Sc with Statistics as the main specialisation'),
(7, 2, 'General',              'B.C.A general programme'),
(8, 3, 'General',              'B.Com general programme'),
(9, 3, 'Computers',            'B.Com with computer applications'),
(10, 3, 'Accounting & Finance','B.Com with accounting and finance'),
(11, 4, 'General',             'B.B.A general programme'),
(12, 4, 'Marketing',           'B.B.A with marketing specialisation'),
(13, 4, 'Finance',             'B.B.A with finance specialisation'),
(14, 4, 'Human Resources',     'B.B.A with human resource specialisation');

-- Subjects -------------------------------------------------------------------
INSERT INTO subjects (id, subcourse_id, code, name) VALUES
(1, 1, 'PH101', 'Mechanics'),
(2, 1, 'PH102', 'Electromagnetic Theory'),
(3, 1, 'PH201', 'Mathematical Physics'),
(4, 1, 'PH301', 'Quantum Mechanics'),
(5, 2, 'CH101', 'Physical Chemistry'),
(6, 2, 'CH201', 'Organic Chemistry'),
(7, 2, 'CH301', 'Inorganic Chemistry'),
(8, 3, 'MA101', 'Calculus'),
(9, 3, 'MA201', 'Linear Algebra'),
(10, 3, 'MA301', 'Real Analysis'),
(11, 4, 'CS101', 'Programming in C'),
(12, 4, 'CS201', 'Data Structures'),
(13, 4, 'CS301', 'Database Management Systems'),
(14, 4, 'CS401', 'Operating Systems'),
(15, 5, 'BO101', 'Plant Physiology'),
(16, 5, 'BO201', 'Genetics'),
(17, 6, 'ST101', 'Probability Theory'),
(18, 6, 'ST201', 'Statistical Inference'),
(19, 7, 'CA101', 'Programming in C'),
(20, 7, 'CA102', 'Digital Computer Fundamentals'),
(21, 7, 'CA201', 'Data Structures'),
(22, 7, 'CA202', 'Database Management Systems'),
(23, 7, 'CA301', 'Operating Systems'),
(24, 7, 'CA302', 'Computer Networks'),
(25, 7, 'CA401', 'Web Technologies'),
(26, 7, 'CA402', 'Software Engineering'),
(27, 8, 'CO101', 'Financial Accounting'),
(28, 8, 'CO102', 'Business Laws'),
(29, 8, 'CO201', 'Corporate Accounting'),
(30, 8, 'CO301', 'Cost Accounting'),
(31, 8, 'CO302', 'Taxation'),
(32, 9, 'CC101', 'Computer Applications in Commerce'),
(33, 9, 'CC201', 'E-Commerce'),
(34, 10, 'AF101', 'Business Mathematics'),
(35, 10, 'AF201', 'Financial Management'),
(36, 11, 'BB101', 'Principles of Management'),
(37, 11, 'BB201', 'Organisational Behaviour'),
(38, 11, 'BB301', 'Business Environment'),
(39, 12, 'BM201', 'Marketing Management'),
(40, 12, 'BM301', 'Consumer Behaviour'),
(41, 13, 'BF201', 'Financial Management'),
(42, 13, 'BF301', 'Investment Analysis'),
(43, 14, 'BH201', 'Human Resource Management'),
(44, 14, 'BH301', 'Industrial Relations');

-- Academic years -----------------------------------------------------------------
INSERT INTO academic_years (id, name) VALUES
(1, '2020-2021'),
(2, '2021-2022'),
(3, '2022-2023'),
(4, '2023-2024'),
(5, '2024-2025'),
(6, '2025-2026');

-- =====================================================================
--  NOTE: No user rows are seeded in this file because passwords must be
--  hashed at runtime. Demo accounts + sample paper records are created
--  by running seed_demo.php once (see README.md). Delete that file in
--  production.
-- =====================================================================