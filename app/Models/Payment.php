<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;
    protected $fillable = ['order_id', 'recorded_by', 'amount', 'method', 'status', 'reference', 'notes', 'paid_at'];
    protected $casts = ['amount' => 'decimal:2', 'paid_at' => 'datetime'];
    public function order() { return $this->belongsTo(Order::class); }
    public function recorder() { return $this->belongsTo(User::class, 'recorded_by'); }
}
