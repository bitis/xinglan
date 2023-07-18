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
        'repair_status',
        'repair_start_at',
        'repair_end_at',
        'cost_images',
        'before_repair_images',
        'repair_images',
        'after_repair_images'
    ];

    const REPAIR_STATUS_WAIT = 0;
    const REPAIR_STATUS_START = 1;
    const REPAIR_STATUS_DONE = 2;

    protected $casts = [
        'cost_tables' => 'array',
        'before_repair_images' => 'array',
        'cost_images' => 'array',
        'repair_images' => 'array',
        'after_repair_images' => 'array',
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

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id', 'id');
    }
}
