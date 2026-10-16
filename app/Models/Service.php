<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    use HasFactory;
    protected $fillable = ['name', 'description', 'unit', 'price', 'duration_days', 'is_active'];
    protected $casts = ['price' => 'decimal:2', 'is_active' => 'boolean'];
    public function orders() { return $this->hasMany(Order::class); }
}
