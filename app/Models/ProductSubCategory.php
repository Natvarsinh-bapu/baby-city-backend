<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductSubCategory extends Model
{
    protected $fillable = [
        'product_id',
        'sub_category_id',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function subCategory()
    {
        return $this->belongsTo(SubCategory::class);
    }
}
