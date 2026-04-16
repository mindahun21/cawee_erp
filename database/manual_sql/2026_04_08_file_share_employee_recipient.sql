SET @column_exists := (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'file_shares'
      AND COLUMN_NAME = 'shared_with_employee_id'
);

SET @add_column_sql := IF(
    @column_exists = 0,
    'ALTER TABLE `file_shares` ADD COLUMN `shared_with_employee_id` BIGINT UNSIGNED NULL AFTER `shared_with_user_id`',
    'SELECT 1'
);

PREPARE add_column_stmt FROM @add_column_sql;
EXECUTE add_column_stmt;
DEALLOCATE PREPARE add_column_stmt;

SET @fk_exists := (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'file_shares'
      AND CONSTRAINT_NAME = 'file_shares_shared_with_employee_id_foreign'
);

SET @add_fk_sql := IF(
    @fk_exists = 0,
    'ALTER TABLE `file_shares` ADD CONSTRAINT `file_shares_shared_with_employee_id_foreign` FOREIGN KEY (`shared_with_employee_id`) REFERENCES `employees`(`id`) ON DELETE SET NULL',
    'SELECT 1'
);

PREPARE add_fk_stmt FROM @add_fk_sql;
EXECUTE add_fk_stmt;
DEALLOCATE PREPARE add_fk_stmt;
