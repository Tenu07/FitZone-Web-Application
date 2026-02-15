-- Drop existing database and create new one
DROP DATABASE IF EXISTS fitzone_db;
CREATE DATABASE fitzone_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE fitzone_db;

-- Users Table
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('customer', 'trainer', 'staff', 'admin', 'manager') DEFAULT 'customer', -- Added 'manager'
    position VARCHAR(100), -- e.g., 'Personal Trainer', 'Front Desk', 'Gym Manager'
    phone VARCHAR(20),
    address TEXT,
    profile_photo VARCHAR(255), -- Path to photo file
    date_of_birth DATE,
    gender ENUM('male', 'female', 'other', 'prefer_not_to_say'),
    height DECIMAL(5,2) COMMENT 'in cm',
    weight DECIMAL(5,2) COMMENT 'in kg',
    fitness_goals TEXT, -- Primarily for customers
    health_conditions TEXT, -- Primarily for customers
    bio TEXT, -- Primarily for trainers/staff
    specialization VARCHAR(255), -- Primarily for trainers
    is_active BOOLEAN DEFAULT TRUE, -- To enable/disable accounts
    last_login DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX (email),
    INDEX (role),
    INDEX (is_active)
) ENGINE=InnoDB;

-- Insert sample users (Ensure passwords are properly hashed in your actual implementation)
-- Passwords below are '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi' which hashes to 'password'
INSERT INTO users (name, email, password, role, position, phone, date_of_birth, gender, is_active, specialization, bio) VALUES
('John Doe', 'john@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'customer', NULL, '1234567890', '1990-05-15', 'male', TRUE, NULL, NULL),
('Jane Smith', 'jane@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'customer', NULL, '9876543210', '1985-08-22', 'female', TRUE, NULL, NULL),
('Mike Johnson (Trainer)', 'mike@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'trainer', 'Personal Trainer', '5551234567', '1988-03-10', 'male', TRUE, 'Strength Training, Bodybuilding', 'Certified NASM trainer focused on results.'),
('Sarah Williams (Trainer)', 'sarah@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'trainer', 'Yoga Instructor', '5559876543', '1992-11-05', 'female', TRUE, 'Yoga, Pilates, Flexibility', 'RYT 500 certified instructor promoting mind-body connection.'),
('Alex Johnson (Staff)', 'alex@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'staff', 'Front Desk Staff', '5551112222', '1985-07-15', 'male', TRUE, NULL, NULL),
('Maria Garcia (Manager)', 'maria@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'manager', 'Gym Manager', '5553334444', '1990-11-22', 'female', TRUE, NULL, NULL),
('Admin User', 'admin@fitzone.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'System Administrator', '5550000000', '1980-01-01', 'male', TRUE, NULL, NULL);


-- Admin-specific login details (alternative approach)
CREATE TABLE admin_users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    full_name VARCHAR(100),
    is_super_admin BOOLEAN DEFAULT FALSE,
    last_login DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX (username),
    INDEX (email)
) ENGINE=InnoDB;

-- Insert admin login credentials
-- Password: admin123 (hashed)
INSERT INTO admin_users (username, password, email, full_name, is_super_admin) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@fitzone.com', 'Super Admin', TRUE),
('manager', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'manager@fitzone.com', 'Gym Manager', FALSE);

-- ==================================================================
-- Staff Permissions Table (Optional - For more granular control)
-- ==================================================================
CREATE TABLE staff_permissions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL UNIQUE, -- Link directly to the user ID
    can_manage_members BOOLEAN DEFAULT FALSE,
    can_manage_appointments BOOLEAN DEFAULT FALSE,
    can_view_reports BOOLEAN DEFAULT FALSE,
    can_manage_classes BOOLEAN DEFAULT FALSE,
    can_manage_staff BOOLEAN DEFAULT FALSE, -- Example permission
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX (user_id)
) ENGINE=InnoDB;

-- Insert sample staff permissions (Link to user IDs based on the INSERTs above)
INSERT INTO staff_permissions (user_id, can_manage_members, can_manage_appointments, can_view_reports, can_manage_classes, can_manage_staff)
SELECT id, TRUE, TRUE, FALSE, FALSE, FALSE FROM users WHERE email = 'alex@example.com'; -- Alex (Staff)

INSERT INTO staff_permissions (user_id, can_manage_members, can_manage_appointments, can_view_reports, can_manage_classes, can_manage_staff)
SELECT id, TRUE, TRUE, TRUE, TRUE, TRUE FROM users WHERE email = 'maria@example.com'; -- Maria (Manager)

-- ==================================================================
-- Password Reset Tokens
-- ==================================================================
CREATE TABLE password_reset_tokens (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    token VARCHAR(255) NOT NULL UNIQUE,
    expires_at DATETIME NOT NULL,
    is_used BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX (token),
    INDEX (expires_at),
    INDEX (user_id)
) ENGINE=InnoDB;



-- ==================================================================
-- Membership Plans
-- ==================================================================
CREATE TABLE plans (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) UNIQUE NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    duration INT NOT NULL COMMENT 'in months',
    benefits TEXT NOT NULL,
    description TEXT,
    features JSON COMMENT 'Structured features in JSON format',
    is_featured BOOLEAN DEFAULT FALSE,
    status ENUM('active', 'inactive', 'archived') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX (status),
    INDEX (is_featured)
) ENGINE=InnoDB;

-- Insert sample membership plans
INSERT INTO plans (name, slug, price, duration, benefits, description, features, is_featured, status) VALUES
('Basic', 'basic', 2999.00, 1, 'Access to gym facilities\nLocker room access', 'Our most affordable membership', '["Cardio Equipment", "Weight Training", "Locker Room"]', FALSE, 'active'),
('Premium', 'premium', 5999.00, 1, 'All Basic benefits\nGroup classes\nSauna access', 'Our most popular membership', '["All Basic Features", "Group Classes", "Sauna Access", "1 Free Personal Training Session"]', TRUE, 'active'),
('Annual Gold', 'annual-gold', 49999.00, 12, 'All Premium benefits\n5 personal training sessions\nNutrition consultation', 'Best value for long-term commitment', '["All Premium Features", "5 Personal Training Sessions", "Nutrition Consultation", "Towel Service"]', TRUE, 'active');


-- ==================================================================
-- Memberships (User Subscriptions)
-- ==================================================================
CREATE TABLE memberships (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    plan_id INT NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    status ENUM('active', 'expired', 'cancelled', 'pending') DEFAULT 'pending',
    payment_status ENUM('paid', 'unpaid', 'partial', 'refunded') DEFAULT 'unpaid',
    auto_renew BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (plan_id) REFERENCES plans(id) ON DELETE RESTRICT, -- Prevent deleting plan if members use it
    INDEX (user_id),
    INDEX (plan_id),
    INDEX (status),
    INDEX (end_date)
) ENGINE=InnoDB;

-- Insert sample memberships (Link to user IDs based on the INSERTs above)
INSERT INTO memberships (user_id, plan_id, start_date, end_date, status, payment_status, auto_renew)
SELECT id, 2, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 1 MONTH), 'active', 'paid', TRUE FROM users WHERE email = 'john@example.com';

INSERT INTO memberships (user_id, plan_id, start_date, end_date, status, payment_status, auto_renew)
SELECT id, 3, DATE_SUB(CURDATE(), INTERVAL 6 MONTH), DATE_ADD(DATE_SUB(CURDATE(), INTERVAL 6 MONTH), INTERVAL 12 MONTH), 'active', 'paid', TRUE FROM users WHERE email = 'jane@example.com';

-- Trainers Table
CREATE TABLE trainers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT UNIQUE,
    specialization VARCHAR(100) NOT NULL,
    certification VARCHAR(255),
    experience_years INT,
    bio TEXT,
    hourly_rate DECIMAL(10,2),
    availability JSON COMMENT 'Structured availability in JSON format',
    is_available BOOLEAN DEFAULT TRUE,
    rating DECIMAL(3,2) DEFAULT 0.00,
    achievements TEXT,
    education TEXT,
    languages_spoken VARCHAR(255),
    social_media_links JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX (specialization),
    INDEX (is_available),
    FULLTEXT INDEX (bio, specialization)  -- For better search capabilities
) ENGINE=InnoDB;

-- Insert sample trainers
INSERT INTO trainers (user_id, specialization, certification, experience_years, bio, hourly_rate, availability, is_available, rating) VALUES
(3, 'Strength Training', 'NASM Certified Personal Trainer', 5, 'Specialized in strength and conditioning for athletes and beginners alike.', 50.00, '{"monday": ["09:00-12:00", "14:00-18:00"], "tuesday": ["09:00-12:00", "14:00-18:00"], "wednesday": ["09:00-12:00"], "thursday": ["09:00-12:00", "14:00-18:00"], "friday": ["14:00-18:00"]}', TRUE, 4.8),
(4, 'Yoga & Pilates', 'RYT 500 Yoga Instructor, Pilates Certified', 8, 'Helping clients achieve balance and flexibility through yoga and pilates.', 45.00, '{"monday": ["07:00-10:00", "16:00-20:00"], "wednesday": ["07:00-10:00", "16:00-20:00"], "friday": ["07:00-10:00", "16:00-20:00"], "saturday": ["09:00-12:00"]}', TRUE, 4.9);

-- ==================================================================
-- Workout Categories (Optional)
-- ==================================================================
CREATE TABLE workout_categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    image VARCHAR(255),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX (is_active)
) ENGINE=InnoDB;

INSERT INTO workout_categories (name, description, is_active) VALUES
('Cardio', 'Exercises that increase heart rate and improve cardiovascular health', TRUE),
('Strength Training', 'Exercises designed to improve muscular strength and endurance', TRUE),
('Flexibility', 'Exercises that improve range of motion and flexibility', TRUE);


-- ==================================================================
-- Workout Types (Specific Exercises/Activities)
-- ==================================================================
CREATE TABLE workout_types (
    id INT PRIMARY KEY AUTO_INCREMENT,
    category_id INT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    difficulty ENUM('beginner', 'intermediate', 'advanced'),
    equipment_needed TEXT,
    video_url VARCHAR(255),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES workout_categories(id) ON DELETE SET NULL,
    INDEX (category_id),
    INDEX (difficulty),
    INDEX (is_active)
) ENGINE=InnoDB;

INSERT INTO workout_types (category_id, name, description, difficulty, equipment_needed, is_active) VALUES
(1, 'Treadmill Running', 'Moderate to intense running on treadmill', 'intermediate', 'Treadmill', TRUE),
(1, 'Stationary Bike', 'Low-impact cardio workout', 'beginner', 'Exercise Bike', TRUE),
(2, 'Deadlifts', 'Compound exercise targeting multiple muscle groups', 'advanced', 'Barbell, Weight Plates', TRUE),
(2, 'Bench Press', 'Upper body strength exercise', 'intermediate', 'Bench, Barbell, Weight Plates', TRUE),
(3, 'Yoga Flow', 'Sequence of yoga poses to improve flexibility', 'beginner', 'Yoga Mat', TRUE);


-- ==================================================================
-- Appointments (Training Sessions, Consultations)
-- ==================================================================
CREATE TABLE appointments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL COMMENT 'FK to users table (customer)',
    trainer_id INT NOT NULL COMMENT 'FK to users table (user with role=trainer)',
    date DATETIME NOT NULL,
    duration INT NOT NULL DEFAULT 60 COMMENT 'in minutes',
    location ENUM('gym', 'online', 'home') DEFAULT 'gym',
    notes TEXT,
    status ENUM('scheduled', 'confirmed', 'completed', 'cancelled', 'no_show') DEFAULT 'scheduled',
    cancellation_reason TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (trainer_id) REFERENCES users(id) ON DELETE CASCADE, -- Points to the trainer's user ID
    INDEX (user_id),
    INDEX (trainer_id),
    INDEX (date),
    INDEX (status)
) ENGINE=InnoDB;

