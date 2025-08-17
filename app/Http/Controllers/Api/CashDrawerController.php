<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CashRegister;
use App\Models\CashTransaction;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;

class CashDrawerController extends Controller
{
    use ApiResponseTrait;

    /**
     * Get cash register data with today's transactions
     */
    public function getCashData(Request $request): JsonResponse
    {
        try {
            // Get or create default cash register
            $cashRegister = $this->getDefaultCashRegister();
            
            // Get today's transactions
            $todayTransactions = CashTransaction::where('id_cash_register', $cashRegister->id_cash_register)
                ->whereDate('created_at', today())
                ->orderBy('created_at', 'desc')
                ->get();

            // Calculate daily totals
            $dailySales = $todayTransactions->where('type', 'in')->where('source', 'sale')->sum('amount');
            $dailyExpenses = $todayTransactions->where('type', 'out')->sum('amount');

            return $this->successResponse([
                'current_balance' => $cashRegister->current_cash_balance,
                'daily_sales' => $dailySales,
                'daily_expenses' => $dailyExpenses,
                'today_transactions' => $todayTransactions->map(function($transaction) {
                    return [
                        'id' => $transaction->id_cash_transaction,
                        'type' => $transaction->type,
                        'amount' => $transaction->amount,
                        'description' => $transaction->description,
                        'notes' => $transaction->notes,
                        'created_at' => $transaction->created_at->format('d/m/Y H:i:s')
                    ];
                })
            ], 'Cash data retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve cash data: ' . $e->getMessage());
        }
    }

