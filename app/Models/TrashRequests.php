<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrashRequests extends Model
{
    protected $fillable = ['trash_type','trash_weight','latitude','longitude','thumb','user_id', 'restaurant_id', 'status', 'description'];
    use HasFactory;
}
