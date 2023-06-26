<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProviderOption extends Model
{
    use HasFactory;

    protected $casts = [
        'area' => 'array'
    ];

    protected $fillable = [
        'company_id',
        'provider_id',
        'insurance_type',
        'province',
        'city',
        'area',
        'weight',
        'status'
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'provider_id', 'id');
    }
}