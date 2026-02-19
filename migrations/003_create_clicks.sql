-- Migration: 003_create_clicks
-- Description: Create clicks table for analytics
-- Created: 2026-02-17

CREATE TABLE IF NOT EXISTS clicks (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    link_id INT UNSIGNED NOT NULL,
    ip_address VARCHAR(45) NULL,
    user_agent VARCHAR(500) NULL,
    country_code VARCHAR(2) NULL,
    city VARCHAR(100) NULL,
    device_type VARCHAR(20) NULL,
    browser VARCHAR(50) NULL,
    os VARCHAR(50) NULL,
    clicked_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_clicks_link_id (link_id),
    INDEX idx_clicks_clicked_at (clicked_at),
    INDEX idx_clicks_country (country_code),
    INDEX idx_clicks_device (device_type),
    
    FOREIGN KEY (link_id) REFERENCES links(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Rollback:
-- DROP TABLE IF EXISTS clicks;
