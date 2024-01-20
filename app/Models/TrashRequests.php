<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrashRequests extends Model
{
    protected $fillable = ['trash_type','proof_payment','point','trash_weight','latitude','longitude','thumb','user_id', 'restaurant_id', 'status', 'description'];
    use HasFactory;

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function driver()
    {
        return $this->belongsTo(User::class, 'driver_id');
    }
}