-- Insert sample appointments (Link to user IDs based on the INSERTs above)
INSERT INTO appointments (user_id, trainer_id, date, duration, location, status)
SELECT u_cust.id, u_trainer.id, DATE_ADD(NOW(), INTERVAL 2 DAY), 60, 'gym', 'scheduled'
FROM users u_cust, users u_trainer
WHERE u_cust.email = 'john@example.com' AND u_trainer.email = 'mike@example.com';

INSERT INTO appointments (user_id, trainer_id, date, duration, location, status)
SELECT u_cust.id, u_trainer.id, DATE_ADD(NOW(), INTERVAL 5 DAY), 45, 'online', 'scheduled'
FROM users u_cust, users u_trainer
WHERE u_cust.email = 'jane@example.com' AND u_trainer.email = 'sarah@example.com';

-- ==================================================================
-- Notifications
-- ==================================================================
CREATE TABLE notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    notification_type ENUM('system', 'appointment', 'membership', 'workout', 'payment', 'class', 'message'),
    related_id INT COMMENT 'ID of related item (e.g., appointment_id, class_id)',
    related_type VARCHAR(50),
    priority ENUM('low', 'medium', 'high') DEFAULT 'medium',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX (user_id, is_read),
    INDEX (notification_type),
    INDEX (created_at)
) ENGINE=InnoDB;

