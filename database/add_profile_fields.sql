-- Add Profile Fields to Users Table
-- Run this to add profile information fields

ALTER TABLE users 
ADD COLUMN phone VARCHAR(20) AFTER full_name,
ADD COLUMN bio TEXT AFTER phone,
ADD COLUMN favorite_team VARCHAR(100) AFTER bio,
ADD COLUMN profile_image VARCHAR(255) AFTER favorite_team,
ADD COLUMN city VARCHAR(100) AFTER profile_image,
ADD COLUMN country VARCHAR(100) AFTER city,
ADD COLUMN date_of_birth DATE AFTER country,
ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at;
