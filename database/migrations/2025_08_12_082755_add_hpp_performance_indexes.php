<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Indexes for product_items table - critical for HPP calculation
        Schema::table('product_items', function (Blueprint $table) {
            // Skip product_id and item_id indexes - already exist from create table migration
            // Skip is_critical index - already exists from create table migration
            
            // Only add composite index yang belum ada
            $table->index(['product_id', 'item_id'], 'idx_product_items_product_item');
        });

        // Indexes for purchase_items table - untuk query harga pembelian
        Schema::table('purchase_items', function (Blueprint $table) {
            // Index untuk query berdasarkan item_id (sering digunakan untuk get latest/average price)
            $table->index('item_id', 'idx_purchase_items_item_id');
            
            // Index untuk query berdasarkan created_at (untuk get latest purchase)
            $table->index('created_at', 'idx_purchase_items_created_at');
            
            // Composite index untuk query item + tanggal
            $table->index(['item_id', 'created_at'], 'idx_purchase_items_item_date');
            
            // Index untuk unit_cost (untuk performance saat calculate average)
            $table->index('unit_cost', 'idx_purchase_items_unit_cost');
        });

        // Indexes for products table - untuk performance product queries
        Schema::table('products', function (Blueprint $table) {
            // Index untuk filter active products
            $table->index('active', 'idx_products_active');
            
            // Index untuk cost field (untuk HPP analysis)
            $table->index('cost', 'idx_products_cost');
            
            // Index untuk price field (untuk profitability analysis)
            $table->index('price', 'idx_products_price');
            
            // Composite index untuk active products dengan cost
            $table->index(['active', 'cost'], 'idx_products_active_cost');
        });

        // Indexes for items table - untuk performance item queries
        Schema::table('items', function (Blueprint $table) {
            // Index untuk filter active items
            $table->index('active', 'idx_items_active');
            
            // Index untuk cost_per_unit field
            $table->index('cost_per_unit', 'idx_items_cost_per_unit');
            
            // Index untuk supplier_id (untuk query items per supplier)
            $table->index('supplier_id', 'idx_items_supplier_id');
            
            // Index untuk current_stock (untuk inventory management)
            $table->index('current_stock', 'idx_items_current_stock');
        });

        // Indexes for purchases table - untuk performance purchase queries
        Schema::table('purchases', function (Blueprint $table) {
            // Index untuk supplier_id
            $table->index('supplier_id', 'idx_purchases_supplier_id');
            
            // Index untuk purchase_date
            $table->index('purchase_date', 'idx_purchases_date');
            
            // Index untuk status
            $table->index('status', 'idx_purchases_status');
            
            // Composite index untuk active purchases
            $table->index(['status', 'purchase_date'], 'idx_purchases_status_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop indexes for product_items
        Schema::table('product_items', function (Blueprint $table) {
            $table->dropIndex('idx_product_items_product_id');
            $table->dropIndex('idx_product_items_item_id');
            $table->dropIndex('idx_product_items_product_item');
            $table->dropIndex('idx_product_items_is_critical');
        });

        // Drop indexes for purchase_items
        Schema::table('purchase_items', function (Blueprint $table) {
            $table->dropIndex('idx_purchase_items_item_id');
            $table->dropIndex('idx_purchase_items_created_at');
            $table->dropIndex('idx_purchase_items_item_date');
            $table->dropIndex('idx_purchase_items_unit_cost');
        });

        // Drop indexes for products
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex('idx_products_active');
            $table->dropIndex('idx_products_cost');
            $table->dropIndex('idx_products_price');
            $table->dropIndex('idx_products_active_cost');
        });

        // Drop indexes for items
        Schema::table('items', function (Blueprint $table) {
            $table->dropIndex('idx_items_active');
            $table->dropIndex('idx_items_cost_per_unit');
            $table->dropIndex('idx_items_supplier_id');
            $table->dropIndex('idx_items_current_stock');
        });

        // Drop indexes for purchases
        Schema::table('purchases', function (Blueprint $table) {
            $table->dropIndex('idx_purchases_supplier_id');
            $table->dropIndex('idx_purchases_date');
            $table->dropIndex('idx_purchases_status');
            $table->dropIndex('idx_purchases_status_date');
        });
    }
};
