# Report Cache System

## Overview
Sistem report cache dibuat untuk mempercepat laporan penjualan dan riwayat transaksi dengan menghindari query heavy ke tabel orders/order_items yang besar.

## Tabel Report

### 1. `report_transaction_cache`
**Cache untuk riwayat transaksi yang cepat**
- Menyimpan data flatten dari orders + customer + items + payments
- Digunakan untuk export riwayat transaksi
- Update: Real-time atau via command

### 2. `report_sales_daily`
**Summary penjualan harian**
- Total orders, revenue, discount, tax per hari
- Breakdown per jam dan payment method
- Update: Setiap akhir hari atau manual

### 3. `report_product_performance`
**Performa produk per hari**
- Quantity sold, revenue, orders count
- Group by product dan category
- Update: Setiap akhir hari

### 4. `report_customer_analytics`
**Analitik customer**
- Orders count, total spent, AOV per customer
- Update: Setiap akhir hari

### 5. `report_hourly_analysis`
**Analisis per jam**
- Order count, revenue per jam (0-23)
- Update: Setiap akhir hari

## Command Usage

### Update Cache Manual
```bash
# Update untuk hari ini
php artisan report:update-cache

# Update untuk tanggal tertentu
php artisan report:update-cache --date="2025-11-08"

# Force update (hapus existing data dulu)
php artisan report:update-cache --force
```

### Schedule di Cron (Production)
Tambahkan di `app/Console/Kernel.php`:

```php
protected function schedule(Schedule $schedule)
{
    // Update cache setiap akhir hari
    $schedule->command('report:update-cache')
        ->dailyAt('23:59')
        ->withoutOverlapping();
    
    // Update cache untuk hari kemarin (backup)
    $schedule->command('report:update-cache', ['--date' => now()->yesterday()->format('Y-m-d')])
        ->dailyAt('00:30')
        ->withoutOverlapping();
}
```

## API Endpoint

### Export Transaction History (Using Cache)
**GET** `/api/pos/orders/history/export`

**Parameters:**
- `start_date` (optional): YYYY-MM-DD
- `end_date` (optional): YYYY-MM-DD
- `month` (optional): YYYY-MM
- `hour_start` (optional): 0-23
- `hour_end` (optional): 0-23
- `per_page` (optional): max 1000

**Response:**
```json
{
    "success": true,
    "data": [
        {
            "id_cache": 1,
            "id_order": 123,
            "order_number": "ORD-001",
            "order_date": "2025-11-09",
            "order_time": "10:30:00",
            "customer_name": "John Doe",
            "table_number": "T-05",
            "order_type": "dine_in",
            "status": "completed",
            "total_amount": 100000,
            "payment_method": "cash",
            "items_count": 3,
            "items": [
                {"name": "Nasi Goreng", "quantity": 2},
                {"name": "Es Teh", "quantity": 1}
            ]
        }
    ],
    "meta": {
        "total": 100,
        "execution_time_ms": 15.2,
        "limit": 500,
        "source": "cache"
    }
}
```

## Performance Comparison

### Before (Direct Query)
- **Query Time**: 5-30 seconds (for 500+ records)
- **Queries**: N+1 problem (1 + 500*3 = 1501 queries)
- **Server Load**: High CPU/Memory
- **Timeout Risk**: Very High

### After (Cache Table)
- **Query Time**: 10-100ms (for 500+ records)
- **Queries**: 1 single query
- **Server Load**: Minimal
- **Timeout Risk**: None

## Maintenance

### Rebuild Cache untuk Range Tanggal
```bash
# Update cache untuk 7 hari terakhir
for i in {0..6}; do
    date=$(date -d "$i days ago" +%Y-%m-%d)
    php artisan report:update-cache --date="$date" --force
done
```

### Check Cache Size
```sql
SELECT 
    report_date,
    COUNT(*) as total_transactions,
    SUM(total_amount) as total_revenue
FROM report_transaction_cache
GROUP BY report_date
ORDER BY report_date DESC
LIMIT 30;
```

### Clear Old Cache (Keep 90 days only)
```sql
DELETE FROM report_transaction_cache 
WHERE order_date < DATE_SUB(CURDATE(), INTERVAL 90 DAY);
```

## Integration Notes

1. **Real-time Update (Optional)**: 
   - Tambahkan Observer di Order model untuk auto-update cache saat order completed
   
2. **Lazy Loading**: 
   - Jika cache belum ada, fallback ke query langsung (dengan warning)
   
3. **Data Validation**: 
   - Compare cache vs real data secara periodic untuk ensure accuracy

## Migration Timeline

1. ✅ Create tables (Done)
2. ✅ Create update command (Done)
3. ✅ Update API endpoint (Done)
4. ⏳ Setup cron job
5. ⏳ Backfill historical data
6. ⏳ Monitor performance
