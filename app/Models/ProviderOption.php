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
        'relation_id',
        'company_id',
        'provider_id',
        'insurance_type',
        'province',
        'city',
        'area',
        'weight',
        'status'
    ];

    public function provider(): BelongsTo
    {
        return $this->belongsTo(CompanyProvider::class, 'relation_id', 'id');
    }
}
