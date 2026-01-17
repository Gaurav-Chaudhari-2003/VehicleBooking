CREATE DATABASE IF NOT EXISTS vehicle_booking;
USE vehicle_booking;

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";
SET NAMES utf8mb4;

-- ========================================
-- 1. USERS & AUTH
-- ========================================

CREATE TABLE users (
                       id INT AUTO_INCREMENT PRIMARY KEY,

                       first_name VARCHAR(100) NOT NULL,
                       last_name  VARCHAR(100) NOT NULL,

                       email VARCHAR(150) NOT NULL UNIQUE,
                       phone VARCHAR(15) NOT NULL,

                       address TEXT,

                       role ENUM('ADMIN','MANAGER','DRIVER','EMPLOYEE')
                                            DEFAULT 'EMPLOYEE',

                       password VARCHAR(255) NOT NULL,

                       is_active BOOLEAN DEFAULT 1,

                       created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                       updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP
);

-- ========================================
-- 2. VEHICLES
-- ========================================

CREATE TABLE vehicles (
                          id INT AUTO_INCREMENT PRIMARY KEY,

                          name VARCHAR(120) NOT NULL,
                          reg_no VARCHAR(50) NOT NULL UNIQUE,

                          category ENUM('SEDAN','SUV','BUS','TRUCK','VAN') NOT NULL,

                          fuel_type ENUM('PETROL','DIESEL','CNG','ELECTRIC'),

                          capacity INT NOT NULL,

                          status ENUM(
                              'AVAILABLE',
                              'IN_SERVICE',
                              'MAINTENANCE',
                              'RETIRED'
                              ) DEFAULT 'AVAILABLE',

                          image VARCHAR(255),

                          last_remark_id INT NULL,

                          created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ========================================
-- 3. DRIVERS
-- ========================================

CREATE TABLE drivers (
                         id INT AUTO_INCREMENT PRIMARY KEY,

                         user_id INT NOT NULL,

                         license_no VARCHAR(50) UNIQUE,
                         license_expiry DATE,

                         experience_years INT,

                         status ENUM('ACTIVE','ON_LEAVE','INACTIVE')
                             DEFAULT 'ACTIVE',

                         last_remark_id INT NULL,

                         FOREIGN KEY (user_id) REFERENCES users(id)
);

-- ========================================
-- 4. BOOKINGS
-- ========================================

CREATE TABLE bookings (
                          id INT AUTO_INCREMENT PRIMARY KEY,

                          user_id INT NOT NULL,
                          vehicle_id INT NOT NULL,
                          driver_id INT NULL,

                          from_datetime DATETIME NOT NULL,
                          to_datetime   DATETIME NOT NULL,

                          pickup_location VARCHAR(200) NOT NULL,
                          drop_location   VARCHAR(200) NOT NULL,

                          purpose TEXT,

                          status ENUM(
                              'PENDING',
                              'APPROVED',
                              'REJECTED',
                              'ONGOING',
                              'COMPLETED',
                              'CANCELLED'
                              ) DEFAULT 'PENDING',

                          last_remark_id INT NULL,

                          created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                          updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,

                          FOREIGN KEY (user_id) REFERENCES users(id),
                          FOREIGN KEY (vehicle_id) REFERENCES vehicles(id),
                          FOREIGN KEY (driver_id) REFERENCES drivers(id)
);

-- ========================================
-- 5. CENTRAL REMARK SYSTEM
-- ========================================

CREATE TABLE entity_remarks (
                                id INT AUTO_INCREMENT PRIMARY KEY,

                                entity_type ENUM(
                                    'BOOKING',
                                    'VEHICLE',
                                    'USER',
                                    'DRIVER',
                                    'MAINTENANCE'
                                    ) NOT NULL,

                                entity_id INT NOT NULL,

                                user_id INT NOT NULL,

                                remark TEXT NOT NULL,

                                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

                                FOREIGN KEY (user_id) REFERENCES users(id)
);

-- ========================================
-- 6. OPERATION HISTORY (AUDIT TRAIL)
-- ========================================

CREATE TABLE operation_history (
                                   id INT AUTO_INCREMENT PRIMARY KEY,

                                   entity_type ENUM(
                                       'BOOKING',
                                       'VEHICLE',
                                       'USER',
                                       'DRIVER',
                                       'MAINTENANCE'
                                       ),

                                   entity_id INT,

                                   action VARCHAR(100),

                                   performed_by INT,

                                   remark TEXT,

                                   performed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

                                   FOREIGN KEY (performed_by) REFERENCES users(id)
);

-- ========================================
-- 7. BOOKING CONFLICTS
-- ========================================

CREATE TABLE booking_conflicts (
                                   id INT AUTO_INCREMENT PRIMARY KEY,

                                   booking_id INT,

                                   conflict_reason VARCHAR(255),

                                   created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

                                   FOREIGN KEY (booking_id) REFERENCES bookings(id)
);

-- ========================================
-- 8. MAINTENANCE
-- ========================================

CREATE TABLE vehicle_maintenance (
                                     id INT AUTO_INCREMENT PRIMARY KEY,

                                     vehicle_id INT,

                                     service_date DATE,
                                     next_service DATE,

                                     description TEXT,
                                     cost DECIMAL(10,2),

                                     status ENUM('OPEN','DONE') DEFAULT 'OPEN',

                                     last_remark_id INT NULL,

                                     FOREIGN KEY (vehicle_id) REFERENCES vehicles(id)
);

-- ========================================
-- 9. SYSTEM LOGS
-- ========================================

CREATE TABLE system_logs (
                             id INT AUTO_INCREMENT PRIMARY KEY,

                             user_id INT,
                             action VARCHAR(150),

                             ip VARCHAR(50),
                             user_agent VARCHAR(255),

                             log_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

                             FOREIGN KEY (user_id) REFERENCES users(id)
);

-- ========================================
-- 10. FEEDBACK
-- ========================================

CREATE TABLE feedback (
                          id INT AUTO_INCREMENT PRIMARY KEY,

                          user_id INT,
                          content TEXT,

                          status ENUM('PUBLISHED','HIDDEN')
                                               DEFAULT 'PUBLISHED',

                          created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

                          FOREIGN KEY (user_id) REFERENCES users(id)
);

-- ========================================
-- 11. VEHICLE SCHEDULE
-- ========================================

CREATE TABLE vehicle_schedule (
                                  id INT AUTO_INCREMENT PRIMARY KEY,

                                  vehicle_id INT,
                                  date DATE,

                                  is_available BOOLEAN DEFAULT 1,

                                  FOREIGN KEY (vehicle_id) REFERENCES vehicles(id)
);

-- ========================================
-- 12. INDEXES
-- ========================================

CREATE INDEX idx_booking_dates
    ON bookings(from_datetime, to_datetime);

CREATE INDEX idx_vehicle_status
    ON vehicles(status);

CREATE INDEX idx_user_email
    ON users(email);
