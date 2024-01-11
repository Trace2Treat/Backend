<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Food extends Model
{
    protected $table = 'foods';
    protected $fillable = [
        'name',
        'description',
        'price',
        'stock',
        'thumb',
        'category_id',
        'restaurant_id'
    ];
    use HasFactory;

    public function restaurant()
    {
        return $this->belongsTo(Restaurant::class, 'restaurant_id', 'id');
    }
}
