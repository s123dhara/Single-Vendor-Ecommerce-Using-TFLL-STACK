<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Brand extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'slug' , 'image', 'is_active'];

    
    public function products() {
        return $this->hasMany(Product::class);
    }

    
    // this value comes from Category resourse Filament packages 
    protected static function booted()
    {
        static::saving(function ($brand) {
            if (empty($brand->slug)) {
                $brand->slug = Str::slug($brand->name);
            }
        });
    }
}
