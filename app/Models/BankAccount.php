<?php

namespace App\Models;

use App\Models\Traits\DefaultDatetimeFormat;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BankAccount extends Model
{
    use HasFactory, DefaultDatetimeFormat;

    protected $fillable = [
        'company_id', 'bank_name', 'number', 'no', 'remark'
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