    /**
     * Record cash in transaction
     */
    public function cashIn(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:0.01',
            'notes' => 'nullable|string|max:500',
            'description' => 'nullable|string|max:255'
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        try {
            DB::beginTransaction();
            
            // Ensure cash_transactions table exists
            $this->ensureCashTransactionsTable();
            
            // Get or create default cash register
            $cashRegister = $this->getDefaultCashRegister();
            
            // Create cash transaction
            $transaction = CashTransaction::create([
                'id_cash_register' => $cashRegister->id_cash_register,
                'id_user' => Auth::id(),
                'type' => 'in',
                'source' => 'manual', // manual cash in
                'amount' => $request->amount,
                'balance_before' => $cashRegister->current_cash_balance,
                'balance_after' => $cashRegister->current_cash_balance + $request->amount,
                'description' => $request->description ?? 'Cash In',
                'notes' => $request->notes,
                'reference_number' => 'CI-' . date('YmdHis') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT),
                'transaction_date' => now(), // Add transaction_date
            ]);
            
            // Update cash register balance
            $cashRegister->update([
                'current_cash_balance' => $cashRegister->current_cash_balance + $request->amount
            ]);
            
            DB::commit();
            
            return $this->successResponse([
                'new_balance' => $cashRegister->current_cash_balance,
                'transaction' => [
                    'id' => $transaction->id_cash_transaction,
                    'amount' => $transaction->amount,
                    'type' => $transaction->type,
                    'description' => $transaction->description,
                    'notes' => $transaction->notes,
                    'reference_number' => $transaction->reference_number,
                    'created_at' => $transaction->created_at->format('d/m/Y H:i:s')
                ]
            ], 'Cash in recorded successfully');

        } catch (\Exception $e) {
            DB::rollback();
            return $this->serverErrorResponse('Failed to record cash in: ' . $e->getMessage());
        }
    }

    /**
     * Record cash out transaction
     */
    public function cashOut(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:0.01',
            'notes' => 'nullable|string|max:500',
            'description' => 'nullable|string|max:255'
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        try {
            DB::beginTransaction();
            
            // Get or create default cash register
            $cashRegister = $this->getDefaultCashRegister();
            
            // Check if sufficient balance
            if ($cashRegister->current_cash_balance < $request->amount) {
                return $this->badRequestResponse('Insufficient cash balance');
            }
            
            // Create cash transaction
            $transaction = CashTransaction::create([
                'id_cash_register' => $cashRegister->id_cash_register,
                'id_user' => Auth::id(),
                'type' => 'out',
                'source' => 'manual', // manual cash out
                'amount' => $request->amount,
                'balance_before' => $cashRegister->current_cash_balance,
                'balance_after' => $cashRegister->current_cash_balance - $request->amount,
                'description' => $request->description ?? 'Cash Out',
                'notes' => $request->notes,
                'reference_number' => 'CO-' . date('YmdHis') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT),
                'transaction_date' => now(), // Add transaction_date
            ]);
            
            // Update cash register balance
            $cashRegister->update([
                'current_cash_balance' => $cashRegister->current_cash_balance - $request->amount
            ]);
            
            DB::commit();
            
            return $this->successResponse([
                'new_balance' => $cashRegister->current_cash_balance,
                'transaction' => [
                    'id' => $transaction->id_cash_transaction,
                    'amount' => $transaction->amount,
                    'type' => $transaction->type,
                    'description' => $transaction->description,
                    'notes' => $transaction->notes,
                    'reference_number' => $transaction->reference_number,
                    'created_at' => $transaction->created_at->format('d/m/Y H:i:s')
                ]
            ], 'Cash out recorded successfully');

        } catch (\Exception $e) {
            DB::rollback();
            return $this->serverErrorResponse('Failed to record cash out: ' . $e->getMessage());
        }
    }

    /**
     * Get daily cash drawer summary
     */
    public function getDailySummary(Request $request): JsonResponse
    {
        try {
            $cashRegister = $this->getDefaultCashRegister();
            
            // Get today's transactions
            $todayTransactions = CashTransaction::where('id_cash_register', $cashRegister->id_cash_register)
                ->whereDate('created_at', today())
                ->get();

            // Calculate summary
            $totalCashIn = $todayTransactions->where('type', 'in')->sum('amount');
            $totalCashOut = $todayTransactions->where('type', 'out')->sum('amount');
            $dailySales = $todayTransactions->where('type', 'in')->where('source', 'sale')->sum('amount');
            $dailyExpenses = $todayTransactions->where('type', 'out')->sum('amount');
            $transactionCount = $todayTransactions->count();

            return $this->successResponse([
                'opening_balance' => 500000, // This should come from shift data
                'total_cash_in' => $totalCashIn,
                'total_cash_out' => $totalCashOut,
                'daily_sales' => $dailySales,
                'daily_expenses' => $dailyExpenses,
                'current_balance' => $cashRegister->current_cash_balance,
                'total_transactions' => $transactionCount,
                'last_transaction_at' => $todayTransactions->max('created_at')?->format('d/m/Y H:i:s')
            ], 'Daily summary retrieved successfully');
            
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve daily summary: ' . $e->getMessage());
        }
    }

    /**
     * Record a sale transaction
     */
    public function recordSaleTransaction(int $orderId, float $amount): void
    {
        try {
            $cashRegister = CashRegister::active()->first();
            if (!$cashRegister) {
                $cashRegister = $this->createDefaultCashRegister();
            }

            $balanceBefore = $cashRegister->current_cash_balance;
            $balanceAfter = $balanceBefore + $amount;

            CashTransaction::create([
                'id_cash_register' => $cashRegister->id_cash_register,
                'id_user' => Auth::id(),
                'id_order' => $orderId,
                'type' => 'in',
                'source' => 'sale',
                'amount' => $amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'description' => 'Penjualan - Order #' . $orderId,
                'reference_number' => 'SALE-' . $orderId . '-' . now()->format('YmdHis'),
                'transaction_date' => now()
            ]);

            $cashRegister->updateBalance($amount, 'in');
        } catch (\Exception $e) {
            \Log::error('Failed to record sale transaction: ' . $e->getMessage());
        }
    }

    /**
     * Ensure cash_transactions table exists
     */
    private function ensureCashTransactionsTable(): void
    {
        if (!Schema::hasTable('cash_transactions')) {
            try {
                $sql = "CREATE TABLE cash_transactions (
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
                    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    deleted_at TIMESTAMP NULL,
                    INDEX idx_cash_register_date (id_cash_register, transaction_date),
                    INDEX idx_type_source (type, source)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

                DB::statement($sql);
            } catch (\Exception $e) {
                // Log error but continue - table might exist from another request
                \Log::error('Failed to create cash_transactions table: ' . $e->getMessage());
            }
        }
    }

    /**
     * Get or create default cash register
     */
    private function getDefaultCashRegister(): CashRegister
    {
        $cashRegister = CashRegister::where('active', true)->first();
        
        if (!$cashRegister) {
            $cashRegister = $this->createDefaultCashRegister();
        }
        
        return $cashRegister;
    }

    /**
     * Create default cash register if none exists
     */
    private function createDefaultCashRegister(): CashRegister
    {
        return CashRegister::create([
            'register_name' => 'Main Register',
            'register_code' => 'MAIN-001',
            'location' => 'Main Counter',
            'active' => true,
            'current_cash_balance' => 500000, // Starting balance
            'supported_payment_methods' => ['cash', 'card', 'digital_wallet'],
            'description' => 'Default main cash register',
            'created_by' => Auth::id()
        ]);
    }
}
