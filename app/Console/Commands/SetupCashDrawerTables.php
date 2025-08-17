<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SetupCashDrawerTables extends Command
{
    protected $signature = 'cashdrawer:setup';
    protected $description = 'Setup cash drawer tables manually';

    public function handle()
    {
        $this->info('Setting up cash drawer tables...');
        
        try {
            // Create cash_registers table if not exists
            if (!Schema::hasTable('cash_registers')) {
                DB::statement("
                    CREATE TABLE cash_registers (
                        id_cash_register BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                        register_name VARCHAR(255) NOT NULL,
                        register_code VARCHAR(100) UNIQUE NOT NULL,
                        location VARCHAR(255) NULL,
                        active BOOLEAN NOT NULL DEFAULT 1,
                        current_cash_balance DECIMAL(15,2) NOT NULL DEFAULT 0.00,
                        supported_payment_methods JSON NULL,
                        hardware_config JSON NULL,
                        description TEXT NULL,
                        created_by BIGINT UNSIGNED NULL,
                        updated_by BIGINT UNSIGNED NULL,
                        created_at TIMESTAMP NULL,
                        updated_at TIMESTAMP NULL,
                        deleted_at TIMESTAMP NULL
                    )
                ");
                $this->info('✓ cash_registers table created');
            } else {
                $this->info('✓ cash_registers table already exists');
            }
            
            // Create cash_transactions table if not exists  
            if (!Schema::hasTable('cash_transactions')) {
                DB::statement("
                    CREATE TABLE cash_transactions (
                        id_cash_transaction BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                        id_cash_register BIGINT UNSIGNED NOT NULL,
                        id_user BIGINT UNSIGNED NOT NULL,
                        id_shift BIGINT UNSIGNED NULL,
                        id_order BIGINT UNSIGNED NULL,
                        type ENUM('in', 'out') NOT NULL,
                        source ENUM('sale', 'manual', 'initial', 'adjustment') NOT NULL,
                        amount DECIMAL(15,2) NOT NULL,
                        balance_before DECIMAL(15,2) NOT NULL,
                        balance_after DECIMAL(15,2) NOT NULL,
                        description VARCHAR(255) NULL,
                        notes TEXT NULL,
                        reference_number VARCHAR(255) UNIQUE NULL,
                        metadata JSON NULL,
                        transaction_date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                        created_at TIMESTAMP NULL,
                        updated_at TIMESTAMP NULL,
                        deleted_at TIMESTAMP NULL,
                        INDEX idx_cash_register_date (id_cash_register, transaction_date),
                        INDEX idx_type_source (type, source)
                    )
                ");
                $this->info('✓ cash_transactions table created');
            } else {
                $this->info('✓ cash_transactions table already exists');
            }
            
            $this->info('Cash drawer tables setup completed successfully!');
            return 0;
            
        } catch (\Exception $e) {
            $this->error('Error setting up tables: ' . $e->getMessage());
            return 1;
        }
    }
}
