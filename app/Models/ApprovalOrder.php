<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ApprovalOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'company_id',
        'approval_type'
    ];

    public function process(): HasMany
    {
        return $this->hasMany(ApprovalOrderProcess::class, 'approval_order_id', 'id');
    }
}
