-- File Sharing Settings rollout for dump-based environments
-- Safe to run multiple times.

CREATE TABLE IF NOT EXISTS `file_sharing_settings` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `key` VARCHAR(100) NOT NULL,
    `group` VARCHAR(60) NOT NULL DEFAULT 'general',
    `label` VARCHAR(140) NOT NULL,
    `value` TEXT NULL,
    `data_type` VARCHAR(20) NOT NULL DEFAULT 'string',
    `description` TEXT NULL,
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    `updated_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `file_sharing_settings_key_unique` (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `file_sharing_settings` (`key`, `group`, `label`, `value`, `data_type`, `description`, `created_at`, `updated_at`)
VALUES
    (
        'max_file_size_mb',
        'general',
        'Maximum File Size (MB)',
        '25',
        'integer',
        'Maximum upload size allowed for a shared file.',
        NOW(),
        NOW()
    ),
    (
        'allowed_file_types',
        'general',
        'Allowed File Types',
        '["pdf","doc","docx","xls","xlsx","png","jpg","jpeg","txt","csv","zip"]',
        'json',
        'List of allowed file extensions.',
        NOW(),
        NOW()
    ),
    (
        'default_link_expiry_days',
        'sharing',
        'Default Link Expiry (Days)',
        '7',
        'integer',
        'Auto-set expiry days for new shares when expiry is not provided.',
        NOW(),
        NOW()
    ),
    (
        'public_sharing_enabled',
        'sharing',
        'Enable Public Sharing',
        'true',
        'boolean',
        'Allow or block creation of public share links.',
        NOW(),
        NOW()
    ),
    (
        'require_public_password',
        'security',
        'Require Password for Public Shares',
        'false',
        'boolean',
        'Require password protection for every public share link.',
        NOW(),
        NOW()
    )
ON DUPLICATE KEY UPDATE
    `group` = VALUES(`group`),
    `label` = VALUES(`label`),
    `value` = VALUES(`value`),
    `data_type` = VALUES(`data_type`),
    `description` = VALUES(`description`),
    `updated_at` = NOW();
