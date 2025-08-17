<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Customer;
use App\Models\User;
use App\Models\CashRegister;
use App\Models\CashTransaction;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class PosTestDataSeeder extends Seeder
{
    public function run(): void
    {
        // Create cash register if not exists
        $cashRegister = CashRegister::first();
        if (!$cashRegister) {
            $cashRegister = CashRegister::create([
                'register_name' => 'Main Register',
                'register_code' => 'MAIN-001',
                'location' => 'Main Counter',
                'active' => true,
                'current_cash_balance' => 500000,
                'supported_payment_methods' => ['cash', 'card', 'digital_wallet'],
                'description' => 'Default main cash register',
                'created_by' => 1
            ]);
        }

        // Get or create user
        $user = User::first();
        if (!$user) {
            $user = User::create([
                'name' => 'Cashier Test',
                'email' => 'cashier@test.com',
                'password' => bcrypt('password')
            ]);
        }

        // Get or create products
        $products = Product::take(3)->get();
        if ($products->isEmpty()) {
            $products = collect([
                Product::create([
                    'name' => 'Kopi Americano',
                    'sku' => 'AMR001',
                    'selling_price' => 25000,
                    'cost_price' => 15000,
                    'stock' => 100,
                    'created_by' => $user->id
                ]),
                Product::create([
                    'name' => 'Cappuccino',
                    'sku' => 'CAP001', 
                    'selling_price' => 30000,
                    'cost_price' => 18000,
                    'stock' => 100,
                    'created_by' => $user->id
                ]),
                Product::create([
                    'name' => 'Ice Tea Lemon',
                    'sku' => 'ITL001',
                    'selling_price' => 18000,
                    'cost_price' => 8000,
                    'stock' => 100,
                    'created_by' => $user->id
                ])
            ]);
        }

        // Get or create customers
        $customers = Customer::take(3)->get();
        if ($customers->isEmpty()) {
            $customers = collect([
                Customer::create([
                    'name' => 'John Doe',
                    'phone' => '081234567890',
                    'email' => 'john@example.com',
                    'created_by' => $user->id
                ]),
                Customer::create([
                    'name' => 'Jane Smith', 
                    'phone' => '081234567891',
                    'email' => 'jane@example.com',
                    'created_by' => $user->id
                ]),
                Customer::create([
                    'name' => 'Bob Johnson',
                    'phone' => '081234567892',
                    'email' => 'bob@example.com',
                    'created_by' => $user->id
                ])
            ]);
        }

        // Create today's orders
        for ($i = 1; $i <= 3; $i++) {
            $orderNumber = 'ORD-' . date('Ymd') . '-' . str_pad($i, 4, '0', STR_PAD_LEFT);
            $customer = $customers->random();
            
            // Calculate amounts
            $subtotal = 0;
            $orderItems = [];
            
            // Random 1-3 products per order
            $selectedProducts = $products->random(rand(1, 2));
            foreach ($selectedProducts as $product) {
                $quantity = rand(1, 2);
                $itemTotal = $product->selling_price * $quantity;
                $subtotal += $itemTotal;
                
                $orderItems[] = [
                    'product' => $product,
                    'quantity' => $quantity,
                    'unit_price' => $product->selling_price,
                    'total_price' => $itemTotal
                ];
            }
            
            $taxAmount = $subtotal * 0.1; // 10% tax
            $totalAmount = $subtotal + $taxAmount;
            
            // Create order
            $order = Order::create([
                'order_number' => $orderNumber,
                'id_customer' => $customer->id_customer,
                'id_user' => $user->id,
                'order_type' => collect(['dine_in', 'takeaway', 'delivery'])->random(),
                'status' => 'completed',
                'guest_count' => rand(1, 4),
                'subtotal' => $subtotal,
                'discount_amount' => 0,
                'tax_amount' => $taxAmount,
                'service_charge' => 0,
                'total_amount' => $totalAmount,
                'notes' => 'Test order ' . $i,
                'order_date' => now()->format('Y-m-d'),
                'completed_at' => now()
            ]);
            
            // Create order items
            foreach ($orderItems as $item) {
                OrderItem::create([
                    'id_order' => $order->id_order,
                    'id_product' => $item['product']->id_product,
                    'item_name' => $item['product']->name,
                    'item_sku' => $item['product']->sku,
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'total_price' => $item['total_price']
                ]);
            }
            
            // Create payment
            $paymentMethod = collect(['cash', 'card'])->random();
            $payment = Payment::create([
                'id_order' => $order->id_order,
                'payment_number' => 'PAY-' . date('YmdHis') . '-' . $i,
                'payment_method' => $paymentMethod,
                'amount' => $totalAmount,
                'cash_received' => $paymentMethod === 'cash' ? $totalAmount + rand(0, 50000) : null,
                'change_amount' => $paymentMethod === 'cash' ? rand(0, 5000) : null,
                'status' => 'paid',
                'processed_by' => $user->id,
                'payment_date' => now()
            ]);
            
            // Create cash transaction for cash payments
            if ($paymentMethod === 'cash') {
                $balanceBefore = $cashRegister->current_cash_balance;
                $balanceAfter = $balanceBefore + $totalAmount;
                
                CashTransaction::create([
                    'id_cash_register' => $cashRegister->id_cash_register,
                    'id_user' => $user->id,
                    'id_order' => $order->id_order,
                    'type' => 'in',
                    'source' => 'sale',
                    'amount' => $totalAmount,
                    'balance_before' => $balanceBefore,
                    'balance_after' => $balanceAfter,
                    'description' => 'Penjualan - Order #' . $order->order_number,
                    'reference_number' => 'SALE-' . $order->id_order . '-' . now()->format('YmdHis'),
                    'transaction_date' => now()
                ]);
                
                $cashRegister->update(['current_cash_balance' => $balanceAfter]);
            }
        }
        
        $this->command->info('POS test data created successfully!');
    }
}
