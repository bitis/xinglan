<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RepairCost extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'repair_plan_id',
        'name',
        'money',
        'remark',
    ];
}
