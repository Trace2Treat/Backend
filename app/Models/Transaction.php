<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    protected $fillable = ['user_id','address','restaurant_id','orders_id', 'total', 'status', 'description','thumb','latitude','longitude','transaction_code'];
    use HasFactory;
}
