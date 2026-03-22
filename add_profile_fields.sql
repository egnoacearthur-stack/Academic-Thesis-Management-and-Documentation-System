-- Database Update Script
-- Add profile picture and bio fields to users table

USE thesis_management;

-- Add profile_picture column
ALTER TABLE users ADD COLUMN profile_picture VARCHAR(255) NULL AFTER phone;

-- Add bio column
ALTER TABLE users ADD COLUMN bio TEXT NULL AFTER profile_picture;

-- Verify changes
DESCRIBE users;