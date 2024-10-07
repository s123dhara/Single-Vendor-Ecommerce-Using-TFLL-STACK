<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id', 'brand_id', 'name', 'slug', 'images', 'description', 'price', 'is_active',
        'is_featured', 'in_stock', 'on_sale' 
    ];

    protected $casts = ['images' => 'array'];

    
    public function category() {
        return $this->belongsTo(Category::class);
    }

    public function brand() {
        return $this->belongsTo(Brand::class);
    }

    public function orderItems() {
        return $this->hasMany(OrderItem::class);
    }

    // this value comes from Category resourse Filament packages 
    protected static function booted()
    {
        static::saving(function ($product) {
            if (empty($product->slug)) {
                $product->slug = Str::slug($product->name);
            }
        });
    }
}
