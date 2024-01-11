<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Restaurant extends Model
{
    protected $fillable = ['name', 'description', 'address', 'phone', 'logo','latitude','longitude', 'owner_id'];
    use HasFactory;


    public function foods()
    {
        return $this->hasMany(Food::class, 'restaurant_id', 'id');
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id', 'id');
    }
}
