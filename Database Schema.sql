-- Database creation
CREATE DATABASE IF NOT EXISTS petadopthub;
USE petadopthub;

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fullname VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    location VARCHAR(255),
    postal_code VARCHAR(20),
    bio TEXT,
    profile_picture VARCHAR(255),
    preferred_animal_types VARCHAR(255),
    preferred_sizes VARCHAR(255),
    preferred_ages VARCHAR(255),
    role ENUM('user', 'admin') NOT NULL DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL DEFAULT NULL
);

-- Pets table
CREATE TABLE IF NOT EXISTS pets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    type ENUM('Dog', 'Cat') NOT NULL,
    breed VARCHAR(100) NOT NULL,
    age INT NOT NULL,
    gender ENUM('Male', 'Female') NOT NULL,
    size ENUM('Small', 'Medium', 'Large') NOT NULL,
    weight VARCHAR(50),
    description TEXT,
    personality TEXT,
    health_status VARCHAR(255),
    shelter_name VARCHAR(100) NOT NULL,
    shelter_email VARCHAR(100) NOT NULL,
    shelter_phone VARCHAR(20) NOT NULL,
    shelter_address VARCHAR(255) NOT NULL,
    status ENUM('Available', 'Pending', 'Adopted') DEFAULT 'Available',
    image VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL DEFAULT NULL,
    INDEX idx_status (status)
);

-- Adoption Applications table
CREATE TABLE IF NOT EXISTS applications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    pet_id INT NOT NULL,
    pet_experience TEXT,
    home_type ENUM('House with yard', 'House without yard', 'Apartment', 'Condominium', 'Others') NOT NULL,
    other_pets TEXT,
    work_schedule TEXT,
    reason_for_adoption TEXT,
    willing_vet ENUM('Yes', 'No') NOT NULL,
    fb_profile VARCHAR(255),
    valid_id VARCHAR(255),
    status ENUM('Pending', 'Approved', 'Rejected') DEFAULT 'Pending',
    applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    interview_type VARCHAR(50) NULL,
    interview_date DATE NULL,
    interview_time TIME NULL,
    meeting_link VARCHAR(500) NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL DEFAULT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (pet_id) REFERENCES pets(id),
    INDEX idx_status (status)
);

-- Favorite Pets table
CREATE TABLE IF NOT EXISTS favorites (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    pet_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (pet_id) REFERENCES pets(id),
    UNIQUE KEY (user_id, pet_id)
);


-- Archive Pets Table
CREATE TABLE IF NOT EXISTS archived_pets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    original_pet_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    type ENUM('Dog', 'Cat') NOT NULL,
    breed VARCHAR(100) NOT NULL,
    age INT NOT NULL,
    gender ENUM('Male', 'Female'),
    size ENUM('Small', 'Medium', 'Large'),
    weight VARCHAR(50),
    description TEXT,
    personality TEXT,
    health_status VARCHAR(255),
    shelter_name VARCHAR(100) NOT NULL,
    shelter_email VARCHAR(100) NOT NULL,
    shelter_phone VARCHAR(20) NOT NULL,
    shelter_address VARCHAR(255) NOT NULL,
    status VARCHAR(50) NOT NULL,
    image VARCHAR(255),
    archive_reason ENUM('Adopted', 'Deleted') DEFAULT 'Deleted',
    archived_by INT,
    archived_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    original_created_at DATETIME NULL DEFAULT NULL,
    notes TEXT
);

-- Messages Table for User-Admin Chat
CREATE TABLE IF NOT EXISTS messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sender_id INT NOT NULL,
    receiver_id INT NOT NULL,
    message TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sender_id) REFERENCES users(id),
    FOREIGN KEY (receiver_id) REFERENCES users(id),
    INDEX idx_conversation (sender_id, receiver_id),
    INDEX idx_created_at (created_at),
    INDEX idx_is_read (is_read)
);


