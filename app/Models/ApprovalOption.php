<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApprovalOption extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'type',
        'approve_type',
        'review_type',
        'review_conditions',
    ];

}
