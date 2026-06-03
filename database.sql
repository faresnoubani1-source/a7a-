CREATE DATABASE IF NOT EXISTS tutorconnect_db
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE tutorconnect_db;

DROP TABLE IF EXISTS resources;
DROP TABLE IF EXISTS payments;
DROP TABLE IF EXISTS bookings;
DROP TABLE IF EXISTS tutors;
DROP TABLE IF EXISTS users;

CREATE TABLE users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  first_name VARCHAR(70) NOT NULL,
  last_name VARCHAR(70) NOT NULL,
  email VARCHAR(160) NOT NULL UNIQUE,
  phone VARCHAR(30) NULL,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('student', 'admin') NOT NULL DEFAULT 'student',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE tutors (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  subject VARCHAR(90) NOT NULL,
  hourly_rate DECIMAL(8,2) NOT NULL,
  rating DECIMAL(3,2) NOT NULL DEFAULT 4.80,
  bio TEXT NOT NULL,
  accent_color VARCHAR(20) NOT NULL DEFAULT '#2f9e44',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE bookings (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  tutor_id INT UNSIGNED NOT NULL,
  session_date DATE NOT NULL,
  session_time TIME NOT NULL,
  duration_minutes SMALLINT UNSIGNED NOT NULL DEFAULT 60,
  focus_area VARCHAR(255) NOT NULL,
  price DECIMAL(8,2) NOT NULL,
  status ENUM('pending', 'confirmed', 'cancelled', 'completed') NOT NULL DEFAULT 'pending',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_bookings_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_bookings_tutor FOREIGN KEY (tutor_id) REFERENCES tutors(id) ON DELETE CASCADE,
  INDEX idx_bookings_user (user_id),
  INDEX idx_bookings_tutor_date (tutor_id, session_date, session_time)
) ENGINE=InnoDB;

CREATE TABLE payments (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  booking_id INT UNSIGNED NOT NULL UNIQUE,
  user_id INT UNSIGNED NOT NULL,
  amount DECIMAL(8,2) NOT NULL,
  payment_method VARCHAR(40) NOT NULL DEFAULT 'Demo card',
  card_last4 CHAR(4) NOT NULL,
  status ENUM('paid', 'refunded') NOT NULL DEFAULT 'paid',
  paid_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_payments_booking FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE,
  CONSTRAINT fk_payments_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE resources (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  booking_id INT UNSIGNED NULL,
  title VARCHAR(140) NOT NULL,
  description VARCHAR(255) NULL,
  original_name VARCHAR(255) NOT NULL,
  stored_name VARCHAR(80) NOT NULL UNIQUE,
  mime_type VARCHAR(120) NOT NULL,
  file_size INT UNSIGNED NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_resources_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_resources_booking FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE SET NULL,
  INDEX idx_resources_user (user_id),
  INDEX idx_resources_booking (booking_id)
) ENGINE=InnoDB;

INSERT INTO tutors (name, subject, hourly_rate, rating, bio, accent_color) VALUES
('Maya Khalil', 'Web Technologies', 18.00, 4.95, 'Helps students prepare HTML, CSS, PHP, and MySQL projects with clear debugging habits and practical examples.', '#60a5fa'),
('Omar Haddad', 'Database Systems', 20.00, 4.90, 'Specializes in SQL design, ER diagrams, indexing basics, and clean database normalization for coursework.', '#34d399'),
('Lina Saleh', 'Java Programming', 16.50, 4.85, 'Focuses on object-oriented programming, NetBeans workflows, and turning confusing errors into small fixable steps.', '#fbbf24'),
('Nour Dajani', 'Calculus', 15.00, 4.80, 'Breaks down derivatives, integrals, and exam practice into direct sessions with lots of solved examples.', '#fb7185'),
('Yousef Naser', 'Cybersecurity Basics', 22.00, 4.92, 'Teaches secure coding, authentication basics, upload validation, and beginner-friendly web security reviews.', '#a78bfa');
