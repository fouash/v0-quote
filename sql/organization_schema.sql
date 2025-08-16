-- Organization Management System for Getlancer Quote Platform
-- This schema supports buyer/supplier organizations with proper validation and security

CREATE TABLE `organizations` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `organization_name` VARCHAR(255) NOT NULL,
    `organization_type` ENUM('Buyer', 'Supplier', 'Both') NOT NULL DEFAULT 'Buyer',
    `address_line1` VARCHAR(255) NOT NULL,
    `national_address` VARCHAR(8) NOT NULL,
    `city` VARCHAR(100) NOT NULL,
    `state_province` VARCHAR(100) NOT NULL,
    `country` VARCHAR(50) NOT NULL,
    `contact_email` VARCHAR(255) NOT NULL,
    `contact_phone` VARCHAR(20) NOT NULL,
    `vat_number` VARCHAR(20) NOT NULL,
    `cr_number` VARCHAR(20) NOT NULL,
    `avl_number` VARCHAR(20),
    `nwc_number` VARCHAR(20),
    `se_number` VARCHAR(20),
    `modon_number` VARCHAR(20),
    `is_active` BOOLEAN DEFAULT TRUE,
    `is_verified` BOOLEAN DEFAULT FALSE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `created_by` INT,
    `updated_by` INT,

    -- Unique constraints
    CONSTRAINT `uq_org_name` UNIQUE (`organization_name`),
    CONSTRAINT `uq_org_vat` UNIQUE (`vat_number`),
    CONSTRAINT `uq_org_cr` UNIQUE (`cr_number`),
    CONSTRAINT `uq_org_avl` UNIQUE (`avl_number`),
    CONSTRAINT `uq_org_nwc` UNIQUE (`nwc_number`),
    CONSTRAINT `uq_org_se` UNIQUE (`se_number`),
    CONSTRAINT `uq_org_modon` UNIQUE (`modon_number`),

    -- Email validation
    CONSTRAINT `chk_contact_email` CHECK (`contact_email` REGEXP '^[^@\\s]+@[^@\\s]+\\.[^@\\s]+$'),
    -- Phone validation (international format)
    CONSTRAINT `chk_contact_phone` CHECK (`contact_phone` REGEXP '^\\+?[0-9\\-\\s\\(\\)]+$'),
    -- National address validation (8 digits)
    CONSTRAINT `chk_national_address` CHECK (`national_address` REGEXP '^[0-9]{8}$'),
    -- VAT number validation (15 digits starting with 3)
    CONSTRAINT `chk_vat_number` CHECK (`vat_number` REGEXP '^3[0-9]{14}$'),
    -- CR number validation (10 digits)
    CONSTRAINT `chk_cr_number` CHECK (`cr_number` REGEXP '^[0-9]{10}$'),

    -- Composite unique constraint for identity verification
    UNIQUE INDEX `uq_org_identity` (`contact_email`, `contact_phone`, `vat_number`, `cr_number`),

    -- Performance indexes
    INDEX `idx_org_type` (`organization_type`),
    INDEX `idx_org_active` (`is_active`),
    INDEX `idx_org_verified` (`is_verified`),
    INDEX `idx_location` (`city`, `state_province`, `country`),
    INDEX `idx_created_at` (`created_at`),
    INDEX `idx_vat_cr` (`vat_number`, `cr_number`),

    -- Foreign key constraints
    CONSTRAINT `fk_org_created_by` FOREIGN KEY (`created_by`) 
        REFERENCES `users` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_org_updated_by` FOREIGN KEY (`updated_by`) 
        REFERENCES `users` (`id`) ON DELETE SET NULL

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `organization_attachments` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `organization_id` INT NOT NULL,
    `attachment_type` ENUM('VAT', 'CR', 'AVL', 'NWC', 'SE', 'MODON', 'Other') NOT NULL,
    `file_name` VARCHAR(255) NOT NULL,
    `file_path` VARCHAR(500) NOT NULL,
    `original_name` VARCHAR(255) NOT NULL,
    `mime_type` VARCHAR(100),
    `file_size_kb` INT,
    `file_hash` VARCHAR(64), -- SHA-256 hash for integrity
    `is_verified` BOOLEAN DEFAULT FALSE,
    `verified_at` TIMESTAMP NULL,
    `verified_by` INT NULL,
    `uploaded_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `uploaded_by` INT,

    -- Foreign key constraints
    CONSTRAINT `fk_org_attachment` FOREIGN KEY (`organization_id`) 
        REFERENCES `organizations` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_attachment_uploaded_by` FOREIGN KEY (`uploaded_by`) 
        REFERENCES `users` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_attachment_verified_by` FOREIGN KEY (`verified_by`) 
        REFERENCES `users` (`id`) ON DELETE SET NULL,

    -- Security constraints
    CONSTRAINT `chk_file_size` CHECK (`file_size_kb` <= 10240), -- Max 10MB
    CONSTRAINT `chk_mime_type` CHECK (`mime_type` IN (
        'application/pdf', 
        'image/jpeg', 
        'image/png', 
        'image/gif',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
    )),

    -- Performance indexes
    INDEX `idx_org_attachment_type` (`organization_id`, `attachment_type`),
    INDEX `idx_attachment_verified` (`is_verified`),
    INDEX `idx_attachment_uploaded` (`uploaded_at`),
    INDEX `idx_file_hash` (`file_hash`)

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Organization users relationship (many-to-many)
CREATE TABLE `organization_users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `organization_id` INT NOT NULL,
    `user_id` INT NOT NULL,
    `role` ENUM('Admin', 'Manager', 'Member', 'Viewer') NOT NULL DEFAULT 'Member',
    `permissions` JSON, -- Store specific permissions
    `is_active` BOOLEAN DEFAULT TRUE,
    `joined_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `invited_by` INT,
    `approved_by` INT,
    `approved_at` TIMESTAMP NULL,

    -- Foreign key constraints
    CONSTRAINT `fk_org_user_org` FOREIGN KEY (`organization_id`) 
        REFERENCES `organizations` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_org_user_user` FOREIGN KEY (`user_id`) 
        REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_org_user_invited_by` FOREIGN KEY (`invited_by`) 
        REFERENCES `users` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_org_user_approved_by` FOREIGN KEY (`approved_by`) 
        REFERENCES `users` (`id`) ON DELETE SET NULL,

    -- Unique constraint - user can only have one active role per organization
    UNIQUE INDEX `uq_org_user_active` (`organization_id`, `user_id`, `is_active`),

    -- Performance indexes
    INDEX `idx_org_users_org` (`organization_id`),
    INDEX `idx_org_users_user` (`user_id`),
    INDEX `idx_org_users_role` (`role`),
    INDEX `idx_org_users_active` (`is_active`)

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Organization verification history
CREATE TABLE `organization_verifications` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `organization_id` INT NOT NULL,
    `verification_type` ENUM('Email', 'Phone', 'Document', 'Manual', 'API') NOT NULL,
    `status` ENUM('Pending', 'Approved', 'Rejected', 'Expired') NOT NULL DEFAULT 'Pending',
    `verified_by` INT,
    `verification_data` JSON, -- Store verification details
    `notes` TEXT,
    `expires_at` TIMESTAMP NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    -- Foreign key constraints
    CONSTRAINT `fk_org_verification` FOREIGN KEY (`organization_id`) 
        REFERENCES `organizations` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_verification_by` FOREIGN KEY (`verified_by`) 
        REFERENCES `users` (`id`) ON DELETE SET NULL,

    -- Performance indexes
    INDEX `idx_org_verification_org` (`organization_id`),
    INDEX `idx_org_verification_type` (`verification_type`),
    INDEX `idx_org_verification_status` (`status`),
    INDEX `idx_org_verification_created` (`created_at`)

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Organization settings and preferences
CREATE TABLE `organization_settings` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `organization_id` INT NOT NULL,
    `setting_key` VARCHAR(100) NOT NULL,
    `setting_value` TEXT,
    `setting_type` ENUM('string', 'number', 'boolean', 'json') DEFAULT 'string',
    `is_public` BOOLEAN DEFAULT FALSE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `updated_by` INT,

    -- Foreign key constraints
    CONSTRAINT `fk_org_setting` FOREIGN KEY (`organization_id`) 
        REFERENCES `organizations` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_setting_updated_by` FOREIGN KEY (`updated_by`) 
        REFERENCES `users` (`id`) ON DELETE SET NULL,

    -- Unique constraint
    UNIQUE INDEX `uq_org_setting` (`organization_id`, `setting_key`),

    -- Performance indexes
    INDEX `idx_org_setting_key` (`setting_key`),
    INDEX `idx_org_setting_public` (`is_public`)

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default organization types and settings
INSERT INTO `organization_settings` (`organization_id`, `setting_key`, `setting_value`, `setting_type`, `is_public`) VALUES
(1, 'notification_email', 'true', 'boolean', false),
(1, 'notification_sms', 'false', 'boolean', false),
(1, 'auto_approve_quotes', 'false', 'boolean', false),
(1, 'max_quote_amount', '1000000', 'number', false),
(1, 'preferred_currency', 'SAR', 'string', true),
(1, 'business_hours', '{"start": "08:00", "end": "17:00", "timezone": "Asia/Riyadh"}', 'json', true);

-- Create indexes for better performance
CREATE INDEX `idx_organizations_search` ON `organizations` 
    (`organization_name`, `vat_number`, `cr_number`, `contact_email`);

CREATE INDEX `idx_organizations_location_search` ON `organizations` 
    (`city`, `state_province`, `country`, `organization_type`);

-- Full-text search index for organization names and addresses
ALTER TABLE `organizations` ADD FULLTEXT(`organization_name`, `address_line1`, `city`);

-- Add triggers for audit trail
DELIMITER $$

CREATE TRIGGER `tr_organizations_updated` 
    BEFORE UPDATE ON `organizations`
    FOR EACH ROW
BEGIN
    SET NEW.updated_at = CURRENT_TIMESTAMP;
END$$

CREATE TRIGGER `tr_org_verification_auto_approve` 
    AFTER INSERT ON `organization_verifications`
    FOR EACH ROW
BEGIN
    -- Auto-approve email verifications if email is valid
    IF NEW.verification_type = 'Email' AND NEW.status = 'Pending' THEN
        UPDATE `organization_verifications` 
        SET `status` = 'Approved', `updated_at` = CURRENT_TIMESTAMP
        WHERE `id` = NEW.id;
    END IF;
END$$

DELIMITER ;

-- Create views for common queries
CREATE VIEW `v_active_organizations` AS
SELECT 
    o.*,
    COUNT(ou.user_id) as user_count,
    COUNT(oa.id) as attachment_count,
    MAX(ov.created_at) as last_verification_date
FROM `organizations` o
LEFT JOIN `organization_users` ou ON o.id = ou.organization_id AND ou.is_active = TRUE
LEFT JOIN `organization_attachments` oa ON o.id = oa.organization_id
LEFT JOIN `organization_verifications` ov ON o.id = ov.organization_id AND ov.status = 'Approved'
WHERE o.is_active = TRUE
GROUP BY o.id;

CREATE VIEW `v_organization_summary` AS
SELECT 
    o.id,
    o.organization_name,
    o.organization_type,
    o.city,
    o.state_province,
    o.country,
    o.is_active,
    o.is_verified,
    o.created_at,
    COUNT(DISTINCT ou.user_id) as total_users,
    COUNT(DISTINCT oa.id) as total_attachments,
    COUNT(DISTINCT CASE WHEN ov.status = 'Approved' THEN ov.id END) as approved_verifications
FROM `organizations` o
LEFT JOIN `organization_users` ou ON o.id = ou.organization_id
LEFT JOIN `organization_attachments` oa ON o.id = oa.organization_id
LEFT JOIN `organization_verifications` ov ON o.id = ov.organization_id
GROUP BY o.id;