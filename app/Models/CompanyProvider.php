<?php

namespace App\Models;

use App\Models\Traits\DefaultDatetimeFormat;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class CompanyProvider extends Pivot
{
    use HasFactory, DefaultDatetimeFormat;

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
        'customer_tax_name',
        'customer_number',
        'customer_remark',
        'customer_address',
        'customer_license_no',
        'customer_phone',
        'customer_bank_name',
        'customer_bank_account_number',
        'customer_mailing_address',
        'contract_files',
        'contract_expiration_date',
        'customer_tex_remark',
    ];

    protected $casts = [
        'license_image' => 'array',
        'contract_files' => 'array'
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
