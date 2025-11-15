<?php

namespace App\Exports;

use App\Models\Order;

class OrdersExport 
{
    protected $orders;

    public function __construct($orders)
    {
        $this->orders = $orders;
    }

    public function generateCSV()
    {
        $headers = [
            'No. Pesanan',
            'Pelanggan', 
            'Status',
            'List Barang',
            'Total',
            'Pembayaran',
            'Tanggal',
            'Kasir',
            'Catatan'
        ];

        $data = [];
        $data[] = $headers;

        foreach ($this->orders as $order) {
            $customerName = $order->customer ? $order->customer->name : 
                          (isset($order->customer_info['name']) ? $order->customer_info['name'] : 'Tamu');
            
            $paymentMethods = $order->payments ? $order->payments->pluck('payment_method')->unique()->join(', ') : '';
            if (empty($paymentMethods)) {
                $paymentMethods = $order->status === 'completed' ? 'Tunai' : 'Belum Dibayar';
            }

            // Build list items string: "{qty}x Product Name"
            $itemsList = '';
            if ($order->orderItems && $order->orderItems->count() > 0) {
                $itemsList = $order->orderItems->map(function ($item) {
                    return (int)$item->quantity . 'x ' . ($item->item_name ?? ($item->product ? $item->product->name : '')); 
                })->join(', ');
            }

            $data[] = [
                $order->order_number,
                $customerName,
                $order->order_status_text,
                $itemsList,
                'Rp ' . number_format($order->total_amount, 0, ',', '.'),
                $paymentMethods,
                $order->created_at->format('d/m/Y H:i'),
                $order->user ? $order->user->name : 'System',
                $order->notes ?? ''
            ];
        }

        return $data;
    }

    public function generateExcelXML()
    {
        $customerData = $this->generateCSV();
        
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<?mso-application progid="Excel.Sheet"?>' . "\n";
        $xml .= '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"' . "\n";
        $xml .= ' xmlns:o="urn:schemas-microsoft-com:office:office"' . "\n";
        $xml .= ' xmlns:x="urn:schemas-microsoft-com:office:excel"' . "\n";
        $xml .= ' xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet"' . "\n";
        $xml .= ' xmlns:html="http://www.w3.org/TR/REC-html40">' . "\n";
        
        $xml .= '<DocumentProperties xmlns="urn:schemas-microsoft-com:office:office">' . "\n";
        $xml .= '<Title>Riwayat Transaksi</Title>' . "\n";
        $xml .= '<Subject>Data Transaksi POS</Subject>' . "\n";
        $xml .= '<Created>' . date('Y-m-d\TH:i:s\Z') . '</Created>' . "\n";
        $xml .= '</DocumentProperties>' . "\n";
        
        $xml .= '<Styles>' . "\n";
        $xml .= '<Style ss:ID="HeaderStyle">' . "\n";
        $xml .= '<Font ss:Bold="1"/>' . "\n";
        $xml .= '<Interior ss:Color="#D3D3D3" ss:Pattern="Solid"/>' . "\n";
        $xml .= '</Style>' . "\n";
        $xml .= '<Style ss:ID="NumberStyle">' . "\n";
        $xml .= '<NumberFormat ss:Format="#,##0"/>' . "\n";
        $xml .= '</Style>' . "\n";
        $xml .= '</Styles>' . "\n";
        
        $xml .= '<Worksheet ss:Name="Riwayat Transaksi">' . "\n";
        $xml .= '<Table>' . "\n";
        
        foreach ($customerData as $rowIndex => $row) {
            $xml .= '<Row>' . "\n";
            foreach ($row as $colIndex => $cell) {
                // Column index 4 (0-based) is the Total column now that we've added a List Barang column
                $styleId = ($rowIndex === 0) ? 'HeaderStyle' : 
                          ($colIndex === 4 && $rowIndex > 0 ? 'NumberStyle' : '');
                $cellValue = htmlspecialchars($cell, ENT_QUOTES, 'UTF-8');
                
                if ($styleId) {
                    $xml .= '<Cell ss:StyleID="' . $styleId . '"><Data ss:Type="String">' . $cellValue . '</Data></Cell>' . "\n";
                } else {
                    $xml .= '<Cell><Data ss:Type="String">' . $cellValue . '</Data></Cell>' . "\n";
                }
            }
            $xml .= '</Row>' . "\n";
        }
        
        $xml .= '</Table>' . "\n";
        $xml .= '</Worksheet>' . "\n";
        $xml .= '</Workbook>';
        
        return $xml;
    }
}