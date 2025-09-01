<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\BluetoothDevice;
use App\Models\User;

class BluetoothDeviceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get first user for testing
        $user = User::first();
        
        if (!$user) {
            $this->command->warn('No users found. Please create a user first.');
            return;
        }

        $devices = [
            [
                'id_user' => $user->id,
                'device_name' => 'Thermal Printer POS',
                'device_address' => '00:11:22:33:44:55',
                'device_type' => 'printer',
                'manufacturer' => 'Epson',
                'model' => 'TM-T82III',
                'device_capabilities' => [
                    'paper_width' => '80mm',
                    'print_speed' => '200mm/s',
                    'auto_cutter' => true,
                    'cash_drawer_kick' => true
                ],
                'connection_settings' => [
                    'baudrate' => 9600,
                    'data_bits' => 8,
                    'stop_bits' => 1,
                    'parity' => 'none'
                ],
                'is_default' => true,
                'is_active' => true,
                'notes' => 'Main receipt printer for POS transactions'
            ],
            [
                'id_user' => $user->id,
                'device_name' => 'Kitchen Printer',
                'device_address' => '00:11:22:33:44:66',
                'device_type' => 'printer',
                'manufacturer' => 'Star',
                'model' => 'TSP143III',
                'device_capabilities' => [
                    'paper_width' => '80mm',
                    'print_speed' => '250mm/s',
                    'auto_cutter' => true,
                    'waterproof' => true
                ],
                'connection_settings' => [
                    'baudrate' => 9600,
                    'data_bits' => 8,
                    'stop_bits' => 1,
                    'parity' => 'none'
                ],
                'is_default' => false,
                'is_active' => true,
                'notes' => 'Kitchen order printer for food preparation'
            ],
            [
                'id_user' => $user->id,
                'device_name' => 'Barcode Scanner',
                'device_address' => '00:11:22:33:44:77',
                'device_type' => 'scanner',
                'manufacturer' => 'Honeywell',
                'model' => 'Voyager 1200g',
                'device_capabilities' => [
                    'scan_types' => ['1D', '2D', 'QR'],
                    'read_distance' => '24cm',
                    'scan_rate' => '100 scans/second'
                ],
                'connection_settings' => [
                    'auto_trigger' => true,
                    'beep_enabled' => true,
                    'led_enabled' => true
                ],
                'is_default' => true,
                'is_active' => true,
                'notes' => 'Primary barcode scanner for product identification'
            ],
            [
                'id_user' => $user->id,
                'device_name' => 'Cash Drawer',
                'device_address' => '00:11:22:33:44:88',
                'device_type' => 'cash_drawer',
                'manufacturer' => 'APG',
                'model' => 'VB320-BL1616',
                'device_capabilities' => [
                    'compartments' => 5,
                    'coin_slots' => 8,
                    'lock_type' => 'electronic',
                    'kick_pulse' => '12V'
                ],
                'connection_settings' => [
                    'open_pulse_duration' => 120,
                    'auto_lock' => true
                ],
                'is_default' => true,
                'is_active' => true,
                'notes' => 'Main cash drawer for storing money'
            ],
            [
                'id_user' => $user->id,
                'device_name' => 'Digital Scale',
                'device_address' => '00:11:22:33:44:99',
                'device_type' => 'scale',
                'manufacturer' => 'Mettler Toledo',
                'model' => 'MS6002S',
                'device_capabilities' => [
                    'max_weight' => '6000g',
                    'precision' => '0.01g',
                    'units' => ['g', 'kg', 'oz', 'lb'],
                    'display_type' => 'LCD'
                ],
                'connection_settings' => [
                    'auto_zero' => true,
                    'tare_enabled' => true,
                    'stable_detection' => true
                ],
                'is_default' => true,
                'is_active' => true,
                'notes' => 'Precision scale for weighing products'
            ]
        ];

        foreach ($devices as $deviceData) {
            BluetoothDevice::create($deviceData);
        }

        $this->command->info('Sample Bluetooth devices created successfully!');
    }
}
