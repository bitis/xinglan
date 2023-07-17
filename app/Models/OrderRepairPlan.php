<?php

namespace App\Models;

use App\Models\Traits\DefaultDatetimeFormat;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class OrderRepairPlan extends Model
{
    use HasFactory, DefaultDatetimeFormat;

    protected $fillable = [
        'company_id',
        'order_id',
        'plan_type',
        'repair_type',
        'repair_days',
        'repair_company_id',
        'repair_company_name',
        'repair_user_id',
        'repair_user_name',
        'repair_cost',
        'cost_tables',
        'plan_text',
        'create_user_id',
        'check_status',
        'checked_at',
    ];

    protected $casts = [
        'cost_tables' => 'array'
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id', 'id');
    }

    public function costs(): HasMany
    {
        return $this->hasMany(RepairCost::class, 'repair_plan_id', 'id');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(RepairTask::class, 'repair_plan_id', 'id');
    }
}
