<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'short_description',
        'price',
        'sale_price',
        'in_stock',
        'image',
        'gallery',
        'active',
        'attributes',
    ];

    // Cast JSON columns to arrays
    protected $casts = [
        'gallery' => 'array',
        'attributes' => 'array',
        'in_stock' => 'boolean',
        'active' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    public function subCategories()
    {
        return $this->hasMany(ProductSubCategory::class);
    }
}
