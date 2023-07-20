<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderInsurer extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id', 'insurer_id', 'type', 'policy_number'
    ];
}
