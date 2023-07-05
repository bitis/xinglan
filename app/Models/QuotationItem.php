<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuotationItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_quotation_id',
        'sort_num',
        'name',
        'specs',
        'unit',
        'number',
        'price',
        'total_price',
        'remark',
    ];
}
