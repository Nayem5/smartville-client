-- =============================================
-- SMARTVILLE COMMUNITY HUB - DATABASE SCHEMA
-- Run this in phpMyAdmin or MySQL CLI
-- =============================================

CREATE DATABASE IF NOT EXISTS smartville_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE smartville_db;

-- =============================================
-- USERS TABLE
-- =============================================
CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  full_name VARCHAR(100) NOT NULL,
  username VARCHAR(50) NOT NULL UNIQUE,
  email VARCHAR(100) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  phone VARCHAR(20) DEFAULT NULL,
  area VARCHAR(100) DEFAULT NULL,
  role ENUM('admin','organizer','resident') NOT NULL DEFAULT 'resident',
  status ENUM('active','inactive','deactivated') NOT NULL DEFAULT 'active',
  profile_pic VARCHAR(255) DEFAULT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- =============================================
-- VENUES TABLE
-- =============================================
CREATE TABLE IF NOT EXISTS venues (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  description TEXT,
  price_per_day DECIMAL(10,2) DEFAULT 0.00,
  capacity INT DEFAULT 100,
  image VARCHAR(255) DEFAULT NULL,
  status ENUM('available','unavailable') DEFAULT 'available'
);

-- =============================================
-- EVENTS TABLE
-- =============================================
CREATE TABLE IF NOT EXISTS events (
  id INT AUTO_INCREMENT PRIMARY KEY,
  organizer_id INT NOT NULL,
  venue_id INT DEFAULT NULL,
  title VARCHAR(150) NOT NULL,
  description TEXT,
  event_type ENUM('free','paid','private') NOT NULL DEFAULT 'free',
  sector VARCHAR(100) DEFAULT NULL,
  date DATE NOT NULL,
  time TIME NOT NULL,
  end_time TIME DEFAULT NULL,
  price DECIMAL(10,2) DEFAULT 0.00,
  max_guests INT DEFAULT NULL,
  poster VARCHAR(255) DEFAULT NULL,
  program_flow TEXT DEFAULT NULL,
  status ENUM('pending','approved','rejected','cancelled') NOT NULL DEFAULT 'pending',
  admin_note TEXT DEFAULT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (organizer_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (venue_id) REFERENCES venues(id) ON DELETE SET NULL
);

-- =============================================
-- PRIVATE EVENT INVITES
-- =============================================
CREATE TABLE IF NOT EXISTS event_invites (
  id INT AUTO_INCREMENT PRIMARY KEY,
  event_id INT NOT NULL,
  user_id INT DEFAULT NULL,
  email VARCHAR(100) NOT NULL,
  status ENUM('pending','accepted','declined') DEFAULT 'pending',
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- =============================================
-- EVENT REGISTRATIONS
-- =============================================
CREATE TABLE IF NOT EXISTS registrations (
  id INT AUTO_INCREMENT PRIMARY KEY,
  event_id INT NOT NULL,
  user_id INT NOT NULL,
  payment_status ENUM('not_required','pending','paid','failed') DEFAULT 'not_required',
  payment_amount DECIMAL(10,2) DEFAULT 0.00,
  attendance_status ENUM('registered','attended','no_show') DEFAULT 'registered',
  registered_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY unique_reg (event_id, user_id),
  FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- =============================================
-- FEEDBACK / RATINGS
-- =============================================
CREATE TABLE IF NOT EXISTS feedback (
  id INT AUTO_INCREMENT PRIMARY KEY,
  event_id INT NOT NULL,
  user_id INT NOT NULL,
  rating TINYINT NOT NULL CHECK (rating BETWEEN 1 AND 5),
  comment TEXT,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY unique_feedback (event_id, user_id),
  FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- =============================================
-- NOTIFICATIONS
-- =============================================
CREATE TABLE IF NOT EXISTS notifications (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  title VARCHAR(150) NOT NULL,
  message TEXT NOT NULL,
  type ENUM('info','success','warning','error') DEFAULT 'info',
  is_read TINYINT(1) DEFAULT 0,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- =============================================
-- SEED DATA
-- =============================================

-- Admin account (password: Admin@12345)
INSERT INTO users (full_name, username, email, password, role) VALUES
('Daniel Rahman', 'admin', 'admin@smartville.my', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- Organizer account (password: Organizer@123)
INSERT INTO users (full_name, username, email, password, phone, area, role) VALUES
('Shahidah Muhyeddin', 'shahidah', 'shahidah@smartville.my', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '0112345678', 'Segamat', 'organizer');

-- Resident account (password: password)
INSERT INTO users (full_name, username, email, password, phone, area, role) VALUES
('Ahmad Resident', 'ahmad', 'ahmad@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '0198765432', 'Segamat Utara', 'resident');

-- Venues
INSERT INTO venues (name, description, price_per_day, capacity) VALUES
('Community Hall', 'Large air-conditioned hall with stage and PA system', 102.90, 500),
('Field Recreation', 'Open field suitable for outdoor events and sports', 52.90, 1000),
('Sport Court', 'Covered sport court for indoor games and events', 82.90, 200);

-- Sample approved events
INSERT INTO events (organizer_id, venue_id, title, description, event_type, sector, date, time, end_time, status) VALUES
(2, 1, 'Gala of Hope: Together, We Rise', 'Our 8th year hosting the Gala of Hope. Join us for a celebratory evening while we raise awareness and much-needed funds for our mission.', 'free', 'Social', '2026-07-26', '18:00:00', '22:00:00', 'approved'),
(2, 2, 'OUM Fun Run 5K', 'Lace up your shoes and join the community for a morning of fitness and fun! Open to all ages.', 'free', 'Sports', '2026-08-01', '07:00:00', '10:00:00', 'approved'),
(2, 3, 'Coachella Night Segamat', 'Experience the magic of a music festival right in the heart of Segamat!', 'paid', 'Entertainment', '2026-09-12', '19:00:00', '23:00:00', 'approved');

UPDATE events SET price = 45.00 WHERE title = 'Coachella Night Segamat';

-- Pending event
INSERT INTO events (organizer_id, venue_id, title, description, event_type, sector, date, time, status) VALUES
(2, 1, 'Residents VIP Mixer', 'An exclusive gathering for invited community members to network and connect.', 'private', 'Networking', '2026-10-05', '19:00:00', 'pending');

-- Notifications for organizer
INSERT INTO notifications (user_id, title, message, type) VALUES
(2, 'Event Approved!', 'Your event "Gala of Hope" has been approved and is now live.', 'success'),
(2, 'Event Approved!', 'Your event "OUM Fun Run 5K" has been approved and is now live.', 'success'),
(2, 'Event Approved!', 'Your event "Coachella Night Segamat" has been approved and is now live.', 'success');
