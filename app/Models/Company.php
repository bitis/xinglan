<?php

namespace App\Models;

use App\Models\Enumerations\CompanyLevel;
use App\Models\Enumerations\CompanyType;
use App\Models\Traits\DefaultDatetimeFormat;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Company extends Model
{
    use HasFactory, DefaultDatetimeFormat;

    protected $fillable = [
        'parent_id',
        'invite_code',
        'type',
        'level',
        'name',
        'contract_name',
        'contract_phone',
        'province',
        'city',
        'area',
        'address',
        'status',
        'bank_name',
        'bank_account_name',
        'bank_account_number',
        'official_seal',
        'logo',
        'remark',
        'service_rate',
        'admin_id'
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function admin(): HasOne
    {
        return $this->hasOne(User::class, 'id', 'admin_id')->select(['id', 'name', 'mobile', 'account']);
    }

    public function parent(): HasOne
    {
        return $this->hasOne(Company::class, 'id', 'parent_id')->select(['id', 'level', 'name']);
    }

    public function type(): Attribute
    {
        return Attribute::make(
            get: fn(int $value) => [
                'type' => $value,
                'name' => CompanyType::from($value)->name()
            ]
        );
    }

    public function level(): Attribute
    {
        return Attribute::make(
            get: fn(int $value) => [
                'type' => $value,
                'name' => CompanyLevel::from($value)->name()
            ]
        );
    }
}