-- ==================================================================
-- Workouts (User Logged Activities)
-- ==================================================================
CREATE TABLE workouts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    workout_type_id INT,
    date DATETIME NOT NULL,
    duration INT NOT NULL COMMENT 'in minutes',
    calories_burned INT,
    distance DECIMAL(10,2) COMMENT 'in km',
    notes TEXT,
    intensity ENUM('low', 'medium', 'high'),
    is_completed BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (workout_type_id) REFERENCES workout_types(id) ON DELETE SET NULL,
    INDEX (user_id),
    INDEX (workout_type_id),
    INDEX (date),
    INDEX (is_completed)
) ENGINE=InnoDB;

-- Workout Exercises (Details within a Workout Log)
CREATE TABLE workout_exercises (
    id INT PRIMARY KEY AUTO_INCREMENT,
    workout_id INT NOT NULL,
    exercise_name VARCHAR(100) NOT NULL,
    sets INT,
    reps INT,
    weight DECIMAL(10,2) COMMENT 'in kg',
    duration INT COMMENT 'in seconds',
    rest_period INT COMMENT 'in seconds',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (workout_id) REFERENCES workouts(id) ON DELETE CASCADE,
    INDEX (workout_id)
) ENGINE=InnoDB;



-- ==================================================================
-- Weight Tracking
-- ==================================================================
CREATE TABLE weight_tracking (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    weight DECIMAL(5,2) NOT NULL COMMENT 'in kg',
    body_fat_percentage DECIMAL(5,2),
    muscle_mass DECIMAL(5,2),
    measurement_date DATE NOT NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY (user_id, measurement_date),
    INDEX (user_id),
    INDEX (measurement_date)
) ENGINE=InnoDB;


