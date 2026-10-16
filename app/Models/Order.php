<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    private const STATUS_LABELS = [
        'pending_confirmation' => 'Menunggu konfirmasi',
        'received' => 'Pesanan diterima',
        'awaiting_weighing' => 'Menunggu penimbangan',
        'washing' => 'Sedang dicuci',
        'drying' => 'Pengeringan',
        'ironing' => 'Penyetrikaan',
        'ready' => 'Siap diambil',
        'delivering' => 'Dalam pengantaran',
        'completed' => 'Selesai',
        'cancelled' => 'Dibatalkan',
    ];

    public static function labelForStatus(string $status): string
    {
        return self::STATUS_LABELS[$status] ?? ucwords(str_replace('_', ' ', $status));
    }

    protected $fillable = [
        'order_code', 'tracking_token', 'customer_id', 'service_id', 'created_by', 'source',
        'weight_kg', 'quantity_items', 'unit_price', 'subtotal', 'delivery_method',
        'delivery_address', 'pickup_schedule', 'delivery_schedule', 'delivery_fee',
        'total_amount', 'amount_finalized_at', 'order_status', 'estimated_completion_at', 'notes', 'cancellation_reason',
    ];
    protected $casts = [
        'weight_kg' => 'decimal:2', 'unit_price' => 'decimal:2', 'subtotal' => 'decimal:2',
        'delivery_fee' => 'decimal:2', 'total_amount' => 'decimal:2', 'pickup_schedule' => 'datetime',
        'delivery_schedule' => 'datetime', 'estimated_completion_at' => 'datetime', 'amount_finalized_at' => 'datetime',
    ];
    public function customer() { return $this->belongsTo(Customer::class); }
    public function service() { return $this->belongsTo(Service::class); }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
    public function payments() { return $this->hasMany(Payment::class); }
    public function statusHistories() { return $this->hasMany(OrderStatusHistory::class); }
}
