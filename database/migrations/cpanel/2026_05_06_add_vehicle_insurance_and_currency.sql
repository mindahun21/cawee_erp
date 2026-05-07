-- =============================================================================
-- cPanel/MySQL Ready Migration
-- File: 2026_05_06_add_vehicle_insurance_and_currency.sql
-- Description: Adds currency_id (FK), structured insurance fields, and
--              third-party inspection expiry date to the `vehicles` table.
-- Instructions:
--   1. Run this SQL via cPanel > phpMyAdmin > SQL tab
--   2. OR import via: Databases > phpMyAdmin > Select DB > Import > choose this file
--   3. Replace `your_database_name` below with your actual cPanel DB name if needed.
-- =============================================================================

-- Step 1: Add currency_id foreign key (links to your existing `currencies` table)
ALTER TABLE `vehicles`
    ADD COLUMN `currency_id` BIGINT UNSIGNED NULL DEFAULT NULL AFTER `purchase_price`,
    ADD CONSTRAINT `vehicles_currency_id_foreign`
        FOREIGN KEY (`currency_id`) REFERENCES `currencies`(`id`)
        ON DELETE SET NULL ON UPDATE CASCADE;

-- Step 2: Add structured insurance documentation fields
ALTER TABLE `vehicles`
    ADD COLUMN `insurance_provider`      VARCHAR(255) NULL DEFAULT NULL AFTER `currency_id`,
    ADD COLUMN `insurance_policy_number` VARCHAR(255) NULL DEFAULT NULL AFTER `insurance_provider`,
    ADD COLUMN `insurance_certificate`   VARCHAR(255) NULL DEFAULT NULL AFTER `insurance_policy_number`;

-- Step 3: Add the missing third-party inspection expiry date
ALTER TABLE `vehicles`
    ADD COLUMN `latest_third_party_inspection_expiry` DATE NULL DEFAULT NULL
        AFTER `latest_third_party_inspection_date`;

-- =============================================================================
-- Rollback (run these if you need to undo the above changes)
-- =============================================================================
-- ALTER TABLE `vehicles` DROP FOREIGN KEY `vehicles_currency_id_foreign`;
-- ALTER TABLE `vehicles`
--     DROP COLUMN `currency_id`,
--     DROP COLUMN `insurance_provider`,
--     DROP COLUMN `insurance_policy_number`,
--     DROP COLUMN `insurance_certificate`,
--     DROP COLUMN `latest_third_party_inspection_expiry`;