-- ==================================================================
-- Payments
-- ==================================================================
CREATE TABLE payments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    membership_id INT,
    amount DECIMAL(10,2) NOT NULL,
    currency VARCHAR(3) DEFAULT 'LKR', -- Changed default to LKR
    payment_method ENUM('credit_card', 'debit_card', 'net_banking', 'upi', 'cash', 'wallet') NOT NULL,
    payment_gateway VARCHAR(50),
    transaction_id VARCHAR(255),
    invoice_number VARCHAR(50),
    status ENUM('pending', 'completed', 'failed', 'refunded') DEFAULT 'pending',
    payment_date DATETIME,
    failure_reason TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (membership_id) REFERENCES memberships(id) ON DELETE SET NULL,
    INDEX (user_id),
    INDEX (membership_id),
    INDEX (transaction_id),
    INDEX (status),
    INDEX (payment_date)
) ENGINE=InnoDB;

-- Insert sample payments (Link to user IDs and membership IDs based on the INSERTs above)
INSERT INTO payments (user_id, membership_id, amount, payment_method, payment_gateway, transaction_id, status, payment_date)
SELECT m.user_id, m.id, p.price, 'credit_card', 'stripe', CONCAT('ch_', UUID()), 'completed', m.start_date
FROM memberships m JOIN plans p ON m.plan_id = p.id
WHERE m.user_id = (SELECT id FROM users WHERE email = 'john@example.com') LIMIT 1;

INSERT INTO payments (user_id, membership_id, amount, payment_method, payment_gateway, transaction_id, status, payment_date)
SELECT m.user_id, m.id, p.price, 'cash', NULL, CONCAT('cash_', UUID()), 'completed', m.start_date
FROM memberships m JOIN plans p ON m.plan_id = p.id
WHERE m.user_id = (SELECT id FROM users WHERE email = 'jane@example.com') LIMIT 1;


-- Staff Table (Note: Consider merging this with `users` table using the `role` column or keeping separate if distinct logic is needed)
CREATE TABLE staff (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'trainer', 'receptionist', 'manager') NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ==================================================================
-- Classes Table
-- ==================================================================
CREATE TABLE classes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(150) NOT NULL,
    description TEXT,
    trainer_id INT COMMENT 'FK to users table (user with role=trainer)',
    start_time DATETIME NOT NULL,
    end_time DATETIME NOT NULL,
    capacity INT NOT NULL DEFAULT 20, -- Added Capacity
    difficulty ENUM('beginner', 'intermediate', 'advanced', 'all_levels') DEFAULT 'all_levels', -- Added Difficulty
    image VARCHAR(255), -- Added Image
    status ENUM('upcoming', 'completed', 'cancelled') DEFAULT 'upcoming',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (trainer_id) REFERENCES users(id) ON DELETE SET NULL, -- Allow class to exist without trainer
    INDEX(trainer_id),
    INDEX(start_time),
    INDEX(status)
) ENGINE=InnoDB;

-- Insert sample classes (Link to user IDs based on the INSERTs above)
INSERT INTO classes (title, description, trainer_id, start_time, end_time, capacity, difficulty, status)
SELECT 'Morning Strength', 'Build full-body strength in a 45-minute session.', u.id, DATE_ADD(CURDATE(), INTERVAL 3 DAY) + INTERVAL 8 HOUR, DATE_ADD(CURDATE(), INTERVAL 3 DAY) + INTERVAL 8 HOUR + INTERVAL 45 MINUTE, 15, 'intermediate', 'upcoming'
FROM users u WHERE u.email = 'mike@example.com';

