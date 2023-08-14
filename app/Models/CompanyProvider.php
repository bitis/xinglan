<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class CompanyProvider extends Pivot
{
    use HasFactory;

    protected $table = 'company_providers';

    protected $fillable = [
        'company_id',
        'company_name',
        'provider_id',
        'provider_name',
        'bank_name',
        'bank_account_name',
        'bank_account_number',
        'license_no',
        'license_image',
        'expiration_date',
        'car_insurance',
        'other_insurance',
        'introduce',
        'remark',
        'status',
    ];

    protected $casts = [
        'license_image' => 'array'
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id', 'id');
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'provider_id', 'id');
    }
}
