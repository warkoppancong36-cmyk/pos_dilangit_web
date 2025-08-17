<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Create the table first without partitioning using raw SQL for better control
        DB::statement("
            CREATE TABLE transaction_logs (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                transaction_id VARCHAR(255) NOT NULL,
                method VARCHAR(10) NOT NULL,
                endpoint VARCHAR(500) NOT NULL,
                url VARCHAR(1000) NOT NULL,
                user_id BIGINT UNSIGNED NULL,
                username VARCHAR(255) NULL,
                user_role VARCHAR(255) NULL,
                token_id VARCHAR(255) NULL,
                request_headers JSON NULL,
                request_payload LONGTEXT NULL,
                request_ip VARCHAR(45) NOT NULL,
                user_agent TEXT NULL,
                response_status INT NOT NULL,
                response_payload LONGTEXT NULL,
                response_headers JSON NULL,
                execution_time DECIMAL(8,3) NULL,
                memory_usage BIGINT NULL,
                session_id VARCHAR(255) NULL,
                device_type VARCHAR(255) NULL,
                browser VARCHAR(255) NULL,
                platform VARCHAR(255) NULL,
                location VARCHAR(255) NULL,
                transaction_type VARCHAR(255) NULL,
                module VARCHAR(255) NULL,
                action VARCHAR(255) NULL,
                entity_type VARCHAR(255) NULL,
                entity_id VARCHAR(255) NULL,
                is_successful BOOLEAN NOT NULL DEFAULT 1,
                error_message TEXT NULL,
                stack_trace TEXT NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                created_date DATE NOT NULL DEFAULT (CURDATE()),
                completed_at TIMESTAMP NULL,
                PRIMARY KEY (id, created_date),
                UNIQUE KEY unique_transaction_id (transaction_id, created_date),
                INDEX idx_user_created (user_id, created_date),
                INDEX idx_transaction_type_created (transaction_type, created_date),
                INDEX idx_endpoint_method (endpoint, method),
                INDEX idx_successful_created (is_successful, created_date),
                INDEX idx_response_status_created (response_status, created_date),
                INDEX idx_created_at (created_at)
            ) ENGINE=InnoDB
            PARTITION BY RANGE COLUMNS(created_date) (
                PARTITION p202408 VALUES LESS THAN ('2024-09-01'),
                PARTITION p202409 VALUES LESS THAN ('2024-10-01'),
                PARTITION p202410 VALUES LESS THAN ('2024-11-01'),
                PARTITION p202411 VALUES LESS THAN ('2024-12-01'),
                PARTITION p202412 VALUES LESS THAN ('2025-01-01'),
                PARTITION p202501 VALUES LESS THAN ('2025-02-01'),
                PARTITION p202502 VALUES LESS THAN ('2025-03-01'),
                PARTITION p202503 VALUES LESS THAN ('2025-04-01'),
                PARTITION p202504 VALUES LESS THAN ('2025-05-01'),
                PARTITION p202505 VALUES LESS THAN ('2025-06-01'),
                PARTITION p202506 VALUES LESS THAN ('2025-07-01'),
                PARTITION p202507 VALUES LESS THAN ('2025-08-01'),
                PARTITION p202508 VALUES LESS THAN ('2025-09-01'),
                PARTITION p202509 VALUES LESS THAN ('2025-10-01'),
                PARTITION p202510 VALUES LESS THAN ('2025-11-01'),
                PARTITION p202511 VALUES LESS THAN ('2025-12-01'),
                PARTITION p202512 VALUES LESS THAN ('2026-01-01'),
                PARTITION p_future VALUES LESS THAN (MAXVALUE)
            )
        ");
        
        // Add auto partition management
        $this->createPartitionMaintenanceEvents();
    }

    /**
     * Create automatic partition maintenance events
     */
    private function createPartitionMaintenanceEvents(): void
    {
        // Create event to automatically add new partitions
        DB::statement("
            CREATE EVENT IF NOT EXISTS ev_add_transaction_logs_partition
            ON SCHEDULE EVERY 1 MONTH
            STARTS (LAST_DAY(CURDATE()) + INTERVAL 1 DAY)
            DO
            BEGIN
                SET @next_month_start = DATE_FORMAT(DATE_ADD(CURDATE(), INTERVAL 2 MONTH), '%Y-%m-01');
                SET @partition_name = CONCAT('p', DATE_FORMAT(DATE_ADD(CURDATE(), INTERVAL 1 MONTH), '%Y%m'));
                
                SET @sql = CONCAT('ALTER TABLE transaction_logs REORGANIZE PARTITION p_future INTO (PARTITION ', 
                                 @partition_name, 
                                 ' VALUES LESS THAN (\"', 
                                 @next_month_start, 
                                 '\"), PARTITION p_future VALUES LESS THAN (MAXVALUE))');
                
                PREPARE stmt FROM @sql;
                EXECUTE stmt;
                DEALLOCATE PREPARE stmt;
            END
        ");

        // Create event to automatically drop old partitions (older than 2 years)
        DB::statement("
            CREATE EVENT IF NOT EXISTS ev_drop_old_transaction_logs_partition
            ON SCHEDULE EVERY 1 MONTH
            STARTS (LAST_DAY(CURDATE()) + INTERVAL 1 DAY + INTERVAL 1 HOUR)
            DO
            BEGIN
                SET @old_partition_name = CONCAT('p', DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 24 MONTH), '%Y%m'));
                
                -- Check if partition exists before dropping
                SET @partition_exists = (
                    SELECT COUNT(*) 
                    FROM information_schema.PARTITIONS 
                    WHERE TABLE_SCHEMA = DATABASE() 
                    AND TABLE_NAME = 'transaction_logs' 
                    AND PARTITION_NAME = @old_partition_name
                );
                
                IF @partition_exists > 0 THEN
                    SET @sql = CONCAT('ALTER TABLE transaction_logs DROP PARTITION ', @old_partition_name);
                    PREPARE stmt FROM @sql;
                    EXECUTE stmt;
                    DEALLOCATE PREPARE stmt;
                END IF;
            END
        ");
    }

    public function down(): void
    {
        // Drop events first
        DB::statement("DROP EVENT IF EXISTS ev_add_transaction_logs_partition");
        DB::statement("DROP EVENT IF EXISTS ev_drop_old_transaction_logs_partition");
        
        // Drop table
        Schema::dropIfExists('transaction_logs');
    }
};
