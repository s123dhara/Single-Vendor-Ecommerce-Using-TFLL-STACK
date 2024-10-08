<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Order extends Model
{
    use HasFactory;


    protected $fillable = [
        'user_id', 'grand_total', 'payment_method', 'payement_status', 'status', 'currency', 'product_id', 
        'shipping_amount', 'shipping_method', 'notes'
    ];

    protected $casts = ['product_id' => 'array'];

    public function user() {
        return $this->belongsTo(User::class);
    }

    public function product() {
        return $this->belongsTo(Product::class);
    }

    // public function product() {
    //     return $this->hasMany(Product::class);
    // }
    public function items() {
        return $this->hasMany(OrderItem::class);
    }

    public function address() {
        return $this->hasOne(Address::class);
    }
    
}
