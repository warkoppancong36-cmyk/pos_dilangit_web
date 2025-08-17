<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use App\Models\Customer;
use Carbon\Carbon;

class OrderSeeder extends Seeder
{
    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        // Get first user as cashier
        $cashier = User::first();
        if (!$cashier) {
            $this->command->info('No users found. Please run UserSeeder first.');
            return;
        }

        // Get some products
        $products = Product::take(5)->get();
        if ($products->isEmpty()) {
            $this->command->info('No products found. Please run ProductSeeder first.');
            return;
        }

        // Get some customers (optional)
        $customers = Customer::take(3)->get();

        // Create sample orders
        $orderData = [
            [
                'order_number' => 'ORD-' . date('Ymd') . '-' . str_pad(rand(100, 999), 3, '0', STR_PAD_LEFT),
                'order_type' => 'dine_in',
                'status' => 'completed',
                'table_number' => 'T001',
                'guest_count' => 2,
                'subtotal' => 85000,
                'discount_amount' => 0,
                'tax_amount' => 8500,
                'service_charge' => 0,
                'total_amount' => 93500,
                'notes' => 'Customer requested extra spicy',
                'order_date' => now(),
                'completed_at' => now(),
            ],
            [
                'order_number' => 'ORD-' . date('Ymd') . '-' . str_pad(rand(100, 999), 3, '0', STR_PAD_LEFT),
                'order_type' => 'takeaway',
                'status' => 'completed',
                'table_number' => null,
                'guest_count' => 1,
                'subtotal' => 45000,
                'discount_amount' => 5000,
                'discount_type' => 'fixed',
                'tax_amount' => 4000,
                'service_charge' => 0,
                'total_amount' => 44000,
                'notes' => 'No sugar',
                'order_date' => now()->subHours(1),
                'completed_at' => now()->subHours(1),
            ],
            [
                'order_number' => 'ORD-' . date('Ymd') . '-' . str_pad(rand(100, 999), 3, '0', STR_PAD_LEFT),
                'order_type' => 'dine_in',
                'status' => 'pending',
                'table_number' => 'T003',
                'guest_count' => 4,
                'subtotal' => 120000,
                'discount_amount' => 0,
                'tax_amount' => 12000,
                'service_charge' => 6000,
                'total_amount' => 138000,
                'notes' => 'Birthday celebration',
                'order_date' => now()->subMinutes(30),
            ],
            [
                'order_number' => 'ORD-' . date('Ymd') . '-' . str_pad(rand(100, 999), 3, '0', STR_PAD_LEFT),
                'order_type' => 'delivery',
                'status' => 'preparing',
                'table_number' => null,
                'guest_count' => 1,
                'subtotal' => 65000,
                'discount_amount' => 10000,
                'discount_type' => 'percentage',
                'tax_amount' => 5500,
                'service_charge' => 5000,
                'total_amount' => 65500,
                'notes' => 'Deliver to office building',
                'order_date' => now()->subMinutes(45),
            ],
            [
                'order_number' => 'ORD-' . date('Ymd') . '-' . str_pad(rand(100, 999), 3, '0', STR_PAD_LEFT),
                'order_type' => 'dine_in',
                'status' => 'completed',
                'table_number' => 'T005',
                'guest_count' => 3,
                'subtotal' => 95000,
                'discount_amount' => 0,
                'tax_amount' => 9500,
                'service_charge' => 0,
                'total_amount' => 104500,
                'notes' => '',
                'order_date' => now()->subHours(2),
                'completed_at' => now()->subHours(2),
            ],
        ];

        foreach ($orderData as $index => $data) {
            // Assign customer randomly (some orders without customer)
            if ($customers->isNotEmpty() && rand(0, 1)) {
                $data['id_customer'] = $customers->random()->id_customer;
            }

            $data['id_user'] = $cashier->id;
            $data['created_by'] = $cashier->id;

            $order = Order::create($data);

            // Create order items
            $itemCount = rand(1, 3);
            $currentSubtotal = 0;

            for ($i = 0; $i < $itemCount; $i++) {
                $product = $products->random();
                $quantity = rand(1, 2);
                $price = (float) $product->selling_price;
                $totalPrice = $price * $quantity;
                $currentSubtotal += $totalPrice;

                OrderItem::create([
                    'id_order' => $order->id_order,
                    'id_product' => $product->id_product,
                    'item_name' => $product->name,
                    'item_sku' => $product->sku,
                    'quantity' => $quantity,
                    'unit_price' => $price,
                    'total_price' => $totalPrice,
                    'notes' => $i === 0 ? 'Extra hot' : null,
                ]);
            }

            // Update order subtotal to match items
            $order->update(['subtotal' => $currentSubtotal]);

            // Create payment for completed orders
            if (in_array($order->status, ['completed', 'preparing'])) {
                Payment::create([
                    'id_order' => $order->id_order,
                    'payment_number' => 'PAY-' . $order->order_number,
                    'payment_method' => ['cash', 'credit_card', 'qris'][rand(0, 2)],
                    'amount' => $order->total_amount,
                    'status' => 'paid',
                    'payment_date' => $order->order_date,
                    'processed_by' => $cashier->id,
                    'notes' => 'Payment processed successfully',
                ]);
            }

            $this->command->info("Created order: {$order->order_number}");
        }

        $this->command->info('Order seeding completed successfully!');
    }
}
