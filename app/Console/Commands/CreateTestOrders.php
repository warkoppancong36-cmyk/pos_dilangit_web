<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Console\Command;

class CreateTestOrders extends Command
{
    protected $signature = 'pos:create-test-orders {count=5}';
    protected $description = 'Create test orders with proper total amounts';

    public function handle()
    {
        $count = $this->argument('count');
        
        // Get test data
        $user = User::first();
        $customer = Customer::first();
        $products = Product::limit(5)->get();
        
        if (!$user) {
            $this->error('No users found. Please create a user first.');
            return 1;
        }
        
        if (!$customer) {
            $this->info('No customers found. Creating a test customer...');
            $customer = Customer::create([
                'name' => 'Test Customer',
                'email' => 'test@example.com',
                'phone' => '08123456789',
                'address' => 'Test Address'
            ]);
        }
        
        if ($products->count() == 0) {
            $this->error('No products found. Please create products first.');
            return 1;
        }
        
        $this->info("Creating {$count} test orders...");
        
        for ($i = 1; $i <= $count; $i++) {
            $orderNumber = 'ORD' . date('YmdHis') . str_pad($i, 3, '0', STR_PAD_LEFT);
            $subtotal = 0;
            
            // Create order first
            $order = Order::create([
                'order_number' => $orderNumber,
                'id_customer' => $customer->id_customer,
                'id_user' => $user->id,
                'order_type' => collect(['dine_in', 'takeaway', 'delivery'])->random(),
                'status' => collect(['completed', 'pending'])->random(),
                'guest_count' => rand(1, 4),
                'subtotal' => 0,
                'discount_amount' => 0,
                'tax_amount' => 0,
                'service_charge' => 0,
                'total_amount' => 0,
                'notes' => 'Test order ' . $i,
                'order_date' => now()->subDays(rand(0, 7))->format('Y-m-d'),
                'completed_at' => now()->subDays(rand(0, 7))
            ]);
            
            // Add random order items
            $selectedProducts = $products->random(rand(1, 3));
            foreach ($selectedProducts as $product) {
                $quantity = rand(1, 3);
                $unitPrice = $product->selling_price;
                $totalPrice = $unitPrice * $quantity;
                $subtotal += $totalPrice;
                
                OrderItem::create([
                    'id_order' => $order->id_order,
                    'id_product' => $product->id_product,
                    'item_name' => $product->name,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'total_price' => $totalPrice
                ]);
            }
            
            // Calculate and update totals
            $taxAmount = $subtotal * 0.1; // 10% tax
            $totalAmount = $subtotal + $taxAmount;
            
            $order->update([
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'total_amount' => $totalAmount
            ]);
            
            $this->info("Created order: {$orderNumber} | Total: Rp" . number_format($totalAmount, 0, ',', '.'));
        }
        
        $this->info('Test orders created successfully!');
        return 0;
    }
}
