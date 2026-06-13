-- ============================================================
-- J.I.OJO Construction Services - Database Setup Script
-- Database: construction_management
-- ============================================================

CREATE DATABASE IF NOT EXISTS `construction_management`
    DEFAULT CHARACTER SET utf8mb4
    DEFAULT COLLATE utf8mb4_unicode_ci;

USE `construction_management`;

-- ============================================================
-- ADMINS TABLE (authentication)
-- ============================================================
CREATE TABLE IF NOT EXISTS `admins` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `email` VARCHAR(255) NOT NULL,
    `password` VARCHAR(255) NOT NULL,
    UNIQUE KEY `uq_admins_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- SKILL CATEGORIES TABLE (manpower folders)
-- ============================================================
CREATE TABLE IF NOT EXISTS `skill_categories` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(255) NOT NULL,
    UNIQUE KEY `uq_skill_categories_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- MANPOWER TABLE (workers)
-- ============================================================
CREATE TABLE IF NOT EXISTS `manpower` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(255) NOT NULL,
    `skills` VARCHAR(255) DEFAULT NULL,
    `contact_number` VARCHAR(50) DEFAULT NULL,
    `address` TEXT DEFAULT NULL,
    `position` VARCHAR(255) DEFAULT NULL,
    `rate` DECIMAL(15,2) DEFAULT 0.00,
    `project_id` INT DEFAULT NULL,
    `project_site_text` VARCHAR(255) DEFAULT NULL,
    `foreman` VARCHAR(255) DEFAULT NULL,
    `photo_path` VARCHAR(500) DEFAULT NULL,
    `status` VARCHAR(50) DEFAULT 'Active',
    `is_archived` TINYINT(1) NOT NULL DEFAULT 0,
    `archived_date` DATETIME DEFAULT NULL,
    INDEX `idx_manpower_skills` (`skills`),
    INDEX `idx_manpower_project` (`project_id`),
    INDEX `idx_manpower_foreman` (`foreman`),
    INDEX `idx_manpower_archived` (`is_archived`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- BILLING PROGRESS TABLE
-- ============================================================
CREATE TABLE IF NOT EXISTS `billing_progress` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `project_id` INT NOT NULL,
    `award_cost_id` INT DEFAULT NULL,
    `billing_date` DATE NOT NULL,
    `billing_reference_no` VARCHAR(255) DEFAULT NULL,
    `billing_description` TEXT NOT NULL,
    `amount_billed` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `amount_collected` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `payment_method` VARCHAR(50) DEFAULT NULL,
    `remarks` TEXT DEFAULT NULL,
    `status` VARCHAR(50) NOT NULL DEFAULT 'Pending',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_bp_project` (`project_id`),
    INDEX `idx_bp_award_cost` (`award_cost_id`),
    INDEX `idx_bp_status` (`status`),
    INDEX `idx_bp_date` (`billing_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- PROJECTS TABLE
-- ============================================================
CREATE TABLE IF NOT EXISTS `projects` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(255) NOT NULL,
    `block_no` VARCHAR(100) NOT NULL DEFAULT '',
    `lot_no` VARCHAR(100) NOT NULL DEFAULT '',
    `client_name` VARCHAR(255) DEFAULT NULL,
    `location` VARCHAR(255) DEFAULT NULL,
    `description` TEXT DEFAULT NULL,
    `foreman` VARCHAR(255) DEFAULT NULL,
    `foreman_2` VARCHAR(255) DEFAULT NULL,
    `start_date` DATE DEFAULT NULL,
    `status` VARCHAR(50) DEFAULT 'pending',
    `completion_date` DATE DEFAULT NULL,
    `work_description` TEXT DEFAULT NULL,
    `project_description` TEXT DEFAULT NULL,
    `total_amount` DECIMAL(15,2) DEFAULT 0.00,
    `ntp_attachment` VARCHAR(500) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_projects_status` (`status`),
    INDEX `idx_projects_block_lot` (`block_no`, `lot_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- PROJECT ACCOMPLISHMENTS TABLE (checklist)
-- ============================================================
CREATE TABLE IF NOT EXISTS `project_accomplishments` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `project_id` INT NOT NULL,
    `category` VARCHAR(255) DEFAULT NULL,
    `task_name` VARCHAR(500) DEFAULT NULL,
    `status` VARCHAR(50) DEFAULT 'Not Started',
    `award_cost` DECIMAL(15,2) DEFAULT 1500.00,
    `assigned_worker` VARCHAR(255) DEFAULT NULL,
    `completion_date` DATE DEFAULT NULL,
    INDEX `idx_pa_project` (`project_id`),
    INDEX `idx_pa_category` (`category`),
    INDEX `idx_pa_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- PROJECT COSTS TABLE
-- ============================================================
CREATE TABLE IF NOT EXISTS `project_costs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `project_id` INT DEFAULT NULL,
    `total_cost` DECIMAL(15,2) DEFAULT 0.00,
    INDEX `idx_pc_project` (`project_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- AWARD COSTS TABLE
-- ============================================================
CREATE TABLE IF NOT EXISTS `award_costs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `project_id` INT DEFAULT NULL,
    `service_agreement_code` VARCHAR(255) NOT NULL,
    `block_no` VARCHAR(100) DEFAULT '',
    `lot_no` VARCHAR(100) DEFAULT '',
    `location` VARCHAR(255) DEFAULT '',
    `item` VARCHAR(255) NOT NULL,
    `unit` VARCHAR(50) NOT NULL,
    `start_date` DATE NOT NULL,
    `completion_date` DATE NOT NULL,
    `work_description` TEXT NOT NULL,
    `project_description` TEXT NOT NULL,
    `total_amount` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `attachment_path` VARCHAR(500) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_ac_project` (`project_id`),
    INDEX `idx_ac_service_code` (`service_agreement_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- BILL OF MATERIALS (BOM) TABLE
-- ============================================================
CREATE TABLE IF NOT EXISTS `bill_of_materials` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `project_id` INT DEFAULT NULL,
    `award_cost_id` INT DEFAULT NULL,
    `award_cost_text` VARCHAR(255) DEFAULT NULL,
    `material_name` VARCHAR(255) NOT NULL,
    `description` TEXT DEFAULT NULL,
    `quantity` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `unit` VARCHAR(50) NOT NULL,
    `unit_cost` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `total_cost` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `supplier_name` VARCHAR(255) DEFAULT NULL,
    `remarks` TEXT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_bom_project` (`project_id`),
    INDEX `idx_bom_award_cost` (`award_cost_id`),
    INDEX `idx_bom_material` (`material_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- SUPPLIERS TABLE
-- ============================================================
CREATE TABLE IF NOT EXISTS `suppliers` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(255) NOT NULL,
    `materials` TEXT DEFAULT NULL,
    `contact` VARCHAR(100) DEFAULT NULL,
    `email` VARCHAR(255) DEFAULT NULL,
    `status` VARCHAR(50) DEFAULT 'Active',
    INDEX `idx_suppliers_name` (`name`),
    INDEX `idx_suppliers_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- INVENTORY CATEGORIES TABLE
-- ============================================================
CREATE TABLE IF NOT EXISTS `inventory_categories` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(255) NOT NULL,
    UNIQUE KEY `uq_inventory_categories_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- INVENTORY TABLE
-- ============================================================
CREATE TABLE IF NOT EXISTS `inventory` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(255) NOT NULL,
    `category` VARCHAR(255) DEFAULT NULL,
    `stock` DECIMAL(15,2) DEFAULT 0.00,
    `unit` VARCHAR(50) DEFAULT NULL,
    `unit_cost` DECIMAL(15,2) DEFAULT 0.00,
    `supplier` VARCHAR(255) DEFAULT NULL,
    INDEX `idx_inventory_name` (`name`),
    INDEX `idx_inventory_category` (`category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- MATERIAL ISSUANCES TABLE
-- ============================================================
CREATE TABLE IF NOT EXISTS `material_issuances` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `project_id` INT NOT NULL,
    `item_id` INT NOT NULL,
    `qty` DECIMAL(15,2) DEFAULT 0.00,
    `unit_cost` DECIMAL(15,2) DEFAULT 0.00,
    `receiver` VARCHAR(255) DEFAULT NULL,
    `issue_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_mi_project` (`project_id`),
    INDEX `idx_mi_item` (`item_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- PAYROLL TABLE (active cycle)
-- ============================================================
CREATE TABLE IF NOT EXISTS `payroll` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `manpower_id` INT NOT NULL,
    `pay_date` DATE DEFAULT NULL,
    `job_description` VARCHAR(500) DEFAULT NULL,
    `rate` DECIMAL(15,2) DEFAULT 0.00,
    `days_worked` DECIMAL(10,2) DEFAULT 0.00,
    `gross_pay` DECIMAL(15,2) DEFAULT 0.00,
    `deductions` DECIMAL(15,2) DEFAULT 0.00,
    `net_pay` DECIMAL(15,2) DEFAULT 0.00,
    `award_cost` DECIMAL(15,2) DEFAULT 0.00,
    `cash_advance` DECIMAL(15,2) DEFAULT 0.00,
    `overall_advance` DECIMAL(15,2) DEFAULT 0.00,
    `balance` DECIMAL(15,2) DEFAULT 0.00,
    INDEX `idx_payroll_manpower` (`manpower_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- PAYROLL HISTORY TABLE (archived cycles)
-- ============================================================
CREATE TABLE IF NOT EXISTS `payroll_history` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `cycle_id` VARCHAR(255) DEFAULT NULL,
    `manpower_id` INT NOT NULL,
    `pay_date` DATE DEFAULT NULL,
    `job_description` VARCHAR(500) DEFAULT NULL,
    `rate` DECIMAL(15,2) DEFAULT 0.00,
    `net_pay` DECIMAL(15,2) DEFAULT 0.00,
    `award_cost` DECIMAL(15,2) DEFAULT 0.00,
    `cash_advance` DECIMAL(15,2) DEFAULT 0.00,
    `overall_advance` DECIMAL(15,2) DEFAULT 0.00,
    `balance` DECIMAL(15,2) DEFAULT 0.00,
    INDEX `idx_ph_cycle` (`cycle_id`),
    INDEX `idx_ph_manpower` (`manpower_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- CASH RELEASES TABLE (Capital Monitoring)
-- ============================================================
CREATE TABLE IF NOT EXISTS `cash_releases` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `project_id` INT NOT NULL,
    `award_cost_id` INT DEFAULT NULL,
    `release_date` DATE NOT NULL,
    `release_reference_no` VARCHAR(255) DEFAULT NULL,
    `release_description` TEXT NOT NULL,
    `category` VARCHAR(50) DEFAULT NULL,
    `capital_amount` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `release_amount` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `released_to` VARCHAR(255) DEFAULT NULL,
    `payment_method` VARCHAR(50) DEFAULT NULL,
    `remarks` TEXT DEFAULT NULL,
    `status` VARCHAR(50) NOT NULL DEFAULT 'Released',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_cr_project` (`project_id`),
    INDEX `idx_cr_award_cost` (`award_cost_id`),
    INDEX `idx_cr_status` (`status`),
    INDEX `idx_cr_date` (`release_date`),
    INDEX `idx_cr_category` (`category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- PROJECT NTP TABLE (Notice to Proceed)
-- ============================================================
CREATE TABLE IF NOT EXISTS `project_ntp` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `project_id` INT NOT NULL,
    `ntp_ticket` VARCHAR(255) DEFAULT NULL,
    `date_received` DATE DEFAULT NULL,
    `award_cost` DECIMAL(15,2) DEFAULT 0.00,
    `due_date` DATE DEFAULT NULL,
    `acceptance_date` DATE DEFAULT NULL,
    `file_path` VARCHAR(500) DEFAULT NULL,
    INDEX `idx_pntp_project` (`project_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- ALTER TABLE for existing databases (run if you already imported)
-- ============================================================
-- ALTER TABLE `projects`
--     ADD COLUMN `block_no` VARCHAR(100) NOT NULL DEFAULT '' AFTER `name`,
--     ADD COLUMN `lot_no` VARCHAR(100) NOT NULL DEFAULT '' AFTER `block_no`,
--     ADD COLUMN `foreman_2` VARCHAR(255) DEFAULT NULL AFTER `foreman`,
--     ADD INDEX `idx_projects_block_lot` (`block_no`, `lot_no`);
--
-- ============================================================
-- ALTER TABLE for award_costs (run if table already exists with old structure)
-- ============================================================
-- If award_costs has no critical data, recommended:
-- DROP TABLE IF EXISTS `award_costs`;
-- Then run the CREATE TABLE above.
--
-- If preserving existing data is needed:
-- ALTER TABLE `award_costs`
--     ADD COLUMN `project_id` INT DEFAULT NULL AFTER `id`,
--     ADD COLUMN `service_agreement_code` VARCHAR(255) NOT NULL AFTER `project_id`,
--     ADD COLUMN `block_no` VARCHAR(100) DEFAULT '' AFTER `service_agreement_code`,
--     ADD COLUMN `lot_no` VARCHAR(100) DEFAULT '' AFTER `block_no`,
--     ADD COLUMN `location` VARCHAR(255) DEFAULT '' AFTER `lot_no`,
--     ADD COLUMN `item` VARCHAR(255) NOT NULL AFTER `location`,
--     ADD COLUMN `unit` VARCHAR(50) NOT NULL AFTER `item`,
--     ADD COLUMN `start_date` DATE NOT NULL AFTER `unit`,
--     ADD COLUMN `completion_date` DATE NOT NULL AFTER `start_date`,
--     CHANGE COLUMN `scope_of_work` `work_description` TEXT NOT NULL,
--     ADD COLUMN `project_description` TEXT NOT NULL AFTER `work_description`,
--     CHANGE COLUMN `amount` `total_amount` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
--     ADD COLUMN `attachment_path` VARCHAR(500) DEFAULT NULL AFTER `total_amount`,
--     ADD COLUMN `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER `attachment_path`,
--     ADD COLUMN `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER `created_at`,
--     ADD INDEX `idx_ac_project` (`project_id`),
--     ADD INDEX `idx_ac_service_code` (`service_agreement_code`);

-- ============================================================
-- ALTER TABLE for bill_of_materials (run if table already exists)
-- ============================================================
-- DROP TABLE IF EXISTS `bill_of_materials`;
-- Then run the CREATE TABLE above.
--
-- If preserving existing data is needed:
-- ALTER TABLE `bill_of_materials`
--     ADD COLUMN `project_id` INT DEFAULT NULL AFTER `id`,
--     ADD COLUMN `award_cost_id` INT DEFAULT NULL AFTER `project_id`,
--     ADD COLUMN `material_name` VARCHAR(255) NOT NULL AFTER `award_cost_id`,
--     ADD COLUMN `description` TEXT DEFAULT NULL AFTER `material_name`,
--     ADD COLUMN `quantity` DECIMAL(15,2) NOT NULL DEFAULT 0.00 AFTER `description`,
--     ADD COLUMN `unit` VARCHAR(50) NOT NULL AFTER `quantity`,
--     ADD COLUMN `unit_cost` DECIMAL(15,2) NOT NULL DEFAULT 0.00 AFTER `unit`,
--     ADD COLUMN `total_cost` DECIMAL(15,2) NOT NULL DEFAULT 0.00 AFTER `unit_cost`,
--     ADD COLUMN `supplier_name` VARCHAR(255) DEFAULT NULL AFTER `total_cost`,
--     ADD COLUMN `remarks` TEXT DEFAULT NULL AFTER `supplier_name`,
--     ADD COLUMN `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER `remarks`,
--     ADD COLUMN `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER `created_at`,
--     ADD INDEX `idx_bom_project` (`project_id`),
--     ADD INDEX `idx_bom_award_cost` (`award_cost_id`),
--     ADD INDEX `idx_bom_material` (`material_name`);

-- ============================================================
-- ALTER TABLE for manpower (run if table already exists)
-- ============================================================
-- ALTER TABLE `manpower`
--     ADD COLUMN `contact_number` VARCHAR(50) DEFAULT NULL AFTER `skills`,
--     ADD COLUMN `address` TEXT DEFAULT NULL AFTER `contact_number`,
--     ADD COLUMN `foreman` VARCHAR(255) DEFAULT NULL AFTER `project_id`,
--     ADD COLUMN `status` VARCHAR(50) DEFAULT 'Active' AFTER `photo_path`,
--     ADD INDEX `idx_manpower_foreman` (`foreman`);

-- ============================================================
-- ALTER TABLE for billing_progress (run if table already exists)
-- ============================================================
-- DROP TABLE IF EXISTS `billing_progress`;
-- Then run the CREATE TABLE above.
--
-- If preserving existing data is needed:
-- ALTER TABLE `billing_progress`
--     ADD COLUMN `project_id` INT NOT NULL AFTER `id`,
--     ADD COLUMN `award_cost_id` INT DEFAULT NULL AFTER `project_id`,
--     ADD COLUMN `billing_date` DATE NOT NULL AFTER `award_cost_id`,
--     ADD COLUMN `billing_reference_no` VARCHAR(255) DEFAULT NULL AFTER `billing_date`,
--     ADD COLUMN `billing_description` TEXT NOT NULL AFTER `billing_reference_no`,
--     ADD COLUMN `amount_billed` DECIMAL(15,2) NOT NULL DEFAULT 0.00 AFTER `billing_description`,
--     ADD COLUMN `amount_collected` DECIMAL(15,2) NOT NULL DEFAULT 0.00 AFTER `amount_billed`,
--     ADD COLUMN `payment_method` VARCHAR(50) DEFAULT NULL AFTER `amount_collected`,
--     ADD COLUMN `remarks` TEXT DEFAULT NULL AFTER `payment_method`,
--     ADD COLUMN `status` VARCHAR(50) NOT NULL DEFAULT 'Pending' AFTER `remarks`,
--     ADD COLUMN `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER `status`,
--     ADD COLUMN `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER `created_at`,
--     ADD INDEX `idx_bp_project` (`project_id`),
--     ADD INDEX `idx_bp_award_cost` (`award_cost_id`),
--     ADD INDEX `idx_bp_status` (`status`),
--     ADD INDEX `idx_bp_date` (`billing_date`);

-- ============================================================
-- ALTER TABLE for cash_releases (run if table already exists)
-- ============================================================
-- DROP TABLE IF EXISTS `cash_releases`;
-- Then run the CREATE TABLE above.
--
-- If preserving existing data is needed:
-- ALTER TABLE `cash_releases`
--     ADD COLUMN `project_id` INT NOT NULL AFTER `id`,
--     ADD COLUMN `award_cost_id` INT DEFAULT NULL AFTER `project_id`,
--     MODIFY COLUMN `release_date` DATE NOT NULL,
--     ADD COLUMN `release_reference_no` VARCHAR(255) DEFAULT NULL AFTER `release_date`,
--     CHANGE COLUMN `description` `release_description` TEXT NOT NULL,
--     ADD COLUMN `capital_amount` DECIMAL(15,2) NOT NULL DEFAULT 0.00 AFTER `category`,
--     CHANGE COLUMN `amount` `release_amount` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
--     CHANGE COLUMN `name` `released_to` VARCHAR(255) DEFAULT NULL,
--     ADD COLUMN `payment_method` VARCHAR(50) DEFAULT NULL AFTER `released_to`,
--     ADD COLUMN `remarks` TEXT DEFAULT NULL AFTER `payment_method`,
--     ADD COLUMN `status` VARCHAR(50) NOT NULL DEFAULT 'Released' AFTER `remarks`,
--     ADD COLUMN `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER `status`,
--     ADD COLUMN `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER `created_at`,
--     ADD INDEX `idx_cr_project` (`project_id`),
--     ADD INDEX `idx_cr_award_cost` (`award_cost_id`),
--     ADD INDEX `idx_cr_status` (`status`);

-- ============================================================
-- PAYROLL ENTRIES TABLE (Manpower & Subcon payroll)
-- ============================================================
CREATE TABLE IF NOT EXISTS `payroll_entries` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `project_id` INT NOT NULL,
    `worker_id` INT DEFAULT NULL,
    `foreman` VARCHAR(255) DEFAULT NULL,
    `payroll_type` VARCHAR(20) NOT NULL DEFAULT 'Manpower',
    `payee_name` VARCHAR(255) NOT NULL,
    `position_or_role` VARCHAR(255) DEFAULT NULL,
    `skill` VARCHAR(255) DEFAULT NULL,
    `period_start` DATE NOT NULL,
    `period_end` DATE NOT NULL,
    `daily_rate` DECIMAL(15,2) DEFAULT 0.00,
    `days_worked` DECIMAL(10,2) DEFAULT 0.00,
    `overtime_hours` DECIMAL(10,2) DEFAULT 0.00,
    `overtime_rate` DECIMAL(15,2) DEFAULT 0.00,
    `gross_amount` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `deductions` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `net_amount` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `payment_method` VARCHAR(50) DEFAULT NULL,
    `payroll_status` VARCHAR(20) NOT NULL DEFAULT 'Pending',
    `subcon_company` VARCHAR(255) DEFAULT NULL,
    `subcon_scope` TEXT DEFAULT NULL,
    `subcon_reference_no` VARCHAR(255) DEFAULT NULL,
    `remarks` TEXT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_pe_project` (`project_id`),
    INDEX `idx_pe_worker` (`worker_id`),
    INDEX `idx_pe_type` (`payroll_type`),
    INDEX `idx_pe_status` (`payroll_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- ALTER TABLE for payroll_entries (run if table already exists)
-- ============================================================
-- DROP TABLE IF EXISTS `payroll_entries`;
-- Then run the CREATE TABLE above.

-- ============================================================
-- ALTER TABLE for suppliers (run if table already exists)
-- Supports MariaDB IF NOT EXISTS syntax
-- ============================================================
-- ALTER TABLE `suppliers` ADD COLUMN IF NOT EXISTS `contact_person` VARCHAR(255) DEFAULT NULL AFTER `name`;
-- ALTER TABLE `suppliers` ADD COLUMN IF NOT EXISTS `address` TEXT DEFAULT NULL AFTER `email`;
-- ALTER TABLE `suppliers` ADD COLUMN IF NOT EXISTS `material_category` VARCHAR(255) DEFAULT NULL AFTER `address`;
-- ALTER TABLE `suppliers` ADD COLUMN IF NOT EXISTS `project_id` INT DEFAULT NULL AFTER `material_category`;
-- ALTER TABLE `suppliers` ADD COLUMN IF NOT EXISTS `bom_id` INT DEFAULT NULL AFTER `project_id`;
-- ALTER TABLE `suppliers` ADD COLUMN IF NOT EXISTS `inventory_item_id` INT DEFAULT NULL AFTER `bom_id`;
-- ALTER TABLE `suppliers` ADD COLUMN IF NOT EXISTS `price_quote` DECIMAL(15,2) DEFAULT 0.00 AFTER `inventory_item_id`;
-- ALTER TABLE `suppliers` ADD COLUMN IF NOT EXISTS `payment_terms` VARCHAR(255) DEFAULT NULL AFTER `price_quote`;
-- ALTER TABLE `suppliers` ADD COLUMN IF NOT EXISTS `remarks` TEXT DEFAULT NULL AFTER `payment_terms`;
-- ALTER TABLE `suppliers` ADD COLUMN IF NOT EXISTS `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER `remarks`;
-- ALTER TABLE `suppliers` ADD COLUMN IF NOT EXISTS `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER `created_at`;
-- ALTER TABLE `suppliers` ADD INDEX IF NOT EXISTS `idx_suppliers_project` (`project_id`);
-- ALTER TABLE `suppliers` ADD INDEX IF NOT EXISTS `idx_suppliers_category` (`material_category`);

-- For MySQL without IF NOT EXISTS support, run these statements individually
-- (each will fail silently if column already exists):
-- ALTER TABLE `suppliers` ADD COLUMN `contact_person` VARCHAR(255) DEFAULT NULL AFTER `name`;
-- ALTER TABLE `suppliers` ADD COLUMN `address` TEXT DEFAULT NULL AFTER `email`;
-- ALTER TABLE `suppliers` ADD COLUMN `material_category` VARCHAR(255) DEFAULT NULL AFTER `address`;
-- ALTER TABLE `suppliers` ADD COLUMN `project_id` INT DEFAULT NULL AFTER `material_category`;
-- ALTER TABLE `suppliers` ADD COLUMN `bom_id` INT DEFAULT NULL AFTER `project_id`;
-- ALTER TABLE `suppliers` ADD COLUMN `inventory_item_id` INT DEFAULT NULL AFTER `bom_id`;
-- ALTER TABLE `suppliers` ADD COLUMN `price_quote` DECIMAL(15,2) DEFAULT 0.00 AFTER `inventory_item_id`;
-- ALTER TABLE `suppliers` ADD COLUMN `payment_terms` VARCHAR(255) DEFAULT NULL AFTER `price_quote`;
-- ALTER TABLE `suppliers` ADD COLUMN `remarks` TEXT DEFAULT NULL AFTER `payment_terms`;
-- ALTER TABLE `suppliers` ADD COLUMN `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER `remarks`;
-- ALTER TABLE `suppliers` ADD COLUMN `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER `created_at`;
-- ALTER TABLE `suppliers` ADD INDEX `idx_suppliers_project` (`project_id`);
-- ALTER TABLE `suppliers` ADD INDEX `idx_suppliers_category` (`material_category`);

-- ============================================================
-- DEFAULT LOCAL ADMIN ACCOUNT (DEVELOPMENT ONLY)
-- IMPORTANT: Change this password after first login!
-- Email:    admin@construction.com
-- Password: Admin123
-- ============================================================
INSERT INTO `admins` (`email`, `password`)
VALUES ('admin@construction.com', '$2y$10$PxiNfrK6LKDFTVI5UZ8ASOX8aFqm4.SOidWx9q9qePW.0qPjpg27W');
