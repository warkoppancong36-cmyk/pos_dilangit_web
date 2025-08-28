<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class InventoryUploadController extends Controller
{
    /**
     * Upload and process inventory data
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function uploadInventoryData(Request $request): JsonResponse
    {
        try {
            // Validate the request
            $validator = Validator::make($request->all(), [
                'inventory_data' => 'required|array',
                'inventory_data.*.id_inventory' => 'required|integer',
                'inventory_data.*.current_stock' => 'required|numeric|min:0',
                'inventory_data.*.available_stock' => 'required|numeric|min:0',
                'inventory_data.*.reorder_level' => 'nullable|numeric|min:0',
                'inventory_data.*.average_cost' => 'nullable|numeric|min:0',
                'summary' => 'required|array',
                'summary.total_items' => 'required|integer|min:0',
                'summary.export_timestamp' => 'required|date',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $inventoryData = $request->input('inventory_data');
            $summary = $request->input('summary');

            // Start database transaction
            DB::beginTransaction();

            try {
                // Process inventory data upload
                $uploadResult = $this->processInventoryUpload($inventoryData, $summary);

                // Log the upload activity
                $this->logUploadActivity($summary, $uploadResult);

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Data inventory berhasil diupload',
                    'data' => [
                        'uploaded_items' => $uploadResult['uploaded_count'],
                        'skipped_items' => $uploadResult['skipped_count'],
                        'updated_items' => $uploadResult['updated_count'],
                        'total_processed' => count($inventoryData),
                        'upload_timestamp' => now()->toISOString(),
                        'summary' => $summary
                    ]
                ]);

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (\Exception $e) {
            Log::error('Inventory upload error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengupload data inventory: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Process the inventory data upload
     *
     * @param array $inventoryData
     * @param array $summary
     * @return array
     */
    private function processInventoryUpload(array $inventoryData, array $summary): array
    {
        $uploadedCount = 0;
        $skippedCount = 0;
        $updatedCount = 0;
        $errors = [];

        foreach ($inventoryData as $item) {
            try {
                // Check if inventory record exists
                $existingInventory = DB::table('inventory')
                    ->where('id_inventory', $item['id_inventory'])
                    ->first();

                if (!$existingInventory) {
                    $skippedCount++;
                    $errors[] = "Inventory ID {$item['id_inventory']} not found";
                    continue;
                }

                // Prepare update data
                $updateData = [
                    'current_stock' => $item['current_stock'],
                    'available_stock' => $item['available_stock'],
                    'reserved_stock' => $item['reserved_stock'] ?? 0,
                    'reorder_level' => $item['reorder_level'] ?? $existingInventory->reorder_level,
                    'max_stock_level' => $item['max_stock_level'] ?? $existingInventory->max_stock_level,
                    'average_cost' => $item['average_cost'] ?? $existingInventory->average_cost,
                    'last_cost' => $item['last_cost'] ?? $existingInventory->last_cost,
                    'storage_location' => $item['storage_location'] ?? $existingInventory->storage_location,
                    'notes' => $item['notes'] ?? $existingInventory->notes,
                    'updated_at' => now(),
                ];

                // Update inventory record
                $updated = DB::table('inventory')
                    ->where('id_inventory', $item['id_inventory'])
                    ->update($updateData);

                if ($updated) {
                    $updatedCount++;

                    // Create inventory movement record for upload sync
                    $this->createUploadMovementRecord($item, $existingInventory);
                } else {
                    $skippedCount++;
                }

                $uploadedCount++;

            } catch (\Exception $e) {
                $skippedCount++;
                $errors[] = "Error processing inventory ID {$item['id_inventory']}: " . $e->getMessage();
                Log::error("Inventory upload item error", [
                    'item' => $item,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return [
            'uploaded_count' => $uploadedCount,
            'updated_count' => $updatedCount,
            'skipped_count' => $skippedCount,
            'errors' => $errors
        ];
    }

    /**
     * Create movement record for upload sync
     *
     * @param array $item
     * @param object $existingInventory
     * @return void
     */
    private function createUploadMovementRecord(array $item, object $existingInventory): void
    {
        try {
            // Only create movement if stock changed
            if ($existingInventory->current_stock != $item['current_stock']) {
                $movementType = $item['current_stock'] > $existingInventory->current_stock ? 'in' : 'out';
                $quantity = abs($item['current_stock'] - $existingInventory->current_stock);

                DB::table('inventory_movements')->insert([
                    'id_inventory' => $item['id_inventory'],
                    'movement_type' => $movementType,
                    'quantity' => $quantity,
                    'stock_before' => $existingInventory->current_stock,
                    'stock_after' => $item['current_stock'],
                    'reason' => 'Data Upload Sync',
                    'notes' => 'Automated sync from inventory data upload at ' . now()->format('Y-m-d H:i:s'),
                    'reference_number' => 'UPLOAD-' . now()->format('YmdHis') . '-' . $item['id_inventory'],
                    'cost_per_unit' => $item['average_cost'] ?? 0,
                    'total_cost' => $quantity * ($item['average_cost'] ?? 0),
                    'created_by' => auth()->id() ?? 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        } catch (\Exception $e) {
            Log::warning("Failed to create movement record for upload sync", [
                'inventory_id' => $item['id_inventory'],
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Log upload activity
     *
     * @param array $summary
     * @param array $uploadResult
     * @return void
     */
    private function logUploadActivity(array $summary, array $uploadResult): void
    {
        try {
            DB::table('inventory_upload_logs')->insert([
                'user_id' => auth()->id() ?? 1,
                'total_items_uploaded' => $uploadResult['uploaded_count'],
                'total_items_updated' => $uploadResult['updated_count'],
                'total_items_skipped' => $uploadResult['skipped_count'],
                'total_items_processed' => $summary['total_items'],
                'total_stock_value' => $summary['total_stock_value'] ?? 0,
                'low_stock_count' => $summary['low_stock_count'] ?? 0,
                'out_of_stock_count' => $summary['out_of_stock_count'] ?? 0,
                'export_timestamp' => $summary['export_timestamp'],
                'filters_applied' => json_encode($summary['filters_applied'] ?? []),
                'upload_status' => 'success',
                'notes' => 'Inventory data uploaded successfully via export function',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Exception $e) {
            Log::warning("Failed to log upload activity", [
                'error' => $e->getMessage(),
                'summary' => $summary
            ]);
        }
    }

    /**
     * Get upload history
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getUploadHistory(Request $request): JsonResponse
    {
        try {
            $page = $request->get('page', 1);
            $perPage = $request->get('per_page', 15);

            $history = DB::table('inventory_upload_logs')
                ->leftJoin('users', 'inventory_upload_logs.user_id', '=', 'users.id')
                ->select([
                    'inventory_upload_logs.*',
                    'users.name as user_name',
                    'users.email as user_email'
                ])
                ->orderBy('inventory_upload_logs.created_at', 'desc')
                ->paginate($perPage, ['*'], 'page', $page);

            return response()->json([
                'success' => true,
                'data' => $history->items(),
                'pagination' => [
                    'current_page' => $history->currentPage(),
                    'per_page' => $history->perPage(),
                    'total' => $history->total(),
                    'last_page' => $history->lastPage(),
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Get upload history error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil riwayat upload'
            ], 500);
        }
    }
}
