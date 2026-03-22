-- Add Google UID column to users table
-- Run this SQL in your database to support Google authentication

ALTER TABLE users 
ADD COLUMN google_uid VARCHAR(255) NULL UNIQUE AFTER email,
ADD COLUMN profile_picture VARCHAR(500) NULL AFTER google_uid;

-- Add index for faster lookups
CREATE INDEX idx_google_uid ON users(google_uid);

-- Update existing users to have NULL for google_uid
-- (Already NULL by default, but this ensures consistency)
UPDATE users SET google_uid = NULL WHERE google_uid = '';