INSERT INTO classes (title, description, trainer_id, start_time, end_time, capacity, difficulty, status)
SELECT 'Evening Yoga', 'Relax and unwind with calming stretches.', u.id, DATE_ADD(CURDATE(), INTERVAL 4 DAY) + INTERVAL 18 HOUR, DATE_ADD(CURDATE(), INTERVAL 4 DAY) + INTERVAL 19 HOUR, 25, 'beginner', 'upcoming'
FROM users u WHERE u.email = 'sarah@example.com';

-- ==================================================================
-- Class Registrations Table
-- ==================================================================
CREATE TABLE class_registrations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    class_id INT NOT NULL,
    user_id INT NOT NULL COMMENT 'FK to users table (customer)',
    registration_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    attendance_status ENUM('registered', 'attended', 'absent', 'cancelled') DEFAULT 'registered',
    payment_status ENUM('pending', 'paid', 'free') DEFAULT 'pending', -- If classes require payment
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY (class_id, user_id), -- Prevent double registration
    INDEX (user_id),
    INDEX (class_id),
    INDEX (attendance_status)
) ENGINE=InnoDB;


-- ==================================================================
-- Equipment Table (Optional)
-- ==================================================================
CREATE TABLE equipment (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    quantity INT NOT NULL,
    `condition` ENUM('excellent', 'good', 'fair', 'poor', 'maintenance') DEFAULT 'good',
    last_maintenance_date DATE,
    next_maintenance_date DATE,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX (is_active),
    INDEX (`condition`)
) ENGINE=InnoDB;

-- ==================================================================
-- Blog Categories (Optional)
-- ==================================================================
CREATE TABLE blog_categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL UNIQUE,
    slug VARCHAR(100) UNIQUE NOT NULL,
    description TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX (is_active)
) ENGINE=InnoDB;


-- ==================================================================
-- Blog Posts (Optional)
-- ==================================================================
CREATE TABLE posts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    content LONGTEXT NOT NULL,
    excerpt TEXT,
    category_id INT,
    author_id INT COMMENT 'FK to users table (admin, staff, trainer)',
    featured_image VARCHAR(255),
    meta_title VARCHAR(255),
    meta_description TEXT,
    is_featured BOOLEAN DEFAULT FALSE,
    is_published BOOLEAN DEFAULT FALSE,
    published_at DATETIME,
    views INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES blog_categories(id) ON DELETE SET NULL,
    FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX (is_published),
    INDEX (is_featured),
    INDEX (category_id),
    INDEX (author_id)
) ENGINE=InnoDB;


-- ==================================================================
-- Blog Comments (Optional)
-- ==================================================================
CREATE TABLE post_comments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    post_id INT NOT NULL,
    user_id INT, -- Can be NULL for guest comments
    parent_id INT, -- For threaded comments
    author_name VARCHAR(100), -- Required if user_id is NULL
    author_email VARCHAR(100), -- Required if user_id is NULL
    content TEXT NOT NULL,
    is_approved BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (parent_id) REFERENCES post_comments(id) ON DELETE SET NULL,
    INDEX (post_id),
    INDEX (user_id),
    INDEX (is_approved)
) ENGINE=InnoDB;


-- ==================================================================
-- Settings Table (Optional)
-- ==================================================================
CREATE TABLE settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    setting_group VARCHAR(50),
    is_public BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX (setting_key),
    INDEX (setting_group)
) ENGINE=InnoDB;

-- ==================================================================
-- Audit Log (Optional)
-- ==================================================================
CREATE TABLE audit_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT, -- Can be NULL for system actions
    action VARCHAR(100) NOT NULL,
    table_name VARCHAR(50),
    record_id INT,
    old_values JSON,
    new_values JSON,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX (user_id),
    INDEX (action),
    INDEX (table_name),
    INDEX (created_at)
) ENGINE=InnoDB;


-- ==================================================================
-- Contact Submissions Table
-- ==================================================================
CREATE TABLE contact_submissions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    subject VARCHAR(100) NOT NULL,
    message TEXT NOT NULL,
    submission_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('new', 'read', 'replied', 'spam') DEFAULT 'new',
    ip_address VARCHAR(45),
    user_agent TEXT,
    replied_by INT COMMENT 'FK to users table (staff/admin who replied)',
    replied_at DATETIME,
    FOREIGN KEY (replied_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX (email),
    INDEX (status),
    INDEX (submission_date)
) ENGINE=InnoDB;

