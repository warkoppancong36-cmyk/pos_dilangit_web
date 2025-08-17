<?php

namespace App\Services;

use App\Models\Product;
// use App\Models\Variant; // DISABLED - Variant system removed
use App\Models\Inventory;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class StockMovementService
{
    public function recordMovement(array $data)
    {
        return DB::transaction(function () use ($data) {
            $this->validateMovementData($data);

            $currentStock = $this->getCurrentStock($data['id_product'], $data['id_variant'] ?? null);

            $newStock = $data['movement_type'] === 'in' 
                ? $currentStock + $data['quantity']
                : $currentStock - $data['quantity'];

            if ($data['movement_type'] === 'out' && $newStock < 0) {
                throw new \Exception('Insufficient stock. Current: ' . $currentStock . ', Requested: ' . $data['quantity']);
            }

            $movement = StockMovement::create([
                'id_product' => $data['id_product'],
                'id_variant' => $data['id_variant'] ?? null,
                'movement_type' => $data['movement_type'],
                'reason' => $data['reason'],
                'quantity' => $data['quantity'],
                'unit_cost' => $data['unit_cost'] ?? null,
                'stock_before' => $currentStock,
                'stock_after' => $newStock,
                'reference_type' => $data['reference_type'] ?? null,
                'reference_id' => $data['reference_id'] ?? null,
                'notes' => $data['notes'] ?? null,
                'created_by' => Auth::id(),
            ]);

            $this->updateStock($data['id_product'], $data['id_variant'] ?? null, $newStock);

            return $movement;
        });
    }

    public function stockIn(int $productId, int $quantity, string $reason, array $options = [])
    {
        return $this->recordMovement([
            'id_product' => $productId,
            'id_variant' => $options['id_variant'] ?? null,
            'movement_type' => 'in',
            'reason' => $reason,
            'quantity' => $quantity,
            'unit_cost' => $options['unit_cost'] ?? null,
            'reference_type' => $options['reference_type'] ?? null,
            'reference_id' => $options['reference_id'] ?? null,
            'notes' => $options['notes'] ?? null,
        ]);
    }

    public function stockOut(int $productId, int $quantity, string $reason, array $options = [])
    {
        return $this->recordMovement([
            'id_product' => $productId,
            'id_variant' => $options['id_variant'] ?? null,
            'movement_type' => 'out',
            'reason' => $reason,
            'quantity' => $quantity,
            'unit_cost' => $options['unit_cost'] ?? null,
            'reference_type' => $options['reference_type'] ?? null,
            'reference_id' => $options['reference_id'] ?? null,
            'notes' => $options['notes'] ?? null,
        ]);
    }

    public function getMovementHistory(int $productId, int $variantId = null, array $filters = [])
    {
        $query = StockMovement::with(['product', 'creator'])
            ->where('id_product', $productId);

        if ($variantId) {
            $query->where('id_variant', $variantId);
        }

        if (!empty($filters['movement_type'])) {
            $query->where('movement_type', $filters['movement_type']);
        }

        if (!empty($filters['reason'])) {
            $query->where('reason', $filters['reason']);
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        return $query->orderBy('created_at', 'desc')->paginate($filters['per_page'] ?? 15);
    }

    private function getCurrentStock(int $productId, int $variantId = null): int
    {
        // Get stock from inventory table instead of product/variant tables
        $inventory = Inventory::where('id_product', $productId);
        
        if ($variantId) {
            $inventory = $inventory->where('id_variant', $variantId);
        } else {
            $inventory = $inventory->whereNull('id_variant');
        }
        
        return $inventory->first()->current_stock ?? 0;
    }

    private function updateStock(int $productId, int $variantId = null, int $newStock): void
    {
        // Update stock in inventory table instead of product/variant tables
        $inventory = Inventory::where('id_product', $productId);
        
        if ($variantId) {
            $inventory = $inventory->where('id_variant', $variantId);
        } else {
            $inventory = $inventory->whereNull('id_variant');
        }
        
        $inventory->update(['current_stock' => $newStock, 'updated_at' => now()]);
    }

    private function validateMovementData(array $data): void
    {
        $required = ['id_product', 'movement_type', 'reason', 'quantity'];
        
        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new \Exception("Field {$field} is required");
            }
        }

        if ($data['quantity'] <= 0) {
            throw new \Exception("Quantity must be greater than 0");
        }

        $validMovementTypes = ['in', 'out'];
        if (!in_array($data['movement_type'], $validMovementTypes)) {
            throw new \Exception("Invalid movement type");
        }

        $validReasons = [
            'purchase', 'sale', 'adjustment', 'return_customer', 'return_supplier',
            'expired', 'transfer_in', 'transfer_out', 'production', 'waste'
        ];
        if (!in_array($data['reason'], $validReasons)) {
            throw new \Exception("Invalid reason");
        }
    }
}
